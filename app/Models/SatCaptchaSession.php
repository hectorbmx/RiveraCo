<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatCaptchaSession extends Model
{
    protected $table = 'sat_captcha_sessions';
    protected $primaryKey = 'token';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'token',
        'image_inline_html',
        'answer',
        'answered',
        'expires_at',
    ];

    protected $casts = [
        'answered' => 'boolean',
        'expires_at' => 'datetime',
    ];
}