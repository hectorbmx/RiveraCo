<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilPartida extends Model
{
    use HasFactory;

    protected $fillable = [
        'civil_building_id',
        'code',
        'name',
        'budget_amount',
        'excel_row',
        'sort_order',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'excel_row' => 'integer',
        'sort_order' => 'integer',
    ];

    public function building()
    {
        return $this->belongsTo(CivilBuilding::class, 'civil_building_id');
    }

    public function concepts()
    {
        return $this->hasMany(CivilConcept::class, 'civil_partida_id');
    }
}

