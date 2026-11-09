<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraRecepcionDirectaDetalle extends Model
{
    protected $table = 'compra_recepcion_directa_detalles';
    protected $fillable = [
        'recepcion_id', 'orden_compra_detalle_id', 'producto_id', 'obra_requisicion_detalle_id',
        'descripcion', 'unidad', 'cantidad_ordenada', 'cantidad_recibida', 'precio_unitario_oc',
        'descuento_oc', 'importe_neto_oc', 'costo_unitario_oc', 'costo_unitario_mxn',
        'tipo_cambio', 'importe_oc', 'importe_mxn',
        'factor_inventario', 'cantidad_inventario', 'unidad_inventario',
    ];
    protected $casts = [
        'cantidad_ordenada' => 'decimal:3', 'cantidad_recibida' => 'decimal:3',
        'precio_unitario_oc' => 'decimal:8', 'descuento_oc' => 'decimal:2',
        'importe_neto_oc' => 'decimal:2', 'costo_unitario_oc' => 'decimal:8',
        'costo_unitario_mxn' => 'decimal:8', 'tipo_cambio' => 'decimal:8',
        'importe_oc' => 'decimal:2', 'importe_mxn' => 'decimal:2',
        'factor_inventario' => 'decimal:8', 'cantidad_inventario' => 'decimal:3',
    ];

    public function recepcion(): BelongsTo { return $this->belongsTo(CompraRecepcionDirecta::class, 'recepcion_id'); }
    public function ordenCompraDetalle(): BelongsTo { return $this->belongsTo(OrdenCompraDetalle::class); }
    public function producto(): BelongsTo { return $this->belongsTo(Producto::class); }
    public function requisicionDetalle(): BelongsTo { return $this->belongsTo(ObraRequisicionDetalle::class, 'obra_requisicion_detalle_id'); }
}
