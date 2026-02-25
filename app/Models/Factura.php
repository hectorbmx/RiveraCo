<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
  protected $table = 'facturas';

  protected $fillable = [
    'source_system',
    'doc_sat_id',
    'uuid','serie','folio','tipo_comprobante',
    'rfc_emisor','rfc_receptor','razon_social',
    'fecha_emision','fecha_timbrado',
    'moneda','tipo_cambio','forma_pago','metodo_pago','uso_cfdi',
    'subtotal','descuento',
    'iva_0','iva_8','iva_16','ieps','iva_retenido','isr_retenido',
    'total','status','fecha_cancelacion','xml',
  ];

  protected $casts = [
    'fecha_emision' => 'datetime',
    'fecha_timbrado' => 'datetime',
    'fecha_cancelacion' => 'datetime',
    'tipo_cambio' => 'decimal:6',
    'subtotal' => 'decimal:2',
    'descuento' => 'decimal:2',
    'iva_0' => 'decimal:2',
    'iva_8' => 'decimal:2',
    'iva_16' => 'decimal:2',
    'ieps' => 'decimal:2',
    'iva_retenido' => 'decimal:2',
    'isr_retenido' => 'decimal:2',
    'total' => 'decimal:2',
  ];
}