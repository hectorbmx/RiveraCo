<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatCfdiPago extends Model
{
    use HasFactory;

    protected $table = 'sat_cfdi_pagos';

    protected $fillable = [
        'sat_cfdi_id',
        'cfdi_uuid',
        'fecha_pago',
        'monto',
        'moneda',
        'tipo_cambio',
        'metodo_pago',
        'referencia',
        'folio_transferencia',
        'numero_cheque',
        'banco_origen',
        'banco_destino',
        'cuenta_origen',
        'cuenta_destino',
        'observaciones',
        'comprobante_path',
        'estatus',
        'created_by',
        'updated_by',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'fecha_pago'   => 'date',
        'monto'        => 'decimal:2',
        'tipo_cambio'  => 'decimal:6',
        'cancelled_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function cfdi()
    {
        return $this->belongsTo(SatCfdi::class, 'sat_cfdi_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActivos($query)
    {
        return $query->where('estatus', 'activo');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function esActivo()
    {
        return $this->estatus === 'activo';
    }
}