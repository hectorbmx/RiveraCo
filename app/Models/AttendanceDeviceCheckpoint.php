<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceDeviceCheckpoint extends Model
{
    use HasFactory;

        protected $fillable = ['attendance_device_id','last_timestamp'];

}
