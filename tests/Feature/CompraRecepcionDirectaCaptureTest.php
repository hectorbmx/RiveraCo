<?php

namespace Tests\Feature;

use App\Http\Controllers\CompraRecepcionDirectaController;
use App\Models\CompraRecepcionDirecta;
use App\Models\OrdenCompra;
use App\Models\User;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CompraRecepcionDirectaCaptureTest extends TestCase
{
    private function authorizeBuyer(bool $allowed): void
    {
        $buyer = Mockery::mock(User::class)->makePartial();
        $buyer->shouldReceive('can')->with(CompraRecepcionDirecta::PERMISO_CONFIRMAR)->andReturn($allowed);
        auth()->setUser($buyer);
    }

    public function test_capture_requires_the_confirmation_permission(): void
    {
        $this->authorizeBuyer(false);
        $this->expectException(HttpException::class);
        try { app(CompraRecepcionDirectaController::class)->create(new OrdenCompra()); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); throw $e; }
    }

    public function test_capture_rejects_normal_or_unauthorized_orders(): void
    {
        $this->authorizeBuyer(true);
        foreach ([['modalidad_entrega' => 'almacen', 'estado' => 'AUTORIZADA'], ['modalidad_entrega' => 'obra_directa', 'estado' => 'BORRADOR']] as $attrs) {
            try {
                app(CompraRecepcionDirectaController::class)->create(new OrdenCompra($attrs));
                $this->fail('Debe rechazar la orden incompatible.');
            } catch (HttpException $e) { $this->assertSame(422, $e->getStatusCode()); }
        }
    }

    public function test_capture_keeps_the_order_context_and_posts_to_its_own_order(): void
    {
        $this->authorizeBuyer(true);
        $order = Mockery::mock(OrdenCompra::class)->makePartial();
        $order->modalidad_entrega = 'obra_directa';
        $order->estado = 'AUTORIZADA';
        $order->id = 1;
        $order->setRelation('detalles', collect());
        $order->shouldReceive('load')->once()->andReturnSelf();
        $order->shouldReceive('loadMissing')->once()->andReturnSelf();
        $view = app(CompraRecepcionDirectaController::class)->create($order);
        $this->assertSame('compras.recepciones-directas.create', $view->name());
        $this->assertSame($order, $view->getData()['ordenCompra']);
        $this->assertTrue($view->getData()['confirmacionHabilitada']);
        $this->assertSame(route('ordenes_compra.recepciones_directas.store', $order), $view->getData()['accionConfirmar']);
    }
}
