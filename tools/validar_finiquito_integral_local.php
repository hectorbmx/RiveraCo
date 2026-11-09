<?php

// Prueba persistente únicamente en MySQL local y sobre obras de simulación identificadas.
use App\Models\Obra;
use App\Models\User;
use App\Services\Obras\FiniquitoMaterialConsultaService;
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
$user = User::findOrFail(1); $obra = null; $entrega = null;
foreach (Obra::where('nombre', 'like', 'PRUEBA LOCAL DEVOLUCION%')->orderBy('id')->get() as $candidate) {
    $item = app(MaterialDevolucionSaldoService::class)->porObra($candidate->id)['entregas']
        ->first(fn ($e) => $e->origen_tipo === 'recepcion_directa' && ! $e->requiere_revision && $e->cantidad_disponible_solicitar >= 0.004);
    if ($item) { $obra = $candidate; $entrega = $item; break; }
}
if (! $entrega) throw new RuntimeException('No hay una entrega de simulación con saldo suficiente.');
$consulta = app(FiniquitoMaterialConsultaService::class);
$find = fn ($f) => $f['entregas']->first(fn ($r) => $r['entrega']->origen_tipo === $entrega->origen_tipo && (int) $r['entrega']->origen_id === (int) $entrega->origen_id);
$antes = $find($consulta->consultar($obra));
if (! $antes || $antes['requiere_revision']) throw new RuntimeException('El origen de prueba requiere revisión.');
$tag = 'PRUEBA LOCAL FINIQUITO 19.23 '.Str::upper(Str::random(8));
$solicitud = app(ObraDevolucionSolicitudService::class)->crear($obra, $user, ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'observaciones' => $tag,
    'partidas' => [['origen_tipo' => $entrega->origen_tipo, 'origen_id' => $entrega->origen_id, 'cantidad' => 0.004, 'condicion' => 'bueno']]]);
$partida = $solicitud->detalles()->sole(); $recepciones = app(ObraDevolucionRecepcionService::class);
$datos = ['almacen_id' => $entrega->almacen_id, 'fecha_recepcion' => now()->toDateString(), 'observaciones' => $tag];
$aplicada = $recepciones->confirmar($solicitud, $user, $datos + ['operacion_uuid' => (string) Str::uuid(), 'partidas' => [['id' => $partida->id, 'cantidad' => 0.002, 'condicion' => 'bueno']]]);
app(ObraDevolucionAplicacionService::class)->aplicar($aplicada, $user);
$pendiente = $recepciones->confirmar($solicitud, $user, $datos + ['operacion_uuid' => (string) Str::uuid(), 'partidas' => [['id' => $partida->id, 'cantidad' => 0.001, 'condicion' => 'desgastado', 'observaciones' => 'Prueba: condición diferente']]]);
$finiquito = $consulta->consultar($obra); $despues = $find($finiquito);
if (! $despues || $despues['requiere_revision'] || abs($despues['entrega']->cantidad_devuelta_confirmada - $antes['entrega']->cantidad_devuelta_confirmada - 0.002) > 0.00001
    || abs($despues['fisico_pendiente_aplicar'] - $antes['fisico_pendiente_aplicar'] - 0.001) > 0.00001
    || abs($despues['pendiente_recibir'] - $antes['pendiente_recibir'] - 0.001) > 0.00001
    || abs($despues['sin_devolucion_fisica'] - $antes['sin_devolucion_fisica'] + 0.003) > 0.00001) throw new RuntimeException('El finiquito no concilia las cantidades de prueba.');
$html = view('obras.devoluciones.finiquito_imprimir', ['obra' => $obra, 'finiquito' => $finiquito, 'generadoEn' => now()])->render();
foreach ([$solicitud->folio, $aplicada->folio, $pendiente->folio, 'Condición diferente; pendiente de aclarar'] as $expected) if (! str_contains($html, $expected)) throw new RuntimeException('Falta un dato de prueba en la impresión.');
echo json_encode(['obra' => $obra->id, 'solicitud' => $solicitud->folio, 'recepcion_aplicada' => $aplicada->folio, 'recepcion_pendiente' => $pendiente->folio, 'movimiento' => $aplicada->detalles()->sole()->inventario_movimiento_id, 'etiqueta' => $tag], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
