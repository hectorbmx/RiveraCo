<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

require_once dirname(__DIR__).'/vendor/autoload.php';

/** This harness never reads DB credentials from .env or phpunit.xml. */
function mobileOrderFixture(string $path): array
{
    $root = dirname(__DIR__);
    $file = realpath($path);
    $base = realpath($root.'/storage/app/testing');
    if (! $file || ! $base || ! str_starts_with(strtolower($file), strtolower($base.DIRECTORY_SEPARATOR))) {
        throw new RuntimeException('Fixture fuera de storage/app/testing.');
    }
    $config = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $token = $config['token'] ?? '';
    $data = realpath($config['datadir'] ?? '');
    $expected = dirname($file).DIRECTORY_SEPARATOR.'data';
    if (! $data || strcasecmp(str_replace('\\', '/', $data), str_replace('\\', '/', $expected)) !== 0
        || ! preg_match('/^[a-f0-9]{32}$/D', $token) || basename(dirname($file)) !== 'mobile-order-'.$token
        || ($config['database'] ?? '') !== 'rivera_mobile_order_test_'.substr($token, 0, 12)
        || ! is_int($config['port'] ?? null) || $config['port'] < 1024 || $config['port'] > 65535
        || realpath($config['datadir'] ?? '') !== realpath(dirname($file).'/data')) {
        throw new RuntimeException('Fixture temporal inválida.');
    }

    return $config + ['root' => $root, 'directory' => dirname($file), 'path' => $file];
}

function mobileOrderPdo(array $config, bool $database = true): PDO
{
    $dsn = 'mysql:host=127.0.0.1;port='.$config['port'].';charset=utf8mb4';
    if ($database) {
        $dsn .= ';dbname='.$config['database'];
    }
    $pdo = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]);
    $actual = realpath((string) $pdo->query('SELECT @@datadir')->fetchColumn());
    if (! $actual || strtolower($actual) !== strtolower((string) realpath($config['datadir']))) {
        throw new RuntimeException('La conexión no pertenece a la instancia temporal. No se permite escribir.');
    }

    return $pdo;
}

function mobileOrderBoot(array $config): Illuminate\Foundation\Application
{
    foreach (['APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'APP_CONFIG_CACHE' => $config['directory'].'/no-config-cache.php',
        'APP_ROUTES_CACHE' => $config['directory'].'/no-routes-cache.php', 'DB_CONNECTION' => 'mysql',
        'DB_HOST' => '127.0.0.1', 'DB_PORT' => (string) $config['port'], 'DB_DATABASE' => $config['database'],
        'DB_USERNAME' => 'root', 'DB_PASSWORD' => '', 'DATABASE_URL' => '', 'DB_URL' => '', 'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync', 'TELESCOPE_ENABLED' => 'false', 'PULSE_ENABLED' => 'false'] as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    $app = require $config['root'].'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    config(['database.default' => 'mysql', 'database.connections.mysql' => [
        'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => $config['port'], 'database' => $config['database'],
        'username' => 'root', 'password' => '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '', 'strict' => true, 'engine' => 'InnoDB',
    ]]);
    Illuminate\Support\Facades\DB::purge('mysql');
    $actual = realpath((string) Illuminate\Support\Facades\DB::selectOne('SELECT @@datadir AS path')->path);
    if (! $actual || strtolower($actual) !== strtolower((string) realpath($config['datadir']))) {
        throw new RuntimeException('Laravel no está conectado a la instancia temporal.');
    }

    return $app;
}

