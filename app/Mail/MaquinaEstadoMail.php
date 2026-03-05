<?php
namespace App\Mail;

use App\Models\Maquina;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaquinaEstadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Maquina $maquina,
        public string $anterior,
        public string $nuevo,
        public ?string $motivo = null,
        public ?string $notas = null,

    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Cambio de Estado: Maquina {$this->maquina->codigo_interno}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maquinas.cambio_estado',
        );
    }
}