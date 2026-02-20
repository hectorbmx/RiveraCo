<?php

namespace App\Models;
use App\Models\Obra;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';
    protected $primaryKey = 'id_Empleado';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'id_Empleado',
        'Nombre',
        'Apellidos',
        'Email',
        'Fecha_nacimiento',
        'Fecha_ingreso',
        'Fecha_baja',
        'Area',
        'Puesto',
        'Telefono',
        'Celular',
        'Direccion',
        'Colonia',
        'Ciudad',
        'CP',
        'RFC',
        'CURP',
        'IMSS',
        'Sangre',
        'Cuenta_banco',
        'Sueldo',
        'Sueldo_real',
        'Complemento',
        'Sueldo_tipo',
        'listaraya',
        'Horassemana',
        'infonavit',
        'Estatus',
        'Honorarios',
        'Notas',
        'foto',
    ];

    protected $casts = [
        'Fecha_nacimiento' => 'date',
        'Fecha_ingreso'    => 'date',
        'Fecha_baja'       => 'date',
        'Sueldo'           => 'decimal:2',
        'Sueldo_real'      => 'decimal:2',
        'Complemento'      => 'decimal:2',
        'infonavit'        => 'decimal:2',
        'Estatus' => 'integer',

    ];

    public function getRouteKeyName()
    {
        return 'id_Empleado';
    }


    public function asignaciones()
    {
        return $this->hasMany(ObraEmpleado::class, 'empleado_id', 'id_Empleado');
    }
    public function area()
    {
        return $this->belongsTo(
            \App\Models\Area::class,
            'Area', // FK en empleados
            'id'    // PK en areas
        );
    }
    

    public function asignacionActiva()
    {
        return $this->hasOne(ObraEmpleado::class, 'empleado_id', 'id_Empleado')
            ->whereNull('fecha_baja')
            ->where('activo', true);
    }
    public function notas()
    {
        return $this->hasMany(\App\Models\EmpleadoNota::class, 'empleado_id', 'id_Empleado')
            ->orderByDesc('fecha_evento')
            ->orderByDesc('created_at');
    }
    public function contactosEmergencia()
    {
        return $this->hasMany(\App\Models\EmpleadoContactoEmergencia::class, 'empleado_id', 'id_Empleado')
            ->orderByDesc('es_principal')
            ->orderBy('nombre');
    }
    public function nominaRecibos()
    {
        return $this->hasMany(\App\Models\NominaRecibo::class, 'empleado_id', 'id_Empleado')
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id');
    }
    public function pagosExtra()
    {
        return $this->hasMany(\App\Models\NominaPagoExtra::class, 'empleado_id', 'id_Empleado')
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id');
    }
    public function obras()
    {
        return $this->belongsToMany(Obra::class, 'obra_empleado', 'empleado_id', 'obra_id')
            ->withPivot(['fecha_alta', 'fecha_baja', 'activo', 'puesto_en_obra', 'notas'])
            ->withTimestamps();
    }

    // solo las asignaciones activas
    // public function obraActiva()
    // {
    //     return $this->belongsToMany(Obra::class, 'obra_empleado', 'empleado_id', 'obra_id')
    //         ->wherePivot('activo', 1);
    // }
    public function obraActiva()
{
    return $this->belongsToMany(Obra::class, 'obra_empleado', 'empleado_id', 'obra_id')
        ->withPivot(['puesto_en_obra', 'activo']) // Añade los campos que necesites de la tabla intermedia
        ->wherePivot('activo', 1);
}
        // Vehículos que ha tenido asignados (histórico)
    public function vehiculosAsignados()
    {
        return $this->hasMany(VehiculoEmpleado::class, 'empleado_id', 'id_Empleado');
    }

    // Mantenimientos donde fue mecánico
    public function mantenimientosComoMecanico()
    {
        return $this->hasMany(Mantenimiento::class, 'mecanico_id', 'id_Empleado');
    }
    public function getNombreCompletoAttribute()
    {
        return trim(
            ($this->Nombre ?? '') . ' ' . ($this->Apellidos ?? '')
        );
    }

    public function vehiculosEnObra()
    {
        return $this->hasMany(VehiculoObra::class, 'empleado_id', 'id_Empleado');
    }


}
