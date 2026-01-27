<?php

namespace App\Jobs;

use App\Models\Distribution;
use App\Services\DistributionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScheduledDistributions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $distributions = Distribution::where('type', 'scheduled')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->get();

        $service = new DistributionService();

        foreach ($distributions as $distribution) {
            $service->startDistribution($distribution);
        }
    }
}