<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class PresupuestoVersion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'presupuesto_versiones';

    protected $fillable = ['revision', 'version_origen_id', 'creado_por', 'motivo', 'datos', 'total_venta'];

    protected $casts = [
        'revision' => 'integer',
        'datos' => 'array',
        'total_venta' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $version): void {
            if ($version->presupuesto?->consecutivo_cliente === null) {
                throw new LogicException('Asigna el consecutivo del cliente antes de guardar una versión.');
            }
        });

        static::updating(function (): never {
            throw new LogicException('Una versión guardada no se puede modificar.');
        });

        static::deleting(function (): never {
            throw new LogicException('Una versión guardada no se puede eliminar.');
        });
    }

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class);
    }

    public function origen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'version_origen_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(PresupuestoEvento::class, 'presupuesto_version_id');
    }

    public function ultimoEvento(): HasOne
    {
        return $this->eventos()->one()->latestOfMany();
    }

    public function folio(): string
    {
        $base = $this->presupuesto->consecutivo_cliente;

        return $this->revision === 0 ? "#{$base}" : "#{$base}.{$this->revision}";
    }

    public function tieneVenta(): bool
    {
        $presentacion = $this->datos['presentacion'] ?? null;

        return $presentacion === null
            ? (float) $this->total_venta > 0
            : !empty($presentacion['filas']) && ($presentacion['subtotal'] ?? 0) > 0;
    }
}
