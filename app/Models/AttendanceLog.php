<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'attendance_device_id',
        'device_uid',
        'enroll_id',
        'state',
        'type',
        'checked_at'
    ];

    protected $casts = [
        'checked_at' => 'datetime'
    ];

    public function device()
    {
        return $this->belongsTo(AttendanceDevice::class, 'attendance_device_id');
    }

    public function user()
    {
        return $this->belongsTo(AttendanceUser::class, 'enroll_id', 'enroll_id');
    }
}