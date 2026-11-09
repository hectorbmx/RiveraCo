<?php

namespace Tests\Feature;

use App\Models\OrdenCompra;
use App\Services\Compras\OrdenCompraEntregaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrdenCompraEntregaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) $this->markTestSkipped('Requiere pdo_sqlite.');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('obras', function (Blueprint $t) {
            $t->id(); $t->integer('estatus_nuevo'); $t->softDeletes();
        });
        Schema::create('almacenes', function (Blueprint $t) {
            $t->id(); $t->boolean('activo'); $t->unsignedBigInteger('area_id')->nullable();
        });
        Schema::create('ordenes_compra', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('obra_id')->nullable(); $t->unsignedBigInteger('area_id'); $t->timestamps();
        });
        Schema::create('orden_compra_detalles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('orden_compra_id'); $t->decimal('cantidad_recibida')->default(0);
        });
        Schema::create('compra_recepciones_directas', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('orden_compra_id');
        });
        (require base_path('database/migrations/2026_11_05_210800_add_delivery_destination_to_ordenes_compra.php'))->up();
        DB::table('obras')->insert([['id' => 41, 'estatus_nuevo' => \App\Models\Obra::ESTATUS_EJECUCION], ['id' => 42, 'estatus_nuevo' => \App\Models\Obra::ESTATUS_TERMINADA]]);
        DB::table('almacenes')->insert([
            ['id' => 1, 'activo' => true, 'area_id' => 1],
            ['id' => 2, 'activo' => false, 'area_id' => 1],
            ['id' => 3, 'activo' => true, 'area_id' => 2],
        ]);
    }

    public function test_normal_orders_keep_the_existing_default(): void
    {
        $order = new OrdenCompra(['area_id' => 1]);
        app(OrdenCompraEntregaService::class)->asignar($order, []);
        $order->save();
        $this->assertSame('almacen', $order->fresh()->modalidad_entrega);
        $this->assertNull($order->fresh()->almacen_destino_id);
    }

    public function test_direct_delivery_has_one_active_work_and_warehouse(): void
    {
        $order = new OrdenCompra(['obra_id' => 41, 'area_id' => 1]);
        app(OrdenCompraEntregaService::class)->asignar($order, ['modalidad_entrega' => 'obra_directa', 'almacen_destino_id' => 1]);
        $order->save();
        $this->assertSame('obra_directa', $order->fresh()->modalidad_entrega);
        $this->assertSame(1, $order->fresh()->almacen_destino_id);
    }

    public function test_inactive_work_missing_warehouse_and_other_area_are_rejected(): void
    {
        foreach ([[42, 1], [41, null], [41, 2], [41, 3], [null, 1]] as [$workId, $warehouseId]) {
            try {
                app(OrdenCompraEntregaService::class)->asignar(new OrdenCompra(['obra_id' => $workId, 'area_id' => 1]),
                    ['modalidad_entrega' => 'obra_directa', 'almacen_destino_id' => $warehouseId]);
                $this->fail('Debe rechazar el destino inválido.');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
    }

    public function test_destination_cannot_change_after_direct_receipt(): void
    {
        $order = OrdenCompra::create(['area_id' => 1, 'obra_id' => 41]);
        DB::table('compra_recepciones_directas')->insert(['orden_compra_id' => $order->id]);
        $order->obra_id = 42;
        $this->expectException(ValidationException::class);
        $order->save();
    }

    public function test_destination_cannot_change_after_normal_receipt_either(): void
    {
        $order = OrdenCompra::create(['area_id' => 1, 'obra_id' => 41]);
        DB::table('orden_compra_detalles')->insert(['orden_compra_id' => $order->id, 'cantidad_recibida' => 1]);
        $order->modalidad_entrega = 'obra_directa';
        $this->expectException(ValidationException::class);
        $order->save();
    }
}
