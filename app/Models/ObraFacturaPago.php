<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraFacturaPago extends Model
{
    protected $table = 'obra_factura_pagos';

    protected $fillable = [
        'obra_id',
        'factura_uuid',
        'factura_source',
        'monto',
        'fecha_pago',
        'cuenta_banco_empresa_id',
        'metodo_pago_empresa_id',
        'referencia',
        'observaciones',
        'comprobante_path',
        'comprobante_nombre_original',
        'comprobante_mime',
        'requiere_complemento_pago',
        'sat_factura_pago_id',
        'registrado_por',
        'registrado_at',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'date',
        'requiere_complemento_pago' => 'boolean',
        'registrado_at' => 'datetime',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function cuentaBanco()
    {
        return $this->belongsTo(CuentaBancoEmpresa::class, 'cuenta_banco_empresa_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPagoEmpresa::class, 'metodo_pago_empresa_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
