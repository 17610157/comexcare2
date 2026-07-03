<?php

namespace App\Console\Commands;

use App\Models\Computer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateAgents extends Command
{
    protected $signature = 'computers:clean-duplicates
        {--dry-run : Solo mostrar lo que se haría sin ejecutar cambios}
        {--computer-name= : Filtrar por nombre de equipo específico}';

    protected $description = 'Limpia agentes duplicados: pasa short_key, plaza y grupo del registro OFFLINE al ONLINE y elimina el OFFLINE';

    public function handle(): int
    {
        $this->info('=== Limpieza de agentes duplicados ===');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $specificName = $this->option('computer-name');

        $duplicates = $this->findDuplicates($specificName);

        if (empty($duplicates)) {
            $this->info('No se encontraron agentes duplicados.');

            return self::SUCCESS;
        }

        $this->info('Se encontraron '.count($duplicates).' grupos de duplicados.');
        $this->newLine();

        $totalProcessed = 0;
        $totalDeleted = 0;
        $totalUpdated = 0;

        foreach ($duplicates as $group) {
            $result = $this->processDuplicateGroup($group, $dryRun);
            $totalProcessed += $result['processed'];
            $totalDeleted += $result['deleted'];
            $totalUpdated += $result['updated'];
        }

        $this->newLine();
        $this->info('=== Resumen ===');
        $this->info("Grupos procesados: {$totalProcessed}");
        $this->info("Registros actualizados: {$totalUpdated}");
        $this->info("Registros eliminados: {$totalDeleted}");
        $this->info('Modo dry-run: '.($dryRun ? 'SI' : 'NO'));

        return self::SUCCESS;
    }

    private function findDuplicates(?string $specificName): array
    {
        $nameQuery = Computer::select('computer_name')
            ->whereNotNull('computer_name')
            ->where('computer_name', '!=', '')
            ->groupBy('computer_name')
            ->havingRaw('COUNT(*) > 1');

        $macQuery = Computer::select('mac_address')
            ->whereNotNull('mac_address')
            ->where('mac_address', '!=', '')
            ->where('mac_address', 'not like', 'AUTO-REC-%')
            ->groupBy('mac_address')
            ->havingRaw('COUNT(*) > 1');

        if ($specificName) {
            $nameQuery->where('computer_name', $specificName);
            $macQuery->where('mac_address', $specificName);
        }

        $duplicateNames = $nameQuery->pluck('computer_name')->toArray();
        $duplicateMacs = $macQuery->pluck('mac_address')->toArray();

        $uniqueKeys = array_unique(array_merge($duplicateNames, $duplicateMacs));
        $groups = [];

        foreach ($uniqueKeys as $key) {
            $byName = Computer::where('computer_name', $key)->orderBy('id')->get();
            $byMac = Computer::where('mac_address', $key)
                ->where('computer_name', '!=', $key)
                ->orderBy('id')
                ->get();

            $computers = collect();

            foreach ($byName as $c) {
                if (! $computers->pluck('id')->contains($c->id)) {
                    $computers->push($c);
                }
            }
            foreach ($byMac as $c) {
                if (! $computers->pluck('id')->contains($c->id)) {
                    $computers->push($c);
                }
            }

            if ($computers->count() > 1) {
                $groups[] = [
                    'key' => $key,
                    'computers' => $computers,
                ];
            }
        }

        return $groups;
    }

    private function processDuplicateGroup(array $group, bool $dryRun): array
    {
        $key = $group['key'];
        $computers = $group['computers'];

        $this->line('──────────────────────────────────────────');
        $this->line("Duplicado: <comment>{$key}</comment> ({$computers->count()} registros)");

        $online = $computers->firstWhere('status', 'online');
        $offline = $computers->firstWhere('status', 'offline');

        if (! $online || ! $offline) {
            $statuses = $computers->pluck('status')->implode(', ');
            $this->warn("  ↳ Sin par online/offline (status: {$statuses}). Se omite.");

            return ['processed' => 0, 'updated' => 0, 'deleted' => 0];
        }

        if ($online->id === $offline->id) {
            $this->warn('  ↳ Mismo registro. Se omite.');

            return ['processed' => 0, 'updated' => 0, 'deleted' => 0];
        }

        $onlineMac = $online->mac_address ?? '';
        $hasRealMac = ! empty($onlineMac) && ! str_starts_with($onlineMac, 'AUTO-REC-') && $onlineMac !== '00:00:00:00:00:00';

        $this->line("  ONLINE   ID:{$online->id} | name:{$online->computer_name} | mac:{$onlineMac} | short_key:{$online->short_key} | plaza:{$online->plaza} | group_id:{$online->group_id}");
        $this->line("  OFFLINE  ID:{$offline->id} | name:{$offline->computer_name} | mac:{$offline->mac_address} | short_key:{$offline->short_key} | plaza:{$offline->plaza} | group_id:{$offline->group_id}");

        $newShortKey = ! empty($offline->short_key) ? $offline->short_key : $online->short_key;
        $newPlaza = ! empty($offline->plaza) ? $offline->plaza : $online->plaza;
        $newGroupId = ! empty($offline->group_id) ? $offline->group_id : $online->group_id;
        $newMac = $hasRealMac ? $online->mac_address : $offline->mac_address;

        if (! $dryRun) {
            DB::transaction(function () use ($offline, $newShortKey, $newPlaza, $newGroupId, $newMac, $online) {
                $offline->update([
                    'short_key' => null,
                    'mac_address' => 'PENDING-DEL-'.$offline->id,
                ]);

                $online->update([
                    'short_key' => $newShortKey,
                    'plaza' => $newPlaza,
                    'group_id' => $newGroupId,
                    'mac_address' => $newMac,
                ]);

                $offline->delete();
            });
        }

        if ($dryRun) {
            $this->line("  ↳ Datos a pasar: short_key={$newShortKey} plaza={$newPlaza} group_id={$newGroupId} mac={$newMac}");
            $this->info("  ↳ [DRY-RUN] Se actualizaría ONLINE (ID:{$online->id}) y se eliminaría OFFLINE (ID:{$offline->id})");
        } else {
            $this->info("  ↳ OK: ONLINE ID:{$online->id} actualizado, OFFLINE ID:{$offline->id} eliminado");
        }

        return ['processed' => 1, 'updated' => 1, 'deleted' => 1];
    }
}
