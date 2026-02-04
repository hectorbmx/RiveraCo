<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioMovimiento extends Model
{
    protected $table = 'inventario_movimientos';

    protected $fillable = [
        'almacen_id',
        'producto_id',
        'documento_id',
        'fecha',
        'tipo_movimiento',   // in | out
        'cantidad',
        'costo_unitario',
        'saldo_cantidad',
        'obra_id',
        'residente_id',
        'creado_por',
    ];

    protected $casts = [
        'fecha'         => 'datetime',
        'cantidad'      => 'decimal:3',
        'costo_unitario'=> 'decimal:4',
        'saldo_cantidad'=> 'decimal:3',
    ];

    // -----------------
    // Relaciones
    // -----------------

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    // Ajustar si tu modelo no se llama Producto::class
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(InventarioDocumento::class, 'documento_id');
    }

    // Opcionales / futuros
    // public function obra(): BelongsTo
    // {
    //     return $this->belongsTo(Obra::class, 'obra_id');
    // }

    // public function residente(): BelongsTo
    // {
    //     return $this->belongsTo(Empleado::class, 'residente_id');
    // }

    // public function creadoPor(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'creado_por');
    // }
}
