<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MantenimientoAbastecimiento extends Model
{
    public const TIPO_ALMACEN = 'almacen';
    public const TIPO_COMPRA = 'compra';

    protected $table = 'mantenimiento_abastecimientos';

    protected $fillable = [
        'mantenimiento_id',
        'folio',
        'tipo',
        'estado',
        'created_by',
        'observaciones',
    ];

    protected static function booted(): void
    {
        static::created(function (self $abastecimiento): void {
            $abastecimiento->forceFill([
                'folio' => sprintf('SMA-%s-%06d', now()->format('y'), $abastecimiento->id),
            ])->saveQuietly();
        });
    }

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class, 'mantenimiento_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(MantenimientoAbastecimientoDetalle::class, 'abastecimiento_id');
    }

    public function inventarioDocumentos(): HasMany
    {
        return $this->hasMany(InventarioDocumento::class, 'mantenimiento_abastecimiento_id');
    }
}
