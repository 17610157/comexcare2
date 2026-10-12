<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CortesFaltantesPorPlaza extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $sinCorte
     * @param  array<int, array<string, mixed>>  $regla
     * @param  array<int, string>  $destinatarios
     */
    public function __construct(
        public string $plaza,
        public Carbon $fecha,
        public array $sinCorte = [],
        public array $regla = [],
        public array $destinatarios = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cortes faltantes - Plaza '.$this->plaza.' - '.$this->fecha->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.cortes-faltantes-por-plaza',
        );
    }
}
