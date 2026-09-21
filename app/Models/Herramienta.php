<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Herramienta extends Model
{
    protected $table = 'herramientas';

    protected $fillable = [
        'almacen_id',
        'proveedor_id',
        'codigo',
        'nombre',
        'descripcion',
        'costo',
        'vida_util_piezas',
        'costo_residual',
        'fecha_compra',
        'fecha_registro',
        'proveedor_nombre',
        'marca',
        'modelo',
        'numero_serie',
        'estado',
        'activo',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'vida_util_piezas' => 'integer',
        'costo_residual' => 'decimal:2',
        'fecha_compra' => 'date',
        'fecha_registro' => 'date',
        'activo' => 'boolean',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}

