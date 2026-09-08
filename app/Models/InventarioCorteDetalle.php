<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioCorteDetalle extends Model
{
    protected $table = 'inventario_corte_detalles';

    protected $fillable = [
        'corte_id',
        'producto_id',
        'numero_importacion',
        'nombre_producto',
        'unidad',
        'existencia_anterior',
        'entrada_periodo',
        'salida_periodo',
        'existencia_actual',
        'precio_unitario',
        'valor_anterior',
        'valor_actual',
        'tipo_inventario',
        'origen_abastecimiento',
        'requiere_formula',
        'es_danado',
        'raw',
    ];

    protected $casts = [
        'existencia_anterior' => 'decimal:3',
        'entrada_periodo' => 'decimal:3',
        'salida_periodo' => 'decimal:3',
        'existencia_actual' => 'decimal:3',
        'precio_unitario' => 'decimal:4',
        'valor_anterior' => 'decimal:4',
        'valor_actual' => 'decimal:4',
        'requiere_formula' => 'boolean',
        'es_danado' => 'boolean',
        'raw' => 'array',
    ];

    public function corte(): BelongsTo
    {
        return $this->belongsTo(InventarioCorte::class, 'corte_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}