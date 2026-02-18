<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominaRecibo extends Model
{
    protected $table = 'nomina_recibos';

    protected $fillable = [
        'corrida_id',

        'empleado_id',
        'obra_id',
        'obra_legacy',

        'periodo_label',
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago',

        // snapshots
        'sueldo_imss_snapshot',
        'complemento_snapshot',
        'infonavit_snapshot',

        // totales
        'total_percepciones',
        'total_deducciones',
        'sueldo_neto',

        'status',
        'folio',
        'referencia_externa',

        // campos operativos
        'faltas',
        'descuentos',
        'descuentos_legacy',
        'infonavit_legacy',

        'horas_extra',
        'metros_lineales',
        'metros_lin_monto',

        'comisiones_monto',
        'comisiones_lock',
        'comisiones_cargadas_at',
        'comisiones_cargadas_by',

        'factura_monto',
        'notas_legacy',

        // redundancia útil para reportes
        'tipo_pago',
        'subtipo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'fecha_pago'   => 'date',

        'sueldo_imss_snapshot' => 'decimal:2',
        'complemento_snapshot' => 'decimal:2',
        'infonavit_snapshot'   => 'decimal:2',

        'total_percepciones' => 'decimal:2',
        'total_deducciones'  => 'decimal:2',
        'sueldo_neto'        => 'decimal:2',

        'faltas'            => 'decimal:2',
        'descuentos'        => 'decimal:2',
        'descuentos_legacy' => 'decimal:2',
        'infonavit_legacy'  => 'decimal:2',

        'horas_extra'     => 'decimal:2',
        'metros_lineales' => 'decimal:2',
        'metros_lin_monto'=> 'decimal:2',

        'comisiones_monto' => 'decimal:2',
        'comisiones_lock'  => 'boolean',
        'comisiones_cargadas_at' => 'datetime',

        'factura_monto' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function corrida()
    {
        return $this->belongsTo(NominaCorrida::class, 'corrida_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function comisionesCargadasPor()
    {
        return $this->belongsTo(User::class, 'comisiones_cargadas_by');
    }
}
