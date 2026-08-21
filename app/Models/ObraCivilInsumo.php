<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraCivilInsumo extends Model
{
    use HasFactory;

    protected $fillable = [
        'obra_civil_insumo_import_id',
        'obra_id',
        'tipo',
        'codigo',
        'concepto',
        'unidad',
        'cantidad_presupuestada',
        'precio_unitario',
        'importe_importado',
        'importe_calculado',
        'incidencia',
        'source_row',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'cantidad_presupuestada' => 'decimal:4',
        'precio_unitario' => 'decimal:4',
        'importe_importado' => 'decimal:2',
        'importe_calculado' => 'decimal:2',
        'incidencia' => 'decimal:6',
        'source_row' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function import()
    {
        return $this->belongsTo(ObraCivilInsumoImport::class, 'obra_civil_insumo_import_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function ordenCompraDetalles()
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'obra_civil_insumo_id');
    }

    public function materialRequestItems()
    {
        return $this->hasMany(ObraCivilMaterialRequestItem::class, 'obra_civil_insumo_id');
    }
}

