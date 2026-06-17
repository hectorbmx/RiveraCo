<?php

namespace App\Notifications;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrdenCompraCreada extends Notification
{
    use Queueable;

    protected $orden;

    /**
     * Create a new notification instance.
     */
    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
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
            'tipo' => 'orden_compra',
            'id' => $this->orden->id,
            'folio' => $this->orden->folio,
            'obra_nombre' => $this->orden->obra?->nombre ?? 'N/A',
            'proveedor' => $this->orden->proveedor?->nombre_comercial ?? 'N/A',
            'total' => $this->orden->total,
            'usuario_registro' => $this->orden->usuario_registro,
            'mensaje' => "Nueva Orden de Compra: {$this->orden->folio} - Total: $" . number_format($this->orden->total, 2),
            // 'url' => route('ordenes-compra.show', $this->orden->id), // Ajustar según ruta real
        ];
    }
}
