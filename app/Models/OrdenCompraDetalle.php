<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenCompraDetalle extends Model
{
    protected $table = 'orden_compra_detalles';

    // protected $fillable = [
    //     'orden_compra_id',
    //     'producto_id',
    //     'legacy_prod_id',
    //     'descripcion',
    //     'unidad',
    //     'cantidad',
    //     'precio_unitario',
    //     'importe',
    //     'iva',
    //     'retenciones',
    //     'otros_impuestos',
    //     'tipo_cambio',
    //     'notas',
    // ];
    protected $fillable = [
    'orden_compra_id',
    'producto_id',
    'legacy_prod_id',
    'descripcion',
    'unidad',
    'cantidad',
    'precio_unitario',
    'importe',
    'iva',
    'tipo_retencion_id',
    'retencion_porcentaje',
    'retenciones',
    'otros_impuestos',
    'tipo_cambio',
    'notas',
];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'precio_unitario' => 'decimal:4',
        'importe' => 'decimal:2',
        'iva' => 'decimal:2',
        'retenciones' => 'decimal:2',
        'otros_impuestos' => 'decimal:2',
        'tipo_cambio' => 'decimal:4',
        'tipo_retencion_id' => 'integer',
        'retencion_porcentaje' => 'decimal:4',
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    public function tipoRetencion()
    {
        return $this->belongsTo(TipoRetencion::class, 'tipo_retencion_id');
    }
}
