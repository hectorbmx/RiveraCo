<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelephonySyncRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'source',
        'status',
        'agent_computer_name',
        'agent_module',
        'window_from',
        'window_to',
        'window_timezone',
        'received',
        'mapped',
        'skipped',
        'new_count',
        'updated_count',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'window_from' => 'datetime',
        'window_to' => 'datetime',
        'received' => 'integer',
        'mapped' => 'integer',
        'skipped' => 'integer',
        'new_count' => 'integer',
        'updated_count' => 'integer',
        'metadata' => 'array',
    ];
}