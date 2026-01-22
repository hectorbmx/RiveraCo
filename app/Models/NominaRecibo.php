<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominaRecibo extends Model
{
    protected $table = 'nomina_recibos';

    protected $fillable = [
        'empleado_id',
        'obra_id',
        'obra_legacy',
        'tipo_pago',       // 👈 nuevo
        'subtipo',         // 👈 nuevo
        'periodo_label',
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago',
        'total_percepciones',
        'total_deducciones',
        'sueldo_neto',
        'status',
        'folio',
        'referencia_externa',
        'faltas',
        'descuentos_legacy',
        'infonavit_legacy',
        'horas_extra',
        'metros_lin_monto',
        'comisiones_monto',
        'factura_monto',
        'notas_legacy',
    ];

    protected $casts = [
        'fecha_inicio'        => 'date',
        'fecha_fin'           => 'date',
        'fecha_pago'          => 'date',
        'total_percepciones'  => 'decimal:2',
        'total_deducciones'   => 'decimal:2',
        'sueldo_neto'         => 'decimal:2',
        'faltas'              => 'decimal:2',
        'descuentos_legacy'   => 'decimal:2',
        'infonavit_legacy'    => 'decimal:2',
        'horas_extra'         => 'decimal:2',
        'metros_lin_monto'    => 'decimal:2',
        'comisiones_monto'    => 'decimal:2',
        'factura_monto'       => 'decimal:2',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }
}
