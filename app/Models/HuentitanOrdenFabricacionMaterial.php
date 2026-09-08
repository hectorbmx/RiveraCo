<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuentitanOrdenFabricacionMaterial extends Model
{
    protected $table = 'huentitan_orden_fabricacion_materiales';

    protected $fillable = [
        'orden_fabricacion_id',
        'formula_material_id',
        'material_producto_id',
        'material_sku',
        'material_nombre',
        'cantidad_por_unidad',
        'cantidad_requerida',
        'unidad',
        'merma_porcentaje',
        'costo_unitario_estimado',
        'costo_total_estimado',
        'stock_actual_calculado',
        'stock_reservado_calculado',
        'stock_disponible_calculado',
        'faltante_calculado',
        'requiere_compra',
        'cantidad_sugerida_compra',
        'compra_marcada_at',
        'compra_marcada_por',
        'notas',
    ];

    protected $casts = [
        'cantidad_por_unidad' => 'decimal:3',
        'cantidad_requerida' => 'decimal:3',
        'merma_porcentaje' => 'decimal:3',
        'costo_unitario_estimado' => 'decimal:4',
        'costo_total_estimado' => 'decimal:4',
        'stock_actual_calculado' => 'decimal:3',
        'stock_reservado_calculado' => 'decimal:3',
        'stock_disponible_calculado' => 'decimal:3',
        'faltante_calculado' => 'decimal:3',
        'requiere_compra' => 'boolean',
        'cantidad_sugerida_compra' => 'decimal:3',
        'compra_marcada_at' => 'datetime',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(HuentitanOrdenFabricacion::class, 'orden_fabricacion_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'material_producto_id');
    }

    public function compraMarcadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'compra_marcada_por');
    }
}
