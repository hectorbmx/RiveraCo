<?php

namespace App\Http\Requests;

use App\Models\CompraRecepcionDirecta;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompraRecepcionDirectaRequest extends FormRequest
{
    public const PERMISO_CONFIRMAR = CompraRecepcionDirecta::PERMISO_CONFIRMAR;

    public function authorize(): bool
    {
        return $this->user()?->can(self::PERMISO_CONFIRMAR) ?? false;
    }

    public function rules(): array
    {
        return [
            'operacion_uuid' => ['required', 'uuid'],
            'fecha_recepcion' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'receptor_nombre' => ['required', 'string', 'max:160'],
            'remision' => ['nullable', 'string', 'max:160'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'evidencias' => ['required', 'array', 'min:1', 'max:5'],
            'evidencias.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'partidas' => ['required', 'array', 'min:1'],
            'partidas.*.orden_compra_detalle_id' => ['required', 'integer', 'min:1', 'distinct'],
            'partidas.*.cantidad_recibida' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'obra_id' => ['prohibited'],
            'almacen_id' => ['prohibited'],
            'destino' => ['prohibited'],
            'destinos' => ['prohibited'],
            'partidas.*.obra_id' => ['prohibited'],
            'partidas.*.almacen_id' => ['prohibited'],
            'partidas.*.destino' => ['prohibited'],
            'partidas.*.obra_requisicion_detalle_id' => ['prohibited'],
            'costo_unitario' => ['prohibited'],
            'partidas.*.costo_unitario' => ['prohibited'],
            'partidas.*.precio_unitario' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'evidencias.required' => 'Adjunta al menos una remisión o fotografía de la entrega.',
            'evidencias.*.mimes' => 'La evidencia debe ser PDF o imagen JPG, PNG o WebP.',
            'evidencias.*.max' => 'Cada evidencia puede ocupar hasta 10 MB.',
            'receptor_nombre.required' => 'Indica quién recibió físicamente el material en obra.',
            'fecha_recepcion.before_or_equal' => 'La recepción no puede tener una fecha futura.',
            'partidas.*.orden_compra_detalle_id.distinct' => 'Una partida no puede repetirse en la misma recepción.',
            'partidas.*.cantidad_recibida.gt' => 'La cantidad recibida debe ser mayor que cero.',
            'obra_id.prohibited' => 'La obra destino se toma de la orden de compra y no puede cambiarse aquí.',
            'almacen_id.prohibited' => 'El almacén se toma de la orden de compra y no puede cambiarse aquí.',
            'destino.prohibited' => 'El destino está fijado en la orden de compra.',
        ];
    }
}
