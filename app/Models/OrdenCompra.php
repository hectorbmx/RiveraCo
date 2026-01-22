<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';

    protected $fillable = [
        'folio',
        'proveedor_id',
        'obra_id',
        'area_id',
        'area',
        'cotizacion',
        'atencion',
        'tipo_pago',
        'forma_pago',
        'subtotal',
        'iva',
        'otros_impuestos',
        'total',
        'tipo_cambio',
        'moneda',
        'fecha',
        'estado',
        'usuario_registro',
        'usuario_autoriza',
        'fecha_autorizacion',
        'comentarios',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_autorizacion' => 'date',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'otros_impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'tipo_cambio' => 'decimal:4',
    ];

    // Mapeo de estado legacy -> "nuevo"
    public function getEstadoNormalizadoAttribute(): string
    {
        $e = strtoupper((string)($this->estado ?? 'BORRADOR'));
        return match ($e) {
            'BORRADOR', 'PROGRAMADA' => 'programada',
            'AUTORIZADA' => 'autorizada',
            'CANCELADA' => 'cancelada',
            default => strtolower($e),
        };
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function areaCatalogo()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function detalles()
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'orden_compra_id');
    }
}
