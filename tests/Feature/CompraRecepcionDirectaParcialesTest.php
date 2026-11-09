<?php

namespace Tests\Feature;

use App\Models\CompraRecepcionDirecta;
use App\Models\OrdenCompra;
use App\Services\Compras\CompraRecepcionDirectaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class CompraRecepcionDirectaParcialesTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    protected function setUp(): void { parent::setUp(); $this->buildDirectReceiptDatabase(); }

    public function test_litros_legacy_abbreviation_matches_litre_base_unit(): void
    {
        DB::table('productos')->where('id', 1)->update(['unidad' => 'LTS', 'unidad_base' => 'LT', 'unidad_compra' => 'LT', 'cantidad_por_unidad_compra' => 1]);
        DB::table('orden_compra_detalles')->where('id', 1)->update(['unidad' => 'LTS']);
        $recepcion = app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        $detalle = $recepcion->detalles()->firstOrFail();
        $this->assertSame(4.0, (float) $detalle->cantidad_inventario);
        $this->assertSame('LT', $detalle->unidad_inventario);
        $this->assertSame(1.0, (float) $detalle->factor_inventario);
    }

    public function test_several_partial_deliveries_cover_each_line_at_the_same_destination(): void
    {
        DB::table('productos')->insert(['id' => 2, 'nombre' => 'Segundo material', 'sku' => 'GL-2', 'unidad' => 'PZA', 'activo' => true]);
        DB::table('orden_compra_detalles')->insert(['id' => 2, 'orden_compra_id' => 1, 'producto_id' => 2,
            'descripcion' => 'Segundo material', 'unidad' => 'PZA', 'cantidad' => 5, 'precio_unitario' => 80, 'importe' => 400]);
        $service = app(CompraRecepcionDirectaService::class);
        $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(4), $this->buyer());
        $this->assertSame('parcial', OrdenCompra::findOrFail(1)->estado_recepcion);
        $second = $this->receiptInput(6);
        $second['partidas'][] = ['orden_compra_detalle_id' => 2, 'cantidad_recibida' => 2];
        $service->confirmar(OrdenCompra::findOrFail(1), $second, $this->buyer());
        $this->assertSame('parcial', OrdenCompra::findOrFail(1)->estado_recepcion);
        $third = $this->receiptInput(3);
        $third['partidas'][0]['orden_compra_detalle_id'] = 2;
        $service->confirmar(OrdenCompra::findOrFail(1), $third, $this->buyer());
        $this->assertSame('recibida', OrdenCompra::findOrFail(1)->estado_recepcion);
        $this->assertSame([41], CompraRecepcionDirecta::pluck('obra_id')->unique()->all());
        $this->assertSame([1], CompraRecepcionDirecta::pluck('almacen_id')->unique()->all());
        $this->assertSame(1900.0, (float) CompraRecepcionDirecta::sum('total_mxn'));
        $this->assertSame([10.0, 5.0], DB::table('orden_compra_detalles')->orderBy('id')->pluck('cantidad_recibida')->map(fn ($v) => (float) $v)->all());
        $this->assertSame(10.0, (float) DB::table('inventario_stock')->where('producto_id', 1)->value('stock_actual'));
        $this->assertSame(0.0, (float) DB::table('inventario_stock')->where('producto_id', 2)->value('stock_actual'));
    }

    public function test_receipt_cannot_split_destinations_in_the_payload(): void
    {
        foreach (['destinos', 'obra_id', 'almacen_id', 'destino_partida'] as $field) {
            $input = $this->receiptInput();
            if ($field === 'destinos') $input['destinos'] = [['obra_id' => 42]];
            elseif ($field === 'destino_partida') $input['partidas'][0]['obra_id'] = 42;
            else $input[$field] = 42;
            try {
                app(CompraRecepcionDirectaService::class)->confirmar(OrdenCompra::findOrFail(1), $input, $this->buyer());
                $this->fail('No debe aceptar un destino alternativo.');
            } catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
        $this->assertSame(0, CompraRecepcionDirecta::count());
    }

    public function test_completed_line_cannot_receive_an_extra_delivery(): void
    {
        $service = app(CompraRecepcionDirectaService::class);
        $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(10), $this->buyer());
        try {
            $service->confirmar(OrdenCompra::findOrFail(1), $this->receiptInput(1), $this->buyer());
            $this->fail('No debe permitir sobre-recepción.');
        } catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        $this->assertSame(1, CompraRecepcionDirecta::count());
        $this->assertSame(10.0, (float) DB::table('orden_compra_detalles')->value('cantidad_recibida'));
    }
}
