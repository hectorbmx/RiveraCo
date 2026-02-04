<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioStock extends Model
{
    protected $table = 'inventario_stock';

    protected $fillable = [
    'almacen_id',
    'producto_id',
    'stock_actual',
    'stock_reservado',
    'valor_total',
    'costo_promedio',
];

    protected $casts = [
    'stock_actual'    => 'decimal:3',
    'stock_reservado' => 'decimal:3',
    'valor_total'     => 'decimal:4',
    'costo_promedio'  => 'decimal:4',
];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    // Ajustar si tu modelo no se llama Producto::class
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
