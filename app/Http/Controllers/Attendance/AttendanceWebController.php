<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use App\Models\AttendanceLog;
use App\Models\AttendanceUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendanceWebController extends Controller
{
    public function index(Request $request)
{
    $deviceId   = $request->query('device_id');
    $employeeId = $request->query('employee_id');
    
    // Si no vienen fechas en el request, asignamos el mes en curso
    $from = $request->query('from') ?? now()->startOfMonth()->format('Y-m-d');
    $to   = $request->query('to')   ?? now()->endOfMonth()->format('Y-m-d');

    $devices = AttendanceDevice::query()->orderBy('name')->get();

    $selectedEmployee = null;
    if ($employeeId) {
        $selectedEmployee = AttendanceUser::query()
            ->when($deviceId, fn($q) => $q->where('attendance_device_id', $deviceId))
            ->find($employeeId);
    }

    $logs = AttendanceLog::query()
        ->with(['device', 'user'])
        ->when($deviceId, fn($q) => $q->where('attendance_device_id', $deviceId))
        ->when($employeeId, function ($q) use ($employeeId, $deviceId) {
            $user = AttendanceUser::query()
                ->when($deviceId, fn($qq) => $qq->where('attendance_device_id', $deviceId))
                ->find($employeeId);

            if ($user) {
                $q->where('attendance_device_id', $user->attendance_device_id)
                  ->where('enroll_id', $user->enroll_id);
            }
        })
        // Estos filtros ahora siempre se ejecutarán porque $from y $to tienen valores por defecto
        ->whereDate('checked_at', '>=', $from)
        ->whereDate('checked_at', '<=', $to)
        ->orderByDesc('checked_at')
        ->paginate(100)
        ->withQueryString();

    return view('attendance.logs.index', compact(
        'logs','devices','deviceId','employeeId','from','to','selectedEmployee'
    ));
}
    // public function index(Request $request)
    // {
    //     $deviceId = $request->query('device_id');  // opcional
    //     $employeeId = $request->query('employee_id'); // attendance_users.id
    //     $from = $request->query('from'); // YYYY-MM-DD
    //     $to = $request->query('to');     // YYYY-MM-DD

    //     // combos para filtros
    //     $devices = AttendanceDevice::query()->orderBy('name')->get();

    //     $employeesQuery = AttendanceUser::query()->orderBy('name');
    //     if ($deviceId) $employeesQuery->where('attendance_device_id', $deviceId);
    //     $employees = $employeesQuery->limit(500)->get(); // evita cargar miles

    //     $logs = AttendanceLog::query()
    //         ->with(['device', 'user']) // definimos relaciones abajo
    //         ->when($deviceId, fn($q) => $q->where('attendance_device_id', $deviceId))
    //         ->when($employeeId, function ($q) use ($employeeId) {
    //             $user = AttendanceUser::find($employeeId);
    //             if ($user) {
    //                 $q->where('attendance_device_id', $user->attendance_device_id)
    //                   ->where('enroll_id', $user->enroll_id);
    //             }
    //         })
    //         ->when($from, fn($q) => $q->whereDate('checked_at', '>=', $from))
    //         ->when($to, fn($q) => $q->whereDate('checked_at', '<=', $to))
    //         ->orderByDesc('checked_at')
    //         ->paginate(100)
    //         ->withQueryString();

    //     return view('attendance.logs.index', compact(
    //         'logs','devices','employees','deviceId','employeeId','from','to'
    //     ));
    // }
public function searchEmployees(Request $request)
{
    $q = trim((string) $request->query('q', ''));
    $deviceId = $request->query('device_id');

    if (mb_strlen($q) < 2) {
        return response()->json(['ok' => true, 'data' => []]);
    }

    $items = AttendanceUser::query()
        ->select(['id','name','enroll_id','attendance_device_id'])
        ->when($deviceId, fn($qq) => $qq->where('attendance_device_id', $deviceId))
        ->where('name', 'like', "%{$q}%")
        ->orderBy('name')
        ->limit(20)
        ->get()
        ->map(function ($u) {
            return [
                'id' => $u->id,
                'text' => $u->name . ' (Enroll: ' . $u->enroll_id . ')',
                'name' => $u->name,
                'enroll_id' => $u->enroll_id,
                'attendance_device_id' => $u->attendance_device_id,
            ];
        });

    return response()->json(['ok' => true, 'data' => $items]);
}
    // public function showEmployee(AttendanceUser $employee, Request $request)
    // {
    //     $from = $request->query('from');
    //     $to   = $request->query('to');

    //     $logs = AttendanceLog::query()
    //         ->where('attendance_device_id', $employee->attendance_device_id)
    //         ->where('enroll_id', $employee->enroll_id)
    //         ->when($from, fn($q) => $q->whereDate('checked_at', '>=', $from))
    //         ->when($to, fn($q) => $q->whereDate('checked_at', '<=', $to))
    //         ->orderByDesc('checked_at')
    //         ->paginate(100)
    //         ->withQueryString();

    //     return view('attendance.employees.show', compact('employee','logs','from','to'));
    // }
//     public function showEmployee(AttendanceUser $employee, Request $request)
// {
//     // Forzamos fechas por defecto (Mes actual) si no existen
//     $from = $request->query('from') ?? now()->startOfMonth()->format('Y-m-d');
//     $to   = $request->query('to')   ?? now()->endOfMonth()->format('Y-m-d');

//     // Obtenemos TODOS los logs del periodo para los KPIs (sin paginar)
//     $allLogs = AttendanceLog::query()
//         ->where('attendance_device_id', $employee->attendance_device_id)
//         ->where('enroll_id', $employee->enroll_id)
//         ->whereDate('checked_at', '>=', $from)
//         ->whereDate('checked_at', '<=', $to)
//         ->orderBy('checked_at', 'asc')
//         ->get();

//     // --- CÁLCULO DE KPIs ---
    
//     // 1. Días trabajados (agrupando por fecha única)
//     $workedDays = $allLogs->groupBy(fn($log) => \Carbon\Carbon::parse($log->checked_at)->format('Y-m-d'))->count();

//     // 2. Horas totales (Basado en primera y última checada de cada día)
//     $totalSeconds = 0;
//     foreach ($allLogs->groupBy(fn($log) => \Carbon\Carbon::parse($log->checked_at)->format('Y-m-d')) as $dayLogs) {
//         if ($dayLogs->count() >= 2) {
//             $first = \Carbon\Carbon::parse($dayLogs->first()->checked_at);
//             $last = \Carbon\Carbon::parse($dayLogs->last()->checked_at);
//             $totalSeconds += $first->diffInSeconds($last);
//         }
//     }
//     $totalHours = round($totalSeconds / 3600, 1);

//     // 3. Promedio de entrada (Solo días que tienen registros)
//     $avgEntry = '—';
//     if ($workedDays > 0) {
//         $totalMinutes = 0;
//         foreach ($allLogs->groupBy(fn($log) => \Carbon\Carbon::parse($log->checked_at)->format('Y-m-d')) as $dayLogs) {
//             $first = \Carbon\Carbon::parse($dayLogs->first()->checked_at);
//             $totalMinutes += ($first->hour * 60) + $first->minute;
//         }
//         $avgEntry = now()->startOfDay()->addMinutes($totalMinutes / $workedDays)->format('h:i A');
//     }

//     // Paginación para la tabla
//     $logs = AttendanceLog::query()
//         ->where('attendance_device_id', $employee->attendance_device_id)
//         ->where('enroll_id', $employee->enroll_id)
//         ->whereDate('checked_at', '>=', $from)
//         ->whereDate('checked_at', '<=', $to)
//         ->orderByDesc('checked_at')
//         ->paginate(100)
//         ->withQueryString();

//     return view('attendance.employees.show', compact(
//         'employee', 'logs', 'from', 'to', 'workedDays', 'totalHours', 'avgEntry'
//     ));
// }
public function showEmployee(AttendanceUser $employee, Request $request)
{
    // Fechas default (mes actual)
    $from = $request->query('from') ?? now()->startOfMonth()->format('Y-m-d');
    $to   = $request->query('to')   ?? now()->endOfMonth()->format('Y-m-d');

    // Traemos todos los logs del periodo (para KPIs + agrupar por día)
    $allLogs = AttendanceLog::query()
        ->where('attendance_device_id', $employee->attendance_device_id)
        ->where('enroll_id', $employee->enroll_id)
        ->whereDate('checked_at', '>=', $from)
        ->whereDate('checked_at', '<=', $to)
        ->orderBy('checked_at', 'asc')
        ->get();

    // Agrupar por fecha (Y-m-d)
    $byDay = $allLogs->groupBy(fn($log) => Carbon::parse($log->checked_at)->format('Y-m-d'));

    // KPI 1) Días trabajados
    $workedDays = $byDay->count();

    // KPI 2) Horas totales estimadas (primera vs última checada de cada día)
    $totalSeconds = 0;
    foreach ($byDay as $dayLogs) {
        if ($dayLogs->count() >= 2) {
            $first = Carbon::parse($dayLogs->first()->checked_at);
            $last  = Carbon::parse($dayLogs->last()->checked_at);
            $totalSeconds += $first->diffInSeconds($last);
        }
    }
    $totalHours = round($totalSeconds / 3600, 1);

    // KPI 3) Promedio de entrada
    $avgEntry = '—';
    if ($workedDays > 0) {
        $totalMinutes = 0;
        foreach ($byDay as $dayLogs) {
            $first = Carbon::parse($dayLogs->first()->checked_at);
            $totalMinutes += ($first->hour * 60) + $first->minute;
        }
        $avgEntry = now()->startOfDay()->addMinutes($totalMinutes / $workedDays)->format('h:i A');
    }

    // Construir filas por día (1 row = 1 día)
    $rows = $byDay->map(function (Collection $dayLogs, string $date) {
        // Aseguramos orden por hora
        $sorted = $dayLogs->sortBy('checked_at')->values();

        $firstLog = $sorted->first();
        $lastLog  = $sorted->last();

        $entryAt = Carbon::parse($firstLog->checked_at);
        $exitAt  = ($sorted->count() >= 2) ? Carbon::parse($lastLog->checked_at) : null;

        $hours = ($exitAt)
            ? round($entryAt->diffInMinutes($exitAt) / 60, 2)
            : 0;

        return (object) [
            'date'       => $date,
            'day_name'   => $entryAt->locale('es')->translatedFormat('l'),
            'entry_at'   => $entryAt,
            'exit_at'    => $exitAt,
            'hours'      => $hours,
            'count'      => $sorted->count(),

            // Si quieres seguir mostrando datos del log (opcional)
            'device_uid' => $firstLog->device_uid ?? null,
        ];
    })
    ->values()
    ->sortByDesc('date') // día más nuevo primero
    ->values();

    // Paginación por días (no por logs)
    $perPage = 31;
    $page    = (int) $request->query('page', 1);
    $slice   = $rows->slice(($page - 1) * $perPage, $perPage)->values();

    $logs = new LengthAwarePaginator(
        $slice,
        $rows->count(),
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    return view('attendance.employees.show', compact(
        'employee', 'logs', 'from', 'to',
        'workedDays', 'totalHours', 'avgEntry'
    ));
}
    public function export(Request $request)
{
    $deviceId   = $request->query('device_id');
    $employeeId = $request->query('employee_id');
    $from       = $request->query('from') ?? now()->startOfMonth()->format('Y-m-d');
    $to         = $request->query('to')   ?? now()->endOfMonth()->format('Y-m-d');

    $logs = AttendanceLog::query()
        ->with(['device', 'user'])
        ->when($deviceId, fn($q) => $q->where('attendance_device_id', $deviceId))
        ->when($employeeId, function ($q) use ($employeeId) {
            $user = AttendanceUser::find($employeeId);
            if ($user) {
                $q->where('attendance_device_id', $user->attendance_device_id)
                  ->where('enroll_id', $user->enroll_id);
            }
        })
        ->whereDate('checked_at', '>=', $from)
        ->whereDate('checked_at', '<=', $to)
        ->orderBy('checked_at', 'asc')
        ->get();

    $response = new StreamedResponse(function () use ($logs) {
        $handle = fopen('php://output', 'w');
        // Bom para UTF-8 (Excel soporte acentos)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        fputcsv($handle, ['Fecha/Hora', 'Empleado', 'Enroll ID', 'Dispositivo', 'Tipo', 'Estado']);

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->checked_at,
                $log->user->name ?? '—',
                $log->enroll_id,
                $log->device->name ?? '—',
                $log->type,
                $log->state
            ]);
        }
        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="reporte_checadas_'.$from.'_al_'.$to.'.csv"');

    return $response;
}
}