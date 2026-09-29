<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmpresaServicioPreventivoTipo extends Model
{
    public const AMBITO_MAQUINARIA = 'maquinaria';
    public const AMBITO_VEHICULO = 'vehiculo';

    public const UNIDAD_HORAS = 'horas';
    public const UNIDAD_KM = 'km';

    protected $table = 'empresa_servicio_preventivo_tipos';

    protected $fillable = [
        'ambito',
        'nombre',
        'codigo',
        'unidad',
        'intervalo_valor',
        'intervalo_meses',
        'alerta_valor',
        'alerta_dias',
        'activo',
        'orden',
    ];

    protected $casts = [
        'intervalo_valor' => 'integer',
        'intervalo_meses' => 'integer',
        'alerta_valor' => 'integer',
        'alerta_dias' => 'integer',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeMaquinaria(Builder $query): Builder
    {
        return $query->where('ambito', self::AMBITO_MAQUINARIA);
    }

    public function scopeVehiculos(Builder $query): Builder
    {
        return $query->where('ambito', self::AMBITO_VEHICULO);
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query
            ->orderBy('orden')
            ->orderBy('intervalo_valor')
            ->orderBy('nombre');
    }
}
