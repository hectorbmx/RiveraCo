<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HuentitanOrdenFabricacion extends Model
{
    protected $table = 'huentitan_ordenes_fabricacion';

    public const ESTADOS = [
        'borrador' => 'Borrador',
        'calculada' => 'Calculada',
        'autorizada' => 'Autorizada',
        'en_produccion' => 'En produccion',
        'cerrada' => 'Cerrada',
        'cancelada' => 'Cancelada',
    ];

    protected $fillable = [
        'folio',
        'producto_id',
        'formula_id',
        'cantidad_solicitada',
        'formula_cantidad_base',
        'formula_unidad_base',
        'formula_merma_esperada_porcentaje',
        'formula_tiempo_estimado_minutos',
        'formula_notas',
        'costo_material_estimado',
        'fecha',
        'estado',
        'creado_por',
        'calculada_at',
        'calculada_por',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'decimal:3',
        'formula_cantidad_base' => 'decimal:3',
        'formula_merma_esperada_porcentaje' => 'decimal:3',
        'formula_tiempo_estimado_minutos' => 'integer',
        'costo_material_estimado' => 'decimal:4',
        'fecha' => 'date',
        'calculada_at' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function formula(): BelongsTo
    {
        return $this->belongsTo(HuentitanFormula::class, 'formula_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function calculador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculada_por');
    }

    public function materiales(): HasMany
    {
        return $this->hasMany(HuentitanOrdenFabricacionMaterial::class, 'orden_fabricacion_id');
    }
}
