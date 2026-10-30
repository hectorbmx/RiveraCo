<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatFacturaPagoDocumento extends Model
{
    protected $table = 'sat_factura_pago_documentos';
    protected $guarded = ['id'];
    protected $casts = ['importe_aplicado' => 'decimal:2', 'saldo_anterior' => 'decimal:2',
        'saldo_insoluto' => 'decimal:2', 'facturapi_resumen' => 'array'];

    public function pago() { return $this->belongsTo(SatFacturaPago::class, 'sat_factura_pago_id'); }
    public function factura() { return $this->belongsTo(SatFactura::class, 'sat_factura_id'); }
}
