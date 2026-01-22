<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominaPagoExtra extends Model
{
    protected $table = 'nomina_pagos_extra';

    protected $fillable = [
        'empleado_id',
        'obra_id',
        'tipo',
        'anio',
        'concepto',
        'monto',
        'fecha_pago',
        'folio',
        'referencia_externa',
        'notas',
    ];

    protected $casts = [
        'anio'       => 'integer',
        'monto'      => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }
}
