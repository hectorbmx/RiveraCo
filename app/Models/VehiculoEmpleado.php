<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoEmpleado extends Model
{
    protected $table = 'vehiculo_empleado';

    protected $fillable = [
        'vehiculo_id',
        'empleado_id',
        'fecha_asignacion',
        'km_inicial',
        'km_final',
        'fecha_fin',
        'notas',
    ];
    protected $casts = [
        'fecha_asignacion' => 'date',
        'fecha_fin' => 'date',
        ];


    /* ----------------- RELACIONES ----------------- */

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    // Ojo: empleados es legacy con PK id_Empleado
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }
}
