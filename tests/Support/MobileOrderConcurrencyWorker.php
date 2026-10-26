<?php

declare(strict_types=1);

use App\Models\Mantenimiento;
use App\Models\User;
use App\Models\UsuarioApp;
use App\Services\Mobile\AppMobileAccessService;
use App\Services\Mobile\ServicioProgramadoConsultaService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

require dirname(__DIR__, 2).'/tools/test_mobile_order_concurrency.php';

try {
    $config = mobileOrderFixture($argv[1] ?? '');
    $scenario = $argv[2] ?? '';
    $role = $argv[3] ?? '';
    $operation = $argv[4] ?? '';
    if (! in_array($scenario, ['double-generation', 'rollback-and-retry', 'generation-and-edit'], true)
        || ! in_array($role, ['A', 'B'], true) || ! in_array($operation, ['generate', 'edit'], true)) {
        throw new RuntimeException('Worker fuera del alcance de la prueba.');
    }
    mobileOrderPdo($config);
    $app = mobileOrderBoot($config);
    $directory = $config['directory'].'/'.$scenario;
    config(['logging.default' => 'single', 'logging.channels.single.path' => $directory.'/'.$role.'.log']);
    $user = Mockery::mock(User::class)->makePartial();
    $user->id = $role === 'A' ? 80 : 81;
    $user->shouldReceive('hasDeniedPermission')->andReturn(false);
    $user->shouldReceive('checkPermissionTo')->andReturn(false);
    Gate::define('app.mecanico.access', fn () => true);
    Auth::guard('sanctum')->setUser($user);
    Auth::shouldUse('sanctum');
    $access = Mockery::mock(AppMobileAccessService::class);
    $access->shouldReceive('usuarioActivo')->andReturn(new UsuarioApp(['user_id' => $user->id, 'empleado_id' => 11, 'is_active' => true]));
    $app->instance(AppMobileAccessService::class, $access);
    $app->instance(ThrottleRequests::class, new class {
        public function handle($request, Closure $next, ...$arguments) { return $next($request); }
    });
    // Projection is controlled here; route binding, ownership, transaction, catalog and snapshot are real.
    $projection = Mockery::mock(ServicioProgramadoConsultaService::class);
    $projection->shouldReceive('consulta')->andReturnUsing(fn () => Mantenimiento::query());
    $projection->shouldReceive('detalle')->andReturnUsing(fn (Mantenimiento $service) => [
        'id' => $service->id, 'orden_servicio_folio' => $service->orden_servicio_folio,
        'materiales' => $service->materialesOrden()->get()->toArray(),
    ]);
    $app->instance(ServicioProgramadoConsultaService::class, $projection);
    if (($argv[5] ?? '') === '1') {
        DB::statement('SET @mobile_fail_snapshot = 1');
    }
    $connection = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
    file_put_contents($directory.'/'.$role.'.connection.json', json_encode(['connection_id' => $connection], JSON_THROW_ON_ERROR));
    DB::connection()->beforeExecuting(function (string $sql, array $bindings, $connection) use ($directory, $role) {
        if (str_contains(strtolower($sql), 'for update') && str_contains($sql, 'mantenimientos')) {
            file_put_contents($directory.'/'.$role.'.lock-connection.json', json_encode([
                'connection_id' => (int) $connection->getPdo()->query('SELECT CONNECTION_ID()')->fetchColumn(),
                'sql' => $sql,
            ], JSON_THROW_ON_ERROR));
        }
    });
    if ($role === 'A') {
        $held = false;
        DB::listen(function (QueryExecuted $query) use ($directory, &$held) {
            if (! $held && str_contains(strtolower($query->sql), 'for update') && str_contains($query->sql, 'mantenimientos')) {
                $held = true;
                file_put_contents($directory.'/locked', 'locked');
                mobileOrderWait(fn () => is_file($directory.'/release'), 'No se liberó la barrera del bloqueo.', 45);
            }
        });
    }
    $id = (int) file_get_contents($directory.'/service-id');
    $url = '/api/v1/mecanico/servicios/'.$id.($operation === 'generate' ? '/generar-orden' : '');
    $request = Request::create($url, $operation === 'generate' ? 'POST' : 'PATCH', [], [], [], [
        'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json',
    ], $operation === 'generate' ? '{}' : '{"categoria_mantenimiento":"Cambio concurrente"}');
    $kernel = $app->make(Kernel::class);
    $response = $kernel->handle($request);
    $result = ['status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true)];
    file_put_contents($directory.'/'.$role.'.result.json', json_encode($result, JSON_THROW_ON_ERROR));
    $kernel->terminate($request, $response);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
}
