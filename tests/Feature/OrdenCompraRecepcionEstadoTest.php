<?php

namespace Tests\Feature;

use App\Models\OrdenCompra;
use App\Services\Compras\CompraRecepcionDirectaService;
use App\Services\Compras\OrdenCompraRecepcionEstadoService;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class OrdenCompraRecepcionEstadoTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    protected function setUp(): void { parent::setUp(); $this->buildDirectReceiptDatabase(); }

    public function test_partial_then_complete_receipt_updates_lines_and_header_without_changing_authorization(): void
    {
        $service = app(CompraRecepcionDirectaService::class);
        $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        $this->assertSame('parcial', OrdenCompra::findOrFail(1)->estado_recepcion);
        $this->assertSame('parcial', DB::table('orden_compra_detalles')->value('estado_recepcion'));
        $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(6), $this->buyer());
        $order = OrdenCompra::findOrFail(1);
        $this->assertSame('recibida', $order->estado_recepcion);
        $this->assertSame('recibida', DB::table('orden_compra_detalles')->value('estado_recepcion'));
        $this->assertSame('AUTORIZADA', $order->estado);
    }

    public function test_excess_on_one_historical_line_does_not_hide_a_missing_different_line(): void
    {
        DB::table('orden_compra_detalles')->where('id', 1)->update(['cantidad_recibida' => 100]);
        DB::table('orden_compra_detalles')->insert(['id' => 2, 'orden_compra_id' => 1, 'producto_id' => 1,
            'descripcion' => 'Otra partida', 'unidad' => 'PZA', 'cantidad' => 50, 'precio_unitario' => 1]);
        app(OrdenCompraRecepcionEstadoService::class)->sincronizar(OrdenCompra::findOrFail(1));
        $this->assertSame('parcial', OrdenCompra::findOrFail(1)->estado_recepcion);
        $this->assertSame('pendiente', DB::table('orden_compra_detalles')->where('id', 2)->value('estado_recepcion'));
        $this->assertSame(100.0, (float) DB::table('orden_compra_detalles')->where('id', 1)->value('cantidad_recibida'));
    }

    public function test_resetting_received_quantity_restores_pending_state(): void
    {
        DB::table('orden_compra_detalles')->where('id', 1)->update(['cantidad_recibida' => 4]);
        $state = app(OrdenCompraRecepcionEstadoService::class);
        $state->sincronizar(OrdenCompra::findOrFail(1));
        DB::table('orden_compra_detalles')->where('id', 1)->update(['cantidad_recibida' => 0]);
        $state->sincronizar(OrdenCompra::findOrFail(1));
        $this->assertSame('pendiente', OrdenCompra::findOrFail(1)->estado_recepcion);
        $this->assertSame('pendiente', DB::table('orden_compra_detalles')->value('estado_recepcion'));
    }
}
