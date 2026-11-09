<?php

namespace Tests\Feature;

use App\Models\OrdenCompra;
use App\Services\Compras\CompraRecepcionDirectaObraService;
use App\Services\Compras\CompraRecepcionDirectaReversionService;
use App\Services\Compras\CompraRecepcionDirectaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class CompraRecepcionDirectaReversionTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    protected function setUp(): void { parent::setUp(); $this->buildDirectReceiptDatabase(); }

    public function test_reversal_preserves_history_compensates_kardex_and_restores_order(): void
    {
        $rec = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        app(CompraRecepcionDirectaReversionService::class)->revertir($rec, $this->buyer(), 'Entrega capturada incorrectamente');
        $this->assertSame('revertida', $rec->fresh()->estado);
        $this->assertSame('Entrega capturada incorrectamente', $rec->fresh()->motivo_reversion);
        $this->assertSame(0.0, (float) OrdenCompra::find(1)->detalles()->first()->cantidad_recibida);
        $this->assertSame('pendiente', OrdenCompra::find(1)->estado_recepcion);
        $this->assertSame(0.0, app(CompraRecepcionDirectaObraService::class)->resumen(41)['total_mxn']);
        $this->assertSame(4, DB::table('inventario_movimientos')->count());
        $this->assertSame(8.0, (float) DB::table('inventario_movimientos')->where('tipo_movimiento', 'in')->sum('cantidad'));
        $this->assertSame(8.0, (float) DB::table('inventario_movimientos')->where('tipo_movimiento', 'out')->sum('cantidad'));
        $this->assertSame(10.0, (float) DB::table('inventario_stock')->value('stock_actual'));
        $this->assertSame(2.0, (float) DB::table('inventario_stock')->value('stock_reservado'));
        $this->assertSame(1000.0, (float) DB::table('inventario_stock')->value('valor_total'));
        $this->expectException(ValidationException::class);
        app(CompraRecepcionDirectaReversionService::class)->revertir($rec, $this->buyer(), 'Repetida');
    }

    public function test_reversal_restores_requisition_balance_and_preserves_other_partial_delivery(): void
    {
        DB::table('obra_requisiciones')->insert(['id' => 1, 'obra_id' => 41, 'estado' => 'autorizada', 'solicitado_por' => 1, 'autorizado_por' => 2, 'autorizado_at' => now()]);
        DB::table('obra_requisicion_detalles')->insert(['id' => 1, 'obra_requisicion_id' => 1, 'item_type' => 'producto', 'item_id' => 1, 'unidad' => 'PZA', 'cantidad_autorizada' => 10]);
        DB::table('orden_compra_detalles')->where('id', 1)->update(['obra_requisicion_detalle_id' => 1]);
        $svc = app(CompraRecepcionDirectaService::class);
        $first = $svc->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        $svc->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(3), $this->buyer());
        app(CompraRecepcionDirectaReversionService::class)->revertir($first, $this->buyer(), 'Corrección');
        $this->assertSame(3.0, (float) DB::table('obra_requisicion_detalles')->value('cantidad_surtida'));
        $this->assertSame('surtida_parcial', DB::table('obra_requisicion_detalles')->value('estado'));
        $this->assertSame(3.0, (float) DB::table('orden_compra_detalles')->value('cantidad_recibida'));
        $this->assertSame(450.0, app(CompraRecepcionDirectaObraService::class)->resumen(41)['total_mxn']);
    }

    public function test_reversal_requires_special_permission_and_keeps_data_when_rejected(): void
    {
        $rec = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        $user = \Mockery::mock(\App\Models\User::class)->makePartial();
        $user->shouldReceive('can')->with(\App\Models\CompraRecepcionDirecta::PERMISO_REVERTIR)->andReturn(false);
        try {
            app(CompraRecepcionDirectaReversionService::class)->revertir($rec, $user, 'Corrección');
            $this->fail('Debe exigir permiso especial.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertSame('aplicada', $rec->fresh()->estado);
            $this->assertSame(2, DB::table('inventario_movimientos')->count());
        }
    }

    public function test_inconsistent_requisition_rolls_back_reversal(): void
    {
        DB::table('obra_requisiciones')->insert(['id' => 1, 'obra_id' => 41, 'estado' => 'autorizada', 'solicitado_por' => 1, 'autorizado_por' => 2, 'autorizado_at' => now()]);
        DB::table('obra_requisicion_detalles')->insert(['id' => 1, 'obra_requisicion_id' => 1, 'item_type' => 'producto', 'item_id' => 1, 'unidad' => 'PZA', 'cantidad_autorizada' => 10]);
        DB::table('orden_compra_detalles')->where('id', 1)->update(['obra_requisicion_detalle_id' => 1]);
        $rec = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        DB::table('obra_requisicion_detalles')->where('id', 1)->update(['cantidad_surtida' => 1]);
        try {
            app(CompraRecepcionDirectaReversionService::class)->revertir($rec, $this->buyer(), 'Corrección');
            $this->fail('Debe rechazar un saldo inconsistente.');
        } catch (ValidationException $e) {
            $this->assertSame('aplicada', $rec->fresh()->estado);
            $this->assertSame(4.0, (float) DB::table('orden_compra_detalles')->value('cantidad_recibida'));
            $this->assertSame(2, DB::table('inventario_movimientos')->count());
        }
    }

    public function test_failure_during_compensating_movements_rolls_back_documents_and_balances(): void
    {
        $rec = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        DB::unprepared("CREATE TRIGGER fail_reversal BEFORE INSERT ON inventario_movimientos BEGIN SELECT RAISE(ABORT, 'failure'); END");
        try {
            app(CompraRecepcionDirectaReversionService::class)->revertir($rec, $this->buyer(), 'Corrección');
            $this->fail('Debe revertir la transacción ante un error.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertSame('aplicada', $rec->fresh()->estado);
            $this->assertSame(2, DB::table('inventario_documentos')->count());
            $this->assertSame(2, DB::table('inventario_movimientos')->count());
            $this->assertSame(4.0, (float) DB::table('orden_compra_detalles')->value('cantidad_recibida'));
        }
    }
}