function mobileOrderExpect(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function mobileOrderWait(callable $condition, string $message, float $seconds = 15): mixed
{
    $deadline = microtime(true) + $seconds;
    do {
        clearstatcache();
        if ($value = $condition()) {
            return $value;
        }
        usleep(25000);
    } while (microtime(true) < $deadline);
    throw new RuntimeException($message);
}

function mobileOrderSchema(PDO $pdo): void
{
    $tables = [
        'empleados' => 'id_Empleado BIGINT UNSIGNED PRIMARY KEY',
        'maquinas' => 'id BIGINT UNSIGNED PRIMARY KEY, nombre VARCHAR(100)',
        'empresa_servicio_preventivo_tipos' => 'id BIGINT UNSIGNED PRIMARY KEY, ambito VARCHAR(30), activo BOOLEAN',
        'productos' => 'id BIGINT UNSIGNED PRIMARY KEY, nombre VARCHAR(100), sku VARCHAR(40), unidad VARCHAR(20)',
        'almacenes' => 'id BIGINT UNSIGNED PRIMARY KEY, codigo VARCHAR(20), nombre VARCHAR(100), activo BOOLEAN',
        'inventario_stock' => 'id BIGINT UNSIGNED PRIMARY KEY, almacen_id BIGINT UNSIGNED, producto_id BIGINT UNSIGNED, stock_actual DECIMAL(12,3), stock_reservado DECIMAL(12,3)',
        'empresa_servicio_preventivo_materiales' => 'id BIGINT UNSIGNED PRIMARY KEY, servicio_preventivo_tipo_id BIGINT UNSIGNED, maquina_id BIGINT UNSIGNED NULL, activo BOOLEAN, orden INT, producto_id BIGINT UNSIGNED, cantidad_requerida DECIMAL(12,3), unidad VARCHAR(20) NULL',
        'mantenimientos' => 'id BIGINT UNSIGNED PRIMARY KEY, maquina_id BIGINT UNSIGNED NULL, vehiculo_id BIGINT UNSIGNED NULL, obra_id BIGINT UNSIGNED NULL, mecanico_id BIGINT UNSIGNED, servicio_preventivo_tipo_id BIGINT UNSIGNED, tipo VARCHAR(30), categoria_mantenimiento VARCHAR(100), estatus VARCHAR(30), orden_servicio_folio VARCHAR(40) NULL, orden_servicio_generada_at DATETIME NULL, orden_servicio_generada_por BIGINT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL',
        'mantenimiento_abastecimientos' => 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, mantenimiento_id BIGINT UNSIGNED',
        'mantenimiento_materiales' => 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, mantenimiento_id BIGINT UNSIGNED, material_preventivo_id BIGINT UNSIGNED, producto_id BIGINT UNSIGNED, producto_nombre_snapshot VARCHAR(100), producto_sku_snapshot VARCHAR(40) NULL, unidad VARCHAR(20) NULL, cantidad_requerida DECIMAL(12,3), orden INT, incluido BOOLEAN, motivo_exclusion VARCHAR(255) NULL, created_at DATETIME NULL, updated_at DATETIME NULL, FOREIGN KEY (mantenimiento_id) REFERENCES mantenimientos(id)',
    ];
    foreach ($tables as $name => $columns) {
        $pdo->exec("CREATE TABLE `$name` ($columns) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $pdo->exec("INSERT INTO empleados VALUES (11)");
    $pdo->exec("INSERT INTO maquinas VALUES (17, 'Equipo aislado')");
    $pdo->exec("INSERT INTO empresa_servicio_preventivo_tipos VALUES (1, 'maquinaria', 1)");
    $pdo->exec("INSERT INTO productos VALUES (5, 'Aceite de prueba', 'ACE-TEST', 'L'), (6, 'Filtro de prueba', 'FIL-TEST', 'PZA')");
    $pdo->exec("INSERT INTO almacenes VALUES (1, 'GL', 'Giralda prueba', 1)");
    $pdo->exec("INSERT INTO inventario_stock VALUES (1, 1, 5, 100, 10), (2, 1, 6, 50, 2)");
    $pdo->exec("INSERT INTO empresa_servicio_preventivo_materiales VALUES (1, 1, NULL, 1, 0, 5, 10, 'L'), (2, 1, NULL, 1, 1, 6, 1, 'PZA')");
    $pdo->exec("CREATE TRIGGER mobile_order_fail BEFORE INSERT ON mantenimiento_materiales FOR EACH ROW BEGIN IF @mobile_fail_snapshot = 1 AND NEW.material_preventivo_id = 2 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'mobile_order_test_snapshot_failure'; END IF; END");
}

function mobileOrderScenario(array $config, PDO $pdo, string $name, int $id, bool $failFirst = false, bool $editSecond = false): array
{
    $directory = $config['directory'].'/'.$name;
    mkdir($directory);
    $pdo->exec("INSERT INTO mantenimientos (id, maquina_id, mecanico_id, servicio_preventivo_tipo_id, tipo, categoria_mantenimiento, estatus) VALUES ($id, 17, 11, 1, 'programado', 'Original', 'borrador')");
    $worker = $config['root'].'/tests/Support/MobileOrderConcurrencyWorker.php';
    $first = new Process([PHP_BINARY, $worker, $config['path'], $name, 'A', 'generate', $failFirst ? '1' : '0'], $config['root']);
    $second = new Process([PHP_BINARY, $worker, $config['path'], $name, 'B', $editSecond ? 'edit' : 'generate', '0'], $config['root']);
    file_put_contents($directory.'/service-id', (string) $id);
    $first->setTimeout(60);
    $second->setTimeout(60);
    try {
        $first->start();
        mobileOrderWait(fn () => is_file($directory.'/locked'), 'La primera petición no adquirió el bloqueo: '.$first->getErrorOutput());
        $second->start();
        mobileOrderWait(fn () => is_file($directory.'/B.lock-connection.json'), 'La segunda petición no intentó bloquear: '.$second->getErrorOutput());
        $a = json_decode(file_get_contents($directory.'/A.lock-connection.json'), true, 512, JSON_THROW_ON_ERROR);
        $b = json_decode(file_get_contents($directory.'/B.lock-connection.json'), true, 512, JSON_THROW_ON_ERROR);
        $waitQuery = $pdo->prepare('SELECT w.requesting_trx_id, w.blocking_trx_id, r.trx_mysql_thread_id AS waiting_connection, b.trx_mysql_thread_id AS blocking_connection FROM information_schema.INNODB_LOCK_WAITS w JOIN information_schema.INNODB_TRX r ON r.trx_id = w.requesting_trx_id JOIN information_schema.INNODB_TRX b ON b.trx_id = w.blocking_trx_id WHERE r.trx_mysql_thread_id = ? AND b.trx_mysql_thread_id = ?');
        $witness = mobileOrderWait(function () use ($waitQuery, $a, $b, $pdo, $directory) {
            $waitQuery->execute([$b['connection_id'], $a['connection_id']]);
            file_put_contents($directory.'/locks-observed.json', json_encode([
                'waits' => $pdo->query('SELECT * FROM information_schema.INNODB_LOCK_WAITS')->fetchAll(PDO::FETCH_ASSOC),
                'transactions' => $pdo->query('SELECT trx_id, trx_state, trx_mysql_thread_id, trx_query FROM information_schema.INNODB_TRX')->fetchAll(PDO::FETCH_ASSOC),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            return $waitQuery->fetch(PDO::FETCH_ASSOC);
        }, 'No se observó espera InnoDB entre las peticiones.');
        file_put_contents($directory.'/release', 'release');
        $first->wait();
        $second->wait();
        mobileOrderExpect($first->isSuccessful() && $second->isSuccessful(), 'Falló un worker: '.$first->getErrorOutput().$second->getErrorOutput());
        $ra = json_decode(file_get_contents($directory.'/A.result.json'), true, 512, JSON_THROW_ON_ERROR);
        $rb = json_decode(file_get_contents($directory.'/B.result.json'), true, 512, JSON_THROW_ON_ERROR);
        mobileOrderExpect($ra['status'] === ($failFirst ? 500 : 200), 'HTTP inesperado en A: '.json_encode($ra));
        mobileOrderExpect($rb['status'] === ($editSecond ? 409 : 200), 'HTTP inesperado en B: '.json_encode($rb));
        $header = $pdo->query("SELECT * FROM mantenimientos WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
        $materials = $pdo->query("SELECT material_preventivo_id, producto_id, cantidad_requerida, producto_nombre_snapshot FROM mantenimiento_materiales WHERE mantenimiento_id=$id ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
        mobileOrderExpect($header['estatus'] === 'orden_generada' && $header['categoria_mantenimiento'] === 'Original', 'Estado/cabecera incorrectos.');
        mobileOrderExpect((int) $header['orden_servicio_generada_por'] === ($failFirst ? 81 : 80), 'Se sobrescribió el autor o no ocurrió rollback.');
        mobileOrderExpect($header['orden_servicio_generada_at'] !== null && preg_match('/^OSM-\d{2}-'.sprintf('%06d', $id).'$/D', $header['orden_servicio_folio']) === 1, 'Metadata de orden inválida.');
        mobileOrderExpect(count($materials) === 2 && array_map('intval', array_column($materials, 'material_preventivo_id')) === [1, 2], 'Snapshot duplicado o incompleto.');
        mobileOrderExpect(array_map('floatval', array_column($materials, 'cantidad_requerida')) === [10.0, 1.0], 'Cantidades del snapshot incorrectas.');
        if ($failFirst) {
            mobileOrderExpect(str_contains((string) file_get_contents($directory.'/A.log'), 'mobile_order_test_snapshot_failure'), 'El 500 no corresponde al fallo controlado del segundo material.');
        } else {
            mobileOrderExpect(($ra['body']['data']['orden_servicio_folio'] ?? null) === $header['orden_servicio_folio'], 'Folio de A distinto del persistido.');
        }
        if (! $editSecond) {
            mobileOrderExpect(($rb['body']['data']['orden_servicio_folio'] ?? null) === $header['orden_servicio_folio'], 'Folio de B distinto del persistido.');
            mobileOrderExpect(count($rb['body']['data']['materiales'] ?? []) === 2, 'Respuesta B sin snapshot completo.');
        }

        return ['scenario' => $name, 'http' => [$ra['status'], $rb['status']], 'lock_wait' => $witness,
            'folio' => $header['orden_servicio_folio'], 'author' => (int) $header['orden_servicio_generada_por'], 'materials' => $materials];
    } finally {
        file_put_contents($directory.'/release', 'release');
        foreach ([$first, $second] as $process) {
            if ($process->isRunning()) {
                $process->stop(3);
            }
        }
    }
}

function mobileOrderMain(string $path): void
{
    $config = mobileOrderFixture($path);
    $pdo = mobileOrderWait(function () use ($config) {
        try {
            return mobileOrderPdo($config, false);
        } catch (PDOException) {
            return false;
        }
    }, 'La instancia temporal no aceptó conexión.');
    $pdo->exec('CREATE DATABASE `'.$config['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo = mobileOrderPdo($config);
    mobileOrderSchema($pdo);
    $engines = $pdo->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()")->fetchAll(PDO::FETCH_KEY_PAIR);
    mobileOrderExpect(count($engines) === 10 && count(array_filter($engines, fn ($engine) => $engine === 'InnoDB')) === 10, 'Todas las tablas deben usar InnoDB.');
    $stock = $pdo->query('SELECT * FROM inventario_stock ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $results = [mobileOrderScenario($config, $pdo, 'double-generation', 1),
        mobileOrderScenario($config, $pdo, 'rollback-and-retry', 2, true),
        mobileOrderScenario($config, $pdo, 'generation-and-edit', 3, false, true)];
    mobileOrderExpect($stock === $pdo->query('SELECT * FROM inventario_stock ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), 'La generación alteró inventario.');
    mobileOrderExpect((int) $pdo->query('SELECT COUNT(*) FROM mantenimiento_abastecimientos')->fetchColumn() === 0, 'Se crearon solicitudes de abastecimiento.');
    $report = ['ok' => true, 'server' => $pdo->query('SELECT VERSION()')->fetchColumn(), 'engines' => $engines,
        'closed_at' => (new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->format(DATE_ATOM),
        'stock_unchanged' => true, 'abastecimientos' => 0, 'scenarios' => $results];
    file_put_contents($config['directory'].'/report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        if (($argv[2] ?? '') === '--shutdown') {
            mobileOrderPdo(mobileOrderFixture($argv[1] ?? ''), false)->exec('SHUTDOWN');
            echo 'Instancia temporal verificada y apagada.'.PHP_EOL;
        } else {
            mobileOrderMain($argv[1] ?? '');
        }
    } catch (Throwable $error) {
        fwrite(STDERR, $error->getMessage().PHP_EOL);
        exit(1);
    }
}
