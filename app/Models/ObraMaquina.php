<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraMaquina extends Model
{
    use HasFactory;

    protected $table = 'obra_maquina';

    protected $fillable = [
        'obra_id',
        'maquina_id',
        'fecha_inicio',
        'horometro_inicio',
        'fecha_fin',
        'horometro_fin',
        'estado',
        'notas',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'horometro_inicio' => 'decimal:2',
        'horometro_fin'    => 'decimal:2',

        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Relación con comisiones
    public function comisionPersonales()
    {
        return $this->hasMany(ComisionPersonal::class, 'obra_maquina_id');
    }

    public function comisionDetalles()
    {
        return $this->hasMany(ComisionDetalle::class, 'obra_maquina_id');
    }

    // Scope para asignaciones activas
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa')->whereNull('fecha_fin');
    }
    public function registrosHoras()
        {
            return $this->hasMany(ObraMaquinaRegistro::class, 'obra_maquina_id');
        }
  


}
