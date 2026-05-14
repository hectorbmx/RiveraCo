<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmpresaDocumentoTipo extends Model
{
    use SoftDeletes;

    protected $table = 'empresa_documento_tipos';

    protected $fillable = [
        'empresa_config_id',
        'codigo',
        'nombre',
        'descripcion',
        'obligatorio',
        'requiere_vencimiento',
        'activo',
        'orden',
    ];

    protected $casts = [
        'obligatorio'          => 'boolean',
        'requiere_vencimiento' => 'boolean',
        'activo'               => 'boolean',
        'orden'                => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function empresa()
    {
        return $this->belongsTo(EmpresaConfig::class, 'empresa_config_id');
    }

    public function documentos()
    {
        return $this->hasMany(EmpleadoDocumento::class, 'documento_tipo_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden')
            ->orderBy('nombre');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getBadgeColorAttribute()
    {
        if (!$this->activo) {
            return 'gray';
        }

        if ($this->obligatorio) {
            return 'red';
        }

        return 'blue';
    }
}