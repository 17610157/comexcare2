<?php

namespace App\Console\Commands;

use App\Models\Command as DistributionCommand;
use App\Models\Distribution;
use App\Models\DistributionTarget;
use Illuminate\Console\Command;

class RestartDistribution extends Command
{
    protected $signature = 'distribution:restart {id : The distribution ID} {--only-in-progress : Only restart targets with in_progress status}';

    protected $description = 'Restart processing for a distribution';

    public function handle(): int
    {
        $distributionId = $this->argument('id');
        $onlyInProgress = $this->option('only-in-progress');

        $distribution = Distribution::find($distributionId);

        if (! $distribution) {
            $this->error("Distribution {$distributionId} not found.");

            return self::FAILURE;
        }

        $query = DistributionTarget::where('distribution_id', $distributionId);

        if ($onlyInProgress) {
            $query->where('status', 'in_progress');
        }

        $targets = $query->get();

        if ($targets->isEmpty()) {
            $this->info("No targets to restart for distribution {$distributionId}.");

            return self::SUCCESS;
        }

        $this->info("Restarting {$targets->count()} targets for distribution {$distributionId}...");

        $commands = [];

        foreach ($targets as $target) {
            $target->update(['status' => 'pending', 'progress' => 0, 'attempts' => 0]);

            foreach ($distribution->files as $file) {
                $commands[] = [
                    'computer_id' => $target->computer_id,
                    'type' => 'download',
                    'data' => json_encode([
                        'file_id' => $file->id,
                        'distribution_target_id' => $target->id,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($commands)) {
            DistributionCommand::insert($commands);
            $this->info('Created '.count($commands).' commands.');
        }

        $distribution->update(['status' => 'in_progress']);
        $this->info("Distribution {$distributionId} restarted successfully.");

        return self::SUCCESS;
    }
}
