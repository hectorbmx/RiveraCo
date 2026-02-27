<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguroMaquina extends Model
{
    protected $table = 'seguros_maquinas';

    protected $fillable = [
        'maquina_id',
        'aseguradora',
        'numero_poliza',
        'vigencia_inicio',
        'vigencia_fin',
        'cobertura',
        'suma_asegurada',
        'deducible',
        'archivo_path',
        'notas',
    ];

    protected $casts = [
        'vigencia_inicio' => 'date',
        'vigencia_fin' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }
}