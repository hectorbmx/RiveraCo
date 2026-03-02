<?php
namespace App\Services\Attendance;

use Rats\Zkteco\Lib\ZKTeco;

class ZkDeviceClient
{
    public function __construct(
        private readonly string $ip,
        private readonly int $port = 4370 // la lib usa 4370; mantenemos port para futuro
    ) {}

    public function fetchSerial(): ?string
    {
        $zk = new ZKTeco($this->ip);
        if (!$zk->connect()) return null;

        try {
            return $zk->serialNumber();
        } finally {
            $zk->disconnect();
        }
    }

    /** @return array<int, array{uid:int,id:int,state:int,timestamp:string,type:int}> */
    public function fetchAttendance(): array
    {
        $zk = new ZKTeco($this->ip);
        if (!$zk->connect()) return [];

        try {
            try { $zk->disableDevice(); } catch (\Throwable $e) {}
            $att = $zk->getAttendance();
            return is_array($att) ? $att : [];
        } finally {
            try { $zk->enableDevice(); } catch (\Throwable $e) {}
            $zk->disconnect();
        }
    }

    /** @return array<int, array{uid:int,userid:int,name:?string,role:int,password:?string,cardno:?string}> */
    public function fetchUsers(): array
    {
        $zk = new ZKTeco($this->ip);
        if (!$zk->connect()) return [];

        try {
            try { $zk->disableDevice(); } catch (\Throwable $e) {}
            $users = $zk->getUser();
            return is_array($users) ? $users : [];
        } finally {
            try { $zk->enableDevice(); } catch (\Throwable $e) {}
            $zk->disconnect();
        }
    }
}