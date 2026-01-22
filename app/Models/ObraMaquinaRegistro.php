<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraMaquinaRegistro extends Model
{
    use HasFactory;

    protected $table = 'obra_maquina_registros';

    protected $fillable = [
        'obra_maquina_id',
        'obra_id',
        'maquina_id',
        'inicio',
        'fin',
        'horometro_inicio',
        'horometro_fin',
        'horas',
        'notas',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'inicio'            => 'datetime',
        'fin'               => 'datetime',
        'horometro_inicio'  => 'decimal:2',
        'horometro_fin'     => 'decimal:2',
        'horas'             => 'decimal:2',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    /* ===================== RELACIONES ===================== */

    public function asignacion()
    {
        return $this->belongsTo(ObraMaquina::class, 'obra_maquina_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }
}
