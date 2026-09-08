<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Almacen extends Model
{
    protected $table = 'almacenes';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',      // general | obra
        'obra_id',   // nullable (futuro)
        'area_id',
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

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function huentitanEntradas(): HasMany
    {
        return $this->hasMany(HuentitanEntrada::class, 'almacen_id');
    }
}



