<?php

namespace Tests\Feature;

use App\Models\OrdenCompra;
use App\Services\Compras\CompraRecepcionDirectaService;
use App\Services\Obras\MaterialDevolucionSaldoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class MaterialDevolucionSaldoTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    protected function setUp(): void
    {
        parent::setUp(); $this->buildDirectReceiptDatabase();
        Schema::table('obra_requisicion_detalles', function (Blueprint $t) { $t->decimal('cantidad_solicitada', 15, 3)->default(0); });
        Schema::create('huentitan_salidas', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('almacen_id'); $t->unsignedBigInteger('obra_id'); $t->string('estado'); $t->string('folio'); $t->dateTime('fecha'); });
        Schema::create('huentitan_salida_detalles', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('huentitan_salida_id'); $t->unsignedBigInteger('producto_id'); $t->string('descripcion'); $t->string('unidad'); $t->decimal('cantidad_salida', 15, 3); $t->decimal('costo_unitario', 15, 4); $t->decimal('importe', 15, 2); });
        (require base_path('database/migrations/2026_11_06_020000_create_obra_devoluciones_tables.php'))->up();
        (require base_path('database/migrations/2026_11_06_030000_add_obra_devolucion_idempotency.php'))->up();
    }

    private function source(): int
    {
        return app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer())->detalles()->firstOrFail()->id;
    }

    private function requestLine(int $origin, string $status, float $quantity): array
    {
        $request = DB::table('obra_devoluciones')->insertGetId(['obra_id' => 41, 'estado' => $status]);
        $line = DB::table('obra_devolucion_detalles')->insertGetId(['devolucion_id' => $request, 'producto_id' => 1,
            'recepcion_directa_detalle_id' => $origin, 'descripcion' => 'Material', 'unidad' => 'PZA', 'cantidad_solicitada' => $quantity,
            'cantidad_entregada_snapshot' => 10, 'costo_unitario_mxn' => 150, 'importe_origen_mxn' => 1500, 'condicion_declarada' => 'bueno']);
        return [$request, $line];
    }

    private function receive(int $request, int $line, float $quantity, string $status = 'aplicada', ?string $evaluation = null): void
    {
        $receipt = DB::table('obra_devolucion_recepciones')->insertGetId(['devolucion_id' => $request, 'operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => '2026-11-06', 'estado' => $status]);
        DB::table('obra_devolucion_recepcion_detalles')->insert(['recepcion_id' => $receipt, 'devolucion_detalle_id' => $line, 'cantidad_recibida' => $quantity, 'condicion_recibida' => 'bueno', 'estado_evaluacion' => $evaluation, 'costo_unitario_mxn' => 150]);
    }

    public function test_confirmed_and_pending_quantities_are_subtracted_once(): void
    {
        $origin = $this->source();
        [$request, $line] = $this->requestLine($origin, 'recepcion_parcial', 4);
        $this->receive($request, $line, 2); $this->receive($request, $line, 1, 'borrador');
        $this->requestLine($origin, 'borrador', 3); $this->requestLine($origin, 'cancelada', 2);
        $result = app(MaterialDevolucionSaldoService::class)->porObra(41);
        $item = $result['entregas']->sole();
        $this->assertSame(2.0, $item->cantidad_devuelta_confirmada);
        $this->assertSame(2.0, $item->cantidad_comprometida);
        $this->assertSame(6.0, $item->cantidad_disponible_solicitar);
        $this->assertFalse($item->requiere_revision);
        $this->assertSame([], $result['inconsistencias']);
    }

    public function test_damaged_tool_received_pending_evaluation_is_physical_return_not_cost_settlement(): void
    {
        [$request, $line] = $this->requestLine($this->source(), 'recibida', 3);
        $this->receive($request, $line, 3, 'aplicada', 'pendiente_evaluacion');
        $item = app(MaterialDevolucionSaldoService::class)->porObra(41)['entregas']->sole();
        $this->assertSame(3.0, $item->cantidad_devuelta_confirmada);
        $this->assertSame(3.0, $item->cantidad_pendiente_evaluacion);
        $this->assertSame(7.0, $item->cantidad_disponible_solicitar);
        $this->assertSame(1500.0, (float) $item->importe_mxn);
    }

    public function test_oversubscription_is_flagged_and_not_presented_as_available(): void
    {
        $origin = $this->source(); $this->requestLine($origin, 'solicitada', 6); $this->requestLine($origin, 'solicitada', 5);
        $result = app(MaterialDevolucionSaldoService::class)->porObra(41); $item = $result['entregas']->sole();
        $this->assertTrue($item->requiere_revision);
        $this->assertNull($item->cantidad_disponible_solicitar);
        $this->assertCount(1, $result['inconsistencias']);
    }

    public function test_orphan_request_from_reverted_origin_is_reported(): void
    {
        $origin = $this->source(); $this->requestLine($origin, 'solicitada', 4);
        DB::table('compra_recepciones_directas')->where('id', 1)->update(['estado' => 'revertida']);
        $result = app(MaterialDevolucionSaldoService::class)->porObra(41);
        $this->assertCount(0, $result['entregas']);
        $this->assertCount(1, $result['inconsistencias']);
    }

    public function test_legacy_return_without_known_origin_blocks_automatic_available_balance(): void
    {
        $this->source();
        $doc = DB::table('inventario_documentos')->insertGetId(['folio' => 'DEV-ANTERIOR', 'tipo' => 'devolucion', 'estado' => 'aplicado', 'fecha' => now(), 'almacen_id' => 1, 'obra_id' => 41]);
        DB::table('inventario_movimientos')->insert(['documento_id' => $doc, 'almacen_id' => 1, 'obra_id' => 41, 'producto_id' => 1, 'fecha' => now(), 'tipo_movimiento' => 'in', 'cantidad' => 2, 'costo_unitario' => 150, 'saldo_cantidad' => 12]);
        $result = app(MaterialDevolucionSaldoService::class)->porObra(41);
        $this->assertCount(1, $result['devoluciones_sin_origen']);
        $this->assertNull($result['entregas']->sole()->cantidad_disponible_solicitar);
        $this->assertTrue($result['entregas']->sole()->requiere_revision);
        $html = view('obras.partials.materiales.tab', ['materialesObra' => $result])->render();
        $this->assertStringContainsString('Requiere revisión', $html);
        $this->assertStringContainsString('Devoluciones anteriores sin origen identificable: 1', $html);
        $this->assertStringContainsString('Material', $html);
        $this->assertStringContainsString('10.000 PZA', $html);
    }

    public function test_empty_materials_view_is_informative(): void
    {
        $html = view('obras.partials.materiales.tab', ['materialesObra' => app(MaterialDevolucionSaldoService::class)->porObra(41)])->render();
        $this->assertStringContainsString('No hay entregas aplicadas identificadas', $html);
        $this->assertStringNotContainsString('Hay movimientos o saldos que requieren revisión', $html);
    }

    public function test_request_reserves_balance_and_same_operation_does_not_duplicate(): void
    {
        $origin = $this->source(); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class);
        $payload = ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 4, 'condicion' => 'bueno']]];
        $request = $svc->crear(\App\Models\Obra::findOrFail(41), $this->buyer(), $payload);
        $again = $svc->crear(\App\Models\Obra::findOrFail(41), $this->buyer(), $payload);
        $this->assertSame($request->id, $again->id);
        $this->assertSame('solicitada', $request->estado);
        $this->assertSame(6.0, app(MaterialDevolucionSaldoService::class)->porObra(41)['entregas']->sole()->cantidad_disponible_solicitar);
        $this->assertSame(1, DB::table('obra_devoluciones')->count());
        $payload['operacion_uuid'] = (string) Str::uuid(); $payload['partidas'][0]['cantidad'] = 7;
        try { $svc->crear(\App\Models\Obra::findOrFail(41), $this->buyer(), $payload); $this->fail('Excede el saldo.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame(1, DB::table('obra_devoluciones')->count()); }
    }

    public function test_drafts_do_not_reserve_and_are_revalidated_when_presented(): void
    {
        $origin = $this->source(); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class); $obra = \App\Models\Obra::findOrFail(41);
        $payload = ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'borrador', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 6, 'condicion' => 'bueno']]];
        $draft = $svc->crear($obra, $this->buyer(), $payload);
        $this->assertSame(10.0, app(MaterialDevolucionSaldoService::class)->porObra(41)['entregas']->sole()->cantidad_disponible_solicitar);
        $payload['operacion_uuid'] = (string) Str::uuid(); $payload['accion'] = 'solicitar'; $payload['partidas'][0]['cantidad'] = 5;
        $svc->crear($obra, $this->buyer(), $payload);
        try { $svc->presentar($obra, $draft, $this->buyer()); $this->fail('El saldo cambió.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame('borrador', $draft->fresh()->estado); }
    }

    public function test_duplicate_origin_and_reused_operation_with_changed_data_are_rejected(): void
    {
        $origin = $this->source(); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class); $obra = \App\Models\Obra::findOrFail(41);
        $payload = ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 4, 'condicion' => 'bueno']]];
        $svc->crear($obra, $this->buyer(), $payload);
        $payload['partidas'][0]['cantidad'] = 3;
        try { $svc->crear($obra, $this->buyer(), $payload); $this->fail('La operación cambió.'); }
        catch (\Symfony\Component\HttpKernel\Exception\ConflictHttpException $e) { $this->assertSame(1, DB::table('obra_devoluciones')->count()); }
        $payload['operacion_uuid'] = (string) Str::uuid(); $payload['partidas'][] = $payload['partidas'][0];
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->crear($obra, $this->buyer(), $payload);
    }

    public function test_draft_quantities_can_be_corrected_before_presentation(): void
    {
        $origin = $this->source(); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class); $obra = \App\Models\Obra::findOrFail(41);
        $draft = $svc->crear($obra, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'borrador', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 6, 'condicion' => 'bueno']]]);
        $line = $draft->detalles()->firstOrFail();
        $svc->actualizarBorrador($obra, $draft, $this->buyer(), [$line->id => '3.000'], 'Observación corregida', [$line->id => 'desgastado'], [$line->id => 'Tiene desgaste']);
        $svc->presentar($obra, $draft, $this->buyer());
        $this->assertSame(3.0, (float) $line->fresh()->cantidad_solicitada);
        $this->assertSame('Observación corregida', $draft->fresh()->observaciones);
        $this->assertSame('desgastado', $line->fresh()->condicion_declarada);
        $this->assertSame('Tiene desgaste', $line->fresh()->observaciones);
        $this->assertSame(7.0, app(MaterialDevolucionSaldoService::class)->porObra(41)['entregas']->sole()->cantidad_disponible_solicitar);
    }

    public function test_presented_return_blocks_reversal_of_its_direct_receipt(): void
    {
        $origin = $this->source(); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class); $obra = \App\Models\Obra::findOrFail(41);
        $svc->crear($obra, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 2, 'condicion' => 'bueno']]]);
        $receipt = \App\Models\CompraRecepcionDirecta::findOrFail(1);
        try { app(\App\Services\Compras\CompraRecepcionDirectaReversionService::class)->revertir($receipt, $this->buyer(), 'Prueba'); $this->fail('La devolución está presentada.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame('aplicada', $receipt->fresh()->estado); }
    }

    public function test_presented_return_blocks_cancellation_of_general_source(): void
    {
        $doc = DB::table('inventario_documentos')->insertGetId(['folio' => 'SAL-NORMAL', 'tipo' => 'salida', 'estado' => 'aplicado', 'fecha' => now(), 'almacen_id' => 1, 'obra_id' => 41]);
        $movement = DB::table('inventario_movimientos')->insertGetId(['documento_id' => $doc, 'almacen_id' => 1, 'obra_id' => 41, 'producto_id' => 1, 'fecha' => now(), 'tipo_movimiento' => 'out', 'cantidad' => 4, 'costo_unitario' => 100, 'saldo_cantidad' => 6]);
        app(\App\Services\Obras\ObraDevolucionSolicitudService::class)->crear(\App\Models\Obra::findOrFail(41), $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [['origen_tipo' => 'salida_general', 'origen_id' => $movement, 'cantidad' => 2, 'condicion' => 'bueno']]]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('solicitudes de devolución presentadas');
        app(\App\Services\Inventario\InventarioDocumentoService::class)->cancelar(\App\Models\InventarioDocumento::findOrFail($doc));
    }

    public function test_creation_requires_permission_and_rejects_another_works_source(): void
    {
        $origin = $this->source(); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class);
        $payload = ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 1, 'condicion' => 'bueno']]];
        $denied = \Mockery::mock(\App\Models\User::class)->makePartial();
        $denied->shouldReceive('can')->with('obra.devoluciones.create.access')->andReturn(false);
        try { $svc->crear(\App\Models\Obra::findOrFail(41), $denied, $payload); $this->fail('Debe exigir permiso.'); }
        catch (\Illuminate\Auth\Access\AuthorizationException $e) { $this->assertSame(0, DB::table('obra_devoluciones')->count()); }
        DB::table('obras')->insert(['id' => 42, 'nombre' => 'Otra obra', 'estatus_nuevo' => 2]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->crear(\App\Models\Obra::findOrFail(42), $this->buyer(), $payload);
    }

    public function test_cancelling_unreceived_request_keeps_history_and_releases_balance(): void
    {
        $origin = $this->source(); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class); $obra = \App\Models\Obra::findOrFail(41);
        $request = $svc->crear($obra, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 4, 'condicion' => 'bueno']]]);
        $svc->cancelar($obra, $request, $this->buyer(), 'Se corrigió la solicitud');
        $this->assertSame('cancelada', $request->fresh()->estado);
        $this->assertSame('Se corrigió la solicitud', $request->fresh()->motivo_cancelacion);
        $this->assertSame(10.0, app(MaterialDevolucionSaldoService::class)->porObra(41)['entregas']->sole()->cantidad_disponible_solicitar);
        $this->assertSame(1, $request->detalles()->count());
    }

    public function test_cancellation_rejects_a_confirmed_receipt(): void
    {
        $origin = $this->source(); [$request, $line] = $this->requestLine($origin, 'solicitada', 3); $this->receive($request, $line, 1);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\Obras\ObraDevolucionSolicitudService::class)->cancelar(\App\Models\Obra::findOrFail(41), \App\Models\ObraDevolucion::findOrFail($request), $this->buyer(), 'Prueba');
    }

    public function test_draft_cannot_be_presented_after_source_is_reversed(): void
    {
        $origin = $this->source(); $obra = \App\Models\Obra::findOrFail(41); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class);
        $draft = $svc->crear($obra, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'borrador', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 2, 'condicion' => 'bueno']]]);
        app(\App\Services\Compras\CompraRecepcionDirectaReversionService::class)->revertir(\App\Models\CompraRecepcionDirecta::findOrFail(1), $this->buyer(), 'Corrección');
        try { $svc->presentar($obra, $draft, $this->buyer()); $this->fail('El origen ya fue revertido.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame('borrador', $draft->fresh()->estado); }
    }

    public function test_draft_cannot_be_presented_if_source_cost_changed(): void
    {
        $origin = $this->source(); $obra = \App\Models\Obra::findOrFail(41); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class);
        $draft = $svc->crear($obra, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'borrador', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 2, 'condicion' => 'bueno']]]);
        DB::table('compra_recepcion_directa_detalles')->where('id', $origin)->update(['costo_unitario_mxn' => 200]);
        try { $svc->presentar($obra, $draft, $this->buyer()); $this->fail('El costo de origen cambió.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame('borrador', $draft->fresh()->estado); }
    }

    public function test_condition_and_notes_are_saved_per_line_and_part_of_idempotency(): void
    {
        $origin = $this->source(); $obra = \App\Models\Obra::findOrFail(41); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class);
        $payload = ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 2, 'condicion' => 'danado', 'observaciones' => 'Mango roto']]];
        $request = $svc->crear($obra, $this->buyer(), $payload); $line = $request->detalles()->sole();
        $this->assertSame('danado', $line->condicion_declarada);
        $this->assertSame('Mango roto', $line->observaciones);
        $this->assertSame($request->id, $svc->crear($obra, $this->buyer(), $payload)->id);
        $payload['partidas'][0]['condicion'] = 'bueno';
        $this->expectException(\Symfony\Component\HttpKernel\Exception\ConflictHttpException::class);
        $svc->crear($obra, $this->buyer(), $payload);
    }

    public function test_new_request_requires_valid_condition(): void
    {
        $origin = $this->source(); $obra = \App\Models\Obra::findOrFail(41); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class);
        foreach ([null, '', 'incorrecta'] as $condition) {
            try {
                $svc->crear($obra, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 1, 'condicion' => $condition]]]);
                $this->fail('Debe exigir una condición válida.');
            } catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame(0, DB::table('obra_devoluciones')->count()); }
        }
    }

    public function test_legacy_draft_must_define_condition_before_presentation(): void
    {
        $origin = $this->source(); $obra = \App\Models\Obra::findOrFail(41); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class);
        [$id, $lineId] = $this->requestLine($origin, 'borrador', 2);
        DB::table('obra_devolucion_detalles')->where('id', $lineId)->update(['condicion_declarada' => 'por_definir']);
        $draft = \App\Models\ObraDevolucion::findOrFail($id);
        try { $svc->presentar($obra, $draft, $this->buyer()); $this->fail('Falta condición.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame('borrador', $draft->fresh()->estado); }
        $svc->actualizarBorrador($obra, $draft, $this->buyer(), [$lineId => 2], null, [$lineId => 'bueno'], [$lineId => 'Sin uso']);
        $svc->presentar($obra, $draft, $this->buyer());
        $this->assertSame('solicitada', $draft->fresh()->estado);
        $this->assertSame('Sin uso', $draft->detalles()->sole()->observaciones);
    }

    public function test_old_operation_retries_without_condition_remain_idempotent(): void
    {
        $origin = $this->source(); $obra = \App\Models\Obra::findOrFail(41); $svc = app(\App\Services\Obras\ObraDevolucionSolicitudService::class);
        $uuid = (string) Str::uuid();
        $oldLine = ['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => '2.000'];
        $hash = hash('sha256', json_encode(['obra' => 41, 'usuario' => 1, 'accion' => 'solicitar', 'observaciones' => '', 'partidas' => [$oldLine]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        [$id, $lineId] = $this->requestLine($origin, 'solicitada', 2);
        DB::table('obra_devoluciones')->where('id', $id)->update(['operacion_uuid' => $uuid, 'solicitud_hash' => $hash, 'creado_por' => 1]);
        DB::table('obra_devolucion_detalles')->where('id', $lineId)->update(['condicion_declarada' => 'por_definir']);
        $retry = $svc->crear($obra, $this->buyer(), ['operacion_uuid' => $uuid, 'accion' => 'solicitar', 'partidas' => [$oldLine]]);
        $this->assertSame($id, $retry->id);
        $this->assertSame('por_definir', $retry->detalles()->sole()->condicion_declarada);
        $this->assertSame(1, DB::table('obra_devoluciones')->count());
    }

    public function test_capture_view_shows_condition_options_and_line_notes(): void
    {
        $this->source(); auth()->setUser($this->buyer()); $this->withSession([]);
        $html = view('obras.partials.materiales.tab', ['obra' => \App\Models\Obra::findOrFail(41), 'materialesObra' => app(MaterialDevolucionSaldoService::class)->porObra(41)])->render();
        $this->assertStringContainsString('Buen estado', $html);
        $this->assertStringContainsString('Dañado', $html);
        $this->assertStringContainsString('Desgastado', $html);
        $this->assertStringContainsString('Observaciones de partida', $html);
        $this->assertStringContainsString('Almacén verificará la condición al recibir', $html);
    }

    public function test_printable_preserves_request_origin_condition_and_draft_status(): void
    {
        $origin = $this->source(); $obra = \App\Models\Obra::findOrFail(41); $user = $this->buyer();
        $request = app(\App\Services\Obras\ObraDevolucionSolicitudService::class)->crear($obra, $user, ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'borrador', 'observaciones' => 'Prueba de entrega', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 2, 'condicion' => 'desgastado', 'observaciones' => 'Revisar mango']]]);
        $user->shouldReceive('hasAnyRole')->andReturn(true); auth()->setUser($user);
        $http = \Illuminate\Http\Request::create('/'); $http->setUserResolver(fn () => $user);
        $view = app(\App\Http\Controllers\ObraController::class)->imprimirDevolucion($http, $obra, $request);
        $html = $view->render();
        $this->assertStringContainsString($request->folio, $html);
        $this->assertStringContainsString('BORRADOR', $html);
        $this->assertStringContainsString('RDO-00000001', $html);
        $this->assertStringContainsString('Desgastado', $html);
        $this->assertStringContainsString('Revisar mango', $html);
        $this->assertStringContainsString('Sin requisición vinculada', $html);
        $this->assertStringContainsString('10.000 PZA', $html);
        $this->assertStringContainsString('La firma física no sustituye la confirmación', $html);
        $this->withSession([]);
        $tab = view('obras.partials.materiales.tab', ['obra' => $obra, 'materialesObra' => app(MaterialDevolucionSaldoService::class)->porObra(41), 'solicitudesDevolucion' => collect([$request])])->render();
        $this->assertStringContainsString(route('obras.devoluciones.imprimir', [$obra->id, $request->id]), $tab);
        $request->update(['estado' => 'cancelada', 'motivo_cancelacion' => 'Prueba de cancelación']);
        $cancelada = app(\App\Http\Controllers\ObraController::class)->imprimirDevolucion($http, $obra, $request)->render();
        $this->assertStringContainsString('SOLICITUD CANCELADA', $cancelada);
        $this->assertStringContainsString('Prueba de cancelación', $cancelada);
    }

    public function test_printable_shows_only_the_linked_requisition_and_requested_quantity(): void
    {
        $origin = $this->source();
        DB::table('obra_requisiciones')->insert(['id' => 1, 'obra_id' => 41, 'estado' => 'autorizada', 'folio' => 'REQ-PRUEBA']);
        DB::table('obra_requisicion_detalles')->insert(['id' => 1, 'obra_requisicion_id' => 1, 'item_type' => 'producto', 'item_id' => 1, 'unidad' => 'PZA', 'cantidad_solicitada' => 12]);
        DB::table('compra_recepcion_directa_detalles')->where('id', $origin)->update(['obra_requisicion_detalle_id' => 1]);
        [$requestId] = $this->requestLine($origin, 'solicitada', 2);
        $user = $this->buyer(); $user->shouldReceive('hasAnyRole')->andReturn(true); auth()->setUser($user);
        $http = \Illuminate\Http\Request::create('/'); $http->setUserResolver(fn () => $user);
        $html = app(\App\Http\Controllers\ObraController::class)->imprimirDevolucion($http, \App\Models\Obra::findOrFail(41), \App\Models\ObraDevolucion::findOrFail($requestId))->render();
        $this->assertStringContainsString('REQ-PRUEBA', $html);
        $this->assertStringContainsString('12.000 PZA', $html);
        $this->assertStringContainsString('10.000 PZA', $html);
        $this->assertStringContainsString('2.000 PZA', $html);
    }

    public function test_printable_requires_permission_and_the_correct_work(): void
    {
        $origin = $this->source(); $obra = \App\Models\Obra::findOrFail(41); $buyer = $this->buyer();
        $request = app(\App\Services\Obras\ObraDevolucionSolicitudService::class)->crear($obra, $buyer, ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => 1, 'condicion' => 'bueno']]]);
        $denied = \Mockery::mock(\App\Models\User::class)->makePartial(); $denied->shouldReceive('hasAnyRole')->andReturn(true); $denied->shouldReceive('can')->with('obra.devoluciones.view.access')->andReturn(false); auth()->setUser($denied);
        $http = \Illuminate\Http\Request::create('/'); $http->setUserResolver(fn () => $denied);
        try { app(\App\Http\Controllers\ObraController::class)->imprimirDevolucion($http, $obra, $request); $this->fail('Debe exigir permiso.'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        DB::table('obras')->insert(['id' => 42, 'nombre' => 'Otra obra', 'estatus_nuevo' => 2]);
        $buyer->shouldReceive('hasAnyRole')->andReturn(true); auth()->setUser($buyer); $http->setUserResolver(fn () => $buyer);
        try { app(\App\Http\Controllers\ObraController::class)->imprimirDevolucion($http, \App\Models\Obra::findOrFail(42), $request); $this->fail('Debe comprobar la obra.'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(404, $e->getStatusCode()); }
    }
}
