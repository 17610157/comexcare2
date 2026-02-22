<?php

namespace App\Jobs;

use App\Models\DistributionTarget;
use App\Services\DistributionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryDistribution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public DistributionTarget $target
    ) {}

    public function handle(): void
    {
        $service = new DistributionService();
        $service->sendDownloadCommand($this->target);
    }
}