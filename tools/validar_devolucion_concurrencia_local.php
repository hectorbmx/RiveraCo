<?php

// Solo para pruebas identificadas y persistentes en una obra local de simulación.
use App\Models\Obra;
use App\Models\ObraDevolucionRecepcion;
use App\Models\User;
use App\Services\Obras\MaterialDevolucionSaldoService;
use App\Services\Obras\ObraDevolucionAplicacionService;
use App\Services\Obras\ObraDevolucionRecepcionService;
use App\Services\Obras\ObraDevolucionSolicitudService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (app()->environment() !== 'local' || DB::getDriverName() !== 'mysql' || ! in_array(config('database.connections.mysql.host'), ['localhost', '127.0.0.1', '::1'], true)) throw new RuntimeException('La validación solo se permite en MySQL local.');
Gate::before(fn ($user, $permission) => in_array($permission, ['obra.devoluciones.view.access', 'obra.devoluciones.receive.access', 'obra.devoluciones.create.access'], true) ? true : null);
$user = User::findOrFail(1);

if (($argv[1] ?? '') === 'worker') {
    $receipt = ObraDevolucionRecepcion::findOrFail((int) $argv[2]);
    $dir = $argv[3]; $number = (int) $argv[4];
    file_put_contents($dir.'/listo-'.$number, 'listo');
    $deadline = microtime(true) + 15;
    while (! file_exists($dir.'/iniciar')) { if (microtime(true) > $deadline) throw new RuntimeException('Tiempo agotado al esperar el inicio.'); usleep(30000); }
    app(ObraDevolucionAplicacionService::class)->aplicar($receipt, $user);
    echo 'OK';
    exit;
}

$obras = Obra::where('nombre', 'like', 'PRUEBA LOCAL DEVOLUCION%')->orderBy('id')->get();
$entrega = null; $obra = null;
foreach ($obras as $candidate) {
    $item = app(MaterialDevolucionSaldoService::class)->porObra($candidate->id)['entregas']
        ->first(fn ($e) => $e->origen_tipo === 'recepcion_directa' && ! $e->requiere_revision && $e->cantidad_disponible_solicitar >= 0.001);
    if ($item) { $obra = $candidate; $entrega = $item; break; }
}
if (! $entrega) throw new RuntimeException('No hay una entrega de simulación con saldo suficiente.');
$tag = 'PRUEBA LOCAL CONCURRENCIA 19.20 '.Str::upper(Str::random(8));
$solicitud = app(ObraDevolucionSolicitudService::class)->crear($obra, $user, ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'observaciones' => $tag,
    'partidas' => [['origen_tipo' => $entrega->origen_tipo, 'origen_id' => $entrega->origen_id, 'cantidad' => 0.001, 'condicion' => 'bueno']]]);
$receipt = app(ObraDevolucionRecepcionService::class)->confirmar($solicitud, $user, ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => $entrega->almacen_id,
    'fecha_recepcion' => now()->toDateString(), 'observaciones' => $tag,
    'partidas' => [['id' => $solicitud->detalles()->sole()->id, 'cantidad' => 0.001, 'condicion' => 'bueno']]]);
$stock = DB::table('inventario_stock')->where('almacen_id', $receipt->almacen_id)->where('producto_id', $entrega->producto_id)->first();
$stockAntes = (float) ($stock->stock_actual ?? 0);
$dir = storage_path('app/validacion-devolucion-'.Str::lower(Str::random(12)));
if (! mkdir($dir, 0777, true)) throw new RuntimeException('No se pudo crear el directorio de prueba.');
$processes = [];
DB::beginTransaction();
try {
    Obra::whereKey($obra->id)->lockForUpdate()->firstOrFail();
    for ($i = 1; $i <= 2; $i++) {
        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' worker '.$receipt->id.' '.escapeshellarg($dir).' '.$i;
        $processes[] = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$i]);
        if (! is_resource(end($processes))) throw new RuntimeException('No se pudo iniciar un proceso de prueba.');
        fclose($pipes[$i][0]);
    }
    $deadline = microtime(true) + 15;
    while (! file_exists($dir.'/listo-1') || ! file_exists($dir.'/listo-2')) { if (microtime(true) > $deadline) throw new RuntimeException('Los procesos no quedaron listos.'); usleep(30000); }
    file_put_contents($dir.'/iniciar', 'iniciar');
    usleep(200000);
    DB::commit();
} catch (Throwable $e) {
    if (DB::transactionLevel()) DB::rollBack();
    file_put_contents($dir.'/iniciar', 'abortar');
    throw $e;
}
foreach ($processes as $i => $process) {
    $out = stream_get_contents($pipes[$i + 1][1]); $err = stream_get_contents($pipes[$i + 1][2]);
    fclose($pipes[$i + 1][1]); fclose($pipes[$i + 1][2]);
    if (proc_close($process) !== 0 || trim($out) !== 'OK') throw new RuntimeException('Falló un proceso: '.$err.$out);
}
$receipt->refresh(); $detail = $receipt->detalles()->sole();
$stockDespues = (float) DB::table('inventario_stock')->where('almacen_id', $receipt->almacen_id)->where('producto_id', $entrega->producto_id)->value('stock_actual');
$count = DB::table('obra_devolucion_recepcion_detalles')->where('recepcion_id', $receipt->id)->whereNotNull('inventario_movimiento_id')->count();
if ($receipt->estado !== 'aplicada' || $count !== 1 || ! $detail->inventario_documento_detalle_id || abs($stockDespues - $stockAntes - 0.001) > 0.00001) throw new RuntimeException('La concurrencia no conservó un único movimiento y saldo correcto.');
echo json_encode(['obra' => $obra->id, 'solicitud' => $solicitud->folio, 'recepcion' => $receipt->folio, 'movimiento' => $detail->inventario_movimiento_id, 'cantidad' => 0.001, 'resultado' => 'dos reintentos, una aplicación'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
