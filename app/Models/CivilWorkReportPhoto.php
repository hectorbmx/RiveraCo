<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilWorkReportPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'civil_work_report_item_id',
        'path',
        'original_name',
        'mime_type',
        'size',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'metadata' => 'array',
    ];

    public function item()
    {
        return $this->belongsTo(CivilWorkReportItem::class, 'civil_work_report_item_id');
    }
}
