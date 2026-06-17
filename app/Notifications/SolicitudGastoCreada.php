<?php

namespace App\Notifications;

use App\Models\ObraSolicitudGasto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudGastoCreada extends Notification
{
    use Queueable;

    protected $solicitud;

    /**
     * Create a new notification instance.
     */
    public function __construct(ObraSolicitudGasto $solicitud)
    {
        $this->solicitud = $solicitud;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'solicitud_gasto',
            'id' => $this->solicitud->id,
            'obra_id' => $this->solicitud->obra_id,
            'obra_nombre' => $this->solicitud->obra?->nombre ?? 'Obra N/A',
            'semana' => $this->solicitud->semana,
            'total' => $this->solicitud->total,
            'solicitado_por_name' => $this->solicitud->solicitadoPor?->name ?? 'N/A',
            'mensaje' => "Nueva solicitud de gasto: {$this->solicitud->obra?->nombre} - Semana {$this->solicitud->semana}",
            'url' => route('obras.solicitudes-gastos.show', [$this->solicitud->obra_id, $this->solicitud->id]),
        ];
    }
}
