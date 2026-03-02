<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use App\Models\AttendanceLog;
use App\Models\AttendanceUser;
use Illuminate\Http\Request;

class AttendanceIngestController extends Controller
{
    public function ingest(string $serial, Request $request)
    {
        // Auth simple por token (puedes hacerlo por device)
        $token = $request->header('X-Device-Token');
        if ($token !== config('services.attendance.device_token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

       $ip   = $request->input('device_ip');
$port = (int) $request->input('device_port', 4370);

// 1) si existe por serial -> update
$device = AttendanceDevice::where('serial', $serial)->first();

// 2) si no existe por serial, intenta por ip+port (por tu unique)
if (!$device && $ip) {
    $device = AttendanceDevice::where('ip', $ip)->where('port', $port)->first();
}

if ($device) {
    $device->update([
        'serial' => $serial, // asegura que quede ligado al serial
        'name' => $request->input('device_name', $device->name),
        'ip' => $ip ?? $device->ip,
        'port' => $port ?: $device->port,
        'is_active' => true,
    ]);
} else {
    $device = AttendanceDevice::create([
        'serial' => $serial,
        'name' => $request->input('device_name', 'Device '.$serial),
        'ip' => $ip,
        'port' => $port,
        'is_active' => true,
    ]);
}
        $logs = $request->input('logs', []);
        $users = $request->input('users', []);

        // Logs: insertOrIgnore en chunks
        $now = now();
        $rows = [];
        $maxCheckedAt = null;

        foreach ($logs as $r) {
            $rows[] = [
                'attendance_device_id' => $device->id,
                'device_uid' => (int)$r['device_uid'],
                'enroll_id'  => (int)$r['enroll_id'],
                'state'      => isset($r['state']) ? (int)$r['state'] : null,
                'type'       => isset($r['type']) ? (int)$r['type'] : null,
                'checked_at' => $r['checked_at'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (!$maxCheckedAt || $r['checked_at'] > $maxCheckedAt) {
                $maxCheckedAt = $r['checked_at'];
            }
        }

        $insertedBatches = 0;
        foreach (array_chunk($rows, 1000) as $chunk) {
            AttendanceLog::query()->insertOrIgnore($chunk);
            $insertedBatches++;
        }

        // Users: upsert básico (sin password)
        foreach ($users as $u) {
            AttendanceUser::updateOrCreate(
                ['attendance_device_id' => $device->id, 'enroll_id' => (int)$u['enroll_id']],
                [
                    'device_uid' => (int)$u['device_uid'],
                    'name' => $u['name'] ?? null,
                    'cardno' => $u['cardno'] ?? null,
                ]
            );
        }

        return response()->json([
            'ok' => true,
            'device_id' => $device->id,
            'received_logs' => count($logs),
            'received_users' => count($users),
            'max_checked_at' => $maxCheckedAt,
        ]);
    }
}