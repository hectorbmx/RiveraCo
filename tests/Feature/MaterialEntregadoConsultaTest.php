<?php

namespace Tests\Feature;

use App\Models\OrdenCompra;
use App\Services\Compras\CompraRecepcionDirectaReversionService;
use App\Services\Compras\CompraRecepcionDirectaService;
use App\Services\Obras\MaterialEntregadoConsultaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class MaterialEntregadoConsultaTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    protected function setUp(): void
    {
        parent::setUp(); $this->buildDirectReceiptDatabase();
        Schema::create('huentitan_salidas', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('almacen_id'); $t->unsignedBigInteger('obra_id')->nullable(); $t->string('estado'); $t->string('folio'); $t->dateTime('fecha');
        });
        Schema::create('huentitan_salida_detalles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('huentitan_salida_id'); $t->unsignedBigInteger('producto_id')->nullable(); $t->string('descripcion');
            $t->string('unidad'); $t->decimal('cantidad_salida', 15, 3); $t->decimal('costo_unitario', 15, 4)->nullable(); $t->decimal('importe', 15, 2)->nullable();
        });
    }

    public function test_returns_applied_deliveries_from_three_sources_without_counting_documentary_entry(): void
    {
        $receipt = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        $doc = DB::table('inventario_documentos')->insertGetId(['folio' => 'SAL-NORMAL', 'tipo' => 'salida', 'estado' => 'aplicado', 'fecha' => now(), 'almacen_id' => 1, 'obra_id' => 41]);
        DB::table('inventario_movimientos')->insert(['documento_id' => $doc, 'almacen_id' => 1, 'obra_id' => 41, 'producto_id' => 1, 'fecha' => now(), 'tipo_movimiento' => 'out', 'cantidad' => 2, 'costo_unitario' => 80, 'saldo_cantidad' => 8]);
        DB::table('huentitan_salidas')->insert(['id' => 500, 'almacen_id' => 2, 'obra_id' => 41, 'estado' => 'aplicada', 'folio' => 'H-SAL', 'fecha' => now()]);
        DB::table('huentitan_salida_detalles')->insert(['id' => 1, 'huentitan_salida_id' => 500, 'producto_id' => 1, 'descripcion' => 'Material HT', 'unidad' => 'PZA', 'cantidad_salida' => 3, 'costo_unitario' => 90, 'importe' => 270]);
        $items = app(MaterialEntregadoConsultaService::class)->porObra(41)->keyBy('origen_tipo');
        $this->assertCount(3, $items);
        $this->assertSame(4.0, (float) $items['recepcion_directa']->cantidad);
        $this->assertSame(600.0, (float) $items['recepcion_directa']->importe_mxn);
        $this->assertSame(160.0, (float) $items['salida_general']->importe_mxn);
        $this->assertSame(270.0, (float) $items['salida_huentitan']->importe_mxn);
        $this->assertSame('catalogo_actual', $items['salida_general']->unidad_fuente);
        $this->assertCount(0, app(MaterialEntregadoConsultaService::class)->porObra(42));
        app(CompraRecepcionDirectaReversionService::class)->revertir($receipt, $this->buyer(), 'Prueba');
        $this->assertCount(2, app(MaterialEntregadoConsultaService::class)->porObra(41));
    }

    public function test_excludes_drafts_cancelled_and_ambiguous_document_ids(): void
    {
        foreach (['borrador', 'cancelado', 'aplicado'] as $index => $state) {
            $id = 30 + $index;
            DB::table('inventario_documentos')->insert(['id' => $id, 'folio' => 'SAL-'.$id, 'tipo' => 'salida', 'estado' => $state, 'fecha' => now(), 'almacen_id' => 1, 'obra_id' => 41]);
            DB::table('inventario_movimientos')->insert(['documento_id' => $id, 'almacen_id' => 1, 'obra_id' => 41, 'producto_id' => 1, 'fecha' => now(), 'tipo_movimiento' => 'out', 'cantidad' => 1, 'costo_unitario' => 10, 'saldo_cantidad' => 9]);
        }
        DB::table('huentitan_salidas')->insert(['id' => 32, 'almacen_id' => 1, 'obra_id' => 41, 'estado' => 'borrador', 'folio' => 'HT-COLISION', 'fecha' => now()]);
        $this->assertCount(0, app(MaterialEntregadoConsultaService::class)->porObra(41));
        $this->assertSame(32, app(MaterialEntregadoConsultaService::class)->ambiguasPorObra(41)->sole()->documento_id);
    }

    public function test_trace_retains_requisition_and_multiple_purchase_orders_without_duplicating_deliveries(): void
    {
        DB::table('obra_requisiciones')->insert(['id' => 1, 'obra_id' => 41, 'folio' => 'REQ-UNO', 'estado' => 'autorizada', 'solicitado_por' => 1, 'autorizado_por' => 2, 'autorizado_at' => now()]);
        DB::table('obra_requisicion_detalles')->insert(['id' => 1, 'obra_requisicion_id' => 1, 'item_type' => 'producto', 'item_id' => 1, 'unidad' => 'PZA', 'cantidad_autorizada' => 10]);
        DB::table('orden_compra_detalles')->where('id', 1)->update(['obra_requisicion_detalle_id' => 1]);
        $receipt = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        DB::table('huentitan_salidas')->insert(['id' => 500, 'almacen_id' => 2, 'obra_id' => 41, 'estado' => 'aplicada', 'folio' => 'H-SAL', 'fecha' => now()]);
        DB::table('huentitan_salida_detalles')->insert(['id' => 20, 'huentitan_salida_id' => 500, 'producto_id' => 1, 'descripcion' => 'Material HT', 'unidad' => 'PZA', 'cantidad_salida' => 3, 'costo_unitario' => 90, 'importe' => 270]);
        $other = OrdenCompra::findOrFail(1)->replicate(); $other->folio = 'OC-OTRA'; $other->save();
        foreach ([1, $other->id] as $orderId) DB::table('orden_compra_detalles')->insert([
            'orden_compra_id' => $orderId, 'producto_id' => 1, 'huentitan_salida_detalle_id' => 20,
            'descripcion' => 'Material', 'unidad' => 'PZA', 'cantidad' => 1, 'precio_unitario' => 90, 'importe' => 90,
        ]);
        $doc = DB::table('inventario_documentos')->insertGetId(['folio' => 'SAL-VINCULADA', 'tipo' => 'salida', 'estado' => 'aplicado', 'fecha' => now(), 'almacen_id' => 1, 'obra_id' => 41, 'orden_compra_id' => 1]);
        DB::table('inventario_movimientos')->insert(['documento_id' => $doc, 'almacen_id' => 1, 'obra_id' => 41, 'producto_id' => 1, 'fecha' => now(), 'tipo_movimiento' => 'out', 'cantidad' => 2, 'costo_unitario' => 80, 'saldo_cantidad' => 8]);

        $items = app(MaterialEntregadoConsultaService::class)->porObra(41)->keyBy('origen_tipo');
        $this->assertCount(3, $items);
        $this->assertSame('REQ-UNO', $items['recepcion_directa']->requisiciones[0]['folio']);
        $this->assertSame($receipt->folio, $items['recepcion_directa']->recepcion['folio']);
        $this->assertSame('OC-PRUEBA', $items['recepcion_directa']->ordenes_compra[0]['folio']);
        $this->assertSame('SAL-VINCULADA', $items['salida_general']->salida['folio']);
        $this->assertSame('documento', $items['salida_general']->ordenes_compra[0]['alcance']);
        $this->assertCount(2, $items['salida_huentitan']->ordenes_compra);
        $this->assertSame(3.0, (float) $items['salida_huentitan']->cantidad);
        $this->assertEquals(1030.0, $items->sum('importe_mxn'));
    }
}
