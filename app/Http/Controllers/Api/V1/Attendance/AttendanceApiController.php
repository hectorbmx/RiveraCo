<?php

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\AttendanceUser;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AttendanceApiController extends Controller
{
    /**
     * Listado general de checadas
     * GET /api/v1/attendance/logs
     *
     * Filtros:
     * - device_id
     * - employee_id   (attendance_users.id)
     * - from          (YYYY-MM-DD)
     * - to            (YYYY-MM-DD)
     * - per_page      (default 100, max 200)
     */
    public function index(Request $request)
    {
        $deviceId   = $request->query('device_id');
        $employeeId = $request->query('employee_id');
        $from       = $request->query('from') ?? now()->startOfMonth()->format('Y-m-d');
        $to         = $request->query('to')   ?? now()->endOfMonth()->format('Y-m-d');
        $perPage    = min((int) $request->query('per_page', 100), 200);

        $query = AttendanceLog::query()
            ->with(['device', 'user'])
            ->when($deviceId, fn($q) => $q->where('attendance_device_id', $deviceId))
            ->when($employeeId, function ($q) use ($employeeId, $deviceId) {
                $user = AttendanceUser::query()
                    ->when($deviceId, fn($qq) => $qq->where('attendance_device_id', $deviceId))
                    ->find($employeeId);

                if ($user) {
                    $q->where('attendance_device_id', $user->attendance_device_id)
                      ->where('enroll_id', $user->enroll_id);
                } else {
                    // si mandan employee_id inválido, no regresamos todo
                    $q->whereRaw('1 = 0');
                }
            })
            ->whereDate('checked_at', '>=', $from)
            ->whereDate('checked_at', '<=', $to)
            ->orderByDesc('checked_at');

        $logs = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'ok' => true,
            'filters' => [
                'device_id'   => $deviceId,
                'employee_id' => $employeeId,
                'from'        => $from,
                'to'          => $to,
                'per_page'    => $perPage,
            ],
            'data' => $logs->getCollection()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'attendance_device_id' => $log->attendance_device_id,
                    'device' => $log->device ? [
                        'id'   => $log->device->id,
                        'name' => $log->device->name,
                    ] : null,
                    'user' => $log->user ? [
                        'id'        => $log->user->id,
                        'name'      => $log->user->name,
                        'enroll_id' => $log->user->enroll_id,
                    ] : null,
                    'enroll_id'   => $log->enroll_id,
                    'checked_at'  => $log->checked_at,
                    'type'        => $log->type,
                    'state'       => $log->state,
                    'device_uid'  => $log->device_uid ?? null,
                ];
            }),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
                'from'         => $logs->firstItem(),
                'to'           => $logs->lastItem(),
            ],
        ]);
    }

    /**
     * Resumen por empleado agrupado por día
     * GET /api/v1/attendance/employees/{employee}/summary
     */
    public function employeeSummary(AttendanceUser $employee, Request $request)
    {
        $from = $request->query('from') ?? now()->startOfMonth()->format('Y-m-d');
        $to   = $request->query('to')   ?? now()->endOfMonth()->format('Y-m-d');
        $perPage = min((int) $request->query('per_page', 31), 100);

        $allLogs = AttendanceLog::query()
            ->where('attendance_device_id', $employee->attendance_device_id)
            ->where('enroll_id', $employee->enroll_id)
            ->whereDate('checked_at', '>=', $from)
            ->whereDate('checked_at', '<=', $to)
            ->orderBy('checked_at', 'asc')
            ->get();

        $byDay = $allLogs->groupBy(fn($log) => Carbon::parse($log->checked_at)->format('Y-m-d'));

        $workedDays = $byDay->count();

        $totalSeconds = 0;
        foreach ($byDay as $dayLogs) {
            if ($dayLogs->count() >= 2) {
                $first = Carbon::parse($dayLogs->first()->checked_at);
                $last  = Carbon::parse($dayLogs->last()->checked_at);
                $totalSeconds += $first->diffInSeconds($last);
            }
        }

        $totalHours = round($totalSeconds / 3600, 1);

        $avgEntry = null;
        if ($workedDays > 0) {
            $totalMinutes = 0;
            foreach ($byDay as $dayLogs) {
                $first = Carbon::parse($dayLogs->first()->checked_at);
                $totalMinutes += ($first->hour * 60) + $first->minute;
            }
            $avgEntry = now()->startOfDay()->addMinutes($totalMinutes / $workedDays)->format('H:i');
        }

        $rows = $byDay->map(function (Collection $dayLogs, string $date) {
            $sorted = $dayLogs->sortBy('checked_at')->values();

            $firstLog = $sorted->first();
            $lastLog  = $sorted->last();

            $entryAt = Carbon::parse($firstLog->checked_at);
            $exitAt  = ($sorted->count() >= 2) ? Carbon::parse($lastLog->checked_at) : null;

            $hours = $exitAt
                ? round($entryAt->diffInMinutes($exitAt) / 60, 2)
                : 0;

            return [
                'date'        => $date,
                'day_name'    => $entryAt->locale('es')->translatedFormat('l'),
                'entry_at'    => $entryAt->format('Y-m-d H:i:s'),
                'exit_at'     => $exitAt?->format('Y-m-d H:i:s'),
                'hours'       => $hours,
                'count'       => $sorted->count(),
                'device_uid'  => $firstLog->device_uid ?? null,
                'logs'        => $sorted->map(fn($log) => [
                    'id'         => $log->id,
                    'checked_at' => $log->checked_at,
                    'type'       => $log->type,
                    'state'      => $log->state,
                ])->values(),
            ];
        })
        ->values()
        ->sortByDesc('date')
        ->values();

        $page  = max((int) $request->query('page', 1), 1);
        $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'ok' => true,
            'employee' => [
                'id'                   => $employee->id,
                'name'                 => $employee->name,
                'enroll_id'            => $employee->enroll_id,
                'attendance_device_id' => $employee->attendance_device_id,
            ],
            'filters' => [
                'from'     => $from,
                'to'       => $to,
                'per_page' => $perPage,
            ],
            'kpis' => [
                'worked_days' => $workedDays,
                'total_hours' => $totalHours,
                'avg_entry'   => $avgEntry,
            ],
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Búsqueda de empleados para autocomplete en app
     * GET /api/v1/attendance/employees/search?q=...
     */
    public function searchEmployees(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $deviceId = $request->query('device_id');

        if (mb_strlen($q) < 2) {
            return response()->json([
                'ok' => true,
                'data' => [],
            ]);
        }

        $items = AttendanceUser::query()
            ->select(['id', 'name', 'enroll_id', 'attendance_device_id'])
            ->when($deviceId, fn($qq) => $qq->where('attendance_device_id', $deviceId))
            ->where('name', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($u) {
                return [
                    'id'                   => $u->id,
                    'name'                 => $u->name,
                    'enroll_id'            => $u->enroll_id,
                    'attendance_device_id' => $u->attendance_device_id,
                    'text'                 => $u->name . ' (Enroll: ' . $u->enroll_id . ')',
                ];
            });

        return response()->json([
            'ok' => true,
            'data' => $items,
        ]);
    }
}