<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraDevolucionRecepcionDetalle extends Model
{
    protected $table = 'obra_devolucion_recepcion_detalles';
    protected $fillable = ['recepcion_id', 'devolucion_detalle_id', 'cantidad_recibida', 'condicion_recibida', 'observaciones', 'estado_evaluacion', 'costo_unitario_mxn', 'importe_mxn', 'inventario_documento_detalle_id', 'inventario_movimiento_id'];
    protected $casts = ['cantidad_recibida' => 'decimal:3', 'costo_unitario_mxn' => 'decimal:8', 'importe_mxn' => 'decimal:2'];
    public function recepcion() { return $this->belongsTo(ObraDevolucionRecepcion::class, 'recepcion_id'); }
    public function devolucionDetalle() { return $this->belongsTo(ObraDevolucionDetalle::class, 'devolucion_detalle_id'); }
    public function documentoDetalle() { return $this->belongsTo(InventarioDocumentoDetalle::class, 'inventario_documento_detalle_id'); }
    public function movimiento() { return $this->belongsTo(InventarioMovimiento::class, 'inventario_movimiento_id'); }
}
