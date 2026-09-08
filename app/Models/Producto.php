<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';
    public $timestamps = true;

    // Columnas reales legacy (para guardar sin errores)
   protected $fillable = [
    'legacy_prod_id',
    'nombre',
    'descripcion',
    'sku',
    'unidad',

    // inventario (si existen en tabla)
    'tipo_inventario',
    'requiere_formula',
    'origen_abastecimiento',
    'stock_minimo',
    'punto_reorden',
    'especificaciones_tecnicas',
    'iva_default',
    'activo',

    // uso/segmento (si existen en tabla)
    'uso_type',
    'uso_id',
    'uso_label',
    'segmento_legacy',
];

    protected $casts = [
        'activo' => 'boolean',
        'requiere_formula' => 'boolean',
        'iva_default' => 'decimal:2',
        'especificaciones_tecnicas' => 'array',
    ];

    // “Aliases” para adaptarse al diseño nuevo
    protected $appends = [
        'codigo',
        'unidad_medida',
    ];

    // codigo <-> sku
    public function getCodigoAttribute(): ?string
    {
        return $this->attributes['sku'] ?? null;
    }

    public function setCodigoAttribute($value): void
    {
        $this->attributes['sku'] = $value;
    }

    // unidad_medida <-> unidad
    public function getUnidadMedidaAttribute(): ?string
    {
        return $this->attributes['unidad'] ?? null;
    }

    public function setUnidadMedidaAttribute($value): void
    {
        $this->attributes['unidad'] = $value;
    }
    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'producto_proveedor', 'producto_id', 'proveedor_id')
            ->withPivot([
                'precio_lista',
                'moneda',
                'tiempo_entrega_dias',
                'activo',
                'notas',
            ])
            ->withTimestamps();
    }
    public function usoSegmento()
    {
        return $this->belongsTo(\App\Models\CatalogoSegmento::class, 'uso_id')
            ->where($this->getTable() . '.uso_type', 'segmento');
    }

    public function usoMaquina()
    {
        return $this->belongsTo(\App\Models\Maquina::class, 'uso_id')
            ->where($this->getTable() . '.uso_type', 'maquina');
    }
    public function uso()
{
    // uso_type: 'segmento' | 'maquina'
    // uso_id:   id del registro destino
    return $this->morphTo(__FUNCTION__, 'uso_type', 'uso_id');
}
public function inventarioStocks()
{
    return $this->hasMany(\App\Models\InventarioStock::class, 'producto_id');
}
public function huentitanFormula()
{
    return $this->hasOne(\App\Models\HuentitanFormula::class, 'producto_id');
}

public function usadoEnFormulasHuentitan()
{
    return $this->hasMany(\App\Models\HuentitanFormulaMaterial::class, 'material_producto_id');
}

public function huentitanEntradaDetalles()
{
    return $this->hasMany(\App\Models\HuentitanEntradaDetalle::class, 'producto_id');
}

}
