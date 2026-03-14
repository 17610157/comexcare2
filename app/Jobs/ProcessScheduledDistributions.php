<?php

namespace App\Jobs;

use App\Models\Distribution;
use App\Services\DistributionService;
use Carbon\Carbon;
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
        $this->processScheduledDistributions();
        $this->processRecurringDistributions();
    }

    private function processScheduledDistributions(): void
    {
        $distributions = Distribution::where('type', 'scheduled')
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $service = new DistributionService;

        foreach ($distributions as $distribution) {
            $service->startDistribution($distribution);
        }
    }

    private function processRecurringDistributions(): void
    {
        $distributions = Distribution::where('type', 'recurring')
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();

        $now = now();

        foreach ($distributions as $distribution) {
            $shouldRun = false;

            switch ($distribution->recurrence) {
                case 'daily':
                    $shouldRun = $this->shouldRunDaily($distribution, $now);
                    break;
                case 'weekly':
                    $shouldRun = $this->shouldRunWeekly($distribution, $now);
                    break;
                case 'monthly':
                    $shouldRun = $this->shouldRunMonthly($distribution, $now);
                    break;
                case 'hourly':
                    $shouldRun = $this->shouldRunHourly($distribution, $now);
                    break;
                case 'minutes':
                    $shouldRun = $this->shouldRunMinutes($distribution, $now);
                    break;
            }

            if ($shouldRun) {
                $service = new DistributionService;
                $service->startDistribution($distribution);
                $distribution->update(['last_run_at' => $now, 'status' => 'in_progress']);
            }
        }
    }

    private function shouldRunDaily(Distribution $distribution, Carbon $now): bool
    {
        if (! $distribution->scheduled_time) {
            return false;
        }

        $scheduledTime = Carbon::parse($distribution->scheduled_time, 'America/Mexico_City');
        $nowWithTime = Carbon::now('America/Mexico_City')->setTime(
            $scheduledTime->hour,
            $scheduledTime->minute,
            0
        );

        if ($distribution->last_run_at) {
            $lastRun = Carbon::parse($distribution->last_run_at);

            return $now->gte($nowWithTime) && $lastRun->lt($nowWithTime->copy()->addDay());
        }

        return $now->gte($nowWithTime) && $now->lt($nowWithTime->copy()->addMinute());
    }

    private function shouldRunWeekly(Distribution $distribution, Carbon $now): bool
    {
        if (! $distribution->scheduled_time || empty($distribution->week_days)) {
            return false;
        }

        $weekDays = is_array($distribution->week_days) ? $distribution->week_days : json_decode($distribution->week_days, true);
        $currentDay = strtolower($now->dayName);

        if (! in_array($currentDay, $weekDays)) {
            return false;
        }

        $scheduledTime = Carbon::parse($distribution->scheduled_time, 'America/Mexico_City');
        $nowWithTime = Carbon::now('America/Mexico_City')->setTime(
            $scheduledTime->hour,
            $scheduledTime->minute,
            0
        );

        if ($distribution->last_run_at) {
            $lastRun = Carbon::parse($distribution->last_run_at);

            return $now->gte($nowWithTime) && $lastRun->lt($nowWithTime->copy()->addDay());
        }

        return $now->gte($nowWithTime) && $now->lt($nowWithTime->copy()->addMinute());
    }

    private function shouldRunMonthly(Distribution $distribution, Carbon $now): bool
    {
        if (! $distribution->scheduled_time) {
            return false;
        }

        $scheduledTime = Carbon::parse($distribution->scheduled_time, 'America/Mexico_City');
        $nowWithTime = Carbon::now('America/Mexico_City')->setTime(
            $scheduledTime->hour,
            $scheduledTime->minute,
            0
        );

        if ($distribution->last_run_at) {
            $lastRun = Carbon::parse($distribution->last_run_at);

            return $now->gte($nowWithTime)
                && $lastRun->lt($nowWithTime->copy()->addDay())
                && $lastRun->month !== $now->month;
        }

        return $now->gte($nowWithTime) && $now->lt($nowWithTime->copy()->addMinute());
    }

    private function shouldRunHourly(Distribution $distribution, Carbon $now): bool
    {
        $interval = $distribution->frequency_interval ?? 1;

        if ($distribution->last_run_at) {
            $lastRun = Carbon::parse($distribution->last_run_at);

            return $lastRun->diffInMinutes($now) >= ($interval * 60);
        }

        return true;
    }

    private function shouldRunMinutes(Distribution $distribution, Carbon $now): bool
    {
        $interval = $distribution->frequency_interval ?? 5;

        if ($distribution->last_run_at) {
            $lastRun = Carbon::parse($distribution->last_run_at);

            return $lastRun->diffInMinutes($now) >= $interval;
        }

        return true;
    }
}
