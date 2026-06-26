<?php

namespace App\Notifications;

use App\Models\ObraFacturaBorrador;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FacturaBorradorAutorizado extends Notification
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
            'tipo' => 'factura_borrador_autorizado',
            'id' => $this->borrador->id,
            'obra_id' => $this->borrador->obra_id,
            'obra_nombre' => $this->borrador->obra?->nombre ?? 'Obra N/A',
            'obra_clave' => $this->borrador->obra?->clave_obra,
            'cliente' => $this->borrador->cliente?->razon_social
                ?: $this->borrador->cliente?->nombre_comercial
                ?: 'Cliente N/A',
            'total' => $this->borrador->total,
            'autorizado_por_name' => $this->borrador->autorizador?->name ?? 'N/A',
            'mensaje' => "Borrador de factura autorizado: {$this->borrador->obra?->nombre}",
            'url' => route('obras.edit', ['obra' => $this->borrador->obra_id, 'tab' => 'facturacion']),
        ];
    }
}
