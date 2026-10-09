<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaquinaHorometroAjuste extends Model
{
    use HasFactory;

    protected $table = 'maquina_horometro_ajustes';

    protected $fillable = [
        'maquina_id',
        'obra_id',
        'obra_maquina_id',
        'horometro_anterior',
        'horometro_nuevo',
        'diferencia',
        'tipo',
        'notas',
        'origen',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'horometro_anterior' => 'decimal:2',
        'horometro_nuevo' => 'decimal:2',
        'diferencia' => 'decimal:2',
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}