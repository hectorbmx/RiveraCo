<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuentitanFormulaMaterial extends Model
{
    protected $table = 'huentitan_formula_materiales';

    protected $fillable = [
        'formula_id',
        'material_producto_id',
        'cantidad',
        'unidad',
        'merma_porcentaje',
        'metodo_costo',
        'costo_unitario_override',
        'notas',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'merma_porcentaje' => 'decimal:3',
        'costo_unitario_override' => 'decimal:4',
    ];

    public function formula(): BelongsTo
    {
        return $this->belongsTo(HuentitanFormula::class, 'formula_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'material_producto_id');
    }
}

