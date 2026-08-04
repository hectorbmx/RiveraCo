<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraReposicionGastoDetalle extends Model
{
    use HasFactory;

    protected $table = 'obra_reposicion_gasto_detalles';

    protected $fillable = [
        'obra_reposicion_gasto_id',
        'sat_cfdi_id',
        'empresa_viatico_tarifa_id',
        'obra_empleado_id',
        'tipo',
        'descripcion',
        'proveedor',
        'rfc',
        'uuid',
        'fecha',
        'fecha_inicio',
        'fecha_fin',
        'monto',
        'comprobante_tipo',
        'numero_nota',
        'dias',
        'importe_unitario',
        'evidencia_path',
        'partida_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'monto' => 'decimal:2',
        'dias' => 'integer',
        'importe_unitario' => 'decimal:2',
    ];

    public function reposicion()
    {
        return $this->belongsTo(
            ObraReposicionGasto::class,
            'obra_reposicion_gasto_id'
        );
    }

    public function cfdi()
    {
        return $this->belongsTo(
            SatCfdi::class,
            'sat_cfdi_id'
        );
    }

    public function viaticoTarifa()
    {
        return $this->belongsTo(EmpresaViaticoTarifa::class, 'empresa_viatico_tarifa_id');
    }

    public function obraEmpleado()
    {
        return $this->belongsTo(ObraEmpleado::class, 'obra_empleado_id');
    }

    public function partida()
    {
        return $this->belongsTo(ObraPlaneacionGasto::class, 'partida_id');
    }
}