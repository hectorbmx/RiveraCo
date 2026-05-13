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
        'tipo',
        'descripcion',
        'proveedor',
        'rfc',
        'uuid',
        'fecha',
        'monto',
        'evidencia_path',
        'partida_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
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
    public function partida()
    {
        return $this->belongsTo(ObraPlaneacionGasto::class, 'partida_id');
    }
}