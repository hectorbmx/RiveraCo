<?php

namespace Tests\Feature;

use App\Models\CompraRecepcionDirecta;
use App\Models\OrdenCompra;
use App\Services\Compras\CompraRecepcionDirectaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class CompraRecepcionDirectaIdempotenciaTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    protected function setUp(): void { parent::setUp(); $this->buildDirectReceiptDatabase(); }

    public function test_retry_returns_the_same_receipt_without_new_movements_files_or_quantities(): void
    {
        $service = app(CompraRecepcionDirectaService::class);
        $input = $this->receiptInput();
        $first = $service->confirmar(OrdenCompra::findOrFail(1), $input, $this->buyer());
        $retry = $service->confirmar(OrdenCompra::findOrFail(1), $input, $this->buyer());
        $this->assertSame($first->id, $retry->id);
        $this->assertSame(1, CompraRecepcionDirecta::count());
        $this->assertSame(2, DB::table('inventario_documentos')->count());
        $this->assertSame(2, DB::table('inventario_movimientos')->count());
        $this->assertSame(4.0, (float) DB::table('orden_compra_detalles')->value('cantidad_recibida'));
        $this->assertSame(2.0, (float) DB::table('inventario_stock')->value('stock_reservado'));
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_operation_cannot_be_reused_with_different_quantities_or_another_order(): void
    {
        $service = app(CompraRecepcionDirectaService::class);
        $input = $this->receiptInput();
        $service->confirmar(OrdenCompra::findOrFail(1), $input, $this->buyer());
        $otherOrder = OrdenCompra::findOrFail(1)->replicate();
        $otherOrder->folio = 'OC-OTRA';
        $otherOrder->save();
        foreach ([false, true] as $other) {
            $retry = $input;
            if (! $other) $retry['partidas'][0]['cantidad_recibida'] = 5;
            try {
                $service->confirmar($other ? $otherOrder : OrdenCompra::findOrFail(1), $retry, $this->buyer());
                $this->fail('Debe detectar el cambio de operación.');
            } catch (ConflictHttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        }
        $this->assertSame(1, CompraRecepcionDirecta::count());
        $this->assertSame(4.0, (float) DB::table('orden_compra_detalles')->where('id', 1)->value('cantidad_recibida'));
    }

    public function test_reversed_receipt_cannot_be_reactivated_by_replaying_its_request(): void
    {
        $input = $this->receiptInput();
        $service = app(CompraRecepcionDirectaService::class);
        $receipt = $service->confirmar(OrdenCompra::findOrFail(1), $input, $this->buyer());
        $receipt->update(['estado' => CompraRecepcionDirecta::ESTADO_REVERTIDA]);
        $this->expectException(ConflictHttpException::class);
        $service->confirmar(OrdenCompra::findOrFail(1), $input, $this->buyer());
    }

    public function test_new_delivery_has_its_own_operation_and_only_uses_remaining_balance(): void
    {
        $service = app(CompraRecepcionDirectaService::class);
        $first = $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(), $this->buyer());
        $second = $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(), $this->buyer());
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(8.0, (float) DB::table('orden_compra_detalles')->value('cantidad_recibida'));
        $this->assertSame(10.0, (float) DB::table('inventario_stock')->value('stock_actual'));
    }
}
