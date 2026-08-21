<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraCivilMaterialRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'obra_civil_material_request_id',
        'obra_civil_insumo_id',
        'quantity',
        'approved_quantity',
        'unit',
        'insumo_snapshot',
        'notes',
        'approval_notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'approved_quantity' => 'decimal:4',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'insumo_snapshot' => 'array',
    ];

    public function request()
    {
        return $this->belongsTo(ObraCivilMaterialRequest::class, 'obra_civil_material_request_id');
    }

    public function insumo()
    {
        return $this->belongsTo(ObraCivilInsumo::class, 'obra_civil_insumo_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function ordenCompraDetalles()
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'obra_civil_material_request_item_id');
    }
}
