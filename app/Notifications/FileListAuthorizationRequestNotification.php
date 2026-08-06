<?php

namespace App\Notifications;

use App\Models\FileList;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FileListAuthorizationRequestNotification extends Notification
{
    public function __construct(
        public FileList $fileList,
        public string $authorizationUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $typeName = $this->fileList->type === 'whitelist' ? 'Whitelist (Blanca)' : 'Blacklist (Negra)';
        $creatorName = $this->fileList->creator->name ?? 'Desconocido';
        $description = $this->fileList->description ?? 'Sin descripción';

        return (new MailMessage)
            ->subject('Solicitud de Autorización - Lista de Archivos')
            ->greeting('¡Hola!')
            ->line('Se ha registrado un nuevo archivo en la lista de archivos que requiere tu autorización.')
            ->line("**Tipo:** {$typeName}")
            ->line("**Archivo:** {$this->fileList->file_name}")
            ->line("**Descripción:** {$description}")
            ->line("**Registrado por:** {$creatorName}")
            ->line("**Fecha de registro:** {$this->fileList->created_at->format('d/m/Y H:i')}")
            ->line('Este enlace expirará en 48 horas.')
            ->action('Autorizar', $this->authorizationUrl)
            ->line('Si no deseas autorizar este registro, simplemente ignora este correo.');
    }
}
