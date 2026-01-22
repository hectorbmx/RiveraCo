<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $table = 'mantenimientos';

    protected $fillable = [
        'vehiculo_id',
        'obra_id',
        'tipo',
        'categoria_mantenimiento',
        'descripcion',
        'km_actuales',
        'km_proximo_servicio',
        'fecha_programada',
        'fecha_inicio',
        'fecha_fin',
        'estatus',
        'mecanico_id',
        'costo_mano_obra',
        'costo_refacciones',
        'costo_total',
        'notas',
    ];

    protected $casts = [
        'fecha_programada' => 'datetime',
        'fecha_inicio'     => 'datetime',
        'fecha_fin'        => 'datetime',
    ];

    /* ----------------- RELACIONES ----------------- */

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    // Mecánico (empleado que atendió el servicio)
    public function mecanico()
    {
        return $this->belongsTo(Empleado::class, 'mecanico_id', 'id_Empleado');
    }

    public function detalles()
    {
        return $this->hasMany(MantenimientoDetalle::class, 'mantenimiento_id');
    }

    public function fotos()
    {
        return $this->hasMany(MantenimientoFoto::class, 'mantenimiento_id');
    }

    /* ----------------- HELPERS ----------------- */

    public function getDuracionEnMinutosAttribute()
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return null;
        }

        return $this->fecha_inicio->diffInMinutes($this->fecha_fin);
    }
}
