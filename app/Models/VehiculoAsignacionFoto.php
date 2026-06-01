<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoAsignacionFoto extends Model
{
    protected $table = 'vehiculo_asignacion_fotos';

    protected $fillable = [
        'vehiculo_empleado_id',
        'url',
        'orden',
    ];

    public function asignacion()
    {
        return $this->belongsTo(VehiculoEmpleado::class, 'vehiculo_empleado_id');
    }
}