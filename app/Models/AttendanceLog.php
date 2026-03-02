<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

        protected $fillable = ['attendance_device_id','device_uid','enroll_id','state','type','checked_at'];

}
