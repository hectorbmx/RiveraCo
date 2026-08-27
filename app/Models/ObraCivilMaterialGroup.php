<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraCivilMaterialGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'family',
        'grade',
        'source_codes',
        'keywords',
        'match_rules',
        'budget_units',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'source_codes' => 'array',
        'keywords' => 'array',
        'match_rules' => 'array',
        'budget_units' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function commercialMaterials()
    {
        return $this->hasMany(ObraCivilCommercialMaterial::class, 'obra_civil_material_group_id');
    }
}
