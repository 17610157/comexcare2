<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Login2faNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('COMEXCARE - Código de verificación')
            ->line('Se ha solicitado acceso a tu cuenta de COMEXCARE.')
            ->line('Utiliza el siguiente código para completar tu inicio de sesión:')
            ->line("**{$this->token}**")
            ->line('Este código expira en 5 minutos.')
            ->line('Si no solicitaste este acceso, ignora este mensaje.');
    }
}