<?php

namespace App\Listeners;

use App\Services\DashboardStatsService;
use Illuminate\Queue\Events\JobFailed;

class DashboardJobFailedListener
{
    public function handle(JobFailed $event): void
    {
        if ($event->job?->resolveName() === \Illuminate\Broadcasting\BroadcastEvent::class) {
            return;
        }

        DashboardStatsService::touch(10);
    }
}
