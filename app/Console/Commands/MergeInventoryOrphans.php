<?php

namespace App\Console\Commands;

use App\Models\Computer;
use App\Models\Group;
use App\Models\GroupShortKey;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MergeInventoryOrphans extends Command
{
    protected $signature = 'computers:merge-inventory-orphans
        {file : Ruta del CSV exportado desde Computers}
        {--dry-run : Solo muestra lo que se haría, sin cambios}
        {--min-confidence=alta : Umbral de confianza: alta|media}';

    protected $description = 'Fusiona huérfanas (MAC auto-*, sin short_key) con su fila del CSV: conserva el registro vivo y sus logs, y le transfiere MAC real, short_key, plaza y grupo del registro duplicado recién creado';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! file_exists($file)) {
            $this->error("No existe el archivo: {$file}");

            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $min = $this->option('min-confidence');
        $rows = $this->parseCsv($file);

        $this->info("CSV: {$rows->count()} filas | Umbral: {$min} | Modo: ".($dryRun ? 'DRY-RUN' : 'REAL'));
        $this->newLine();

        $orphans = Computer::where(function ($q) {
            $q->whereNull('short_key')->orWhere('short_key', '');
        })->get();

        $this->line("Huérfanas en BD: {$orphans->count()}");
        $this->newLine();

        $pairs = $this->findPairs($orphans, $rows, $min);
        $this->info('Pares encontrados: '.count($pairs));
        $this->newLine();

        if ($pairs === []) {
            return self::SUCCESS;
        }

        $merged = 0;
        foreach ($pairs as $p) {
            $result = $this->mergePair($p, $dryRun);
            $merged += $result ? 1 : 0;
        }

        $this->newLine();
        $this->info("Total: {$merged} pares a fusionar / fusionados.");

        return self::SUCCESS;
    }

    private function findPairs(Collection $orphans, Collection $rows, string $minConfidence): array
    {
        $pairs = [];
        $usedCsvRows = [];
        $usedOrphans = [];

        foreach ($orphans as $orphan) {
            $match = $this->bestCsvMatch($orphan, $rows, $usedCsvRows, $minConfidence);
            if (! $match) {
                continue;
            }

            [$confidence, $row, $via] = $match;
            $duplicate = $this->findDuplicateByCsvRow($orphan, $row);

            $pairs[] = [
                'orphan' => $orphan,
                'csv' => $row,
                'via' => $via,
                'confidence' => $confidence,
                'duplicate' => $duplicate,
            ];
            $usedCsvRows[] = strtolower(trim($row['mac']));
            $usedOrphans[] = $orphan->id;
        }

        return $pairs;
    }

    private function bestCsvMatch(Computer $orphan, Collection $rows, array $usedCsvRows, string $minConfidence): ?array
    {
        $candidates = [];
        $orphanName = $this->norm($orphan->computer_name);
        $orphanIp = trim($orphan->ip_address ?? '');

        foreach ($rows as $row) {
            $mac = strtolower(trim($row['mac']));
            if ($mac === '' || in_array($mac, $usedCsvRows, true)) {
                continue;
            }

            $confidence = null;
            $via = null;

            if ($orphanIp !== '' && trim($row['ip']) === $orphanIp) {
                $confidence = 'alta';
                $via = 'ip-unica';
            } elseif ($orphanName !== ''
                && ($this->norm($row['nombre']) === $orphanName || $this->norm($row['short_key']) === $orphanName)) {
                $confidence = $minConfidence === 'media' ? 'media' : null;
                $via = 'nombre';
            }

            if ($confidence === null) {
                continue;
            }

            $candidates[] = [$confidence, $row, $via];
        }

        if ($candidates === []) {
            return null;
        }

        $byConfidence = [];
        foreach ($candidates as [$confidence, $row, $via]) {
            $byConfidence[$confidence][] = [$row, $via];
        }

        if (isset($byConfidence['alta']) && count($byConfidence['alta']) === 1) {
            return ['alta', $byConfidence['alta'][0][0], $byConfidence['alta'][0][1]];
        }

        if (isset($byConfidence['media']) && count($byConfidence['media']) === 1) {
            return ['media', $byConfidence['media'][0][0], $byConfidence['media'][0][1]];
        }

        return null;
    }

    private function findDuplicateByCsvRow(Computer $orphan, array $row): ?Computer
    {
        $mac = strtolower(trim($row['mac']));

        return Computer::whereRaw('LOWER(mac_address) = ?', [$mac])
            ->where('id', '!=', $orphan->id)
            ->first();
    }

    private function mergePair(array $p, bool $dryRun): bool
    {
        $orphan = $p['orphan'];
        $row = $p['csv'];
        $duplicate = $p['duplicate'];

        $mac = strtolower(trim($row['mac']));
        $shortKey = strtoupper(trim($row['short_key']));
        $group = $this->findGroup(trim($row['group_name']));

        $newMac = $duplicate?->mac_address ?? $mac;
        if (! $newMac) {
            $newMac = $mac;
        }

        $update = ['mac_address' => $newMac];
        if ($shortKey !== '' && ! $orphan->short_key) {
            $update['short_key'] = $shortKey;
        }
        if (trim($row['nombre']) !== '' && ! $orphan->nombre_instalacion) {
            $update['nombre_instalacion'] = trim($row['nombre']);
        }
        if (trim($row['plaza']) !== '' && ! $orphan->plaza) {
            $update['plaza'] = trim($row['plaza']);
        }
        if ($group?->id && ! $orphan->group_id) {
            $update['group_id'] = $group->id;
        }
        if (trim($row['download_path']) !== '' && ! $orphan->download_path) {
            $update['download_path'] = trim($row['download_path']);
        }

        $this->line('──────────────────────────────────────────');
        $this->line("[{$p['confidence']} via {$p['via']}] {$orphan->computer_name} (ID:{$orphan->id})");
        $this->line("  huérfana:  mac={$orphan->mac_address} status={$orphan->status}");
        $this->line("  CSV:       [{$row['short_key']}] {$row['nombre']} mac={$mac}");
        if ($duplicate) {
            $this->line("  duplicado: ID:{$duplicate->id} {$duplicate->computer_name} mac={$duplicate->mac_address} status={$duplicate->status}");
        } else {
            $this->warn('  aviso: no se encontró duplicado con esa MAC en BD');
        }

        if (! $dryRun) {
            DB::transaction(function () use ($orphan, $duplicate, $update, $shortKey) {
                if ($duplicate) {
                    $duplicate->update([
                        'short_key' => null,
                        'mac_address' => 'PENDING-DEL-'.$duplicate->id,
                    ]);
                    $duplicate->delete();
                }
                $orphan->update($update);

                if ($shortKey !== '') {
                    $exists = GroupShortKey::where('short_key', $shortKey)->first();
                    if (! $exists && isset($update['group_id'])) {
                        GroupShortKey::create(['group_id' => $update['group_id'], 'short_key' => $shortKey]);
                    }
                }
            });
            $this->info("  OK: huérfana ID:{$orphan->id} actualizada".($duplicate ? ", duplicado ID:{$duplicate->id} eliminado" : ''));

            return true;
        }

        $this->info('  [DRY-RUN] '.json_encode($update));

        return true;
    }

    private function findGroup(string $name): ?Group
    {
        if ($name === '') {
            return null;
        }

        return Group::where('name', $name)->first();
    }

    protected function parseCsv(string $path): Collection
    {
        $map = [
            'Short Key' => 'short_key', 'Nombre' => 'nombre', 'MAC' => 'mac', 'IP' => 'ip',
            'Estado' => 'estado', 'Plaza' => 'plaza', 'Grupo' => 'group_name', 'Agent' => 'agent',
            'PVSI' => 'pvsi', 'PVSI Fecha' => 'pvsi_fecha', 'PVSI Hora' => 'pvsi_hora',
            'PVSI Bepartners' => 'pvsi_bep', 'PVSI Bepartners Fecha' => 'pvsi_bep_fecha',
            'PVSI Bepartners Hora' => 'pvsi_bep_hora', 'AgentResurtido' => 'agent_resurtido',
            'Resurtido Fecha' => 'resurtido_fecha', 'Windows' => 'windows', 'Arquitectura' => 'arquitectura',
            'RAM (GB)' => 'ram', 'Disco (GB)' => 'disco', 'BitLocker' => 'bitlocker',
            'Download Path' => 'download_path', 'Última Actividad' => 'last_seen',
        ];

        $handle = fopen($path, 'r');
        $raw = fgetcsv($handle, 0, ',', '"', '');
        $headers = array_map(fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $h), '"'), $raw);
        $rows = collect();

        while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if (count($data) === count($headers)) {
                $rows->push(array_combine(array_map(fn ($h) => $map[$h] ?? $h, $headers), $data));
            }
        }
        fclose($handle);

        return $rows;
    }

    private function norm(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;

        return preg_replace('/[^A-Z0-9]/', '', $value);
    }

    private function isFullMac(string $mac): bool
    {
        return preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/i', $mac) === 1;
    }
}
