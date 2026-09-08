<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HuentitanSalida extends Model
{
    protected $table = 'huentitan_salidas';

    protected $fillable = [
        'folio',
        'almacen_id',
        'tipo_destino',
        'obra_id',
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

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class, 'obra_id');
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
        return $this->hasMany(HuentitanSalidaDetalle::class, 'huentitan_salida_id');
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

    public function getTotalSalidaAttribute(): float
    {
        return (float) ($this->relationLoaded('detalles')
            ? $this->detalles->sum('cantidad_salida')
            : $this->detalles()->sum('cantidad_salida'));
    }

    public function getTotalImporteAttribute(): float
    {
        return (float) ($this->relationLoaded('detalles')
            ? $this->detalles->sum('importe')
            : $this->detalles()->sum('importe'));
    }
}
