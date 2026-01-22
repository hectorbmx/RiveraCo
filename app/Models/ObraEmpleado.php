<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraEmpleado extends Model
{
    protected $table = 'obra_empleado';

    protected $fillable = [
        'obra_id',
        'empleado_id',
        'rol_id',
        'fecha_alta',
        'fecha_baja',
        'activo',
        'puesto_en_obra',
        'sueldo_en_obra',
        'notas',
    ];

    protected $casts = [
        'fecha_alta' => 'date',
        'fecha_baja' => 'date',
        'sueldo_en_obra' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }
    public function comisionPersonales()
    {
        return $this->hasMany(ComisionPersonal::class, 'obra_empleado_id');
    }
    public function rol()
    {
        return $this->belongsTo(CatalogoRol::class, 'rol_id');
    }


    /**
     * Días trabajados en esta asignación
     * (si sigue activo, cuenta hasta hoy).
     */
    public function getDiasTrabajadosAttribute()
    {
        $fin = $this->fecha_baja ?? now()->startOfDay();
        return $this->fecha_alta ? $this->fecha_alta->diffInDays($fin) + 1 : 0;
    }
}
