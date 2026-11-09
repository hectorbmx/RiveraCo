<?php

namespace Tests\Feature;

use App\Models\CompraRecepcionDirecta;
use App\Models\OrdenCompra;
use App\Services\Compras\CompraRecepcionDirectaService;
use App\Services\Compras\CompraRequisicionSaldoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class CompraRecepcionDirectaRequisicionTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDirectReceiptDatabase();
        DB::table('users')->insert(['id' => 2, 'name' => 'Autoriza']);
        DB::table('obra_requisiciones')->insert(['id' => 1, 'obra_id' => 41, 'estado' => 'autorizada',
            'solicitado_por' => 1, 'autorizado_por' => 2, 'autorizado_at' => now()]);
        DB::table('obra_requisicion_detalles')->insert(['id' => 1, 'obra_requisicion_id' => 1,
            'item_type' => 'producto', 'item_id' => 1, 'unidad' => 'PZA', 'cantidad_autorizada' => 10]);
        DB::table('orden_compra_detalles')->where('id', 1)->update(['obra_requisicion_detalle_id' => 1]);
    }

    public function test_partial_deliveries_update_authorized_balance_and_retry_does_not_count_twice(): void
    {
        $service = app(CompraRecepcionDirectaService::class);
        $input = $this->receiptInput(4);
        $receipt = $service->confirmar(OrdenCompra::findOrFail(1), $input, $this->buyer());
        $service->confirmar(OrdenCompra::findOrFail(1), $input, $this->buyer());
        $this->assertSame(4.0, $this->supplied());
        $this->assertSame(1, $receipt->detalles()->first()->obra_requisicion_detalle_id);
        $this->assertSame('surtida_parcial', DB::table('obra_requisicion_detalles')->value('estado'));
        $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(6), $this->buyer());
        $this->assertSame(10.0, $this->supplied());
        $this->assertSame('surtida', DB::table('obra_requisicion_detalles')->value('estado'));
        $this->assertSame(10.0, (float) DB::table('inventario_stock')->value('stock_actual'));
        $this->assertSame(2.0, (float) DB::table('inventario_stock')->value('stock_reservado'));
    }

    public function test_pending_self_authorized_and_wrong_destination_product_or_unit_are_blocked(): void
    {
        foreach ([['obra_requisiciones', ['estado' => 'pendiente_revision']],
            ['obra_requisiciones', ['autorizado_por' => 1]], ['obra_requisiciones', ['autorizado_at' => null]],
            ['obra_requisiciones', ['obra_id' => 42]], ['obra_requisicion_detalles', ['item_id' => 99]],
            ['obra_requisicion_detalles', ['item_type' => 'empleado']], ['obra_requisicion_detalles', ['unidad' => 'KG']],
            ['obra_requisicion_detalles', ['cantidad_autorizada' => null]], ['obra_requisicion_detalles', ['estado' => 'cancelada']]] as [$table, $changes]) {
            $original = (array) DB::table($table)->where('id', 1)->first();
            DB::table($table)->where('id', 1)->update($changes);
            $this->assertBlocked($this->receiptInput(1));
            DB::table($table)->where('id', 1)->update($original);
        }
        $this->assertSame(0.0, $this->supplied());
        $this->assertSame(0, CompraRecepcionDirecta::count());
    }

    public function test_balance_is_shared_by_multiple_purchase_orders(): void
    {
        app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(6), $this->buyer());
        $order = OrdenCompra::findOrFail(1)->replicate();
        $order->folio = 'OC-OTRA'; $order->save();
        $line = OrdenCompra::findOrFail(1)->detalles()->first()->replicate();
        $line->orden_compra_id = $order->id; $line->cantidad_recibida = 0; $line->save();
        $input = $this->receiptInput(5); $input['partidas'][0]['orden_compra_detalle_id'] = $line->id;
        $this->assertBlocked($input, $order);
        $input['partidas'][0]['cantidad_recibida'] = 4;
        app(CompraRecepcionDirectaService::class)->confirmar($order, $input, $this->buyer());
        $this->assertSame(10.0, $this->supplied());
        $this->assertSame(2, CompraRecepcionDirecta::count());
    }

    public function test_two_purchase_lines_cannot_each_spend_the_same_remaining_balance(): void
    {
        $line = OrdenCompra::findOrFail(1)->detalles()->first()->replicate(); $line->save();
        $input = $this->receiptInput(6);
        $input['partidas'][] = ['orden_compra_detalle_id' => $line->id, 'cantidad_recibida' => 6];
        $this->assertBlocked($input);
        $this->assertSame(0.0, $this->supplied());
    }

    public function test_purchase_units_are_converted_to_requisition_base_units(): void
    {
        DB::table('productos')->where('id', 1)->update(['unidad_base' => 'PZA', 'unidad_compra' => 'CAJA', 'cantidad_por_unidad_compra' => 5]);
        DB::table('orden_compra_detalles')->where('id', 1)->update(['unidad' => 'CAJA', 'cantidad' => 2, 'importe' => 300]);
        app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(1), $this->buyer());
        $this->assertSame(5.0, $this->supplied());
        $this->assertBlocked($this->receiptInput(1.001));
    }

    public function test_later_failure_rolls_back_the_requisition_balance(): void
    {
        DB::table('ordenes_compra')->where('id', 1)->update(['moneda' => 'USD', 'tipo_cambio' => 0]);
        $this->assertBlocked($this->receiptInput(4));
        $this->assertSame(0.0, $this->supplied());
        $this->assertSame('pendiente', DB::table('obra_requisicion_detalles')->value('estado'));
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
    }

    public function test_link_validation_rejects_purchase_quantity_above_authorization(): void
    {
        DB::table('obra_requisicion_detalles')->where('id', 1)->update(['cantidad_autorizada' => 3]);
        $this->expectException(ValidationException::class);
        DB::transaction(fn () => app(CompraRequisicionSaldoService::class)->validarVinculo(OrdenCompra::findOrFail(1), OrdenCompra::findOrFail(1)->detalles()->first()));
    }

    private function supplied(): float { return (float) DB::table('obra_requisicion_detalles')->where('id', 1)->value('cantidad_surtida'); }

    private function assertBlocked(array $input, ?OrdenCompra $order = null): void
    {
        try {
            app(CompraRecepcionDirectaService::class)->confirmar($order ?? OrdenCompra::findOrFail(1), $input, $this->buyer());
            $this->fail('Debe rechazar la entrega.');
        } catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
    }
}
