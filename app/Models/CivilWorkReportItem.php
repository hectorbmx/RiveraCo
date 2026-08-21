<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilWorkReportItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'civil_work_report_id',
        'civil_concept_id',
        'quantity',
        'unit',
        'concept_snapshot',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'concept_snapshot' => 'array',
    ];

    public function report()
    {
        return $this->belongsTo(CivilWorkReport::class, 'civil_work_report_id');
    }

    public function concept()
    {
        return $this->belongsTo(CivilConcept::class, 'civil_concept_id');
    }

    public function photos()
    {
        return $this->hasMany(CivilWorkReportPhoto::class, 'civil_work_report_item_id');
    }
}
