<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComisionPersonal extends Model
{
    use HasFactory;

    protected $table = 'comision_personal';

    protected $fillable = [
        'comision_id',
        'obra_empleado_id',
        'obra_maquina_id',
        'rol_id',
        'rol',
        'trabaja',
        'hora_inicio',
        'hora_fin',
        'comida_min',
        'horas_laboradas',
        'tiempo_extra',
    ];

    protected $casts = [
        'trabaja'        => 'boolean',
        'comida_min'     => 'integer',
        'horas_laboradas'=> 'decimal:2',
        'tiempo_extra'   => 'decimal:2',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public function comision()
    {
        return $this->belongsTo(Comision::class, 'comision_id');
    }

    public function asignacionEmpleado()
    {
        return $this->belongsTo(ObraEmpleado::class, 'obra_empleado_id');
    }

    public function asignacionMaquina()
    {
        return $this->belongsTo(ObraMaquina::class, 'obra_maquina_id');
    }
    public function rol()
    {
        return $this->belongsTo(CatalogoRol::class, 'rol_id');
    }
    public function obraEmpleado() {
    return $this->belongsTo(ObraEmpleado::class, 'obra_empleado_id');
    }


    // Helpers
    public function getEmpleadoAttribute()
    {
        return optional($this->asignacionEmpleado)->empleado;
    }

    public function getMaquinaAttribute()
    {
        return optional($this->asignacionMaquina)->maquina;
    }
    public function actividades()
    {
        return $this->belongsToMany(
            CatalogoActividadComision::class,
            'comision_personal_actividades',
            'comision_personal_id',
            'actividad_id'
        )->withTimestamps();
    }

    
}
