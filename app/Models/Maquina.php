<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
    public const ESTADO_OPERATIVA = 'operativa';
    public const ESTADO_FUERA_SERVICIO = 'fuera_servicio';
    public const ESTADO_BAJA_DEFINITIVA = 'baja_definitiva';

    public const UBIC_EN_OBRA = 'en_obra';
    public const UBIC_EN_CAMINO = 'en_camino';
    public const UBIC_EN_REPARACION = 'en_reparacion';
    public const UBIC_EN_PATIO = 'en_patio';
    use HasFactory;

    protected $table = 'maquinas';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'marca',
        'modelo',
        'numero_serie',
        'placas',
        'color',
        'horometro_base',
        'estado',
        'ubicacion',
        'notas',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',  
    ];

    // Asignaciones a obras
    public function asignaciones()
    {
        return $this->hasMany(ObraMaquina::class, 'maquina_id');
    }

    // Máquinas actualmente asignadas a alguna obra
    public function scopeOperativas($query)
    {
        return $query->where('estado', self::ESTADO_OPERATIVA);
    }

    public function scopeFueraDeServicio($query)
    {
        return $query->where('estado', 'fuera_de_servicio');
    }
    public function asignacionActiva()
    {
        return $this->hasOne(ObraMaquina::class, 'maquina_id')
            ->whereNull('fecha_fin')
            ->latestOfMany('fecha_inicio'); // si hay varias activas por error, toma la más reciente
    }
// Movimientos (bitácora)
public function movimientos()
{
    return $this->hasMany(\App\Models\MaquinaMovimiento::class, 'maquina_id');
}

// Seguros
public function seguros()
{
    return $this->hasMany(\App\Models\SeguroMaquina::class, 'maquina_id');
}

public function seguroVigente()
{
    return $this->hasOne(\App\Models\SeguroMaquina::class, 'maquina_id')
        ->whereDate('vigencia_inicio', '<=', now()->toDateString())
        ->whereDate('vigencia_fin', '>=', now()->toDateString())
        ->latestOfMany('vigencia_fin');
}

// Mantenimientos (reutilizando tabla mantenimientos)
public function mantenimientos()
{
    return $this->hasMany(\App\Models\Mantenimiento::class, 'maquina_id');
}

// Scopes
// public function scopeOperativas($query)
// {
//     return $query->where('estado', 'operativa');
// }

public function scopeFueraServicio($query)
{
    return $query->where('estado', 'fuera_servicio');
}

public function scopeBajaDefinitiva($query)
{
    return $query->where('estado', 'baja_definitiva');
}

public function scopeEnObra($query)
{
    return $query->where('ubicacion', 'en_obra');
}
}
