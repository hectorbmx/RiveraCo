<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaquinaMovimiento extends Model
{
    protected $table = 'maquina_movimientos';

    protected $fillable = [
        'maquina_id',
        'obra_id',
        'obra_maquina_id',
        'tipo',
        'ubicacion_anterior',
        'ubicacion_nueva',
        'estado_anterior',
        'estado_nuevo',
        'motivo',
        'notas',
        'user_id',
        'fecha_evento',
    ];

    protected $casts = [
        'fecha_evento' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function asignacion()
    {
        return $this->belongsTo(ObraMaquina::class, 'obra_maquina_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}