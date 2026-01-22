<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaquinariaReporteSnapshotItem extends Model
{
    use HasFactory;

    protected $table = 'maquinaria_reporte_snapshot_items';

    protected $fillable = [
        'snapshot_id',
        'maquina_id',

        'obra_id',
        'obra_maquina_id',
        'cliente_id',

        'obra_nombre',
        'cliente_nombre',
        'residente_nombre',

        'pilas_programadas',
        'pilas_ejecutadas',
        'avance_global_pct',

        'horometro_inicio_obra',
        'horometro_actual',
        'horas_trabajadas',

        'total_obra',
        'monto_cobrado',
        'pago_pct',

        'maquina_nombre',
        'maquina_codigo',
        'placas',
        'color',
        'horometro_base',
        'maquina_estado',

        'equipo',

        'observaciones_comisiones',
        'observaciones_snapshot',

        'estatus_flotilla',
        'motivo_estatus',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'equipo' => 'array',

        'horometro_inicio_obra' => 'decimal:2',
        'horometro_actual'      => 'decimal:2',
        'horas_trabajadas'      => 'decimal:2',

        'total_obra'    => 'decimal:2',
        'monto_cobrado' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function snapshot()
    {
        return $this->belongsTo(
            MaquinariaReporteSnapshot::class,
            'snapshot_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes útiles
    |--------------------------------------------------------------------------
    */

    public function scopePorSnapshot($query, int $snapshotId)
    {
        return $query->where('snapshot_id', $snapshotId);
    }

    public function scopePorMaquina($query, int $maquinaId)
    {
        return $query->where('maquina_id', $maquinaId);
    }
}
