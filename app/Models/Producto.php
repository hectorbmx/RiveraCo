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
        'tipo',
        'activo',
        'iva_default', // la agregaremos con ALTER
    ];

    protected $casts = [
        'activo' => 'boolean',
        'iva_default' => 'decimal:2',
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

}
