<?php

namespace Tests\Feature;

use App\Models\OrdenCompra;
use App\Services\Compras\CompraRecepcionDirectaObraService;
use App\Services\Compras\CompraRecepcionDirectaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class CompraRecepcionDirectaCostoObraTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;
    protected function setUp(): void { parent::setUp(); $this->buildDirectReceiptDatabase(); }

    public function test_work_cost_counts_the_outbound_amount_once_and_not_purchase_or_entry(): void
    {
        app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        $report = app(CompraRecepcionDirectaObraService::class)->resumen(41);
        $this->assertSame(600.0, $report['total_mxn']);
        $this->assertCount(1, $report['partidas']);
        $this->assertSame(4.0, (float) $report['partidas']->sole()->cantidad_neta);
        $this->assertSame(0.0, app(CompraRecepcionDirectaObraService::class)->resumen(42)['total_mxn']);
        $html = view('obras.partials.ordenes_compra.material_directo', ['materialDirectoObra' => $report])->render();
        $this->assertStringContainsString('$600.00', $html);
        $this->assertStringContainsString('Cantidad neta', $html);
    }

    public function test_repeated_product_lines_do_not_duplicate_the_work_cost(): void
    {
        DB::table('orden_compra_detalles')->insert(['id' => 2, 'orden_compra_id' => 1, 'producto_id' => 1,
            'descripcion' => 'Mismo producto, otra partida', 'unidad' => 'PZA', 'cantidad' => 2, 'precio_unitario' => 80, 'importe' => 160]);
        $input = $this->receiptInput(4);
        $input['partidas'][] = ['orden_compra_detalle_id' => 2, 'cantidad_recibida' => 2];
        app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $input, $this->buyer());
        $report = app(CompraRecepcionDirectaObraService::class)->resumen(41);
        $this->assertCount(2, $report['partidas']);
        $this->assertSame(760.0, $report['total_mxn']);
    }

    public function test_partial_receipts_reconcile_cents_to_the_order_net_amount(): void
    {
        DB::table('orden_compra_detalles')->where('id', 1)->update(['cantidad' => 3, 'precio_unitario' => 33.3333, 'importe' => 100]);
        $service = app(CompraRecepcionDirectaService::class);
        foreach ([1, 1, 1] as $quantity) $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput($quantity), $this->buyer());
        $this->assertSame([33.33, 33.34, 33.33], DB::table('compra_recepcion_directa_detalles')->orderBy('id')->pluck('importe_mxn')->map(fn ($v) => (float) $v)->all());
        $this->assertSame(100.0, app(CompraRecepcionDirectaObraService::class)->resumen(41)['total_mxn']);
    }

    public function test_changing_cost_after_receiving_material_requires_review(): void
    {
        $service = app(CompraRecepcionDirectaService::class);
        $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        DB::table('orden_compra_detalles')->where('id', 1)->update(['importe' => 1800]);
        $this->expectException(ValidationException::class);
        $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(6), $this->buyer());
    }

    public function test_foreign_currency_cost_uses_the_purchase_rate_without_revaluing_warehouse_stock(): void
    {
        DB::table('ordenes_compra')->where('id', 1)->update(['moneda' => 'USD', 'tipo_cambio' => 20]);
        app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        $this->assertSame(12000.0, app(CompraRecepcionDirectaObraService::class)->resumen(41)['total_mxn']);
        $this->assertSame(1000.0, (float) DB::table('inventario_stock')->value('valor_total'));
    }
}
