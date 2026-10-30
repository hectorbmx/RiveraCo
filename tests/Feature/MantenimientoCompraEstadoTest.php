<?php

namespace Tests\Feature;

use App\Http\Controllers\OrdenCompraController;
use App\Http\Requests\StoreOrdenCompraRequest;
use App\Models\MantenimientoAbastecimiento;
use App\Models\OrdenCompra;
use App\Models\User;
use App\Services\Mantenimiento\MantenimientoCompraEstadoService;
use App\Services\ObraCivil\ObraCivilMaterialRequestOrderService;
use App\Services\OrdenCompraNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MantenimientoCompraEstadoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Requiere pdo_sqlite.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('mantenimiento_abastecimientos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('mantenimiento_id'); $t->string('folio')->nullable();
            $t->string('tipo'); $t->string('estado'); $t->timestamps();
        });
        Schema::create('ordenes_compra', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('mantenimiento_abastecimiento_id')->nullable();
            foreach (['proveedor_id', 'obra_id', 'centro_costo_id', 'area_id', 'planeacion_gasto_id', 'registrado_por', 'autorizado_por'] as $campo) {
                $t->unsignedBigInteger($campo)->nullable();
            }
            foreach (['folio', 'estado', 'area', 'moneda', 'cotizacion', 'atencion', 'tipo_pago', 'forma_pago', 'comentarios', 'usuario_registro', 'usuario_autoriza', 'fecha', 'fecha_autorizacion'] as $campo) {
                $t->string($campo)->nullable();
            }
            foreach (['subtotal', 'iva', 'otros_impuestos', 'total', 'tipo_cambio'] as $campo) { $t->decimal($campo)->nullable(); }
            $t->boolean('es_caja_chica')->default(false); $t->boolean('gastos_sin_factura')->default(false); $t->timestamps();
        });
        Schema::create('orden_compra_detalles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('orden_compra_id');
            foreach (['mantenimiento_abastecimiento_detalle_id', 'producto_id', 'civil_concept_id', 'obra_civil_insumo_id'] as $campo) { $t->unsignedBigInteger($campo)->nullable(); }
            foreach (['cantidad', 'cantidad_recibida', 'precio_unitario', 'importe'] as $campo) { $t->decimal($campo)->default(0); }
            foreach (['descripcion', 'unidad', 'notas'] as $campo) { $t->string($campo)->nullable(); }
            $t->timestamps();
        });
        Schema::create('mantenimiento_abastecimiento_detalles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('abastecimiento_id'); $t->unsignedBigInteger('producto_id');
            $t->string('producto_nombre_snapshot'); $t->string('unidad'); $t->decimal('cantidad_solicitada');
        });
        Schema::create('pagos_proveedores', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('orden_compra_id'); });
        Schema::create('areas', function (Blueprint $t) { $t->id(); $t->string('codigo'); $t->string('nombre'); });
        DB::table('areas')->insert(['id' => 1, 'codigo' => 'GL', 'nombre' => 'Giralda']);
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 80; $user->name = 'Prueba'; $user->shouldReceive('canAny')->andReturn(true);
        $this->actingAs($user);
    }

    private function solicitud(string $estado = 'pendiente'): MantenimientoAbastecimiento
    {
        return MantenimientoAbastecimiento::create(['mantenimiento_id' => 16, 'tipo' => 'compra', 'estado' => $estado]);
    }

    private function orden(MantenimientoAbastecimiento $s, string $estado = 'BORRADOR'): OrdenCompra
    {
        $oc = OrdenCompra::forceCreate(['mantenimiento_abastecimiento_id' => $s->id, 'estado' => $estado]);
        $oc->detalles()->create(['producto_id' => 5, 'cantidad' => 10]);

        return $oc;
    }

    public function test_draft_creation_keeps_request_pending_and_authorization_advances_it(): void
    {
        $s = $this->solicitud();
        DB::table('mantenimiento_abastecimiento_detalles')->insert(['abastecimiento_id' => $s->id,
            'producto_id' => 5, 'producto_nombre_snapshot' => 'Aceite', 'unidad' => 'L', 'cantidad_solicitada' => 10]);
        $notificaciones = Mockery::mock(OrdenCompraNotificationService::class);
        $notificaciones->shouldReceive('creada')->once(); $notificaciones->shouldReceive('autorizada')->once();
        $request = StoreOrdenCompraRequest::create('/ordenes-compra', 'POST', ['area_id' => 1, 'moneda' => 'MXN',
            'fecha' => '2026-10-29', 'iva' => 16, 'mantenimiento_abastecimiento_id' => $s->id]);
        app(OrdenCompraController::class)->store($request, $notificaciones, app(ObraCivilMaterialRequestOrderService::class));
        $oc = OrdenCompra::firstOrFail();
        $this->assertSame('BORRADOR', $oc->estado);
        $this->assertSame('pendiente', $s->fresh()->estado);
        app(OrdenCompraController::class)->autorizar(Request::create('/', 'POST'), $oc->id, $notificaciones);
        $this->assertSame('AUTORIZADA', $oc->fresh()->estado);
        $this->assertSame('en_compra', $s->fresh()->estado);
    }

    public function test_failed_authorization_does_not_advance_request(): void
    {
        $s = $this->solicitud(); $oc = $this->orden($s); $oc->detalles()->delete();
        app(OrdenCompraController::class)->autorizar(Request::create('/', 'POST'), $oc->id, Mockery::mock(OrdenCompraNotificationService::class));
        $this->assertSame('BORRADOR', $oc->fresh()->estado);
        $this->assertSame('pendiente', $s->fresh()->estado);
    }

    public function test_revocation_keeps_other_authorized_orders_and_returns_to_pending_after_last_one(): void
    {
        $s = $this->solicitud('en_compra'); $a = $this->orden($s, 'AUTORIZADA'); $b = $this->orden($s, 'AUTORIZADA');
        $controller = app(OrdenCompraController::class);
        $controller->revocarAutorizacion(Request::create('/', 'POST'), $a->id);
        $this->assertSame('en_compra', $s->fresh()->estado);
        $controller->revocarAutorizacion(Request::create('/', 'POST'), $b->id);
        $this->assertSame('pendiente', $s->fresh()->estado);
    }

    public function test_authorization_preserves_receipt_and_supply_stages(): void
    {
        foreach (['recibida_parcial', 'material_en_almacen', 'surtida', 'cancelada'] as $estado) {
            $s = $this->solicitud($estado); $oc = $this->orden($s, 'AUTORIZADA');
            app(MantenimientoCompraEstadoService::class)->sincronizar($oc);
            $this->assertSame($estado, $s->fresh()->estado);
        }
    }

    public function test_sync_repairs_legacy_draft_state_without_affecting_other_requests(): void
    {
        $s = $this->solicitud('en_compra'); $oc = $this->orden($s);
        $otra = $this->solicitud('en_compra'); $this->orden($otra, 'AUTORIZADA');
        app(MantenimientoCompraEstadoService::class)->sincronizar($oc);
        $this->assertSame('pendiente', $s->fresh()->estado);
        $this->assertSame('en_compra', $otra->fresh()->estado);
    }
}
