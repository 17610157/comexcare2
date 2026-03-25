<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DistributionCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $distributionName,
        public string $status,
        public int $totalTargets,
        public int $completedTargets,
        public int $failedTargets
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusText = $this->status === 'completed' ? 'completada exitosamente' : 'falló';

        return (new MailMessage)
            ->subject("Distribución {$this->distributionName} - {$statusText}")
            ->line("La distribución **{$this->distributionName}** ha {$statusText}.")
            ->line('**Resumen:**')
            ->line("- Total de objetivos: {$this->totalTargets}")
            ->line("- Completados: {$this->completedTargets}")
            ->line("- Fallidos: {$this->failedTargets}")
            ->action('Ver Detalles', url('/admin/distributions'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'distribution_completed',
            'distribution_name' => $this->distributionName,
            'status' => $this->status,
            'total_targets' => $this->totalTargets,
            'completed_targets' => $this->completedTargets,
            'failed_targets' => $this->failedTargets,
            'message' => "Distribución {$this->distributionName} {$this->status}",
        ];
    }
}
