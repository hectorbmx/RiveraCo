<?php

namespace App\Notifications;

use App\Models\OrdenCompra;
use App\Models\PagoProveedor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrdenCompraFlujoNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected OrdenCompra $orden,
        protected string $evento,
        protected ?PagoProveedor $pago = null
    ) {
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
        $orden = $this->orden;
        $proveedor = $orden->proveedor?->nombre
            ?: $orden->proveedor?->nombre_comercial
            ?: $orden->proveedor?->razon_social
            ?: 'Proveedor N/A';
        $obra = $orden->obra?->nombre ?? 'N/A';
        $total = (float) ($orden->total ?? 0);
        $fechaProgramada = $this->pago?->fecha_programada?->format('d/m/Y') ?? 'fecha pendiente';
        $autorizadoPor = $orden->autorizadoPor?->name ?: $orden->usuario_autoriza ?: 'N/A';
        $programadoPor = $this->pago?->programadoPor?->name ?? 'N/A';

        [$titulo, $mensaje, $url, $icono, $prioridad] = match ($this->evento) {
            'creada' => [
                'Orden de compra por autorizar',
                "Nueva orden {$orden->folio} por autorizar. Revisar version impresa desde ordenes de compra.",
                route('ordenes_compra.index', ['estado' => 'programada']),
                'shopping-cart',
                'normal',
            ],
            'autorizada' => [
                'Orden de compra autorizada',
                "La orden {$orden->folio} fue autorizada.",
                route('ordenes_compra.edit', $orden->id),
                'check-circle',
                'normal',
            ],
            'lista_pago' => [
                'Orden lista para programar pago',
                "La orden {$orden->folio} esta autorizada y lista para programar pago.",
                route('pagos-proveedores.create', ['orden_compra_id' => $orden->id]),
                'calendar',
                'normal',
            ],
            'pago_programado' => [
                'Pago programado',
                "Pago programado para la orden {$orden->folio} el {$fechaProgramada}. Autorizo: {$autorizadoPor}. Programo: {$programadoPor}.",
                route('pagos-proveedores.index'),
                'calendar-check',
                'normal',
            ],
            default => [
                'Orden de compra',
                "Actualizacion de la orden {$orden->folio}.",
                route('ordenes_compra.edit', $orden->id),
                'info',
                'normal',
            ],
        };

        return [
            'tipo' => 'orden_compra',
            'evento' => $this->evento,
            'title' => $titulo,
            'titulo' => $titulo,
            'message' => $mensaje,
            'mensaje' => $mensaje,
            'url' => $url,
            'icon' => $icono,
            'priority' => $prioridad,
            'id' => $orden->id,
            'orden_compra_id' => $orden->id,
            'folio' => $orden->folio,
            'obra_nombre' => $obra,
            'proveedor' => $proveedor,
            'total' => $total,
            'usuario_registro' => $orden->usuario_registro,
            'usuario_autoriza' => $orden->usuario_autoriza,
            'autorizado_por_name' => $autorizadoPor,
            'programado_por_name' => $programadoPor,
            'pago_id' => $this->pago?->id,
            'pago_monto' => $this->pago ? (float) $this->pago->monto : null,
            'fecha_programada' => $this->pago?->fecha_programada?->format('Y-m-d'),
            'fecha_programada_formatted' => $this->pago?->fecha_programada?->format('d/m/Y'),
        ];
    }
}
