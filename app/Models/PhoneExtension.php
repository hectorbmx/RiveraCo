<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'extension',
        'account_type',
        'fullname',
        'user_name',
        'email',
        'status',
        'addr',
        'out_of_service',
        'enable_contact',
        'email_to_user',
        'raw_payload',
        'synced_at',
    ];

    protected $casts = [
        'out_of_service' => 'boolean',
        'enable_contact' => 'boolean',
        'email_to_user' => 'boolean',
        'raw_payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function calls()
    {
        return $this->hasMany(PhoneCall::class);
    }
}
