<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaServicioPreventivoMaterial extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'empresa_servicio_preventivo_materiales';

    protected $fillable = [
        'servicio_preventivo_tipo_id',
        'maquina_id',
        'producto_id',
        'cantidad_requerida',
        'unidad',
        'orden',
        'activo',
    ];

    protected $casts = [
        'cantidad_requerida' => 'decimal:3',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $material): void {
            if ((float) $material->cantidad_requerida <= 0) {
                throw new \InvalidArgumentException('La cantidad requerida debe ser mayor a cero.');
            }
        });
    }

    public function servicioPreventivoTipo(): BelongsTo
    {
        return $this->belongsTo(EmpresaServicioPreventivoTipo::class, 'servicio_preventivo_tipo_id');
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
