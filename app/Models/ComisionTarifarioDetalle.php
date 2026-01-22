<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionTarifarioDetalle extends Model
{
    protected $table = 'comision_tarifario_detalles';

    protected $fillable = [
        'tarifario_id',
        'trabajo_id',
        'rol_id',
        'concepto',
        'variable_origen',
        'actividad_id',
        'uom_id',
        'precio_unitario',
        'estado',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'vigente_desde'   => 'datetime',
        'vigente_hasta'   => 'datetime',
    ];

    public function tarifario()
    {
        return $this->belongsTo(ComisionTarifario::class, 'tarifario_id');
    }
}
