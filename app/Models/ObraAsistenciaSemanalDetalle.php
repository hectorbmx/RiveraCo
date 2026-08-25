<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraAsistenciaSemanalDetalle extends Model
{
    protected $table = 'obra_asistencia_semanal_detalles';

    protected $fillable = [
        'reporte_id',
        'empleado_id',
        'fecha',
        'planeado_asistir',
        'estado_admin',
        'estado_campo',
        'obra_asistencia_entrada_id',
        'obra_asistencia_salida_id',
        'excepcion_tipo',
        'excepcion_motivo',
        'autorizado_por_user_id',
        'ajuste_nomina_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'planeado_asistir' => 'boolean',
    ];

    public function reporte()
    {
        return $this->belongsTo(ObraAsistenciaSemanalReporte::class, 'reporte_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function entrada()
    {
        return $this->belongsTo(ObraAsistencia::class, 'obra_asistencia_entrada_id');
    }

    public function salida()
    {
        return $this->belongsTo(ObraAsistencia::class, 'obra_asistencia_salida_id');
    }
}
