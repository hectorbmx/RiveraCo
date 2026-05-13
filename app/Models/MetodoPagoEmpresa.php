<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPagoEmpresa extends Model
{
    protected $table = 'metodos_pago_empresa';

    protected $fillable = [
        'nombre',
        'clave',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}