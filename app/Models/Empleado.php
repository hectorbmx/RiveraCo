<?php

namespace App\Models;

use App\Models\Obra;
use App\Models\Area;
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
        'puesto_base',
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
        'lista_raya_principal_id',
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


    public function listaRayaPrincipal()
    {
        return $this->belongsTo(\App\Models\NominaListaRaya::class, 'lista_raya_principal_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(ObraEmpleado::class, 'empleado_id', 'id_Empleado');
    }
//    public function area()
// {
//     return $this->belongsTo(
//         \App\Models\Area::class,
//         'Area',
//         'id'
//     );
// }
public function areaRef()
{
    return $this->belongsTo(\App\Models\Area::class, 'Area', 'id');
}

// ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ helper seguro si en algÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âºn lado te estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡n accediendo raro:
public function getAreaIdAttribute()
{
    return $this->getAttribute('Area');
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
            ->withPivot(['fecha_alta', 'fecha_baja', 'puesto_en_obra', 'activo'])
            ->wherePivot('activo', 1)
            ->wherePivotNull('fecha_baja')
            ->where('obras.estatus_nuevo', '!=', Obra::ESTATUS_CANCELADA)
            ->orderByDesc('obra_empleado.fecha_alta')
            ->orderByDesc('obra_empleado.id');
    }
        // VehÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­culos que ha tenido asignados (histÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³rico)
    public function vehiculosAsignados()
    {
        return $this->hasMany(VehiculoEmpleado::class, 'empleado_id', 'id_Empleado');
    }

    // Mantenimientos donde fue mecÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡nico
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

        public function documentos()
        {
            return $this->hasMany(\App\Models\EmpleadoDocumento::class, 'empleado_id', 'id_Empleado')
                ->orderByDesc('vigente')
                ->orderByDesc('created_at');
        }
    public function documentosUltimos()
        {
            return $this->hasMany(EmpleadoDocumento::class, 'empleado_id', 'id_Empleado')
                ->latestOfMany();
        }
}

