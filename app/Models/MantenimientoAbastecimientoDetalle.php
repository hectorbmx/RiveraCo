<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MantenimientoAbastecimientoDetalle extends Model
{
    protected $table = 'mantenimiento_abastecimiento_detalles';

    protected $fillable = [
        'abastecimiento_id',
        'producto_id',
        'material_preventivo_id',
        'producto_nombre_snapshot',
        'producto_sku_snapshot',
        'unidad',
        'cantidad_requerida',
        'cantidad_solicitada',
        'cantidad_atendida',
        'estado',
    ];

    protected $casts = [
        'cantidad_requerida' => 'decimal:3',
        'cantidad_solicitada' => 'decimal:3',
        'cantidad_atendida' => 'decimal:3',
    ];

    public function abastecimiento(): BelongsTo
    {
        return $this->belongsTo(MantenimientoAbastecimiento::class, 'abastecimiento_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
