<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilEstimation extends Model
{
    use HasFactory;

    protected $fillable = [
        'obra_id',
        'civil_catalog_import_id',
        'folio',
        'status',
        'total_items',
        'total_quantity',
        'subtotal',
        'created_by',
        'confirmed_at',
        'metadata',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'total_quantity' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function catalogImport()
    {
        return $this->belongsTo(CivilCatalogImport::class, 'civil_catalog_import_id');
    }

    public function items()
    {
        return $this->hasMany(CivilEstimationItem::class, 'civil_estimation_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}