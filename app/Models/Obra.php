<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ObraMaquina;
use App\Models\ObraPila;

class Obra extends Model
{

    public static $estatusLabels = [
    1 => 'Planeación',
    2 => 'En ejecución',
    3 => 'Suspendida',
    4 => 'Terminada',
    5 => 'Cancelada',
];

    const ESTATUS_PLANEACION = 1;
    const ESTATUS_EJECUCION  = 2;
    const ESTATUS_SUSPENDIDA = 3;
    const ESTATUS_TERMINADA  = 4;
    const ESTATUS_CANCELADA  = 5;
    

    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'nombre',
        'clave_obra',
        'descripcion',
        'tipo_obra',
        'estatus_nuevo',
        'fecha_inicio_programada',
        'fecha_inicio_real',
        'fecha_fin_programada',
        'fecha_fin_real',
        'monto_contratado',
        'monto_modificado',
        'responsable_id',
        'ubicacion',
        'profundidad_total',
        'kg_acero_total',
        'bentonita_total',
        'concreto_total',

    ];
    protected $casts = [
    'fecha_inicio_programada' => 'date',
    'fecha_inicio_real'       => 'date',
    'fecha_fin_programada'    => 'date',
    'fecha_fin_real'          => 'date',
];


    // Relación con Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // Relación con User (responsable)
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
    public function contratos()
    {
        return $this->hasMany(ObraContrato::class);
    }
    public function planos()
    {
        return $this->hasMany(ObraPlano::class);
    }
    public function presupuestos()
    {
        return $this->hasMany(ObraPresupuesto::class);
    }
    public function empleadosAsignados()
    {
        return $this->hasMany(\App\Models\ObraEmpleado::class, 'obra_id', 'id');
    }
     public function empleados()
    {
        return $this->belongsToMany(Empleado::class, 'obra_empleado', 'obra_id', 'empleado_id')
            ->withPivot(['fecha_alta', 'fecha_baja', 'activo', 'puesto_en_obra', 'notas'])
            ->withTimestamps();
    }
    public function pilas()
    {
        return $this->hasMany(ObraPila::class, 'obra_id');
    }

    public function asignacionesMaquina()
    {
        return $this->hasMany(ObraMaquina::class, 'obra_id');
    }
     public function maquinasAsignadas()
    {
        return $this->hasMany(ObraMaquina::class, 'obra_id');
    }

    public function comisiones()
    {
        return $this->hasMany(Comision::class, 'obra_id');
    }
   

    public function getEstatusLabelAttribute(): string
    {
        return match ($this->estatus) {
            1 => 'Planeación',
            2 => 'En curso',
            3 => 'Terminada',
            4 => 'Cancelada',
            default => 'Desconocido',
        };
    }
    public function facturas()
    {
        return $this->hasMany(ObraFactura::class);
    }
    public function getTotalFacturadoAttribute()
    {
        // Si solo quieres sumar las pagadas:
        return $this->facturas()
            ->whereNotNull('fecha_pago')
            ->sum('monto');

        // Si quieres todas (emitidas), quita el whereNotNull
    }
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'obra_id');
    }
    public function vehiculos()
    {
        return $this->hasMany(VehiculoObra::class, 'obra_id');
    }



}
