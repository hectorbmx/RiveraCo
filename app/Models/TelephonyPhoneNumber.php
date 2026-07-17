<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelephonyPhoneNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'phoneable_type',
        'phoneable_id',
        'source_table',
        'source_column',
        'label',
        'raw_number',
        'normalized_number',
        'display_name',
        'is_primary',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function phoneable()
    {
        return $this->morphTo();
    }
}