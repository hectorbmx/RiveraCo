<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraDevolucionDetalle extends Model
{
    protected $table = 'obra_devolucion_detalles';
    protected $fillable = ['devolucion_id', 'producto_id', 'recepcion_directa_detalle_id', 'inventario_movimiento_id', 'huentitan_salida_detalle_id', 'descripcion', 'unidad', 'cantidad_solicitada', 'cantidad_entregada_snapshot', 'costo_unitario_mxn', 'importe_origen_mxn', 'condicion_declarada', 'observaciones'];
    protected $casts = ['cantidad_solicitada' => 'decimal:3', 'cantidad_entregada_snapshot' => 'decimal:3', 'costo_unitario_mxn' => 'decimal:8', 'importe_origen_mxn' => 'decimal:2'];
    public function devolucion() { return $this->belongsTo(ObraDevolucion::class, 'devolucion_id'); }
    public function producto() { return $this->belongsTo(Producto::class); }
    public function recepcionDirectaDetalle() { return $this->belongsTo(CompraRecepcionDirectaDetalle::class, 'recepcion_directa_detalle_id'); }
    public function movimientoOrigen() { return $this->belongsTo(InventarioMovimiento::class, 'inventario_movimiento_id'); }
    public function salidaHuentitanDetalle() { return $this->belongsTo(HuentitanSalidaDetalle::class, 'huentitan_salida_detalle_id'); }
    public function recepcionesDetalle() { return $this->hasMany(ObraDevolucionRecepcionDetalle::class, 'devolucion_detalle_id'); }
}
