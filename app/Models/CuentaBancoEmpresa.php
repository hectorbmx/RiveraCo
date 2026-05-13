<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentaBancoEmpresa extends Model
{
    protected $table = 'cuentas_banco_empresa';

    protected $fillable = [
        'nombre',
        'banco',
        'titular',
        'numero_cuenta',
        'clabe',
        'moneda',
        'activa',
        'principal',
        'observaciones',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'principal' => 'boolean',
    ];
}