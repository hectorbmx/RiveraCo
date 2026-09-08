<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuentitanSalidaDetalle extends Model
{
    protected $table = 'huentitan_salida_detalles';

    protected $fillable = [
        'huentitan_salida_id',
        'producto_id',
        'descripcion',
        'unidad',
        'cantidad_solicitada',
        'cantidad_salida',
        'stock_disponible_snapshot',
        'cantidad_faltante',
        'requiere_compra',
        'cantidad_sugerida_compra',
        'costo_unitario',
        'importe',
        'observaciones',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'decimal:3',
        'cantidad_salida' => 'decimal:3',
        'stock_disponible_snapshot' => 'decimal:3',
        'cantidad_faltante' => 'decimal:3',
        'requiere_compra' => 'boolean',
        'cantidad_sugerida_compra' => 'decimal:3',
        'costo_unitario' => 'decimal:4',
        'importe' => 'decimal:2',
    ];

    public function salida(): BelongsTo
    {
        return $this->belongsTo(HuentitanSalida::class, 'huentitan_salida_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}

