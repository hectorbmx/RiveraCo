<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SatFactura;

class SatFacturaPago extends Model
{
    protected $table = 'sat_factura_pagos';

    protected $fillable = [

        // Relaciones
        'sat_factura_id',
        'solicitud_id',
        'solicitud_hash',
        'facturapi_payload',

        // PAC / CFDI
        'facturapi_invoice_id',
        'uuid',

        // Pago
        'fecha_pago',
        'forma_pago',
        'moneda',
        'tipo_cambio',

        // Importes
        'monto',
        'saldo_anterior',
        'saldo_insoluto',
        'numero_parcialidad',

        // Estado
        'estado',
        'fecha_cancelacion',
        'cancelado_por',
        'motivo_cancelacion',
        'sustitucion_uuid',

        // Archivos
        'xml_path',
        'pdf_path',

        // Debug
        'facturapi_response',
        'error_message',
    ];

    protected $casts = [

        'fecha_pago' => 'datetime',
        'fecha_cancelacion' => 'datetime',

        'monto' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_insoluto' => 'decimal:2',
        'tipo_cambio' => 'decimal:6',

        'facturapi_response' => 'array',
        'facturapi_payload' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function factura()
    {
        return $this->belongsTo(SatFactura::class, 'sat_factura_id');
    }

    public function documentos()
    {
        return $this->hasMany(SatFacturaPagoDocumento::class, 'sat_factura_pago_id');
    }

    public function pagosInternosObra()
    {
        return $this->hasMany(ObraFacturaPago::class, 'sat_factura_pago_id');
    }

    public function canceladoPor()
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }
}
