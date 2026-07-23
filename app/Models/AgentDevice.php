<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken;

class AgentDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_uuid',
        'computer_name',
        'token_id',
        'is_default',
        'remember_web_session',
        'open_notifications_in_browser',
        'notification_click_behavior',
        'trusted_until',
        'last_seen_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'remember_web_session' => 'boolean',
        'open_notifications_in_browser' => 'boolean',
        'trusted_until' => 'datetime',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function token()
    {
        return $this->belongsTo(PersonalAccessToken::class, 'token_id');
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->revoked_at === null;
    }
}
