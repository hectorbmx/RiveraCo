<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoEmpleadoKmLog extends Model
{
    protected $table = 'vehiculo_empleado_km_logs';

    protected $fillable = [
        'vehiculo_empleado_id',
        'fecha',
        'km',
        'foto',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function asignacion()
    {
        return $this->belongsTo(VehiculoEmpleado::class, 'vehiculo_empleado_id');
    }
}
