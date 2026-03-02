<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use App\Models\AttendanceLog;
use App\Models\AttendanceUser;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function devices()
    {
        $devices = AttendanceDevice::query()
            ->orderBy('name')
            ->get();

        return view('attendance.devices', compact('devices'));
    }

    public function employees(AttendanceDevice $device, Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $employees = AttendanceUser::query()
            ->where('attendance_device_id', $device->id)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('enroll_id', 'like', "%{$q}%")
                       ->orWhere('cardno', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('attendance.employees', compact('device','employees','q'));
    }

    public function employeeShow(AttendanceDevice $device, int $enrollId, Request $request)
    {
        $from = $request->query('from'); // YYYY-MM-DD
        $to   = $request->query('to');   // YYYY-MM-DD

        $employee = AttendanceUser::query()
            ->where('attendance_device_id', $device->id)
            ->where('enroll_id', $enrollId)
            ->first();

        $logs = AttendanceLog::query()
            ->where('attendance_device_id', $device->id)
            ->where('enroll_id', $enrollId)
            ->when($from, fn($q) => $q->whereDate('checked_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('checked_at', '<=', $to))
            ->orderByDesc('checked_at')
            ->paginate(100)
            ->withQueryString();

        return view('attendance.employee-show', compact('device','employee','enrollId','logs','from','to'));
    }
}