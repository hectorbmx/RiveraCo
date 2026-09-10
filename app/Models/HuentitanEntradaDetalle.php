<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuentitanEntradaDetalle extends Model
{
    protected $table = 'huentitan_entrada_detalles';

    protected $fillable = [
        'huentitan_entrada_id',
        'orden_compra_detalle_id',
        'huentitan_salida_detalle_id',
        'producto_id',
        'descripcion',
        'unidad',
        'cantidad_ordenada',
        'cantidad_recibida',
        'costo_unitario',
        'importe',
        'observaciones',
    ];

    protected $casts = [
        'cantidad_ordenada' => 'decimal:3',
        'cantidad_recibida' => 'decimal:3',
        'costo_unitario' => 'decimal:4',
        'importe' => 'decimal:2',
        'huentitan_salida_detalle_id' => 'integer',
    ];

    public function entrada(): BelongsTo
    {
        return $this->belongsTo(HuentitanEntrada::class, 'huentitan_entrada_id');
    }

    public function ordenCompraDetalle(): BelongsTo
    {
        return $this->belongsTo(OrdenCompraDetalle::class, 'orden_compra_detalle_id');
    }

    public function huentitanSalidaDetalle(): BelongsTo
    {
        return $this->belongsTo(HuentitanSalidaDetalle::class, 'huentitan_salida_detalle_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}



