<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraCivilCommercialMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'obra_civil_material_group_id',
        'category',
        'subcategory',
        'grade',
        'sku',
        'descripcion',
        'medida',
        'diametro',
        'calibre_espesor',
        'longitud',
        'unidad_compra',
        'conversion_type',
        'peso_por_metro',
        'peso_por_pieza',
        'peso_por_m2',
        'peso_por_rollo',
        'factor_conversion',
        'tolerance',
        'validation_status',
        'technical_source',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'longitud' => 'decimal:4',
        'peso_por_metro' => 'decimal:6',
        'peso_por_pieza' => 'decimal:6',
        'peso_por_m2' => 'decimal:6',
        'peso_por_rollo' => 'decimal:6',
        'factor_conversion' => 'decimal:6',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function group()
    {
        return $this->belongsTo(ObraCivilMaterialGroup::class, 'obra_civil_material_group_id');
    }
}
