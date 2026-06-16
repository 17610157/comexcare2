<?php

namespace App\Jobs;

use App\Models\Command;
use App\Models\Distribution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDistributionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $distribution;

    public $timeout = 300;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public function __construct(Distribution $distribution)
    {
        $this->distribution = $distribution;
        $this->onQueue('distributions');
    }

    public function handle(): void
    {
        $distribution = $this->distribution->fresh(['targets.computer', 'files']);

        Log::info('ProcessDistributionJob: Loaded distribution '.$distribution->id.' with '.$distribution->targets->count().' targets and '.$distribution->files->count().' files');

        if ($distribution->status === 'stopped') {
            Log::info("Distribution {$distribution->id} is stopped, skipping");

            return;
        }

        if ($distribution->type !== 'recurring') {
            $distribution->update(['status' => 'in_progress']);
        }

        $targets = $distribution->targets->where('status', 'pending');

        Log::info('ProcessDistributionJob: Processing '.$targets->count().' pending targets');

        $batchSize = 10;
        $commandDelay = 0.1;

        $targets->chunk($batchSize)->each(function ($chunk, $index) use ($distribution, $commandDelay) {
            if ($distribution->fresh()->status === 'stopped') {
                return false;
            }

            Log::info('ProcessDistributionJob: Processing batch '.($index + 1).' with '.$chunk->count().' targets');

            $commands = [];
            $targetIds = [];

            foreach ($chunk as $target) {
                $targetIds[] = $target->id;
                $target->update(['status' => 'in_progress', 'progress' => 0]);

                // Si es tipo comando/ejecutar
                if ($distribution->distribution_type === 'command' && $distribution->command) {
                    $commandData = [
                        'command' => $distribution->command,
                        'command_args' => $distribution->command_args ?? '',
                        'distribution_target_id' => $target->id,
                    ];

                    $commands[] = [
                        'computer_id' => $target->computer_id,
                        'type' => 'execute',
                        'data' => json_encode($commandData),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } else {
                    // Tipo archivo normal o update
                    foreach ($distribution->files as $file) {
                        $commandData = [
                            'file_id' => $file->id,
                            'distribution_target_id' => $target->id,
                        ];

                        if ($distribution->subfolder) {
                            $commandData['subfolder'] = $distribution->subfolder;
                        }

                        $commands[] = [
                            'computer_id' => $target->computer_id,
                            'type' => 'download',
                            'data' => json_encode($commandData),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            if (! empty($commands)) {
                Command::insert($commands);
                Log::info('ProcessDistributionJob: Inserted '.count($commands).' commands for batch '.($index + 1));
            }

            if ($commandDelay > 0) {
                usleep((int) ($commandDelay * 1000000));
            }

            return true;
        });

        if ($distribution->type !== 'recurring') {
            $this->checkAndUpdateDistributionStatus($distribution);
        }
    }

    private function checkAndUpdateDistributionStatus(Distribution $distribution): void
    {
        $targets = $distribution->fresh()->targets;
        $allCompleted = $targets->every(function ($target) {
            return in_array($target->status, ['completed', 'failed']);
        });

        if ($allCompleted && $targets->isNotEmpty()) {
            $hasFailures = $targets->contains('status', 'failed');
            $distribution->update([
                'status' => $hasFailures ? 'failed' : 'completed',
            ]);
            Log::info("Distribution {$distribution->id} completed with status: ".($hasFailures ? 'failed' : 'completed'));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessDistributionJob failed for distribution {$this->distribution->id}: ".$exception->getMessage());
        $this->distribution->update(['status' => 'failed']);
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(1);
    }
}
