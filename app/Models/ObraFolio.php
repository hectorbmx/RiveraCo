<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraFolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_obra',
        'prefijo',
        'anio',
        'ultimo_consecutivo',
    ];

    protected $casts = [
        'anio' => 'integer',
        'ultimo_consecutivo' => 'integer',
    ];
}
