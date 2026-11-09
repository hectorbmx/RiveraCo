<?php

namespace Tests\Unit;

use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Services\Compras\CompraRecepcionDirectaPartidasService;
use Tests\TestCase;

class CompraRecepcionDirectaPartidasTest extends TestCase
{
    public function test_only_pending_quantities_are_offered_without_changing_the_order(): void
    {
        $lines = collect([
            new OrdenCompraDetalle(['cantidad' => 10, 'cantidad_recibida' => 4, 'descripcion' => 'Parcial', 'producto_id' => 1]),
            new OrdenCompraDetalle(['cantidad' => 5, 'cantidad_recibida' => 5, 'descripcion' => 'Completa', 'producto_id' => 1]),
            new OrdenCompraDetalle(['cantidad' => 2, 'cantidad_recibida' => 3, 'descripcion' => 'Histórica excedida', 'producto_id' => 1]),
            new OrdenCompraDetalle(['cantidad' => 1, 'cantidad_recibida' => 0, 'descripcion' => 'Sin catálogo']),
        ]);
        $lines->each(fn ($line) => $line->setRelation('producto', null));
        $order = new OrdenCompra();
        $order->setRelation('detalles', $lines);
        $pending = app(CompraRecepcionDirectaPartidasService::class)->pendientes($order);
        $this->assertSame(['Parcial', 'Sin catálogo'], $pending->pluck('descripcion')->all());
        $this->assertSame([6.0, 1.0], $pending->pluck('pendiente')->all());
        $this->assertSame('4.000', $lines[0]->cantidad_recibida);
        $this->assertFalse($lines[0]->getAttributes()['cantidad_pendiente'] ?? false);
    }

    public function test_decimal_balance_is_rounded_to_the_supported_quantity_precision(): void
    {
        $line = new OrdenCompraDetalle(['cantidad' => 1, 'cantidad_recibida' => 0.999, 'producto_id' => 1]);
        $line->setRelation('producto', null);
        $order = new OrdenCompra();
        $order->setRelation('detalles', collect([$line]));
        $this->assertSame(0.001, app(CompraRecepcionDirectaPartidasService::class)->pendientes($order)->sole()['pendiente']);
    }
}
