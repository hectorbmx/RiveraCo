<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
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
        return $query->where('estado', 'operativa');
    }
    public function asignacionActiva()
    {
        return $this->hasOne(ObraMaquina::class, 'maquina_id')
            ->whereNull('fecha_fin')
            ->latestOfMany('fecha_inicio'); // si hay varias activas por error, toma la más reciente
    }

}
