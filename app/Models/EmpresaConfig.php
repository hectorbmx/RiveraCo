<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaConfig extends Model
{
    protected $table = 'empresa_config';

    protected $fillable = [
        'razon_social',
        'nombre_comercial',
        'rfc',
        'telefono',
        'email',
        'domicilio_fiscal',
        'moneda_base',
        'iva_por_defecto',
        'logo_path',
        'activa',
    ];
}
