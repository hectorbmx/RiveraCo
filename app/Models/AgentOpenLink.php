<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentOpenLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_device_id',
        'notification_id',
        'token_hash',
        'target_url',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agentDevice()
    {
        return $this->belongsTo(AgentDevice::class);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getIsUsedAttribute(): bool
    {
        return $this->used_at !== null;
    }
}
