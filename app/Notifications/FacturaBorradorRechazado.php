<?php

namespace App\Notifications;

use App\Models\ObraFacturaBorrador;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FacturaBorradorRechazado extends Notification
{
    use Queueable;

    public function __construct(protected ObraFacturaBorrador $borrador)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'factura_borrador_rechazado',
            'id' => $this->borrador->id,
            'obra_id' => $this->borrador->obra_id,
            'obra_nombre' => $this->borrador->obra?->nombre ?? 'Obra N/A',
            'obra_clave' => $this->borrador->obra?->clave_obra,
            'cliente' => $this->borrador->cliente?->razon_social
                ?: $this->borrador->cliente?->nombre_comercial
                ?: 'Cliente N/A',
            'total' => $this->borrador->total,
            'rechazado_por_name' => $this->borrador->rechazador?->name ?? 'N/A',
            'observaciones_revision' => $this->borrador->observaciones_revision,
            'mensaje' => "Borrador de factura rechazado: {$this->borrador->obra?->nombre}",
            'url' => route('obras.factura-borradores.show', [
                'obra' => $this->borrador->obra_id,
                'borrador' => $this->borrador->id,
            ]),
        ];
    }
}
