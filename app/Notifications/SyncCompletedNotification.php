<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SyncCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tableName,
        public int $recordsAffected,
        public string $duration,
        public bool $success = true,
        public ?string $error = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Sincronización {$this->tableName} - ".($this->success ? 'Completada' : 'Fallida'));

        if ($this->success) {
            $message->line("La sincronización de **{$this->tableName}** se completó exitosamente.")
                ->line("**Registros afectados:** {$this->recordsAffected}")
                ->line("**Duración:** {$this->duration}");
        } else {
            $message->line("La sincronización de **{$this->tableName}** falló.")
                ->line("**Error:** {$this->error}");
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sync_completed',
            'table_name' => $this->tableName,
            'records_affected' => $this->recordsAffected,
            'duration' => $this->duration,
            'success' => $this->success,
            'error' => $this->error,
            'message' => "Sincronización {$this->tableName}: {$this->success}",
        ];
    }
}
