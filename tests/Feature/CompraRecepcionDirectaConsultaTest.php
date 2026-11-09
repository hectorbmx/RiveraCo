<?php

namespace Tests\Feature;

use App\Http\Controllers\CompraRecepcionDirectaController;
use App\Models\CompraRecepcionDirecta;
use App\Models\OrdenCompra;
use App\Services\Compras\CompraRecepcionDirectaConsultaService;
use App\Services\Compras\CompraRecepcionDirectaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class CompraRecepcionDirectaConsultaTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    public function test_print_and_evidence_respect_receipt_ownership(): void
    {
        $receipt = $this->receive(4);
        auth()->setUser($this->buyer());
        $controller = app(CompraRecepcionDirectaController::class);
        $order = OrdenCompra::findOrFail(1);
        $html = $controller->imprimir($order, $receipt)->render();
        $this->assertStringContainsString('COMPROBANTE DE RECEPCIÓN DIRECTA EN OBRA', $html);
        $this->assertStringContainsString('$600.00', $html);
        $file = $receipt->archivos()->firstOrFail();
        $response = $controller->evidencia($order, $receipt, $file);
        $this->assertSame(200, $response->getStatusCode());
        $file->recepcion_id = 999;
        try {
            $controller->evidencia($order, $receipt, $file);
            $this->fail('No debe entregar evidencia de otra recepción.');
        } catch (HttpException $e) { $this->assertSame(404, $e->getStatusCode()); }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->buildDirectReceiptDatabase();
    }

    public function test_detail_requires_view_permission_even_when_confirmation_is_allowed(): void
    {
        $user = \Mockery::mock(\App\Models\User::class)->makePartial();
        $user->shouldReceive('can')->with(CompraRecepcionDirecta::PERMISO_CONFIRMAR)->andReturn(true);
        $user->shouldReceive('can')->with(CompraRecepcionDirecta::PERMISO_VER)->andReturn(false);
        auth()->setUser($user);
        try {
            app(CompraRecepcionDirectaController::class)->show(new OrdenCompra(['id' => 1]), new CompraRecepcionDirecta());
            $this->fail('Debe exigir permiso de consulta.');
        } catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
    }

    public function test_detail_cannot_be_opened_under_another_purchase_order(): void
    {
        auth()->setUser($this->buyer());
        $order = new OrdenCompra(); $order->id = 2;
        $receipt = new CompraRecepcionDirecta(['orden_compra_id' => 1]);
        try {
            app(CompraRecepcionDirectaController::class)->show($order, $receipt);
            $this->fail('Debe comprobar la relación OC/recepción.');
        } catch (HttpException $e) { $this->assertSame(404, $e->getStatusCode()); }
    }

    public function test_route_has_separate_read_permission_and_does_not_shadow_capture(): void
    {
        $route = Route::getRoutes()->getByName('ordenes_compra.recepciones_directas.show');
        $this->assertContains('permission:'.CompraRecepcionDirecta::PERMISO_VER, $route->gatherMiddleware());
        $this->assertContains('GET', $route->methods());
        $capture = Route::getRoutes()->match(\Illuminate\Http\Request::create('/ordenes_compra/1/recepciones-directas/create'));
        $this->assertSame('ordenes_compra.recepciones_directas.create', $capture->getName());
    }

    public function test_queries_are_scoped_to_order_or_work_and_include_reverted_history(): void
    {
        $receipt = $this->receive(4);
        DB::table('obras')->insert(['id' => 42, 'nombre' => 'Otra obra', 'estatus_nuevo' => 2]);
        $otherOrder = OrdenCompra::findOrFail(1)->replicate();
        $otherOrder->folio = 'OC-OTRA'; $otherOrder->obra_id = 42; $otherOrder->save();
        $other = $receipt->replicate();
        $other->operacion_uuid = (string) \Illuminate\Support\Str::uuid();
        $other->folio = 'RDO-OTRA'; $other->orden_compra_id = $otherOrder->id; $other->obra_id = 42;
        $other->entrada_documento_id = null; $other->salida_documento_id = null; $other->save();
        $receipt->update(['estado' => 'revertida']);
        $service = app(CompraRecepcionDirectaConsultaService::class);
        $this->assertSame([$receipt->id], $service->porOrden(1)->pluck('id')->all());
        $this->assertSame([$receipt->id], $service->porObra(41)->pluck('id')->all());
        $this->assertSame([$other->id], $service->porObra(42)->pluck('id')->all());
        $history = $service->porOrden(1);
        $html = view('compras.recepciones-directas.partials.historial', ['recepcionesDirectas' => $history])->render();
        $this->assertStringContainsString('Revertida', $html);
        $this->assertStringContainsString('Importe registrado MXN', $html);
        $this->assertStringContainsString(route('ordenes_compra.recepciones_directas.show', [1, $receipt]), $html);
        $this->assertStringNotContainsString('RDO-OTRA', $html);
        $obraHtml = view('compras.recepciones-directas.partials.historial', ['recepcionesDirectas' => $history, 'mostrarMateriales' => true, 'montoNeto' => 0])->render();
        $this->assertStringContainsString('Recepciones directas en obra', $obraHtml);
        $this->assertStringContainsString('Monto neto entregado', $obraHtml);
        $this->assertStringContainsString('Revertida', $obraHtml);
        $this->assertStringContainsString('materiales-rdo-'.$receipt->id, $obraHtml);
        $this->assertStringContainsString('Material', $obraHtml);
        $this->assertStringContainsString('4.000 PZA', $obraHtml);
    }

    public function test_history_paginates_without_losing_work_tab(): void
    {
        $receipt = $this->receive(1);
        for ($n = 0; $n < 10; $n++) {
            $copy = $receipt->replicate();
            $copy->operacion_uuid = (string) \Illuminate\Support\Str::uuid(); $copy->folio = 'RDO-PAG-'.$n;
            $copy->entrada_documento_id = null; $copy->salida_documento_id = null; $copy->save();
        }
        request()->query->set('tab', 'ordenes-compra');
        $page = app(CompraRecepcionDirectaConsultaService::class)->porObra(41);
        $this->assertSame(11, $page->total());
        $this->assertCount(10, $page->items());
        $this->assertSame($copy->id, $page->first()->id);
        $this->assertStringContainsString('tab=ordenes-compra', $page->url(2));
        $this->assertStringContainsString('recepciones_page=2', $page->url(2));
    }

    public function test_detail_preserves_historical_delivery_and_shows_current_balances_and_origin(): void
    {
        $this->linkRequisition();
        $first = $this->receive(4); $this->receive(3);
        $viewer = $this->buyer();
        $viewer->setRelation('unreadNotifications', collect());
        $viewer->setRelation('empleado', null);
        $viewer->shouldReceive('getAllPermissions')->andReturn(collect());
        auth()->setUser($viewer);
        $view = app(CompraRecepcionDirectaController::class)->show(OrdenCompra::findOrFail(1), $first);
        $receipt = $view->getData()['recepcion'];
        $this->assertSame('compras.recepciones-directas.show', $view->name());
        $this->assertSame(4.0, (float) $receipt->detalles->sole()->cantidad_recibida);
        $this->assertSame(7.0, (float) $receipt->detalles->sole()->ordenCompraDetalle->cantidad_recibida);
        $html = view('compras.recepciones-directas.partials.detalle', ['recepcion' => $receipt])->render();
        $this->assertStringContainsString('REQ-PRUEBA', $html);
        $this->assertStringContainsString('OC pendiente: 3.000 PZA', $html);
        $this->assertStringContainsString('Requisición surtida: 7.000 PZA', $html);
        $this->assertStringContainsString('$600.00', $html);
        $this->assertStringContainsString('4.000 PZA', $html);
        $this->assertSame('ENTD-00000001', $receipt->entrada->folio);
        $this->assertSame('SALD-00000002', $receipt->salida->folio);
        $this->assertSame('Compras de prueba', $receipt->confirmadoPor->name);
        $fullHtml = $view->render();
        $this->assertStringContainsString('ENTD-00000001', $fullHtml);
        $this->assertStringContainsString('SALD-00000002', $fullHtml);
        $this->assertStringContainsString(route('obras.edit', ['obra' => 41, 'tab' => 'ordenes-compra']), $fullHtml);
    }

    public function test_order_progress_handles_unlinked_purchase_without_inventing_an_origin(): void
    {
        $this->receive(4);
        $order = OrdenCompra::with('detalles.requisicionDetalle.requisicion')->findOrFail(1);
        $html = view('compras.recepciones-directas.partials.avance_orden', ['oc' => $order])->render();
        $this->assertStringContainsString('Sin requisición vinculada', $html);
        $this->assertStringContainsString('6.000', $html);
        $this->assertStringContainsString('4.000', $html);
        $this->assertStringNotContainsString('obras/41/requisiciones/', $html);
        $receipt = app(CompraRecepcionDirectaConsultaService::class)->porOrden(1)->first();
        $this->assertTrue($receipt->relationLoaded('detalles'));
        $this->assertTrue($receipt->detalles->first()->relationLoaded('ordenCompraDetalle'));
    }

    public function test_order_trace_shows_all_partial_receipts_and_separates_reversed_amounts(): void
    {
        $first = $this->receive(4); $second = $this->receive(3);
        app(\App\Services\Compras\CompraRecepcionDirectaReversionService::class)->revertir($first, $this->buyer(), 'Prueba');
        auth()->setUser($this->buyer());
        \Illuminate\Support\Facades\Gate::before(fn () => true);
        $html = view('obras.partials.ordenes_compra.trazabilidad', [
            'ordenTrazada' => OrdenCompra::findOrFail(1),
            'recepcionesPorOrden' => app(CompraRecepcionDirectaConsultaService::class)->todasPorObra(41),
            'materialDirectoObra' => app(\App\Services\Compras\CompraRecepcionDirectaObraService::class)->resumen(41),
        ])->render();
        $this->assertStringContainsString($first->folio, $html);
        $this->assertStringContainsString($second->folio, $html);
        $this->assertStringContainsString('Revertida', $html);
        $this->assertStringContainsString('Aplicada', $html);
        $this->assertStringContainsString('Material directo neto: $450.00', $html);
        $this->assertStringContainsString('No integra el monto neto entregado', $html);
        $this->assertStringContainsString('4.000 PZA', $html);
        $this->assertStringContainsString('3.000 PZA', $html);
    }

    private function receive(float $quantity): CompraRecepcionDirecta
    {
        return app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput($quantity), $this->buyer());
    }

    private function linkRequisition(): void
    {
        DB::table('obra_requisiciones')->insert(['id' => 1, 'obra_id' => 41, 'folio' => 'REQ-PRUEBA',
            'estado' => 'autorizada', 'solicitado_por' => 1, 'autorizado_por' => 2, 'autorizado_at' => now()]);
        DB::table('obra_requisicion_detalles')->insert(['id' => 1, 'obra_requisicion_id' => 1,
            'item_type' => 'producto', 'item_id' => 1, 'unidad' => 'PZA', 'cantidad_autorizada' => 10]);
        DB::table('orden_compra_detalles')->where('id', 1)->update(['obra_requisicion_detalle_id' => 1]);
    }
}
