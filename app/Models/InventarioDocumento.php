<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioDocumento extends Model
{
    protected $table = 'inventario_documentos';

    protected $fillable = [
        'folio',
        'tipo',               // inicial|entrada|salida|ajuste|resguardo|devolucion
        'almacen_id',
        'obra_id',
        'orden_compra_id',
        'proveedor_id',
        'estado',             // borrador|aplicado|cancelado
        'fecha',
        'motivo',
        'creado_por',
        'notas',
        'residente_id',
        'documento_origen_id', // para devoluciones (origen = resguardo)
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    // -----------------
    // Relaciones
    // -----------------

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(InventarioDocumentoDetalle::class, 'documento_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InventarioMovimiento::class, 'documento_id');
    }

    // Documento origen (ej. devolución -> resguardo)
    public function origen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'documento_origen_id');
    }

    public function derivados(): HasMany
    {
        return $this->hasMany(self::class, 'documento_origen_id');
    }

    // Opcionales (activar cuando confirmemos nombres)
    // public function obra(): BelongsTo
    // {
    //     return $this->belongsTo(Obra::class, 'obra_id');
    // }

    // public function proveedor(): BelongsTo
    // {
    //     return $this->belongsTo(Proveedor::class, 'proveedor_id');
    // }

    // public function ordenCompra(): BelongsTo
    // {
    //     return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    // }

    // Residente snapshot (empleado)
    // public function residente(): BelongsTo
    // {
    //     return $this->belongsTo(Empleado::class, 'residente_id');
    // }

    // Usuario que creó
    // public function creadoPor(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'creado_por');
    // }
}
