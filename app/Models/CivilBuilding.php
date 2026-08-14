<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilBuilding extends Model
{
    use HasFactory;

    protected $fillable = [
        'civil_catalog_import_id',
        'name',
        'excel_row',
        'sort_order',
    ];

    protected $casts = [
        'excel_row' => 'integer',
        'sort_order' => 'integer',
    ];

    public function catalogImport()
    {
        return $this->belongsTo(CivilCatalogImport::class, 'civil_catalog_import_id');
    }

    public function partidas()
    {
        return $this->hasMany(CivilPartida::class, 'civil_building_id');
    }
}

