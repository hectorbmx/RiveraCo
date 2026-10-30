<?php

namespace Tests\Feature;

use App\Http\Controllers\Inventario\InventarioDocumentoController;
use App\Services\Inventario\InventarioDocumentoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventarioAlmacenGiraldaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) { $this->markTestSkipped('Requiere pdo_sqlite.'); }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('almacenes', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->string('codigo')->nullable(); $t->boolean('activo');
        });
        DB::table('almacenes')->insert([
            ['id' => 1, 'nombre' => 'Almacén Giralda', 'codigo' => null, 'activo' => true],
            ['id' => 2, 'nombre' => 'AL-HUENTITAN', 'codigo' => 'AL-HUENTITAN', 'activo' => true],
        ]);
        Schema::create('inventario_documentos', function (Blueprint $t) {
            $t->id();
            foreach (['almacen_id', 'obra_id', 'proveedor_id', 'orden_compra_id', 'mantenimiento_abastecimiento_id', 'creado_por', 'residente_id'] as $campo) { $t->unsignedBigInteger($campo)->nullable(); }
            foreach (['folio', 'tipo', 'estado', 'motivo', 'notas'] as $campo) { $t->string($campo)->nullable(); }
            $t->dateTime('fecha')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('inventario_documento_detalles', function (Blueprint $t) {
            $t->id();
            foreach (['documento_id', 'producto_id', 'vehiculo_id', 'maquina_id', 'orden_compra_id', 'orden_compra_detalle_id'] as $campo) { $t->unsignedBigInteger($campo)->nullable(); }
            foreach (['equipo_tipo', 'equipo_nombre_snapshot', 'equipo_identificador_snapshot', 'notas'] as $campo) { $t->string($campo)->nullable(); }
            $t->decimal('cantidad'); $t->decimal('costo_unitario')->nullable(); $t->timestamps();
        });
        Schema::create('obras', function (Blueprint $t) {
            $t->id(); $t->string('clave_obra'); $t->string('nombre'); $t->string('status'); $t->string('estatus_nuevo'); $t->softDeletes();
        });
        Schema::create('proveedores', function (Blueprint $t) {
            $t->id(); $t->string('rfc'); $t->string('nombre'); $t->boolean('activo');
        });
        Schema::create('vehiculos', function (Blueprint $t) {
            $t->id(); $t->string('marca'); $t->string('modelo'); $t->string('placas'); $t->string('tipo');
        });
        Schema::create('maquinas', function (Blueprint $t) {
            $t->id(); foreach (['nombre', 'codigo', 'tipo', 'marca', 'modelo', 'estado'] as $campo) { $t->string($campo); }
        });
        Schema::create('mantenimiento_abastecimientos', function (Blueprint $t) { $t->id(); $t->string('tipo'); $t->string('estado'); });
        Schema::create('mantenimiento_abastecimiento_detalles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('abastecimiento_id'); $t->decimal('cantidad_solicitada'); $t->decimal('cantidad_atendida');
        });
        Schema::create('ordenes_compra', function (Blueprint $t) {
            $t->id(); $t->string('folio'); $t->unsignedBigInteger('proveedor_id')->nullable(); $t->string('estado'); $t->string('estado_recepcion');
        });
        Schema::create('orden_compra_detalles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('orden_compra_id'); $t->decimal('cantidad'); $t->decimal('cantidad_recibida');
        });
    }

    public function test_entry_and_exit_forms_only_offer_giralda_even_with_another_warehouse_in_query(): void
    {
        foreach (['entrada', 'salida'] as $tipo) {
            $view = app(InventarioDocumentoController::class)->create(Request::create('/', 'GET', ['tipo' => $tipo, 'almacen_id' => 2]));
            $data = $view->getData();
            $this->assertSame(1, $data['almacenFijo']->id);
            $this->assertSame([1], $data['almacenes']->modelKeys());
        }
    }

    public function test_entry_and_exit_ignore_forged_or_missing_warehouse_ids_when_saving(): void
    {
        foreach (['entrada', 'salida'] as $tipo) {
            foreach ([null, 2, 999] as $almacenEnviado) {
                $request = Request::create('/', 'POST', ['tipo' => $tipo, 'almacen_id' => $almacenEnviado,
                    'fecha' => '2026-10-29 10:00:00', 'action' => 'draft',
                    'detalles' => [['producto_id' => 1958, 'cantidad' => 50, 'costo_unitario' => 10]]]);
                app(InventarioDocumentoController::class)->store($request, app(InventarioDocumentoService::class));
                $this->assertSame(1, (int) DB::table('inventario_documentos')->latest('id')->value('almacen_id'));
            }
        }
        $this->assertDatabaseCount('inventario_documentos', 6);
        $this->assertDatabaseMissing('inventario_documentos', ['almacen_id' => 2]);
    }

    public function test_missing_active_giralda_does_not_fall_back_to_huentitan(): void
    {
        DB::table('almacenes')->where('id', 1)->update(['activo' => false]);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(InventarioDocumentoController::class)->store(Request::create('/', 'POST', ['tipo' => 'entrada', 'almacen_id' => 2]), app(InventarioDocumentoService::class));
    }
}
