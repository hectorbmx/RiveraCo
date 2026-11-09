<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraDevolucionRecepcion extends Model
{
    protected $table = 'obra_devolucion_recepciones';
    protected $fillable = ['devolucion_id', 'operacion_uuid', 'folio', 'almacen_id', 'fecha_recepcion', 'estado', 'confirmado_por', 'confirmado_at', 'observaciones'];
    protected $casts = ['fecha_recepcion' => 'date', 'confirmado_at' => 'datetime'];
    public function devolucion() { return $this->belongsTo(ObraDevolucion::class, 'devolucion_id'); }
    public function almacen() { return $this->belongsTo(Almacen::class); }
    public function detalles() { return $this->hasMany(ObraDevolucionRecepcionDetalle::class, 'recepcion_id'); }
}
