<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HuentitanFormula extends Model
{
    protected $table = 'huentitan_formulas';

    protected $fillable = [
        'producto_id',
        'cantidad_base',
        'unidad_base',
        'merma_esperada_porcentaje',
        'tiempo_estimado_minutos',
        'notas',
    ];

    protected $casts = [
        'cantidad_base' => 'decimal:3',
        'merma_esperada_porcentaje' => 'decimal:3',
        'tiempo_estimado_minutos' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function materiales(): HasMany
    {
        return $this->hasMany(HuentitanFormulaMaterial::class, 'formula_id');
    }
}
