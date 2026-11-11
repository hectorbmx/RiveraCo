<?php

namespace Tests\Feature;

use App\Models\Obra;
use App\Models\OrdenCompra;
use App\Models\User;
use App\Services\Compras\CompraRecepcionDirectaService;
use App\Services\Obras\ObraDevolucionPendienteConsultaService;
use App\Services\Obras\ObraDevolucionSolicitudService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class ObraDevolucionPendienteConsultaTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    protected function setUp(): void
    {
        parent::setUp(); $this->buildDirectReceiptDatabase();
        Schema::table('productos', function (Blueprint $t) { $t->string('tipo_inventario')->default('consumible'); });
        Schema::create('huentitan_salidas', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('almacen_id'); $t->unsignedBigInteger('obra_id'); $t->string('estado'); $t->string('folio'); $t->dateTime('fecha'); });
        Schema::create('huentitan_salida_detalles', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('huentitan_salida_id'); $t->unsignedBigInteger('producto_id'); $t->string('descripcion'); $t->string('unidad'); $t->decimal('cantidad_salida', 15, 3); $t->decimal('costo_unitario', 15, 4); $t->decimal('importe', 15, 2); });
        (require base_path('database/migrations/2026_11_06_020000_create_obra_devoluciones_tables.php'))->up();
        (require base_path('database/migrations/2026_11_06_030000_add_obra_devolucion_idempotency.php'))->up();
        (require base_path('database/migrations/2026_11_09_010000_add_evaluacion_custodia_a_devoluciones.php'))->up();
    }

    private function createRequest(int $origin, string $action, float $quantity): \App\Models\ObraDevolucion
    {
        return app(ObraDevolucionSolicitudService::class)->crear(Obra::findOrFail(41), $this->buyer(), [
            'operacion_uuid' => (string) Str::uuid(), 'accion' => $action,
            'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $origin, 'cantidad' => $quantity, 'condicion' => 'desgastado', 'observaciones' => 'Revisar mango']],
        ]);
    }

    public function test_unified_returns_view_shows_applied_delivery_and_pending_request(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 4);
        $obra = Obra::findOrFail(41);
        auth()->setUser($this->buyer()); $this->withSession([]);
        $solicitudes = app(ObraDevolucionPendienteConsultaService::class)->consultarPreparadas(null, 'solicitudes_page');
        $datos = ['obra' => $obra, 'obras' => collect([$obra]), 'busqueda' => '',
            'solicitudes' => $solicitudes, 'finiquito' => app(\App\Services\Obras\FiniquitoMaterialConsultaService::class)->consultar($obra)];
        $html = view('inventario.documentos.partials.devoluciones-obra', compact('datos'))->render();
        $this->assertStringContainsString('Material entregado a obra', $html);
        $this->assertStringContainsString($source->folio, $html);
        $this->assertStringContainsString('10.000', $html);
        $this->assertStringContainsString($request->folio, $html);
        $this->assertStringContainsString('Registrar recepción física', $html);
        $this->assertSame('solicitudes_page', $solicitudes->getPageName());
    }

    public function test_work_selector_includes_only_outstanding_deliveries_and_removes_fully_returned_work(): void
    {
        $selector = app(\App\Services\Obras\ObrasConMaterialEnUsoService::class);
        DB::table('obras')->insert(['id' => 42, 'nombre' => 'Sin entrega', 'estatus_nuevo' => 2]);
        DB::table('obra_requisiciones')->insert(['id' => 1, 'obra_id' => 42, 'estado' => 'autorizada']);
        $this->assertCount(0, $selector->consultar());

        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $this->assertSame([41], $selector->consultar()->pluck('id')->all());
        $request = app(ObraDevolucionSolicitudService::class)->crear(Obra::findOrFail(41), $this->buyer(), [
            'operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar',
            'partidas' => [['origen_tipo' => 'recepcion_directa', 'origen_id' => $source->detalles()->sole()->id, 'cantidad' => 10, 'condicion' => 'bueno']],
        ]);
        $receipt = app(\App\Services\Obras\ObraDevolucionRecepcionService::class)->confirmar($request, $this->buyer(), [
            'operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(),
            'partidas' => [['id' => $request->detalles()->sole()->id, 'cantidad' => 10, 'condicion' => 'bueno']],
        ]);
        $this->assertSame([41], $selector->consultar()->pluck('id')->all());
        app(\App\Services\Obras\ObraDevolucionAplicacionService::class)->aplicar($receipt, $this->buyer());
        $this->assertCount(0, $selector->consultar());
    }

    public function test_list_includes_submitted_and_partial_only_and_counts_applied_receipts(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $origin = $source->detalles()->firstOrFail()->id;
        $partial = $this->createRequest($origin, 'solicitar', 4);
        $submitted = $this->createRequest($origin, 'solicitar', 3);
        $this->createRequest($origin, 'borrador', 2);
        $cancelled = $this->createRequest($origin, 'solicitar', 1);
        app(ObraDevolucionSolicitudService::class)->cancelar(Obra::findOrFail(41), $cancelled, $this->buyer(), 'Prueba');
        $partial->update(['estado' => 'recepcion_parcial']);
        foreach (['aplicada' => 2, 'borrador' => 1] as $state => $quantity) {
            $receiptId = DB::table('obra_devolucion_recepciones')->insertGetId(['devolucion_id' => $partial->id, 'operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => '2026-11-06', 'estado' => $state]);
            DB::table('obra_devolucion_recepcion_detalles')->insert(['recepcion_id' => $receiptId, 'devolucion_detalle_id' => $partial->detalles()->sole()->id, 'cantidad_recibida' => $quantity, 'condicion_recibida' => 'bueno', 'costo_unitario_mxn' => 150]);
        }
        $list = app(ObraDevolucionPendienteConsultaService::class)->consultar();
        $this->assertSame(2, $list->total());
        $this->assertEqualsCanonicalizing([$partial->id, $submitted->id], $list->pluck('id')->all());
        $line = $list->getCollection()->firstWhere('id', $partial->id)->detalles->sole();
        $this->assertSame(2.0, $line->consulta_recibido);
        $this->assertSame(2.0, $line->consulta_pendiente);
        $this->assertSame('Giralda', $line->consulta_almacen_origen);
        $this->assertSame($source->folio, $line->consulta_origen);
        $html = view('inventario.devoluciones-obra.partials.lista', ['solicitudes' => $list])->render();
        $this->assertStringContainsString('Recepción parcial', $html);
        $this->assertStringContainsString('Desgastado', $html);
        $this->assertStringContainsString('Revisar mango', $html);
        $this->assertStringNotContainsString($cancelled->folio, $html);
        $this->assertSame(1, app(ObraDevolucionPendienteConsultaService::class)->consultar($submitted->folio)->total());
        $this->assertSame(2, app(ObraDevolucionPendienteConsultaService::class)->consultar('Obra de prueba')->total());
    }

    public function test_invalid_origin_and_overreceived_quantity_are_marked_for_review(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 2);
        $receiptId = DB::table('obra_devolucion_recepciones')->insertGetId(['devolucion_id' => $request->id, 'operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => '2026-11-06', 'estado' => 'aplicada']);
        DB::table('obra_devolucion_recepcion_detalles')->insert(['recepcion_id' => $receiptId, 'devolucion_detalle_id' => $request->detalles()->sole()->id, 'cantidad_recibida' => 3, 'condicion_recibida' => 'bueno', 'costo_unitario_mxn' => 150]);
        $source->update(['estado' => 'revertida']);
        $line = app(ObraDevolucionPendienteConsultaService::class)->consultar()->first()->detalles->sole();
        $this->assertNull($line->consulta_pendiente);
        $this->assertCount(2, $line->consulta_errores);
    }

    public function test_list_requires_both_warehouse_permissions(): void
    {
        foreach ([[false, true], [true, false]] as [$view, $receive]) {
            $user = \Mockery::mock(User::class)->makePartial();
            $user->shouldReceive('can')->with('obra.devoluciones.view.access')->andReturn($view);
            if ($view) $user->shouldReceive('can')->with('obra.devoluciones.receive.access')->andReturn($receive);
            $request = Request::create('/'); $request->setUserResolver(fn () => $user);
            try { app(\App\Http\Controllers\Inventario\ObraDevolucionPendienteController::class)->index($request, app(ObraDevolucionPendienteConsultaService::class)); $this->fail('Debe exigir ambos permisos.'); }
            catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        }
        $middleware = Route::getRoutes()->getByName('inventario.devoluciones-obra.index')->gatherMiddleware();
        $this->assertContains('permission:obra.devoluciones.view.access', $middleware);
        $this->assertContains('permission:obra.devoluciones.receive.access', $middleware);
    }

    public function test_physical_receipt_is_partial_idempotent_and_does_not_move_inventory(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 4);
        $line = $request->detalles()->sole();
        $service = app(\App\Services\Obras\ObraDevolucionRecepcionService::class);
        $data = ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $line->id, 'cantidad' => 2, 'condicion' => 'danado', 'observaciones' => 'Golpe']]];
        $movements = DB::table('inventario_movimientos')->count();
        $stock = DB::table('inventario_stock')->where('almacen_id', 1)->where('producto_id', 1)->value('stock_actual');
        $receipt = $service->confirmar($request, $this->buyer(), $data);
        $this->assertSame('pendiente_aplicacion', $receipt->estado);
        $this->assertSame($receipt->id, $service->confirmar($request, $this->buyer(), $data)->id);
        $this->assertSame('danado', $receipt->detalles()->sole()->condicion_recibida);
        $this->assertSame('desgastado', $line->fresh()->condicion_declarada);
        $this->assertSame($movements, DB::table('inventario_movimientos')->count());
        $this->assertEquals($stock, DB::table('inventario_stock')->where('almacen_id', 1)->where('producto_id', 1)->value('stock_actual'));
        $pending = app(ObraDevolucionPendienteConsultaService::class)->consultar(null, $request->id)->first()->detalles->sole();
        $this->assertSame(2.0, $pending->consulta_pendiente);
        $html = view('inventario.devoluciones-obra.partials.lista', ['solicitudes' => app(ObraDevolucionPendienteConsultaService::class)->consultar()])->render();
        $this->assertStringContainsString('Condición diferente', $html);
        $different = $data; $different['partidas'][0]['cantidad'] = 1;
        try { $service->confirmar($request, $this->buyer(), $different); $this->fail('La clave no debe aceptar otros datos.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertCount(1, $request->recepciones()->get()); }
        $data['operacion_uuid'] = (string) Str::uuid(); $data['partidas'][0]['cantidad'] = 3;
        try { $service->confirmar($request, $this->buyer(), $data); $this->fail('Debe rechazar el excedente.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertCount(1, $request->recepciones()->get()); }
        try { app(ObraDevolucionSolicitudService::class)->cancelar(Obra::findOrFail(41), $request, $this->buyer(), 'Prueba'); $this->fail('Debe impedir cancelar una recepción física.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame('recepcion_parcial', $request->fresh()->estado); }
        $data['partidas'][0]['cantidad'] = 2;
        $service->confirmar($request, $this->buyer(), $data);
        $pending = app(ObraDevolucionPendienteConsultaService::class)->consultar(null, $request->id)->first()->detalles->sole();
        $this->assertSame(0.0, $pending->consulta_pendiente);
        $html = view('inventario.devoluciones-obra.partials.lista', ['solicitudes' => app(ObraDevolucionPendienteConsultaService::class)->consultar()])->render();
        $this->assertStringContainsString('Recibida físicamente', $html);
        $this->assertStringNotContainsString('Registrar recepción física', $html);
    }

    public function test_entry_preparation_preserves_origin_and_historical_cost_without_moving_stock(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $sourceLine = $source->detalles()->sole();
        $request = $this->createRequest($sourceLine->id, 'solicitar', 4);
        $request->detalles()->update(['condicion_declarada' => 'bueno']);
        $line = $request->detalles()->sole();
        $receipt = app(\App\Services\Obras\ObraDevolucionRecepcionService::class)->confirmar($request, $this->buyer(), [
            'operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(),
            'partidas' => [['id' => $line->id, 'cantidad' => 2, 'condicion' => 'bueno']],
        ]);
        $movementCount = DB::table('inventario_movimientos')->count();
        $documentCount = DB::table('inventario_documentos')->count();
        $prepared = app(\App\Services\Obras\ObraDevolucionEntradaPreparacionService::class)->preparar($receipt);
        $this->assertSame(41, (int) $prepared['obra_id']);
        $this->assertSame(1, (int) $prepared['almacen_id']);
        $this->assertSame((int) $sourceLine->id, (int) $prepared['entradas'][0]['recepcion_directa_detalle_id']);
        $this->assertSame('2.000', $prepared['entradas'][0]['cantidad']);
        $this->assertSame($line->costo_unitario_mxn, $prepared['entradas'][0]['costo_unitario_mxn']);
        $this->assertSame([], $prepared['pendientes']);
        $this->assertSame($movementCount, DB::table('inventario_movimientos')->count());
        $this->assertSame($documentCount, DB::table('inventario_documentos')->count());
        $http = Request::create('/inventario/devoluciones-obra'); $http->setUserResolver(fn () => $this->buyer());
        $response = app(\App\Http\Controllers\Inventario\ObraDevolucionPendienteController::class)->index($http, app(ObraDevolucionPendienteConsultaService::class));
        $html = view('inventario.devoluciones-obra.partials.lista', ['solicitudes' => $response->getData()['solicitudes']])->render();
        $this->assertStringContainsString('Preparación de entrada: 1 partida(s)', $html);
        $receipt->detalles()->update(['condicion_recibida' => 'danado']);
        $prepared = app(\App\Services\Obras\ObraDevolucionEntradaPreparacionService::class)->preparar($receipt->fresh());
        $this->assertSame([], $prepared['entradas']);
        $this->assertStringContainsString('aclaración', $prepared['pendientes'][0]['motivo']);
        $receipt->detalles()->update(['costo_unitario_mxn' => 1]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\Obras\ObraDevolucionEntradaPreparacionService::class)->preparar($receipt->fresh());
    }

    public function test_applying_good_return_updates_stock_traceability_and_cost_once(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 4);
        $request->detalles()->update(['condicion_declarada' => 'bueno']);
        $line = $request->detalles()->sole();
        $receipt = app(\App\Services\Obras\ObraDevolucionRecepcionService::class)->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $line->id, 'cantidad' => 2, 'condicion' => 'bueno']]]);
        $before = DB::table('inventario_stock')->where('almacen_id', 1)->where('producto_id', 1)->first();
        $service = app(\App\Services\Obras\ObraDevolucionAplicacionService::class);
        $service->aplicar($receipt, $this->buyer()); $service->aplicar($receipt, $this->buyer());
        $row = $receipt->detalles()->sole();
        $this->assertSame('aplicada', $receipt->fresh()->estado);
        $this->assertNotNull($row->inventario_movimiento_id);
        $this->assertEquals(300, $row->importe_mxn);
        $this->assertEquals((float) $before->stock_actual + 2, DB::table('inventario_stock')->where('id', $before->id)->value('stock_actual'));
        $this->assertEquals((float) $before->valor_total + 300, DB::table('inventario_stock')->where('id', $before->id)->value('valor_total'));
        $movement = DB::table('inventario_movimientos')->where('id', $row->inventario_movimiento_id)->first();
        $this->assertEquals($source->id, $movement->compra_recepcion_directa_id);
        $this->assertEquals(300, $movement->importe_costo);
        $this->assertEquals(1200, app(\App\Services\Compras\CompraRecepcionDirectaObraService::class)->resumen(41)['total_mxn']);
        $material = app(\App\Services\Obras\MaterialDevolucionSaldoService::class)->porObra(41)['entregas']->sole();
        $this->assertEquals(300, $material->importe_devuelto_mxn);
        $this->assertEquals(1200, $material->importe_neto_mxn);
        $this->assertSame('recepcion_parcial', $request->fresh()->estado);
        $document = \App\Models\InventarioDocumento::findOrFail($movement->documento_id);
        try { app(\App\Services\Inventario\InventarioDocumentoService::class)->cancelar($document); $this->fail('No se debe cancelar por separado.'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('corrección conjunta', $e->getMessage()); }
    }

    public function test_application_blocks_different_condition_without_writing_inventory(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 2);
        $receipt = app(\App\Services\Obras\ObraDevolucionRecepcionService::class)->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $request->detalles()->sole()->id, 'cantidad' => 2, 'condicion' => 'bueno']]]);
        $movements = DB::table('inventario_movimientos')->count(); $documents = DB::table('inventario_documentos')->count();
        try { app(\App\Services\Obras\ObraDevolucionAplicacionService::class)->aplicar($receipt, $this->buyer()); $this->fail('La condición diferente requiere aclaración.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame($movements, DB::table('inventario_movimientos')->count()); $this->assertSame($documents, DB::table('inventario_documentos')->count()); $this->assertSame('pendiente_aplicacion', $receipt->fresh()->estado); }
    }

    public function test_failed_movement_rolls_back_document_stock_and_receipt(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 2);
        $request->detalles()->update(['condicion_declarada' => 'bueno']);
        $receipt = app(\App\Services\Obras\ObraDevolucionRecepcionService::class)->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $request->detalles()->sole()->id, 'cantidad' => 2, 'condicion' => 'bueno']]]);
        $stock = DB::table('inventario_stock')->where('almacen_id', 1)->where('producto_id', 1)->first();
        $documents = DB::table('inventario_documentos')->count(); $movements = DB::table('inventario_movimientos')->count();
        DB::unprepared("CREATE TRIGGER test_fallo_devolucion BEFORE INSERT ON inventario_movimientos WHEN NEW.tipo_movimiento = 'in' BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");
        try { app(\App\Services\Obras\ObraDevolucionAplicacionService::class)->aplicar($receipt, $this->buyer()); $this->fail('Debe fallar la inserción.'); }
        catch (\Illuminate\Database\QueryException $e) {
            $this->assertSame($documents, DB::table('inventario_documentos')->count());
            $this->assertSame($movements, DB::table('inventario_movimientos')->count());
            $this->assertEquals($stock->stock_actual, DB::table('inventario_stock')->where('id', $stock->id)->value('stock_actual'));
            $this->assertEquals($stock->valor_total, DB::table('inventario_stock')->where('id', $stock->id)->value('valor_total'));
            $this->assertSame('pendiente_aplicacion', $receipt->fresh()->estado);
            $this->assertNull($receipt->detalles()->sole()->inventario_movimiento_id);
        }
    }

    public function test_partial_returns_distribute_original_cents_without_losing_value(): void
    {
        DB::table('orden_compra_detalles')->where('id', 1)->update(['cantidad' => 3, 'precio_unitario' => 0.3333, 'importe' => 1]);
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(3), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 3);
        $request->detalles()->update(['condicion_declarada' => 'bueno']);
        $amounts = [];
        for ($i = 0; $i < 3; $i++) {
            $receipt = app(\App\Services\Obras\ObraDevolucionRecepcionService::class)->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $request->detalles()->sole()->id, 'cantidad' => 1, 'condicion' => 'bueno']]]);
            app(\App\Services\Obras\ObraDevolucionAplicacionService::class)->aplicar($receipt, $this->buyer());
            $amounts[] = (float) $receipt->detalles()->sole()->importe_mxn;
        }
        $this->assertSame([0.33, 0.34, 0.33], $amounts);
        $this->assertSame('recibida', $request->fresh()->estado);
        $this->assertEquals(0, app(\App\Services\Compras\CompraRecepcionDirectaObraService::class)->resumen(41)['total_mxn']);
    }

    public function test_application_preserves_general_and_huentitan_origins(): void
    {
        $document = DB::table('inventario_documentos')->insertGetId(['folio' => 'SAL-GENERAL', 'tipo' => 'salida', 'almacen_id' => 1, 'obra_id' => 41, 'estado' => 'aplicado', 'fecha' => now()]);
        $movement = DB::table('inventario_movimientos')->insertGetId(['almacen_id' => 1, 'producto_id' => 1, 'documento_id' => $document, 'fecha' => now(), 'tipo_movimiento' => 'out', 'cantidad' => 4, 'costo_unitario' => 10, 'importe_costo' => 40, 'saldo_cantidad' => 6, 'obra_id' => 41]);
        DB::table('huentitan_salidas')->insert(['id' => 50, 'almacen_id' => 1, 'obra_id' => 41, 'estado' => 'aplicada', 'folio' => 'SAL-HUENTITAN', 'fecha' => now()]);
        $huentitan = DB::table('huentitan_salida_detalles')->insertGetId(['huentitan_salida_id' => 50, 'producto_id' => 1, 'descripcion' => 'Material', 'unidad' => 'PZA', 'cantidad_salida' => 4, 'costo_unitario' => 20, 'importe' => 80]);
        foreach (['salida_general' => $movement, 'salida_huentitan' => $huentitan] as $type => $origin) {
            $request = app(ObraDevolucionSolicitudService::class)->crear(Obra::findOrFail(41), $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [['origen_tipo' => $type, 'origen_id' => $origin, 'cantidad' => 2, 'condicion' => 'bueno']]]);
            $receipt = app(\App\Services\Obras\ObraDevolucionRecepcionService::class)->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $request->detalles()->sole()->id, 'cantidad' => 2, 'condicion' => 'bueno']]]);
            app(\App\Services\Obras\ObraDevolucionAplicacionService::class)->aplicar($receipt, $this->buyer());
            $this->assertEquals($type === 'salida_general' ? 20 : 40, $receipt->detalles()->sole()->importe_mxn);
            $this->assertNotNull($receipt->detalles()->sole()->inventario_movimiento_id);
        }
        $delivered = app(\App\Services\Obras\MaterialDevolucionSaldoService::class)->porObra(41)['entregas']->keyBy('origen_tipo');
        $this->assertEquals(20, $delivered['salida_general']->importe_neto_mxn);
        $this->assertEquals(40, $delivered['salida_huentitan']->importe_neto_mxn);
    }

    public function test_application_requires_both_return_permissions(): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('obra.devoluciones.view.access')->andReturn(true);
        $user->shouldReceive('can')->with('obra.devoluciones.receive.access')->andReturn(false);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(\App\Services\Obras\ObraDevolucionAplicacionService::class)->aplicar(new \App\Models\ObraDevolucionRecepcion(), $user);
    }

    public function test_damaged_tools_are_recorded_separately_without_stock_or_cost_adjustment(): void
    {
        DB::table('productos')->where('id', 1)->update(['tipo_inventario' => 'herramienta']);
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 4);
        $receipt = app(\App\Services\Obras\ObraDevolucionRecepcionService::class)->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $request->detalles()->sole()->id, 'cantidad' => 2, 'condicion' => 'desgastado']]]);
        $stock = DB::table('inventario_stock')->where('almacen_id', 1)->where('producto_id', 1)->first();
        $movements = DB::table('inventario_movimientos')->count();
        $view = view('inventario.devoluciones-obra.partials.lista', ['solicitudes' => app(ObraDevolucionPendienteConsultaService::class)->consultar()])->render();
        $this->assertStringContainsString('Registrar herramientas pendientes de evaluación', $view);
        $service = app(\App\Services\Obras\ObraDevolucionEvaluacionPendienteService::class);
        $this->assertSame(1, $service->registrar($receipt, $this->buyer()));
        $this->assertSame(0, $service->registrar($receipt, $this->buyer()));
        $line = $receipt->detalles()->sole();
        $this->assertSame('pendiente_evaluacion', $line->estado_evaluacion);
        $this->assertSame('herramienta', $line->evaluacion_tipo_snapshot);
        $this->assertEquals(1, $line->evaluacion_registrada_por);
        $this->assertNotNull($line->evaluacion_registrada_at);
        $this->assertNull($line->inventario_movimiento_id);
        $this->assertNull($line->importe_mxn);
        $this->assertSame($movements, DB::table('inventario_movimientos')->count());
        $this->assertEquals($stock->stock_actual, DB::table('inventario_stock')->where('id', $stock->id)->value('stock_actual'));
        $this->assertEquals($stock->valor_total, DB::table('inventario_stock')->where('id', $stock->id)->value('valor_total'));
        $material = app(\App\Services\Obras\MaterialDevolucionSaldoService::class)->porObra(41)['entregas']->sole();
        $this->assertSame(2.0, $material->cantidad_pendiente_evaluacion);
        $this->assertSame(0.0, $material->cantidad_devuelta_confirmada);
        $this->assertSame(4.0, $material->cantidad_comprometida);
        $this->assertEquals(1500, $material->importe_neto_mxn);
        $this->assertFalse($material->requiere_revision);
        $view = view('inventario.devoluciones-obra.partials.lista', ['solicitudes' => app(ObraDevolucionPendienteConsultaService::class)->consultar()])->render();
        $this->assertStringContainsString('Herramienta recibida, pendiente de evaluación; no disponible', $view);
        $this->assertStringNotContainsString('Registrar herramientas pendientes de evaluación', $view);
        DB::table('productos')->where('id', 1)->update(['tipo_inventario' => 'consumible']);
        $this->assertSame(0, $service->registrar($receipt, $this->buyer()));
        $this->assertSame('herramienta', $receipt->detalles()->sole()->evaluacion_tipo_snapshot);
    }

    public function test_non_tool_cannot_be_registered_as_tool_pending_evaluation(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 2);
        $receipt = app(\App\Services\Obras\ObraDevolucionRecepcionService::class)->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $request->detalles()->sole()->id, 'cantidad' => 2, 'condicion' => 'danado']]]);
        try { app(\App\Services\Obras\ObraDevolucionEvaluacionPendienteService::class)->registrar($receipt, $this->buyer()); $this->fail('Un consumible no debe tratarse automáticamente como herramienta.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertNull($receipt->detalles()->sole()->estado_evaluacion); }
    }

    public function test_difference_summary_separates_physical_pending_applied_and_condition(): void
    {
        DB::table('productos')->where('id', 1)->update(['tipo_inventario' => 'herramienta']);
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 4);
        $request->detalles()->update(['condicion_declarada' => 'bueno']);
        $lineId = $request->detalles()->sole()->id;
        $service = app(\App\Services\Obras\ObraDevolucionRecepcionService::class);
        $good = $service->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $lineId, 'cantidad' => 1, 'condicion' => 'bueno']]]);
        app(\App\Services\Obras\ObraDevolucionAplicacionService::class)->aplicar($good, $this->buyer());
        $damaged = $service->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $lineId, 'cantidad' => 1, 'condicion' => 'danado', 'observaciones' => 'Mango roto']]]);
        app(\App\Services\Obras\ObraDevolucionEvaluacionPendienteService::class)->registrar($damaged, $this->buyer());
        $solicitud = app(ObraDevolucionPendienteConsultaService::class)->consultar(null, $request->id)->first();
        $summary = $solicitud->detalles->sole()->resumen_devolucion;
        $this->assertSame(2.0, $summary['recibido']);
        $this->assertSame(2.0, $summary['pendiente']);
        $this->assertSame(1.0, $summary['aplicado']);
        $this->assertSame(1.0, $summary['por_aplicar']);
        $this->assertSame(1.0, $summary['evaluacion']);
        $this->assertSame(1, $solicitud->resumen_diferencias['condiciones_diferentes']);
        $this->assertSame([], $summary['errores']);
        $html = view('obras.devoluciones.partials.seguimiento', ['solicitud' => $solicitud, 'condicionesDevolucion' => ['bueno' => 'Buen estado', 'danado' => 'Dañado', 'desgastado' => 'Desgastado']])->render();
        $this->assertStringContainsString('Pendiente de recibir: 2.000', $html);
        $this->assertStringContainsString('Aplicado a inventario y costo: 1.000', $html);
        $this->assertStringContainsString('Condición diferente a la declarada', $html);
        $this->assertStringContainsString('Mango roto', $html);
        $this->assertStringContainsString($good->folio, $html);
        $this->assertStringContainsString($damaged->folio, $html);
    }

    public function test_unknown_declared_condition_and_excess_are_not_presented_as_normal_balances(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 2);
        $request->detalles()->update(['condicion_declarada' => 'por_definir']);
        $id = DB::table('obra_devolucion_recepciones')->insertGetId(['devolucion_id' => $request->id, 'operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now(), 'estado' => 'pendiente_aplicacion']);
        DB::table('obra_devolucion_recepcion_detalles')->insert(['recepcion_id' => $id, 'devolucion_detalle_id' => $request->detalles()->sole()->id, 'cantidad_recibida' => 3, 'condicion_recibida' => 'bueno', 'costo_unitario_mxn' => 150]);
        $solicitud = app(ObraDevolucionPendienteConsultaService::class)->consultar(null, $request->id)->first();
        $summary = $solicitud->detalles->sole()->resumen_devolucion;
        $this->assertNull($summary['pendiente']);
        $this->assertNotEmpty($summary['errores']);
        $this->assertSame(0, $solicitud->resumen_diferencias['condiciones_diferentes']);
        $this->assertTrue($summary['historial'][0]['sin_referencia']);
    }

    public function test_mixed_receipt_applies_good_lines_once_and_preserves_pending_tool(): void
    {
        DB::table('productos')->where('id', 1)->update(['tipo_inventario' => 'herramienta']);
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        $doc = DB::table('inventario_documentos')->insertGetId(['folio' => 'SAL-MIXTA', 'tipo' => 'salida', 'almacen_id' => 1, 'obra_id' => 41, 'estado' => 'aplicado', 'fecha' => now()]);
        $origin = DB::table('inventario_movimientos')->insertGetId(['almacen_id' => 1, 'producto_id' => 1, 'documento_id' => $doc, 'fecha' => now(), 'tipo_movimiento' => 'out', 'cantidad' => 4, 'costo_unitario' => 10, 'importe_costo' => 40, 'saldo_cantidad' => 6, 'obra_id' => 41]);
        $request = app(ObraDevolucionSolicitudService::class)->crear(Obra::findOrFail(41), $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [
            ['origen_tipo' => 'recepcion_directa', 'origen_id' => $source->detalles()->sole()->id, 'cantidad' => 2, 'condicion' => 'bueno'],
            ['origen_tipo' => 'salida_general', 'origen_id' => $origin, 'cantidad' => 1, 'condicion' => 'desgastado'],
        ]]);
        $good = $request->detalles()->whereNotNull('recepcion_directa_detalle_id')->sole();
        $tool = $request->detalles()->whereNotNull('inventario_movimiento_id')->sole();
        $receiptService = app(\App\Services\Obras\ObraDevolucionRecepcionService::class);
        $receipt = $receiptService->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [
            ['id' => $good->id, 'cantidad' => 1, 'condicion' => 'bueno'], ['id' => $tool->id, 'cantidad' => 1, 'condicion' => 'desgastado'],
        ]]);
        app(\App\Services\Obras\ObraDevolucionEvaluacionPendienteService::class)->registrar($receipt, $this->buyer());
        $stock = DB::table('inventario_stock')->where('almacen_id', 1)->where('producto_id', 1)->value('stock_actual');
        $service = app(\App\Services\Obras\ObraDevolucionAplicacionService::class);
        $service->aplicar($receipt, $this->buyer()); $service->aplicar($receipt, $this->buyer());
        $this->assertSame('pendiente_aplicacion', $receipt->fresh()->estado);
        $this->assertEquals((float) $stock + 1, DB::table('inventario_stock')->where('almacen_id', 1)->where('producto_id', 1)->value('stock_actual'));
        $items = $receipt->detalles()->get()->keyBy('devolucion_detalle_id');
        $this->assertNotNull($items[$good->id]->inventario_movimiento_id);
        $this->assertNull($items[$tool->id]->inventario_movimiento_id);
        $this->assertSame('pendiente_evaluacion', $items[$tool->id]->estado_evaluacion);
        $this->assertEquals(150, $items[$good->id]->importe_mxn);
        $summary = app(ObraDevolucionPendienteConsultaService::class)->consultar(null, $request->id)->first()->detalles->keyBy('id');
        $this->assertSame(1.0, $summary[$good->id]->resumen_devolucion['aplicado']);
        $this->assertSame(1.0, $summary[$tool->id]->resumen_devolucion['evaluacion']);
        $solicitudVista = app(ObraDevolucionPendienteConsultaService::class)->consultar(null, $request->id)->first();
        $this->assertSame(1.0, $solicitudVista->detalles->firstWhere('id', $good->id)->resumen_devolucion['aplicado']);
        $delivered = app(\App\Services\Obras\MaterialDevolucionSaldoService::class)->porObra(41)['entregas']->keyBy('origen_tipo');
        $this->assertSame(1.0, $delivered['recepcion_directa']->cantidad_devuelta_confirmada);
        $this->assertEquals(150, $delivered['recepcion_directa']->importe_devuelto_mxn);
        $this->assertSame(1.0, $delivered['salida_general']->cantidad_pendiente_evaluacion);
        $later = $receiptService->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $good->id, 'cantidad' => 1, 'condicion' => 'bueno']]]);
        $service->aplicar($later, $this->buyer());
        $this->assertEquals(150, $later->detalles()->sole()->importe_mxn);
        $this->assertSame('pendiente_aplicacion', $receipt->fresh()->estado);
    }

    public function test_incomplete_application_links_block_follow_up_application(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 2);
        $request->detalles()->update(['condicion_declarada' => 'bueno']);
        $receipt = app(\App\Services\Obras\ObraDevolucionRecepcionService::class)->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $request->detalles()->sole()->id, 'cantidad' => 1, 'condicion' => 'bueno']]]);
        $receipt->detalles()->update(['importe_mxn' => 1]);
        $before = DB::table('inventario_movimientos')->count();
        $summary = app(ObraDevolucionPendienteConsultaService::class)->consultar(null, $request->id)->first()->detalles->sole();
        $this->assertNull($summary->consulta_pendiente);
        $this->assertNotEmpty($summary->resumen_devolucion['errores']);
        try { app(\App\Services\Obras\ObraDevolucionAplicacionService::class)->aplicar($receipt, $this->buyer()); $this->fail('No debe aplicar una partida con vínculos incompletos.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame($before, DB::table('inventario_movimientos')->count()); }
    }

    public function test_finiquito_shows_physical_pending_and_applied_without_double_counting(): void
    {
        DB::table('productos')->where('id', 1)->update(['tipo_inventario' => 'herramienta']);
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 2);
        $request->detalles()->update(['condicion_declarada' => 'bueno']);
        $line = $request->detalles()->sole(); $service = app(\App\Services\Obras\ObraDevolucionRecepcionService::class);
        $good = $service->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $line->id, 'cantidad' => 1, 'condicion' => 'bueno']]]);
        app(\App\Services\Obras\ObraDevolucionAplicacionService::class)->aplicar($good, $this->buyer());
        $tool = $service->confirmar($request, $this->buyer(), ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [['id' => $line->id, 'cantidad' => 0.5, 'condicion' => 'danado']]]);
        app(\App\Services\Obras\ObraDevolucionEvaluacionPendienteService::class)->registrar($tool, $this->buyer());
        DB::table('obras')->insert(['id' => 42, 'nombre' => 'Otra obra', 'estatus_nuevo' => 2]);
        DB::table('obra_devoluciones')->insert(['obra_id' => 42, 'folio' => 'OTRA-OBRA', 'estado' => 'solicitada']);
        $before = DB::table('inventario_movimientos')->count();
        $finiquito = app(\App\Services\Obras\FiniquitoMaterialConsultaService::class)->consultar(Obra::findOrFail(41));
        $row = $finiquito['entregas']->sole();
        $this->assertSame(0.5, $row['fisico_pendiente_aplicar']);
        $this->assertSame(0.5, $row['pendiente_recibir']);
        $this->assertSame(8.5, $row['sin_devolucion_fisica']);
        $this->assertSame(0.5, $row['entrega']->cantidad_pendiente_evaluacion);
        $this->assertEquals(1350, $row['entrega']->importe_neto_mxn);
        $this->assertSame([$request->folio], $row['solicitudes']);
        $this->assertSame(1, $finiquito['condiciones_diferentes']);
        $this->assertFalse($finiquito['solicitudes']->contains('folio', 'OTRA-OBRA'));
        $this->assertSame($before, DB::table('inventario_movimientos')->count());
        $html = view('obras.devoluciones.partials.finiquito_contenido', ['obra' => Obra::findOrFail(41), 'finiquito' => $finiquito])->render();
        $this->assertStringContainsString('Consulta preliminar', $html);
        $this->assertStringContainsString(route('obras.devoluciones.finiquito.imprimir', 41), $html);
        $this->assertStringContainsString($request->folio, $html);
        $this->assertStringContainsString('8.500', $html);
        $this->assertStringContainsString('Pendiente de evaluación', $html);
        $this->assertStringNotContainsString('OTRA-OBRA', $html);
        $impresion = view('obras.devoluciones.finiquito_imprimir', ['obra' => Obra::findOrFail(41), 'finiquito' => $finiquito, 'generadoEn' => now()])->render();
        $this->assertStringContainsString('Finiquito preliminar de materiales', $impresion);
        $this->assertStringContainsString('CONSULTA PRELIMINAR', $impresion);
        $this->assertStringContainsString('8.500', $impresion);
        $this->assertStringContainsString($request->folio, $impresion);
        $this->assertStringContainsString($good->folio, $impresion);
        $this->assertStringContainsString($tool->folio, $impresion);
        $this->assertStringContainsString('Condición diferente; pendiente de aclarar', $impresion);
        $this->assertStringContainsString('Pendiente de evaluación', $impresion);
        $this->assertStringNotContainsString('OTRA-OBRA', $impresion);
        $this->assertSame($before, DB::table('inventario_movimientos')->count());
    }

    public function test_finiquito_does_not_present_corrupt_quantities_as_a_balance(): void
    {
        $source = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $request = $this->createRequest($source->detalles()->sole()->id, 'solicitar', 2);
        $id = DB::table('obra_devolucion_recepciones')->insertGetId(['devolucion_id' => $request->id, 'operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now(), 'estado' => 'pendiente_aplicacion']);
        DB::table('obra_devolucion_recepcion_detalles')->insert(['recepcion_id' => $id, 'devolucion_detalle_id' => $request->detalles()->sole()->id, 'cantidad_recibida' => 3, 'condicion_recibida' => 'bueno', 'costo_unitario_mxn' => 150]);
        $obra = Obra::findOrFail(41);
        $finiquito = app(\App\Services\Obras\FiniquitoMaterialConsultaService::class)->consultar($obra);
        $row = $finiquito['entregas']->sole();
        $this->assertTrue($row['requiere_revision']);
        $this->assertNull($row['sin_devolucion_fisica']);
        $this->assertNull($row['pendiente_recibir']);
        $impresion = view('obras.devoluciones.finiquito_imprimir', ['obra' => $obra, 'finiquito' => $finiquito, 'generadoEn' => now()])->render();
        $this->assertStringContainsString('Revisar', $impresion);
        $this->assertStringContainsString('Las cantidades requieren revisión', $impresion);
    }

    public function test_finiquito_requires_view_permission(): void
    {
        $user = \Mockery::mock(User::class)->makePartial(); $user->shouldReceive('hasAnyRole')->andReturn(true); $user->shouldReceive('can')->with('obra.devoluciones.view.access')->andReturn(false); auth()->setUser($user);
        $http = Request::create('/'); $http->setUserResolver(fn () => $user);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(\App\Http\Controllers\ObraController::class)->finiquitoMateriales($http, Obra::findOrFail(41), app(\App\Services\Obras\FiniquitoMaterialConsultaService::class));
    }

    public function test_printable_finiquito_requires_view_permission(): void
    {
        $user = \Mockery::mock(User::class)->makePartial(); $user->shouldReceive('hasAnyRole')->andReturn(true); $user->shouldReceive('can')->with('obra.devoluciones.view.access')->andReturn(false); auth()->setUser($user);
        $http = Request::create('/'); $http->setUserResolver(fn () => $user);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(\App\Http\Controllers\ObraController::class)->imprimirFiniquitoMateriales($http, Obra::findOrFail(41), app(\App\Services\Obras\FiniquitoMaterialConsultaService::class));
    }

    public function test_printable_finiquito_handles_work_without_deliveries_or_requests(): void
    {
        $obra = Obra::findOrFail(41);
        $finiquito = app(\App\Services\Obras\FiniquitoMaterialConsultaService::class)->consultar($obra);
        $impresion = view('obras.devoluciones.finiquito_imprimir', ['obra' => $obra, 'finiquito' => $finiquito, 'generadoEn' => now()])->render();
        $this->assertStringContainsString('No hay entregas aplicadas identificadas', $impresion);
        $this->assertStringContainsString('No hay solicitudes de devolución', $impresion);
        $this->assertStringContainsString('ni cierra la obra', $impresion);
    }

    public function test_full_workflow_reconciles_three_origins_partial_returns_and_tool_in_evaluation(): void
    {
        DB::table('productos')->where('id', 1)->update(['tipo_inventario' => 'herramienta']);
        $direct = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        $doc = DB::table('inventario_documentos')->insertGetId(['folio' => 'SAL-INTEGRAL', 'tipo' => 'salida', 'almacen_id' => 1, 'obra_id' => 41, 'estado' => 'aplicado', 'fecha' => now()]);
        $general = DB::table('inventario_movimientos')->insertGetId(['almacen_id' => 1, 'producto_id' => 1, 'documento_id' => $doc, 'fecha' => now(), 'tipo_movimiento' => 'out', 'cantidad' => 4, 'costo_unitario' => 10, 'importe_costo' => 40, 'saldo_cantidad' => 6, 'obra_id' => 41]);
        DB::table('huentitan_salidas')->insert(['id' => 50, 'almacen_id' => 1, 'obra_id' => 41, 'estado' => 'aplicada', 'folio' => 'HUE-INTEGRAL', 'fecha' => now()]);
        $huentitan = DB::table('huentitan_salida_detalles')->insertGetId(['huentitan_salida_id' => 50, 'producto_id' => 1, 'descripcion' => 'Material', 'unidad' => 'PZA', 'cantidad_salida' => 4, 'costo_unitario' => 20, 'importe' => 80]);
        $obra = Obra::findOrFail(41); $buyer = $this->buyer();
        $solicitud = app(ObraDevolucionSolicitudService::class)->crear($obra, $buyer, ['operacion_uuid' => (string) Str::uuid(), 'accion' => 'solicitar', 'partidas' => [
            ['origen_tipo' => 'recepcion_directa', 'origen_id' => $direct->detalles()->sole()->id, 'cantidad' => 4, 'condicion' => 'bueno'],
            ['origen_tipo' => 'salida_general', 'origen_id' => $general, 'cantidad' => 3, 'condicion' => 'bueno'],
            ['origen_tipo' => 'salida_huentitan', 'origen_id' => $huentitan, 'cantidad' => 2, 'condicion' => 'desgastado'],
        ]]);
        $directLine = $solicitud->detalles()->whereNotNull('recepcion_directa_detalle_id')->sole();
        $generalLine = $solicitud->detalles()->whereNotNull('inventario_movimiento_id')->sole();
        $toolLine = $solicitud->detalles()->whereNotNull('huentitan_salida_detalle_id')->sole();
        $receptionService = app(\App\Services\Obras\ObraDevolucionRecepcionService::class);
        $apply = app(\App\Services\Obras\ObraDevolucionAplicacionService::class);
        $input = ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [
            ['id' => $directLine->id, 'cantidad' => 2, 'condicion' => 'bueno'], ['id' => $generalLine->id, 'cantidad' => 2, 'condicion' => 'bueno'],
            ['id' => $toolLine->id, 'cantidad' => 1, 'condicion' => 'desgastado', 'observaciones' => 'Evaluar desgaste'],
        ]];
        $mixed = $receptionService->confirmar($solicitud, $buyer, $input);
        $this->assertSame($mixed->id, $receptionService->confirmar($solicitud, $buyer, $input)->id);
        app(\App\Services\Obras\ObraDevolucionEvaluacionPendienteService::class)->registrar($mixed, $buyer);
        $stockBefore = (float) DB::table('inventario_stock')->where('almacen_id', 1)->where('producto_id', 1)->value('stock_actual');
        $apply->aplicar($mixed, $buyer); $apply->aplicar($mixed, $buyer);
        $this->assertEquals($stockBefore + 4, DB::table('inventario_stock')->where('almacen_id', 1)->where('producto_id', 1)->value('stock_actual'));
        $mixedDetails = $mixed->detalles()->get()->keyBy('devolucion_detalle_id');
        $this->assertEquals(300, $mixedDetails[$directLine->id]->importe_mxn);
        $this->assertEquals(20, $mixedDetails[$generalLine->id]->importe_mxn);
        $this->assertNull($mixedDetails[$toolLine->id]->inventario_movimiento_id);
        $this->assertSame('pendiente_evaluacion', $mixedDetails[$toolLine->id]->estado_evaluacion);
        $later = $receptionService->confirmar($solicitud, $buyer, ['operacion_uuid' => (string) Str::uuid(), 'almacen_id' => 1, 'fecha_recepcion' => now()->toDateString(), 'partidas' => [
            ['id' => $directLine->id, 'cantidad' => 2, 'condicion' => 'bueno'], ['id' => $generalLine->id, 'cantidad' => 1, 'condicion' => 'bueno'],
            ['id' => $toolLine->id, 'cantidad' => 1, 'condicion' => 'desgastado'],
        ]]);
        app(\App\Services\Obras\ObraDevolucionEvaluacionPendienteService::class)->registrar($later, $buyer);
        $apply->aplicar($later, $buyer);
        $finiquito = app(\App\Services\Obras\FiniquitoMaterialConsultaService::class)->consultar($obra);
        $this->assertCount(3, $finiquito['entregas']);
        $rows = $finiquito['entregas']->keyBy(fn ($row) => $row['entrega']->origen_tipo);
        $this->assertSame(6.0, $rows['recepcion_directa']['sin_devolucion_fisica']);
        $this->assertSame(1.0, $rows['salida_general']['sin_devolucion_fisica']);
        $this->assertSame(2.0, $rows['salida_huentitan']['sin_devolucion_fisica']);
        $this->assertSame(2.0, $rows['salida_huentitan']['fisico_pendiente_aplicar']);
        $this->assertSame(2.0, $rows['salida_huentitan']['entrega']->cantidad_pendiente_evaluacion);
        $this->assertEquals(900, $rows['recepcion_directa']['entrega']->importe_neto_mxn);
        $this->assertEquals(10, $rows['salida_general']['entrega']->importe_neto_mxn);
        $this->assertEquals(80, $rows['salida_huentitan']['entrega']->importe_neto_mxn);
        $this->assertSame('recepcion_parcial', $solicitud->fresh()->estado);
        $printed = view('obras.devoluciones.finiquito_imprimir', ['obra' => $obra, 'finiquito' => $finiquito, 'generadoEn' => now()])->render();
        foreach ([$solicitud->folio, $mixed->folio, $later->folio, 'SAL-INTEGRAL', 'HUE-INTEGRAL', 'Pendiente de evaluación'] as $text) $this->assertStringContainsString($text, $printed);
        $this->assertEquals(7, (float) DB::table('inventario_stock')->where('almacen_id', 1)->where('producto_id', 1)->value('stock_actual') - $stockBefore);
    }
}
