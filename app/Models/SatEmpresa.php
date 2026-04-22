<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatEmpresa extends Model
{
    protected $table = 'sat_empresas';

    protected $fillable = [
        'nombre',
        'rfc',
        'cer_path',
        'key_path',
        'fiel_password',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fiel_password' => 'encrypted',
    ];
}