<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenCompraDetalle extends Model
{
    protected $table = 'orden_compra_detalles';

    // protected $fillable = [
    //     'orden_compra_id',
    //     'producto_id',
    //     'civil_concept_id',
    //     'civil_concept_snapshot',
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
    'civil_concept_id',
    'civil_concept_snapshot',
    'obra_civil_insumo_id',
    'obra_civil_insumo_snapshot',
    'obra_civil_material_request_item_id',
    'legacy_prod_id',
    'descripcion',
    'unidad',
    'cantidad',
    'precio_unitario',
    'precio_tope',
    'sobreprecio_autorizado_por',
    'sobreprecio_autorizado_at',
    'sobreprecio_autorizacion_motivo',
    'descuento_porcentaje',
    'descuento_importe',
    'importe',
    'iva',
    'iva_importe_manual',
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
        'precio_tope' => 'decimal:4',
        'sobreprecio_autorizado_por' => 'integer',
        'sobreprecio_autorizado_at' => 'datetime',
        'descuento_porcentaje' => 'decimal:2',
        'descuento_importe' => 'decimal:2',
        'importe' => 'decimal:2',
        'iva' => 'decimal:2',
        'iva_importe_manual' => 'decimal:2',
        'retenciones' => 'decimal:2',
        'otros_impuestos' => 'decimal:2',
        'tipo_cambio' => 'decimal:4',
        'civil_concept_snapshot' => 'array',
        'obra_civil_insumo_id' => 'integer',
        'obra_civil_insumo_snapshot' => 'array',
        'obra_civil_material_request_item_id' => 'integer',
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
    public function sobreprecioAutorizadoPor()
    {
        return $this->belongsTo(User::class, 'sobreprecio_autorizado_por');
    }
    public function civilConcept()
    {
        return $this->belongsTo(CivilConcept::class, 'civil_concept_id');
    }

    public function obraCivilInsumo()
    {
        return $this->belongsTo(ObraCivilInsumo::class, 'obra_civil_insumo_id');
    }
    public function obraCivilMaterialRequestItem()
    {
        return $this->belongsTo(ObraCivilMaterialRequestItem::class, 'obra_civil_material_request_item_id');
    }
}
