<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'ucm_cdr_id',
        'phone_extension_id',
        'user_id',
        'extension_snapshot',
        'extension_name_snapshot',
        'user_name_snapshot',
        'matched_phone_number_id',
        'phoneable_type',
        'phoneable_id',
        'phoneable_name',
        'matched_number',
        'match_status',
        'session',
        'acct_id',
        'uniqueid',
        'action_type',
        'action_owner',
        'direction',
        'status',
        'disposition',
        'ucm_userfield',
        'source_number',
        'destination_number',
        'source_extension',
        'destination_extension',
        'answered_by',
        'caller_name',
        'clid',
        'started_at',
        'answered_at',
        'ended_at',
        'duration_seconds',
        'billsec',
        'source_trunk_name',
        'destination_trunk_name',
        'channel',
        'destination_channel',
        'lastapp',
        'lastdata',
        'device_info',
        'device_info_peer',
        'recordfiles',
        'reason',
        'raw_payload',
        'imported_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'billsec' => 'integer',
        'raw_payload' => 'array',
        'imported_at' => 'datetime',
    ];

    public function extension()
    {
        return $this->belongsTo(PhoneExtension::class, 'phone_extension_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
