<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Rats\Zkteco\Lib\ZKTeco;

$ip = '192.168.1.244';

$zk = new ZKTeco($ip);

if (!$zk->connect()) {
    fwrite(STDERR, "ERROR: No conecta con el reloj $ip\n");
    exit(1);
}

echo "OK: Conectado a $ip\n";

try {
    $attendance = $zk->getAttendance();
    echo "OK: Checadas recibidas = " . count($attendance) . PHP_EOL;

    foreach (array_slice($attendance, 0, 5) as $i => $row) {
        echo "---- Row #" . ($i+1) . " ----\n";
        print_r($row);
    }
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR leyendo datos: " . $e->getMessage() . "\n");
    exit(2);
} finally {
    $zk->disconnect();
    echo "OK: Desconectado\n";
}