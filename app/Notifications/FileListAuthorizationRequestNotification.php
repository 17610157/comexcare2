<?php

namespace App\Notifications;

use App\Models\FileList;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

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

        $mail = (new MailMessage)
            ->subject('Solicitud de Autorización - Lista de Archivos')
            ->greeting('¡Hola!')
            ->line('Se ha registrado un nuevo archivo en la lista de archivos que requiere tu autorización.')
            ->line("**Tipo:** {$typeName}")
            ->line("**Archivo:** {$this->fileList->file_name}")
            ->line("**Descripción:** {$description}")
            ->line("**Registrado por:** {$creatorName}")
            ->line("**Fecha de registro:** {$this->fileList->created_at->format('d/m/Y H:i')}");

        if ($this->fileList->file_md5) {
            $mail->line("**Hash MD5:** `{$this->fileList->file_md5}`");
        }

        if ($this->fileList->hasAttachment() && Storage::disk('local')->exists($this->fileList->file_path)) {
            $mail->line('El archivo se adjunta a este correo para que puedas revisarlo antes de autorizar.')
                ->attach(Storage::disk('local')->path($this->fileList->file_path), [
                    'as' => $this->fileList->file_name,
                ]);
        }

        return $mail
            ->line('Este enlace expirará en 48 horas.')
            ->action('Autorizar', $this->authorizationUrl)
            ->line('Si no deseas autorizar este registro, simplemente ignora este correo.');
    }
}