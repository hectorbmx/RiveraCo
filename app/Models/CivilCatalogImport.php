<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilCatalogImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'obra_id',
        'filename',
        'original_path',
        'sheet_name',
        'status',
        'total_buildings',
        'total_partidas',
        'total_concepts',
        'total_amount',
        'imported_by',
        'validated_by',
        'validated_at',
        'metadata',
    ];

    protected $casts = [
        'total_buildings' => 'integer',
        'total_partidas' => 'integer',
        'total_concepts' => 'integer',
        'total_amount' => 'decimal:2',
        'validated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function buildings()
    {
        return $this->hasMany(CivilBuilding::class, 'civil_catalog_import_id');
    }

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function estimations()
    {
        return $this->hasMany(CivilEstimation::class, 'civil_catalog_import_id');
    }
}
