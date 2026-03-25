<?php

namespace App\Events;

use App\Models\Distribution;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DistributionProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $distribution;

    public $targetId;

    public $targetStatus;

    public $targetProgress;

    public $totalTargets;

    public $completedTargets;

    public $percent;

    public function __construct(Distribution $distribution, ?int $targetId = null, ?string $targetStatus = null, ?int $targetProgress = null)
    {
        $this->distribution = $distribution;
        $this->targetId = $targetId;
        $this->targetStatus = $targetStatus;
        $this->targetProgress = $targetProgress;

        $targets = $distribution->targets;
        $this->totalTargets = $targets->count();
        $this->completedTargets = $targets->where('status', 'completed')->count();
        $this->percent = $this->totalTargets > 0 ? round(($this->completedTargets / $this->totalTargets) * 100) : 0;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('distribution.'.$this->distribution->id),
            new Channel('distributions'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'distribution.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'distribution_id' => $this->distribution->id,
            'distribution_name' => $this->distribution->name,
            'distribution_status' => $this->distribution->status,
            'target_id' => $this->targetId,
            'target_status' => $this->targetStatus,
            'target_progress' => $this->targetProgress,
            'total_targets' => $this->totalTargets,
            'completed_targets' => $this->completedTargets,
            'percent' => $this->percent,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
