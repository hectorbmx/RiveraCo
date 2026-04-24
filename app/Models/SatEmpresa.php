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
        'cer_path',
        'key_path',
        'fiel_password',
        'sat_password',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fiel_password' => 'encrypted',
         'sat_password' => 'encrypted',
    ];

    public function documentRequests(): HasMany
    {
        return $this->hasMany(SatDocumentRequest::class, 'sat_empresa_id');
    }
}