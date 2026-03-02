<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceUser extends Model
{
    use HasFactory;

        protected $fillable = ['attendance_device_id','device_uid','enroll_id','name','cardno'];

        public function device()
        {
            return $this->belongsTo(AttendanceDevice::class, 'attendance_device_id');
        }

}
