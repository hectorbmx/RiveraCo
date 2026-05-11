<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SatCfdiProgramacion extends Model
{
    use SoftDeletes;

    protected $table = 'sat_cfdi_programaciones';

    protected $fillable = [
        'sat_cfdi_id',
        'cfdi_uuid',
        'origen',
        'area',
        'proveedor_nombre',
        'proveedor_rfc',
        'concepto',
        'fecha_gasto',
        'fecha_programada',
        'monto_programado',
        'moneda',
        'tipo_cambio',
        'requiere_factura',
        'estatus_factura',
        'tipo_pago',
        'estatus',
        'solicitado_by',
        'solicitado_at',
        'revisado_by',
        'revisado_at',
        'comentario_revision',
        'aprobado_by',
        'aprobado_at',
        'comentario_aprobacion',
        'sat_cfdi_pago_id',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_gasto' => 'date',
        'fecha_programada' => 'date',
        'monto_programado' => 'decimal:2',
        'tipo_cambio' => 'decimal:6',
        'requiere_factura' => 'boolean',
        'solicitado_at' => 'datetime',
        'revisado_at' => 'datetime',
        'aprobado_at' => 'datetime',
    ];

    public function cfdi()
    {
        return $this->belongsTo(SatCfdi::class, 'sat_cfdi_id');
    }

    public function pago()
    {
        return $this->belongsTo(SatCfdiPago::class, 'sat_cfdi_pago_id');
    }

    public function solicitadoPor()
    {
        return $this->belongsTo(User::class, 'solicitado_by');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_by');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_by');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}