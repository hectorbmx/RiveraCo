<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguroVehiculo extends Model
{
    protected $table = 'seguros_vehiculos';

    protected $fillable = [
        'vehiculo_id',
        'aseguradora',
        'numero_poliza',
        'tipo_cobertura',
        'fecha_inicio',
        'fecha_fin',
        'costo_anual',
        'archivo_poliza',
        'estatus',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}
