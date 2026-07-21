<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelephonyCallRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'requested_by_user_id',
        'phone_extension_id',
        'telephony_phone_number_id',
        'caller_extension',
        'outbound_number',
        'normalized_outbound_number',
        'phoneable_type',
        'phoneable_id',
        'phoneable_name',
        'status',
        'source',
        'claimed_by_agent',
        'claimed_at',
        'completed_at',
        'failed_at',
        'ucm_status',
        'error_message',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function extension()
    {
        return $this->belongsTo(PhoneExtension::class, 'phone_extension_id');
    }

    public function phoneNumber()
    {
        return $this->belongsTo(TelephonyPhoneNumber::class, 'telephony_phone_number_id');
    }

    public function phoneable()
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
