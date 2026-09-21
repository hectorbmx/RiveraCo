<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuentitanFormulaHerramienta extends Model
{
    protected $table = 'huentitan_formula_herramientas';

    protected $fillable = [
        'formula_id',
        'herramienta_id',
        'cantidad',
        'costo_unitario_aplicado',
        'metodo_calculo',
        'notas',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'costo_unitario_aplicado' => 'decimal:4',
    ];

    public function formula(): BelongsTo
    {
        return $this->belongsTo(HuentitanFormula::class, 'formula_id');
    }

    public function herramienta(): BelongsTo
    {
        return $this->belongsTo(Herramienta::class, 'herramienta_id');
    }
}
