<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgentOfflineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $computerName,
        public string $lastSeen,
        public int $offlineMinutes
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Agente Offline: {$this->computerName}")
            ->line("El agente **{$this->computerName}** está offline.")
            ->line("**Última conexión:** {$this->lastSeen}")
            ->line("**Tiempo offline:** {$this->offlineMinutes} minutos")
            ->action('Ver Detalles', url('/admin/computers'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'agent_offline',
            'computer_name' => $this->computerName,
            'last_seen' => $this->lastSeen,
            'offline_minutes' => $this->offlineMinutes,
            'message' => "Agente {$this->computerName} está offline",
        ];
    }
}
