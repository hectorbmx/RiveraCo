<?php

namespace Tests\Unit;

use App\Models\Almacen;
use App\Models\Obra;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Producto;
use App\Services\Compras\CompraRecepcionDirectaValidacionService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompraRecepcionDirectaValidacionTest extends TestCase
{
    private function order(): OrdenCompra
    {
        $product = new Producto(['sku' => 'GL-1', 'activo' => true, 'unidad' => 'PZA']);
        $line = new OrdenCompraDetalle(['orden_compra_id' => 1, 'cantidad' => 10, 'cantidad_recibida' => 4, 'unidad' => 'PZA', 'producto_id' => 1]);
        $line->id = 1;
        $line->setRelation('producto', $product);
        $order = new OrdenCompra(['modalidad_entrega' => 'obra_directa', 'estado' => 'AUTORIZADA', 'area_id' => 1]);
        $order->id = 1;
        $order->setRelation('obra', new Obra(['estatus_nuevo' => Obra::ESTATUS_EJECUCION]));
        $order->setRelation('almacenDestino', new Almacen(['codigo' => 'GL', 'activo' => true, 'area_id' => 1]));
        $order->setRelation('detalles', collect([$line]));

        return $order;
    }

    private function input(int $id = 1, float $quantity = 6): array
    {
        return [['orden_compra_detalle_id' => $id, 'cantidad_recibida' => $quantity]];
    }

    public function test_authorized_pending_quantity_is_accepted_without_mutating_the_order(): void
    {
        $order = $this->order();
        $result = app(CompraRecepcionDirectaValidacionService::class)->validar($order, $this->input())->sole();
        $this->assertSame(6.0, $result['cantidad']);
        $this->assertSame(6.0, $result['cantidad_inventario']);
        $this->assertSame('4.000', $order->detalles->sole()->cantidad_recibida);
    }

    public function test_foreign_line_excess_and_duplicate_are_rejected(): void
    {
        foreach ([$this->input(2), $this->input(1, 6.001), array_merge($this->input(), $this->input())] as $input) {
            try {
                app(CompraRecepcionDirectaValidacionService::class)->validar($this->order(), $input);
                $this->fail('La partida inválida no debe recibirse.');
            } catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
    }

    public function test_changed_order_work_warehouse_or_catalog_are_rejected(): void
    {
        foreach (['unauthorized', 'closed_work', 'inactive_warehouse', 'wrong_catalog', 'inactive_product'] as $case) {
            $order = $this->order();
            if ($case === 'unauthorized') $order->estado = 'BORRADOR';
            if ($case === 'closed_work') $order->obra->estatus_nuevo = Obra::ESTATUS_TERMINADA;
            if ($case === 'inactive_warehouse') $order->almacenDestino->activo = false;
            if ($case === 'wrong_catalog') $order->detalles->sole()->producto->sku = 'HUE-1';
            if ($case === 'inactive_product') $order->detalles->sole()->producto->activo = false;
            try {
                app(CompraRecepcionDirectaValidacionService::class)->validar($order, $this->input());
                $this->fail('Debe rechazar '.$case);
            } catch (ValidationException $e) { $this->assertNotEmpty($e->errors(), $case); }
        }
    }

    public function test_configured_purchase_unit_is_converted_to_inventory_units(): void
    {
        $order = $this->order();
        $line = $order->detalles->sole();
        $line->unidad = 'CAJA';
        $line->producto->unidad_compra = 'CAJA';
        $line->producto->unidad_base = 'PZA';
        $line->producto->cantidad_por_unidad_compra = 12;
        $result = app(CompraRecepcionDirectaValidacionService::class)->validar($order, $this->input(1, 2))->sole();
        $this->assertSame(24.0, $result['cantidad_inventario']);
        $this->assertSame(12.0, $result['factor_inventario']);
    }
}
