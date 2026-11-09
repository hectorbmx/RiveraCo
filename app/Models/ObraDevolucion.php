<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraDevolucion extends Model
{
    protected $table = 'obra_devoluciones';
    protected $fillable = ['folio', 'obra_id', 'estado', 'observaciones', 'creado_por', 'presentado_por', 'presentado_at', 'cancelado_por', 'cancelado_at', 'motivo_cancelacion', 'operacion_uuid', 'solicitud_hash'];
    protected $casts = ['presentado_at' => 'datetime', 'cancelado_at' => 'datetime'];
    public function obra() { return $this->belongsTo(Obra::class); }
    public function creadoPor() { return $this->belongsTo(User::class, 'creado_por'); }
    public function presentadoPor() { return $this->belongsTo(User::class, 'presentado_por'); }
    public function detalles() { return $this->hasMany(ObraDevolucionDetalle::class, 'devolucion_id'); }
    public function recepciones() { return $this->hasMany(ObraDevolucionRecepcion::class, 'devolucion_id'); }
}
