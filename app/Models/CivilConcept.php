<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilConcept extends Model
{
    use HasFactory;

    protected $fillable = [
        'civil_partida_id',
        'excel_code',
        'description',
        'unit',
        'budget_quantity',
        'unit_price',
        'unit_price_text',
        'budget_amount',
        'excel_row',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'budget_quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'budget_amount' => 'decimal:2',
        'excel_row' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function partida()
    {
        return $this->belongsTo(CivilPartida::class, 'civil_partida_id');
    }

    public function ordenCompraDetalles()
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'civil_concept_id');
    }
}

