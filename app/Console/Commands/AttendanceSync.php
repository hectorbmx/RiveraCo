<?php

namespace App\Console\Commands;

use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceCheckpoint;
use App\Models\AttendanceLog;
use App\Models\AttendanceUser;
use App\Services\Attendance\ZkDeviceClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AttendanceSync extends Command
{
    protected $signature = 'attendance:sync {deviceId?} {--users : Sincroniza también usuarios}';
    protected $description = 'Sincroniza checadas desde relojes biométricos (ZK compatibles)';

    public function handle(): int
    {
        $deviceId = $this->argument('deviceId');

        $devices = AttendanceDevice::query()
            ->where('is_active', true)
            ->when($deviceId, fn($q) => $q->whereKey($deviceId))
            ->get();

        if ($devices->isEmpty()) {
            $this->error('No hay dispositivos activos (o deviceId inválido).');
            return self::FAILURE;
        }

        foreach ($devices as $device) {
            $this->info("== Sync device #{$device->id} {$device->name} ({$device->ip}:{$device->port}) ==");

            $client = new ZkDeviceClient($device->ip, (int)$device->port);

            // Guardar serial si no está
            if (!$device->serial) {
                $serial = $client->fetchSerial();
                if ($serial) {
                    $device->update(['serial' => $serial]);
                    $this->line("Serial actualizado: {$serial}");
                }
            }

            $checkpoint = AttendanceDeviceCheckpoint::firstOrCreate(
                ['attendance_device_id' => $device->id],
                ['last_timestamp' => null]
            );

            $lastTs = $checkpoint->last_timestamp ? Carbon::parse($checkpoint->last_timestamp) : null;

            $attendance = $client->fetchAttendance();
            $this->line('Recibidas (raw): ' . count($attendance));

            // Filtrar incremental
            $filtered = [];
            $maxSeen = $lastTs;

            foreach ($attendance as $row) {
                $ts = Carbon::parse($row['timestamp']);
                if ($lastTs && $ts->lte($lastTs)) {
                    continue;
                }
                $filtered[] = $row;
                if (!$maxSeen || $ts->gt($maxSeen)) $maxSeen = $ts;
            }

            $this->line('Nuevas (filtradas): ' . count($filtered));

            DB::transaction(function () use ($device, $filtered, $checkpoint, $maxSeen) {
                // Insertar deduplicando por unique index (usamos insertOrIgnore)
                $now = now();
                $rows = array_map(function ($r) use ($device, $now) {
                    return [
                        'attendance_device_id' => $device->id,
                        'device_uid' => (int)$r['uid'],
                        'enroll_id' => (int)$r['id'],
                        'state' => isset($r['state']) ? (int)$r['state'] : null,
                        'type' => isset($r['type']) ? (int)$r['type'] : null,
                        'checked_at' => $r['timestamp'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }, $filtered);

                if (!empty($rows)) {
                    // AttendanceLog::query()->insertOrIgnore($rows);
                    $chunkSize = 1000;

                        foreach (array_chunk($rows, $chunkSize) as $chunk) {
                            AttendanceLog::query()->insertOrIgnore($chunk);
                        }
                }

                if ($maxSeen) {
                    $checkpoint->update(['last_timestamp' => $maxSeen->toDateTimeString()]);
                }
            });

            if ($this->option('users')) {
                $users = $client->fetchUsers();
                $this->line('Usuarios (raw): ' . count($users));

                DB::transaction(function () use ($device, $users) {
                    foreach ($users as $u) {
                        AttendanceUser::updateOrCreate(
                            ['attendance_device_id' => $device->id, 'enroll_id' => (int)$u['userid']],
                            [
                                'device_uid' => (int)$u['uid'],
                                'name' => $u['name'] ?? null,
                                'cardno' => $u['cardno'] ?? null,
                            ]
                        );
                    }
                });

                // Nota: intencionalmente NO guardamos password.
            }

            $this->info("OK device #{$device->id}");
        }

        return self::SUCCESS;
    }
}