<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoEmpleadoKmLog extends Model
{
    protected $table = 'vehiculo_empleado_km_logs';

    protected $fillable = [
        'vehiculo_empleado_id',
        'obra_id',
        'fecha',
        'km',
        'foto',
        'foto_ticket_gasolina',
        'monto_gasolina',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'monto_gasolina' => 'decimal:2',
    ];

    public function asignacion()
    {
        return $this->belongsTo(VehiculoEmpleado::class, 'vehiculo_empleado_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }
}