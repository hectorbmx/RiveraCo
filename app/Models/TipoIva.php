<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIva extends Model
{
    protected $table = 'tipos_iva';

    protected $fillable = [
        'nombre',
        'porcentaje',
        'activo',
        'default',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'activo' => 'boolean',
        'default' => 'boolean',
    ];
}
