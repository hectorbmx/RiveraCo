<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmpresaViaticoTarifa extends Model
{
    protected $table = 'empresa_viatico_tarifas';

    protected $fillable = [
        'importe_diario',
        'vigencia_desde',
        'vigencia_hasta',
        'activo',
        'creado_por',
        'notas',
    ];

    protected $casts = [
        'importe_diario' => 'decimal:2',
        'vigencia_desde' => 'date',
        'vigencia_hasta' => 'date',
        'activo' => 'boolean',
    ];

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('activo', true)
            ->whereNull('vigencia_hasta');
    }

    public function scopeParaFecha(Builder $query, $fecha): Builder
    {
        return $query->whereDate('vigencia_desde', '<=', $fecha)
            ->where(function (Builder $query) use ($fecha) {
                $query->whereNull('vigencia_hasta')
                    ->orWhereDate('vigencia_hasta', '>=', $fecha);
            });
    }

    public static function actual(): ?self
    {
        return static::vigentes()
            ->latest('vigencia_desde')
            ->latest('id')
            ->first();
    }
}