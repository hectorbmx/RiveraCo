<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoComputoMovimiento extends Model
{
    protected $table = 'equipo_computo_movimientos';

    protected $fillable = [
        'equipo_computo_id',
        'tipo',
        'responsable_anterior_id',
        'responsable_nuevo_id',
        'area_anterior_id',
        'area_nueva_id',
        'ubicacion_anterior',
        'ubicacion_nueva',
        'estatus_anterior',
        'estatus_nuevo',
        'fecha_movimiento',
        'notas',
        'created_by',
    ];

    protected $casts = [
        'fecha_movimiento' => 'date',
    ];

    public function equipo()
    {
        return $this->belongsTo(EquipoComputo::class, 'equipo_computo_id');
    }

    public function responsableAnterior()
    {
        return $this->belongsTo(Empleado::class, 'responsable_anterior_id', 'id_Empleado');
    }

    public function responsableNuevo()
    {
        return $this->belongsTo(Empleado::class, 'responsable_nuevo_id', 'id_Empleado');
    }

    public function areaAnterior()
    {
        return $this->belongsTo(Area::class, 'area_anterior_id');
    }

    public function areaNueva()
    {
        return $this->belongsTo(Area::class, 'area_nueva_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fotos()
    {
        return $this->hasMany(EquipoComputoFoto::class, 'equipo_computo_movimiento_id');
    }
}
