<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use App\Models\AttendanceLog;
use App\Models\AttendanceUser;
use Illuminate\Http\Request;

class AttendanceWebController extends Controller
{
    public function index(Request $request)
    {
        $deviceId = $request->query('device_id');  // opcional
        $employeeId = $request->query('employee_id'); // attendance_users.id
        $from = $request->query('from'); // YYYY-MM-DD
        $to = $request->query('to');     // YYYY-MM-DD

        // combos para filtros
        $devices = AttendanceDevice::query()->orderBy('name')->get();

        $employeesQuery = AttendanceUser::query()->orderBy('name');
        if ($deviceId) $employeesQuery->where('attendance_device_id', $deviceId);
        $employees = $employeesQuery->limit(500)->get(); // evita cargar miles

        $logs = AttendanceLog::query()
            ->with(['device', 'user']) // definimos relaciones abajo
            ->when($deviceId, fn($q) => $q->where('attendance_device_id', $deviceId))
            ->when($employeeId, function ($q) use ($employeeId) {
                $user = AttendanceUser::find($employeeId);
                if ($user) {
                    $q->where('attendance_device_id', $user->attendance_device_id)
                      ->where('enroll_id', $user->enroll_id);
                }
            })
            ->when($from, fn($q) => $q->whereDate('checked_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('checked_at', '<=', $to))
            ->orderByDesc('checked_at')
            ->paginate(100)
            ->withQueryString();

        return view('attendance.logs.index', compact(
            'logs','devices','employees','deviceId','employeeId','from','to'
        ));
    }

    public function showEmployee(AttendanceUser $employee, Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $logs = AttendanceLog::query()
            ->where('attendance_device_id', $employee->attendance_device_id)
            ->where('enroll_id', $employee->enroll_id)
            ->when($from, fn($q) => $q->whereDate('checked_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('checked_at', '<=', $to))
            ->orderByDesc('checked_at')
            ->paginate(100)
            ->withQueryString();

        return view('attendance.employees.show', compact('employee','logs','from','to'));
    }
}