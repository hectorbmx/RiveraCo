<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Almacen extends Model
{
    protected $table = 'almacenes';

    protected $fillable = [
        'nombre',
        'tipo',      // general | obra
        'obra_id',   // nullable (futuro)
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /** Stock (materializado) por producto */
    public function stocks(): HasMany
    {
        return $this->hasMany(InventarioStock::class, 'almacen_id');
    }

    /** Documentos (entradas/salidas/ajustes/resguardos/devoluciones) */
    public function documentos(): HasMany
    {
        return $this->hasMany(InventarioDocumento::class, 'almacen_id');
    }

    /** Movimientos (kardex) */
    public function movimientos(): HasMany
    {
        return $this->hasMany(InventarioMovimiento::class, 'almacen_id');
    }

    // Futuro:
    // public function obra(): BelongsTo { ... }  // si después amarras obra_id con FK
}
