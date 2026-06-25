<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    public static function estatusLabels(): array
    {
        return [
            self::ESTATUS_PLANEACION => 'Planeación',
            self::ESTATUS_EJECUCION => 'En ejecución',
            self::ESTATUS_SUSPENDIDA => 'Suspendida',
            self::ESTATUS_TERMINADA => 'Terminada',
            self::ESTATUS_CANCELADA => 'Cancelada',
        ];
    }

    public static function estatusSlugs(): array
    {
        return [
            'planeacion' => self::ESTATUS_PLANEACION,
            'ejecucion' => self::ESTATUS_EJECUCION,
            'suspendida' => self::ESTATUS_SUSPENDIDA,
            'terminada' => self::ESTATUS_TERMINADA,
            'cancelada' => self::ESTATUS_CANCELADA,
        ];
    }

    public static function estatusBadgeClasses(): array
    {
        return [
            self::ESTATUS_PLANEACION => 'bg-slate-100 text-slate-700',
            self::ESTATUS_EJECUCION => 'bg-blue-100 text-blue-700',
            self::ESTATUS_SUSPENDIDA => 'bg-yellow-100 text-yellow-700',
            self::ESTATUS_TERMINADA => 'bg-green-100 text-green-700',
            self::ESTATUS_CANCELADA => 'bg-red-100 text-red-700',
        ];
    }

    public static function estatusFilterClasses(): array
    {
        return [
            self::ESTATUS_PLANEACION => 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100',
            self::ESTATUS_EJECUCION => 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100',
            self::ESTATUS_SUSPENDIDA => 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100',
            self::ESTATUS_TERMINADA => 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100',
            self::ESTATUS_CANCELADA => 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100',
        ];
    }

    public static function estatusFilterActiveClasses(): array
    {
        return [
            self::ESTATUS_PLANEACION => 'bg-slate-600 text-white border-slate-600',
            self::ESTATUS_EJECUCION => 'bg-blue-600 text-white border-blue-600',
            self::ESTATUS_SUSPENDIDA => 'bg-yellow-600 text-white border-yellow-600',
            self::ESTATUS_TERMINADA => 'bg-green-600 text-white border-green-600',
            self::ESTATUS_CANCELADA => 'bg-red-600 text-white border-red-600',
        ];
    }
    

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cliente_id',
        'nombre',
        'clave_obra',
        'descripcion',
        'tipo_obra',
        'area_id',
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

    // Relación con Empleado (responsable)
    public function facturaBorradores()
    {
        return $this->hasMany(ObraFacturaBorrador::class);
    }

    public function responsable()
    {
        return $this->belongsTo(Empleado::class, 'responsable_id', 'id_Empleado');
    }
    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
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
        return self::estatusLabels()[(int) ($this->estatus_nuevo ?? self::ESTATUS_PLANEACION)] ?? 'Desconocido';
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

   public function presupuestos_vinculados()
    {
        // Apuntamos a la cabecera 'Presupuesto' a través de la tabla pivote 'obra_presupuesto'
        return $this->belongsToMany(Presupuesto::class, 'obra_presupuesto', 'obra_id', 'presupuesto_id');
    }
// app/Models/Obra.php

    public function getSemanasTotalesAttribute()
        {
            if (!$this->fecha_inicio_programada || !$this->fecha_fin_programada) {
                return 0;
            }

            $inicio = \Carbon\Carbon::parse($this->fecha_inicio_programada);
            $fin = \Carbon\Carbon::parse($this->fecha_fin_programada);

            // diffInWeeks nos da la diferencia, +1 para incluir la semana de inicio
            return $inicio->diffInWeeks($fin) + 1;
        }

        public function planeacionGastos()
    {
        return $this->hasMany(ObraPlaneacionGasto::class);
    }
    

public function gastosPlaneados()
{
    // Forzamos a que use el modelo correcto que ya configuraste
    return $this->hasMany(ObraPlaneacionGasto::class, 'obra_id');
}
            
    
public function cfdis()
{
    return $this->hasMany(SatCfdi::class, 'obra_id');
}
}
