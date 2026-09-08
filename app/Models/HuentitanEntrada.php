<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HuentitanEntrada extends Model
{
    protected $table = 'huentitan_entradas';

    protected $fillable = [
        'folio',
        'almacen_id',
        'orden_compra_id',
        'tipo_origen',
        'fecha',
        'estado',
        'usuario_id',
        'aplicada_por',
        'fecha_aplicacion',
        'cancelada_por',
        'fecha_cancelacion',
        'motivo_cancelacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_aplicacion' => 'datetime',
        'fecha_cancelacion' => 'datetime',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function aplicadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicada_por');
    }

    public function canceladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelada_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(HuentitanEntradaDetalle::class, 'huentitan_entrada_id');
    }

    public function isBorrador(): bool
    {
        return $this->estado === 'borrador';
    }

    public function isAplicada(): bool
    {
        return $this->estado === 'aplicada';
    }

    public function isCancelada(): bool
    {
        return $this->estado === 'cancelada';
    }

    public function getTotalRecibidoAttribute(): float
    {
        return (float) ($this->relationLoaded('detalles')
            ? $this->detalles->sum('cantidad_recibida')
            : $this->detalles()->sum('cantidad_recibida'));
    }

    public function getTotalImporteAttribute(): float
    {
        return (float) ($this->relationLoaded('detalles')
            ? $this->detalles->sum('importe')
            : $this->detalles()->sum('importe'));
    }
}
