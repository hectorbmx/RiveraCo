<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraSolicitudGastoDetalle extends Model
{
    use HasFactory;

    protected $table = 'obra_solicitud_gasto_detalles';

    protected $fillable = [
        'obra_solicitud_gasto_id',
        'planeacion_gasto_id',
        'monto_solicitado',
        'concepto_manual',
    ];

    protected $casts = [
        'monto_solicitado' => 'decimal:2',
    ];

    public function solicitud()
    {
        return $this->belongsTo(ObraSolicitudGasto::class, 'obra_solicitud_gasto_id');
    }

    public function planeacionGasto()
    {
        return $this->belongsTo(ObraPlaneacionGasto::class, 'planeacion_gasto_id');
    }
}
