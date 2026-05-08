<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SatEmpresa extends Model
{
    protected $table = 'sat_empresas';

    protected $fillable = [
    'nombre',
    'rfc',
    'facturapi_organization_id',
    'cer_path',
    'key_path',
    'fiel_password',
    'sat_password',
    'activo',
    'csd_cer_path',   // nuevo
    'csd_key_path',   // nuevo
    'csd_password',   // nuevo
    ];

    protected $casts = [
        'activo'       => 'boolean',
        'fiel_password' => 'encrypted',
        'sat_password'  => 'encrypted',
        'csd_password'  => 'encrypted',  // nuevo — mismo tratamiento que los otros
    ];

    public function documentRequests(): HasMany
    {
        return $this->hasMany(SatDocumentRequest::class, 'sat_empresa_id');
    }
}