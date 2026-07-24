<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmpresaDocumentoTipo extends Model
{
    use SoftDeletes;

    public const APLICA_EMPLEADO = 'empleado';
    public const APLICA_CLIENTE = 'cliente';
    public const APLICA_AMBOS = 'ambos';

    protected $table = 'empresa_documento_tipos';

    protected $fillable = [
        'empresa_config_id',
        'codigo',
        'nombre',
        'descripcion',
        'aplica_a',
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

    public function scopeAplicaACliente($query)
    {
        return $query->whereIn('aplica_a', [
            self::APLICA_CLIENTE,
            self::APLICA_AMBOS,
        ]);
    }

    public function scopeAplicaAEmpleado($query)
    {
        return $query->whereIn('aplica_a', [
            self::APLICA_EMPLEADO,
            self::APLICA_AMBOS,
        ]);
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