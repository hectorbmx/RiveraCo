<?php

namespace Tests\Feature;

use App\Models\CompraRecepcionDirecta;
use App\Models\OrdenCompra;
use App\Services\Compras\CompraRecepcionDirectaService;
use App\Services\Inventario\InventarioDocumentoService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class CompraRecepcionDirectaAplicacionTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    protected function setUp(): void { parent::setUp(); $this->buildDirectReceiptDatabase(); }

    public function test_one_confirmation_applies_both_documents_and_preserves_physical_stock(): void
    {
        $before = (array) DB::table('inventario_stock')->first();
        $receipt = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(), $this->buyer());
        $this->assertSame(CompraRecepcionDirecta::ESTADO_APLICADA, $receipt->estado);
        $this->assertSame(2, $receipt->documentos()->where('estado', 'aplicado')->count());
        $this->assertSame(2, $receipt->movimientos()->count());
        $this->assertSame(600.0, (float) $receipt->total_mxn);
        $this->assertSame(150.0, (float) $receipt->salida->detalles->sole()->costo_unitario);
        $this->assertSame(4.0, (float) DB::table('orden_compra_detalles')->value('cantidad_recibida'));
        $this->assertSame($before, (array) DB::table('inventario_stock')->first());
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_failure_during_outbound_movement_rolls_back_every_document_and_cleans_evidence(): void
    {
        $before = (array) DB::table('inventario_stock')->first();
        $count = 0;
        DB::listen(function (QueryExecuted $query) use (&$count) {
            if (str_starts_with(strtolower($query->sql), 'insert') && str_contains($query->sql, 'inventario_movimientos') && ++$count === 2) {
                throw new RuntimeException('Fallo de salida simulado');
            }
        });
        try {
            app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(), $this->buyer());
            $this->fail('Debe revertir la operación completa.');
        } catch (RuntimeException $e) { $this->assertSame('Fallo de salida simulado', $e->getMessage()); }
        $this->assertSame(0, CompraRecepcionDirecta::count());
        $this->assertSame(0, DB::table('inventario_documentos')->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
        $this->assertSame(0.0, (float) DB::table('orden_compra_detalles')->value('cantidad_recibida'));
        $this->assertSame($before, (array) DB::table('inventario_stock')->first());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_pair_cannot_be_cancelled_using_the_normal_inventory_action(): void
    {
        $receipt = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(), $this->buyer());
        $this->expectException(RuntimeException::class);
        app(InventarioDocumentoService::class)->cancelar($receipt->salida);
    }

    public function test_direct_order_cannot_be_received_as_a_normal_warehouse_entry(): void
    {
        $doc = \App\Models\InventarioDocumento::create([
            'tipo' => 'entrada', 'almacen_id' => 1, 'orden_compra_id' => 1,
            'estado' => 'borrador', 'fecha' => now(),
        ]);
        $doc->detalles()->create(['producto_id' => 1, 'orden_compra_id' => 1,
            'orden_compra_detalle_id' => 1, 'cantidad' => 4, 'costo_unitario' => 150]);
        try {
            app(InventarioDocumentoService::class)->aplicar($doc);
            $this->fail('No debe convertir una entrega directa en existencia física de almacén.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('entrega directa', $e->getMessage());
        }
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
        $this->assertSame(0.0, (float) DB::table('orden_compra_detalles')->value('cantidad_recibida'));
    }
}
