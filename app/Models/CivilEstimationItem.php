<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilEstimationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'civil_estimation_id',
        'civil_concept_id',
        'quantity',
        'unit_price',
        'amount',
        'concept_snapshot',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:2',
        'concept_snapshot' => 'array',
    ];

    public function estimation()
    {
        return $this->belongsTo(CivilEstimation::class, 'civil_estimation_id');
    }

    public function concept()
    {
        return $this->belongsTo(CivilConcept::class, 'civil_concept_id');
    }
}