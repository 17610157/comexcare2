<?php

namespace App\Jobs;

use App\Models\Command;
use App\Models\Reception;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScheduledReceptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $this->processScheduledReceptions();
        $this->processRecurringReceptions();
    }

    private function processScheduledReceptions(): void
    {
        $receptions = Reception::where('type', 'scheduled')
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($receptions as $reception) {
            $this->executeReception($reception);
        }
    }

    private function processRecurringReceptions(): void
    {
        $receptions = Reception::where('type', 'recurring')
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();

        $now = now();

        foreach ($receptions as $reception) {
            $shouldRun = false;

            switch ($reception->recurrence) {
                case 'daily':
                    $shouldRun = $this->shouldRunDaily($reception, $now);
                    break;
                case 'weekly':
                    $shouldRun = $this->shouldRunWeekly($reception, $now);
                    break;
                case 'monthly':
                    $shouldRun = $this->shouldRunMonthly($reception, $now);
                    break;
                case 'hourly':
                    $shouldRun = $this->shouldRunHourly($reception, $now);
                    break;
                case 'minutes':
                    $shouldRun = $this->shouldRunMinutes($reception, $now);
                    break;
            }

            if ($shouldRun) {
                $this->executeReception($reception);
                $reception->update(['last_run_at' => $now, 'status' => 'in_progress']);
            }
        }
    }

    private function shouldRunDaily(Reception $reception, Carbon $now): bool
    {
        if (! $reception->scheduled_time) {
            return false;
        }

        $scheduledTime = Carbon::parse($reception->scheduled_time, 'America/Mexico_City');
        $nowWithTime = Carbon::now('America/Mexico_City')->setTime(
            $scheduledTime->hour,
            $scheduledTime->minute,
            0
        );

        if ($reception->last_run_at) {
            $lastRun = Carbon::parse($reception->last_run_at);

            return $now->gte($nowWithTime) && $lastRun->lt($nowWithTime->copy()->addDay());
        }

        return $now->gte($nowWithTime) && $now->lt($nowWithTime->copy()->addMinute());
    }

    private function shouldRunWeekly(Reception $reception, Carbon $now): bool
    {
        if (! $reception->scheduled_time || empty($reception->week_days)) {
            return false;
        }

        $weekDays = is_array($reception->week_days) ? $reception->week_days : json_decode($reception->week_days, true);
        $currentDay = strtolower($now->dayName);

        if (! in_array($currentDay, $weekDays)) {
            return false;
        }

        $scheduledTime = Carbon::parse($reception->scheduled_time, 'America/Mexico_City');
        $nowWithTime = Carbon::now('America/Mexico_City')->setTime(
            $scheduledTime->hour,
            $scheduledTime->minute,
            0
        );

        if ($reception->last_run_at) {
            $lastRun = Carbon::parse($reception->last_run_at);

            return $now->gte($nowWithTime) && $lastRun->lt($nowWithTime->copy()->addDay());
        }

        return $now->gte($nowWithTime) && $now->lt($nowWithTime->copy()->addMinute());
    }

    private function shouldRunMonthly(Reception $reception, Carbon $now): bool
    {
        if (! $reception->scheduled_time) {
            return false;
        }

        $scheduledTime = Carbon::parse($reception->scheduled_time, 'America/Mexico_City');
        $nowWithTime = Carbon::now('America/Mexico_City')->setTime(
            $scheduledTime->hour,
            $scheduledTime->minute,
            0
        );

        if ($reception->last_run_at) {
            $lastRun = Carbon::parse($reception->last_run_at);

            return $now->gte($nowWithTime)
                && $lastRun->lt($nowWithTime->copy()->addDay())
                && $lastRun->month !== $now->month;
        }

        return $now->gte($nowWithTime) && $now->lt($nowWithTime->copy()->addMinute());
    }

    private function shouldRunHourly(Reception $reception, Carbon $now): bool
    {
        $interval = $reception->frequency_interval ?? 1;

        if ($reception->last_run_at) {
            $lastRun = Carbon::parse($reception->last_run_at);

            return $lastRun->diffInMinutes($now) >= ($interval * 60);
        }

        return true;
    }

    private function shouldRunMinutes(Reception $reception, Carbon $now): bool
    {
        $interval = $reception->frequency_interval ?? 5;

        if ($reception->last_run_at) {
            $lastRun = Carbon::parse($reception->last_run_at);

            return $lastRun->diffInMinutes($now) >= $interval;
        }

        return true;
    }

    private function executeReception(Reception $reception): void
    {
        // Para recepciones recurrentes, resetear los targets a pending
        if ($reception->type === 'recurring') {
            $reception->targets()->update(['status' => 'pending', 'progress' => 0, 'completed_at' => null]);
        }

        $targets = $reception->targets()->where('status', 'pending')->get();

        $commandCount = 0;

        foreach ($targets as $target) {
            $computer = $target->computer;

            if (! $computer || ! in_array($computer->status, ['active', 'online'])) {
                continue;
            }

            $command = Command::create([
                'computer_id' => $computer->id,
                'type' => 'receive',
                'status' => 'pending',
                'data' => [
                    'reception_target_id' => $target->id,
                    'receive_paths' => $computer->receive_paths ?? [],
                    'file_types' => $reception->file_types,
                    'specific_files' => $reception->specific_files,
                    'all_files' => $reception->all_files,
                    'scheduled_time' => $reception->scheduled_time,
                    'reception_type' => $reception->type,
                    'recurrence' => $reception->recurrence,
                    'frequency_interval' => $reception->frequency_interval,
                    'week_days' => $reception->week_days,
                ],
            ]);

            Log::info("Created receive command for reception {$reception->id}, computer {$computer->id}, command {$command->id}");
        }

        if ($reception->type !== 'recurring') {
            $reception->update(['status' => 'in_progress']);
        }
    }
}
