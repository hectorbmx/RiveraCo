<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatFacturaConcepto extends Model
{
    protected $table = 'sat_factura_conceptos';

    protected $fillable = [

        'sat_factura_id',

        'descripcion',
        'cantidad',
        'unidad',

        'clave_producto_servicio',
        'clave_unidad',

        'precio_unitario',
        'descuento',

        'subtotal',
        'iva',
        'retenciones',
        'total',

        'taxes',
        'facturapi_payload',
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'precio_unitario' => 'decimal:6',

        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'iva' => 'decimal:2',
        'retenciones' => 'decimal:2',
        'total' => 'decimal:2',

        'taxes' => 'array',
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
}