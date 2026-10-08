<?php

namespace App\Console\Commands;

use App\Models\Computer;
use App\Models\Group;
use App\Models\GroupShortKey;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportComputersInventory extends Command
{
    protected $signature = 'computers:import-inventory {file : Ruta del CSV exportado desde Computers} {--dry-run : Solo reporta, no escribe}';

    protected $description = 'Importa el inventario de computadoras: crea grupos, busca por MAC o short_key y completa los campos sin regresar datos de la BD';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! file_exists($file)) {
            $this->error("No existe el archivo: {$file}");

            return self::FAILURE;
        }

        $rows = $this->parseCsv($file);
        $this->info("CSV: {$rows->count()} filas");

        $stats = [
            'create' => 0, 'update' => 0, 'unchanged' => 0, 'nomatch' => 0, 'errors' => 0,
            'by_mac' => 0, 'by_sk' => 0, 'groups_create' => 0, 'gsk' => 0, 'fields' => [],
        ];
        $problems = [];
        $groupCache = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $mac = trim($row['mac']);
                if ($mac === '') {
                    $stats['errors']++;
                    $problems[] = 'fila '.($i + 1).': sin MAC';

                    continue;
                }

                $group = null;
                $groupIsNew = false;
                $groupName = trim($row['group_name']);
                if ($groupName !== '') {
                    if (! isset($groupCache[$groupName])) {
                        $group = Group::where('name', $groupName)->first();
                        if (! $group) {
                            $stats['groups_create']++;
                            $groupIsNew = true;
                            if ($this->option('dry-run')) {
                                $group = new Group(['name' => $groupName, 'type' => $this->inferType($groupName)]);
                                $group->id = 0;
                            } else {
                                $group = Group::create([
                                    'name' => $groupName,
                                    'type' => $this->inferType($groupName),
                                    'description' => 'Cargado desde inventario CSV',
                                ]);
                            }
                        }
                        $groupCache[$groupName] = $group;
                    }
                    $group = $groupCache[$groupName];
                }

                $shortKey = strtoupper(trim($row['short_key']));
                $computer = Computer::where('mac_address', $mac)->first();
                if ($computer) {
                    $stats['by_mac']++;
                } elseif ($shortKey !== '') {
                    $computer = Computer::where('short_key', $shortKey)->first();
                    if ($computer) {
                        $stats['by_sk']++;
                    }
                }

                if (! $computer) {
                    $stats['create']++;
                    if (! $this->option('dry-run')) {
                        try {
                            $computer = Computer::create($this->newAttributes($row, $shortKey, $group));
                        } catch (\Exception $e) {
                            $stats['errors']++;
                            $problems[] = 'fila '.($i + 1)." ({$mac}): {$e->getMessage()}";

                            continue;
                        }
                    }
                } else {
                    $attrs = $this->mergeAttributes($computer, $row, $shortKey, $group);
                    if ($attrs !== []) {
                        $stats['update']++;
                        foreach (array_keys($attrs) as $key) {
                            $stats['fields'][$key] = ($stats['fields'][$key] ?? 0) + 1;
                        }
                        if (! $this->option('dry-run')) {
                            try {
                                $computer->update($attrs);
                            } catch (\Exception $e) {
                                $stats['errors']++;
                                $problems[] = 'fila '.($i + 1)." ({$mac}): {$e->getMessage()}";

                                continue;
                            }
                        }
                    } else {
                        $stats['unchanged']++;
                    }
                }

                if ($shortKey !== '' && $group) {
                    $exists = GroupShortKey::where('short_key', $shortKey)->first();
                    if (! $exists) {
                        $stats['gsk']++;
                        if (! $this->option('dry-run')) {
                            GroupShortKey::create(['group_id' => $group->id, 'short_key' => $shortKey]);
                        }
                    } elseif (! $groupIsNew && $exists->group_id !== $group->id) {
                        $problems[] = "short_key {$shortKey} ya pertenece a otro grupo (id {$exists->group_id})";
                    }
                }
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Fallo general: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Modo: '.($this->option('dry-run') ? 'DRY-RUN (sin escribir)' : 'EJECUCIÓN REAL'));
        $this->line("Match por MAC: {$stats['by_mac']} | por short_key: {$stats['by_sk']}");
        $this->line("Nuevas: {$stats['create']} | actualizadas: {$stats['update']} | sin cambios: {$stats['unchanged']}");
        $this->line("Grupos a crear: {$stats['groups_create']} | group_short_keys a crear: {$stats['gsk']}");
        $this->line("Errores: {$stats['errors']}");

        if (! empty($stats['fields'])) {
            ksort($stats['fields']);
            $this->newLine();
            $this->line('Campos que se completarían en equipos existentes:');
            foreach ($stats['fields'] as $field => $count) {
                $this->line(sprintf('  %-28s %d', $field, $count));
            }
        }

        if ($stats['groups_create'] > 0) {
            $this->newLine();
            $this->line('Grupos:');
            foreach ($groupCache as $name => $g) {
                $this->line(sprintf('  %-32s type=%s', $name, $g->type));
            }
        }

        if ($problems !== []) {
            $this->newLine();
            $this->warn('Avisos:');
            foreach (array_slice($problems, 0, 60) as $p) {
                $this->line("  - {$p}");
            }
            if (count($problems) > 60) {
                $this->line('  ... y '.(count($problems) - 60).' más');
            }
        }

        return self::SUCCESS;
    }

    protected function inferType(string $name): string
    {
        $n = strtoupper($name);
        if (str_contains($n, 'ALMACEN')) {
            return 'almacen';
        }
        if (str_contains($n, 'VENDEDOR')) {
            return 'vendedor';
        }
        if (str_contains($n, 'ESPECIAL')) {
            return 'especial';
        }
        if (str_contains($n, 'CEDIS')) {
            return 'cedis';
        }
        if (str_contains($n, 'COBRANZA')) {
            return 'cobranza';
        }

        return 'tienda';
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

    protected function newAttributes(array $row, string $shortKey, ?Group $group): array
    {
        $attrs = [
            'computer_name' => trim($row['nombre']) !== '' ? trim($row['nombre']) : ($shortKey !== '' ? $shortKey : trim($row['mac'])),
            'mac_address' => trim($row['mac']),
            'ip_address' => trim($row['ip']) ?: null,
            'agent_version' => trim($row['agent']) ?: null,
            'status' => in_array($row['estado'], ['online', 'offline'], true) ? $row['estado'] : 'offline',
            'last_seen' => $this->parseDateTime($row['last_seen']),
            'short_key' => $shortKey !== '' ? $shortKey : null,
            'nombre_instalacion' => trim($row['nombre']) ?: null,
            'plaza' => trim($row['plaza']) ?: null,
            'download_path' => trim($row['download_path']) ?: null,
            'pvsi_version' => trim($row['pvsi']) ?: null,
            'pvsi_fecha' => trim($row['pvsi_fecha']) ?: null,
            'pvsi_hora' => trim($row['pvsi_hora']) ?: null,
            'pvsi_bepartners_version' => trim($row['pvsi_bep']) ?: null,
            'pvsi_bepartners_fecha' => trim($row['pvsi_bep_fecha']) ?: null,
            'pvsi_bepartners_hora' => trim($row['pvsi_bep_hora']) ?: null,
            'resurtido_version' => trim($row['agent_resurtido']) ?: null,
            'resurtido_fecha' => trim($row['resurtido_fecha']) ?: null,
            'windows_version' => trim($row['windows']) ?: null,
            'architecture' => trim($row['arquitectura']) ?: null,
            'total_ram' => $this->gbToBytes($row['ram']),
            'total_disk_space' => $this->gbToBytes($row['disco']),
            'bitlocker_status' => $this->parseBitlocker($row['bitlocker']),
            'group_id' => $group?->id ?: null,
        ];
        $attrs = array_filter($attrs, fn ($v) => $v !== null && $v !== '');

        return $attrs;
    }

    protected function mergeAttributes(Computer $computer, array $row, string $shortKey, ?Group $group): array
    {
        $attrs = [];

        if ($computer->short_key === null && $shortKey !== '') {
            $attrs['short_key'] = $shortKey;
        }
        if (! $computer->nombre_instalacion && trim($row['nombre']) !== '') {
            $attrs['nombre_instalacion'] = trim($row['nombre']);
        }
        if (! $computer->plaza && trim($row['plaza']) !== '') {
            $attrs['plaza'] = trim($row['plaza']);
        }
        if (! $computer->group_id && $group?->id) {
            $attrs['group_id'] = $group->id;
        }
        if (! $computer->download_path && trim($row['download_path']) !== '') {
            $attrs['download_path'] = trim($row['download_path']);
        }
        if (! $computer->ip_address && trim($row['ip']) !== '') {
            $attrs['ip_address'] = trim($row['ip']);
        }

        $csvAgent = trim($row['agent']);
        if ($csvAgent !== '' && ! $computer->agent_version) {
            $attrs['agent_version'] = $csvAgent;
        }

        $csvFecha = trim($row['pvsi_fecha']);
        if ($csvFecha !== '' && ! $computer->pvsi_fecha) {
            $attrs['pvsi_version'] = trim($row['pvsi']) ?: null;
            $attrs['pvsi_fecha'] = $csvFecha;
            $attrs['pvsi_hora'] = trim($row['pvsi_hora']) ?: null;
        }

        $csvBep = trim($row['pvsi_bep_fecha']);
        if ($csvBep !== '' && ! $computer->pvsi_bepartners_fecha) {
            $attrs['pvsi_bepartners_version'] = trim($row['pvsi_bep']) ?: null;
            $attrs['pvsi_bepartners_fecha'] = $csvBep;
            $attrs['pvsi_bepartners_hora'] = trim($row['pvsi_bep_hora']) ?: null;
        }

        $csvRes = trim($row['agent_resurtido']);
        if ($csvRes !== '' && ! $computer->resurtido_version) {
            $attrs['resurtido_version'] = $csvRes;
            $attrs['resurtido_fecha'] = trim($row['resurtido_fecha']) ?: null;
        }

        if (! $computer->windows_version && trim($row['windows']) !== '') {
            $attrs['windows_version'] = trim($row['windows']);
        }
        if (! $computer->architecture && trim($row['arquitectura']) !== '') {
            $attrs['architecture'] = trim($row['arquitectura']);
        }
        if (! $computer->total_ram && ($bytes = $this->gbToBytes($row['ram']))) {
            $attrs['total_ram'] = $bytes;
        }
        if (! $computer->total_disk_space && ($bytes = $this->gbToBytes($row['disco']))) {
            $attrs['total_disk_space'] = $bytes;
        }
        if (! $computer->bitlocker_status && trim($row['bitlocker']) !== '') {
            $attrs['bitlocker_status'] = $this->parseBitlocker($row['bitlocker']);
        }

        return $attrs;
    }

    protected function parseDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) ? $value : null;
    }

    protected function gbToBytes(string $gb): ?int
    {
        $gb = trim($gb);
        if ($gb === '' || ! is_numeric($gb)) {
            return null;
        }

        return (int) round(((float) $gb) * 1073741824);
    }

    protected function parseBitlocker(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $result = [];
        foreach (explode(';', $raw) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            [$drive, $status] = array_pad(explode(':', $part, 2), 2, '');
            $result[trim($drive)] = trim($status);
        }

        return $result ?: null;
    }
}
