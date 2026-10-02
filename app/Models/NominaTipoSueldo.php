<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NominaTipoSueldo extends Model
{
    protected $table = 'nomina_tipos_sueldo';

    protected $fillable = [
        'codigo',
        'nombre',
        'dias_periodo',
        'factor_mensual',
        'activo',
        'orden',
    ];

    protected $casts = [
        'dias_periodo' => 'integer',
        'factor_mensual' => 'decimal:4',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query
            ->orderBy('orden')
            ->orderBy('nombre');
    }
}
