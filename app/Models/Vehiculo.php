<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';
    protected $guarded = [];
    protected $fillable = [
        'marca',
        'modelo',
        'anio',
        'color',
        'placas',
        'serie',
        'tipo',
        'foto_principal',
        'estatus',
        'fecha_registro',
    ];

    /* ----------------- RELACIONES ----------------- */

    // Historial de asignaciones (empleados que han tenido el vehículo)
    public function asignaciones()
    {
        return $this->hasMany(VehiculoEmpleado::class, 'vehiculo_id');
    }

    // Asignación actual (empleado que lo tiene hoy)
    public function asignacionActual()
    {
        return $this->hasOne(VehiculoEmpleado::class, 'vehiculo_id')
                    ->whereNull('fecha_fin')
                    ->latest('fecha_asignacion');
    }

    // Mantenimientos del vehículo
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'vehiculo_id');
    }

    // Seguros del vehículo
    public function seguros()
    {
        return $this->hasMany(SeguroVehiculo::class, 'vehiculo_id');
    }

    // Documentos (tarjeta circulación, verificación, etc.)
    public function documentos()
    {
        return $this->hasMany(DocumentoVehiculo::class, 'vehiculo_id');
    }
    public function obras()
    {
        return $this->hasMany(VehiculoObra::class, 'vehiculo_id');
    }

   

}
