<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MantenimientoDetalle extends Model
{
    protected $table = 'mantenimiento_detalles';

    protected $fillable = [
        'mantenimiento_id',
        'concepto',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'tipo',
    ];

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'mantenimiento_id');
    }
}
