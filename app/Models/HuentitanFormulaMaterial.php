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
        'notas',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'merma_porcentaje' => 'decimal:3',
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
