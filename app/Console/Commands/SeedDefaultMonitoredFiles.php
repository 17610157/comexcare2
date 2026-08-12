<?php

namespace App\Console\Commands;

use App\Models\Group;
use Database\Seeders\DefaultMonitoredFilesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedDefaultMonitoredFiles extends Command
{
    protected $signature = 'monitored-files:seed-defaults
        {--group= : ID del grupo al que asignar la lista (por defecto, a todos los grupos)}';

    protected $description = 'Asigna la lista predeterminada de archivos monitoreados a los grupos';

    public function handle(): int
    {
        $groupId = $this->option('group');

        $groups = $groupId
            ? Group::whereKey((int) $groupId)->get()
            : Group::all();

        if ($groups->isEmpty()) {
            $this->warn('No hay grupos. Crea grupos o pasa --group={id}.');

            return self::FAILURE;
        }

        $entries = DefaultMonitoredFilesSeeder::defaultEntries();

        foreach ($groups as $group) {
            DB::transaction(function () use ($group, $entries) {
                $group->monitoredFiles()->delete();

                foreach ($entries as $i => $entry) {
                    $group->monitoredFiles()->create($entry + ['sort_order' => $i + 1]);
                }
            });

            $this->info("Lista predeterminada asignada al grupo '{$group->name}' (id={$group->id}).");
        }

        return self::SUCCESS;
    }
}
