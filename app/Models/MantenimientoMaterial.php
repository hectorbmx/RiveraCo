<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MantenimientoMaterial extends Model
{
    protected $table = 'mantenimiento_materiales';

    protected $fillable = [
        'mantenimiento_id',
        'material_preventivo_id',
        'producto_id',
        'producto_nombre_snapshot',
        'producto_sku_snapshot',
        'unidad',
        'cantidad_requerida',
        'orden',
        'incluido',
        'motivo_exclusion',
    ];

    protected $casts = [
        'cantidad_requerida' => 'decimal:3',
        'incluido' => 'boolean',
    ];

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class, 'mantenimiento_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
