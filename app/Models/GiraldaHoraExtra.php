<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiraldaHoraExtra extends Model
{
    protected $table = 'giralda_horas_extras';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'total_horas',
        'motivo',
        'responsable_solicita',
        'responsable_autoriza',
        'autorizado_por',
        'fecha_autorizacion',
        'estado',
        'observaciones',
        'empleado_hora_extra_tarifa_id',
        'costo_hora_extra',
        'importe',
        'sueldo_base_snapshot',
        'origen_tarifa',
        'snapshot_fuente',
        'snapshot_at',
        'snapshot_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total_horas' => 'decimal:2',
        'fecha_autorizacion' => 'datetime',
        'costo_hora_extra' => 'decimal:4',
        'importe' => 'decimal:2',
        'sueldo_base_snapshot' => 'decimal:2',
        'snapshot_at' => 'datetime',
    ];

    public function tieneSnapshot(): bool
    {
        return $this->snapshot_at !== null && $this->costo_hora_extra !== null
            && $this->importe !== null && $this->origen_tarifa !== null && $this->snapshot_fuente !== null;
    }

    public function snapshotEtiqueta(): string
    {
        if (! $this->tieneSnapshot()) {
            return 'Pendiente de completar';
        }

        return match ($this->snapshot_fuente) {
            'captura' => 'Captura',
            'reconstruido_cuota_fija' => 'Reconstruido por cuota fija',
            'estimado_sueldo_actual' => 'Estimado con sueldo actual',
            default => 'Importe guardado',
        };
    }

    public function tarifaAplicada()
    {
        return $this->belongsTo(EmpleadoHoraExtraTarifa::class, 'empleado_hora_extra_tarifa_id');
    }

    public function snapshotRegistradoPor()
    {
        return $this->belongsTo(User::class, 'snapshot_por');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}
