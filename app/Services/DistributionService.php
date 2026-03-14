<?php

namespace App\Services;

use App\Models\Command;
use App\Models\Computer;
use App\Models\Distribution;
use App\Models\DistributionFile;
use App\Models\DistributionTarget;
use Illuminate\Support\Facades\Storage;

class DistributionService
{
    public function createDistribution(array $data, $userId): Distribution
    {
        $distribution = Distribution::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'schedule' => $data['schedule'] ?? null,
            'description' => $data['description'] ?? null,
            'created_by' => $userId,
            'status' => 'pending',
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'recurrence' => $data['recurrence'] ?? null,
            'frequency_interval' => $data['frequency_interval'] ?? null,
            'week_days' => $data['week_days'] ?? null,
        ]);

        // Handle files
        if (isset($data['files'])) {
            foreach ($data['files'] as $file) {
                // Store in public disk for API access
                $path = $file->store('distributions', 'public');
                DistributionFile::create([
                    'distribution_id' => $distribution->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'checksum' => hash_file('sha256', Storage::disk('public')->path($path)),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Handle targets - multiple groups support
        $targetComputerIds = $data['computer_ids'] ?? ($data['targets'] ?? []);

        if ($data['target_type'] === 'all') {
            $computers = Computer::all();
        } elseif ($data['target_type'] === 'group') {
            // Support multiple groups
            $groupIds = $data['group_ids'] ?? ($data['group_id'] ? [$data['group_id']] : []);
            $computers = Computer::whereIn('group_id', $groupIds)->get();
        } elseif (! empty($targetComputerIds)) {
            $computers = Computer::whereIn('id', $targetComputerIds)->get();
        } else {
            $computers = collect();
        }

        foreach ($computers as $computer) {
            DistributionTarget::create([
                'distribution_id' => $distribution->id,
                'computer_id' => $computer->id,
            ]);
        }

        return $distribution;
    }

    public function startDistribution(Distribution $distribution)
    {
        $distribution->update(['status' => 'in_progress']);

        $targets = $distribution->targets;

        foreach ($targets as $target) {
            $this->sendDownloadCommand($target);
        }
    }

    public function sendDownloadCommand(DistributionTarget $target)
    {
        $files = $target->distribution->files;

        foreach ($files as $file) {
            Command::create([
                'computer_id' => $target->computer_id,
                'type' => 'download',
                'data' => [
                    'file_id' => $file->id,
                    'distribution_target_id' => $target->id,
                ],
            ]);
        }
    }

    public function handleRetry(DistributionTarget $target)
    {
        if ($target->attempts >= 3) {
            $target->update(['status' => 'failed']);

            return;
        }

        $delays = [1, 5, 15, 60]; // minutes
        $delay = $delays[$target->attempts] ?? 60;

        $target->update([
            'attempts' => $target->attempts + 1,
            'next_retry_at' => now()->addMinutes($delay),
            'status' => 'pending',
        ]);

        // Schedule job to retry
        \App\Jobs\RetryDistribution::dispatch($target)->delay($target->next_retry_at);
    }

    public function validateFileSpace(Computer $computer, DistributionFile $file): bool
    {
        // Check system_info for disk space
        $systemInfo = $computer->system_info;
        if (! $systemInfo || ! isset($systemInfo['disk_free'])) {
            return true; // Assume ok if not available
        }

        return $systemInfo['disk_free'] > $file->file_size;
    }
}
