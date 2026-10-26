<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UsuarioApp;
use App\Services\Maquinas\MaquinaHorometroService;
use App\Services\Mobile\AppMobileAccessService;
use App\Services\Vehiculos\PreventivoVehiculoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AppMobileCaptureCatalogTest extends TestCase
{
    private array $permisos = ['app.mecanico.access'];

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ejecutar con php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter=AppMobileCaptureCatalogTest');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->crearEsquema();
        $this->cargarDatos();
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 80;
        $user->shouldReceive('hasDeniedPermission')->andReturn(false);
        $user->shouldReceive('checkPermissionTo')->andReturn(false);
        foreach (['app.mecanico.access', 'app.gerencial.access', 'app.regular.access'] as $permiso) {
            Gate::define($permiso, fn (User $u) => in_array($permiso, $this->permisos, true));
        }
        $this->actingAs($user, 'sanctum');
        $this->mock(AppMobileAccessService::class)->shouldReceive('usuarioActivo')->andReturn(new UsuarioApp(['is_active' => true]));
        $this->mock(MaquinaHorometroService::class)->shouldReceive('horometroSugeridoParaAsignacion')->andReturn(432.5);
        $this->mock(PreventivoVehiculoService::class)->shouldReceive('calcularParaVehiculo')->andReturn(['km_actual' => 12000]);
    }

    private function crearEsquema(): void
    {
        Schema::create('almacenes', function (Blueprint $t) {
            $t->id();
            $t->string('codigo');
            $t->string('nombre');
            $t->boolean('activo');
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('sku');
            $t->string('unidad');
            $t->string('unidad_base')->nullable();
            $t->boolean('activo');
        });
        Schema::create('inventario_stock', function (Blueprint $t) {
            $t->id();
            $t->integer('almacen_id');
            $t->integer('producto_id');
            $t->decimal('stock_actual');
            $t->decimal('stock_reservado');
            $t->unique(['almacen_id', 'producto_id']);
        });
        Schema::create('empresa_servicio_preventivo_tipos', function (Blueprint $t) {
            $t->id();
            $t->string('ambito');
            $t->string('nombre');
            $t->string('codigo');
            $t->string('unidad');
            $t->integer('intervalo_valor');
            $t->integer('intervalo_meses')->nullable();
            $t->integer('alerta_valor')->nullable();
            $t->integer('alerta_dias')->nullable();
            $t->integer('orden');
            $t->boolean('activo');
        });
        Schema::create('empresa_servicio_preventivo_materiales', function (Blueprint $t) {
            $t->id();
            $t->integer('servicio_preventivo_tipo_id');
            $t->integer('maquina_id')->nullable();
            $t->integer('producto_id');
            $t->decimal('cantidad_requerida');
            $t->string('unidad')->nullable();
            $t->integer('orden');
            $t->boolean('activo');
        });
        Schema::create('maquinas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
        });
        Schema::create('vehiculos', function (Blueprint $t) {
            $t->id();
            $t->string('marca');
        });
    }

    private function cargarDatos(): void
    {
        DB::table('almacenes')->insert([
            ['id' => 1, 'codigo' => 'GL', 'nombre' => 'Giralda', 'activo' => true],
            ['id' => 2, 'codigo' => 'OTRO', 'nombre' => 'Otro almacén', 'activo' => true],
        ]);
        foreach ([1 => 'Aceite', 2 => 'Filtro', 3 => 'Grasa', 4 => 'Inactivo', 5 => 'Reservado', 6 => 'Solo otro'] as $id => $nombre) {
            DB::table('productos')->insert(['id' => $id, 'nombre' => $nombre, 'sku' => 'SKU-'.$id,
                'unidad' => 'pieza', 'unidad_base' => $id === 1 ? 'litro' : null, 'activo' => $id !== 4]);
        }
        DB::table('inventario_stock')->insert([
            ['almacen_id' => 1, 'producto_id' => 1, 'stock_actual' => 10, 'stock_reservado' => 3],
            ['almacen_id' => 1, 'producto_id' => 3, 'stock_actual' => 0, 'stock_reservado' => 0],
            ['almacen_id' => 2, 'producto_id' => 3, 'stock_actual' => 999, 'stock_reservado' => 0],
            ['almacen_id' => 1, 'producto_id' => 4, 'stock_actual' => 8, 'stock_reservado' => 0],
            ['almacen_id' => 1, 'producto_id' => 5, 'stock_actual' => 5, 'stock_reservado' => 10],
            ['almacen_id' => 2, 'producto_id' => 6, 'stock_actual' => 777, 'stock_reservado' => 0],
        ]);
        foreach ([1 => ['maquinaria', true, 2], 2 => ['maquinaria', true, 1], 3 => ['maquinaria', false, 0]] as $id => [$ambito, $activo, $orden]) {
            DB::table('empresa_servicio_preventivo_tipos')->insert(['id' => $id, 'ambito' => $ambito,
                'nombre' => 'Preventivo '.$id, 'codigo' => 'P'.$id, 'unidad' => 'horas',
                'intervalo_valor' => 250, 'orden' => $orden, 'activo' => $activo]);
        }
        DB::table('empresa_servicio_preventivo_materiales')->insert([
            ['servicio_preventivo_tipo_id' => 1, 'maquina_id' => null, 'producto_id' => 1, 'cantidad_requerida' => 2, 'orden' => 0, 'activo' => true],
            ['servicio_preventivo_tipo_id' => 1, 'maquina_id' => 17, 'producto_id' => 2, 'cantidad_requerida' => 3, 'orden' => 1, 'activo' => true],
            ['servicio_preventivo_tipo_id' => 1, 'maquina_id' => 17, 'producto_id' => 3, 'cantidad_requerida' => 1, 'orden' => 2, 'activo' => false],
        ]);
        DB::table('maquinas')->insert([['id' => 17, 'nombre' => 'Equipo 17'], ['id' => 18, 'nombre' => 'Equipo 18']]);
        DB::table('vehiculos')->insert(['id' => 7, 'marca' => 'Vehículo de prueba']);
    }

    public function test_inventory_includes_active_products_without_stock_and_never_other_warehouse_quantities(): void
    {
        $response = $this->getJson('/api/v1/mecanico/inventario/productos')->assertOk()
            ->assertJsonPath('almacen.id', 1)->assertJsonPath('meta.total', 5)->assertJsonCount(5, 'data');
        $items = collect($response->json('data'))->keyBy('id');
        $this->assertSame(7, $items[1]['stock']['disponible']);
        $this->assertSame('litro', $items[1]['unidad_stock']);
        $this->assertFalse($items[2]['tiene_registro_stock']);
        $this->assertSame(0, $items[2]['stock']['stock_actual']);
        $this->assertSame(0, $items[3]['stock']['stock_actual']);
        $this->assertSame(0, $items[5]['stock']['disponible']);
        $this->assertFalse($items[6]['tiene_registro_stock']);
        $this->assertSame(0, $items[6]['stock']['stock_actual']);
        $this->assertFalse($items->has(4));
        $response->assertJsonMissingPath('data.0.costo_promedio')->assertJsonMissingPath('data.0.stock.valor_total');
    }

    public function test_inventory_search_pagination_and_available_filter_use_real_queries(): void
    {
        $this->getJson('/api/v1/mecanico/inventario/productos?q=SKU-2')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', 2);
        $this->getJson('/api/v1/mecanico/inventario/productos?q=Filtro')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/mecanico/inventario/productos?con_existencia=1')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', 1);
        $this->getJson('/api/v1/mecanico/inventario/productos?per_page=2&page=2')->assertOk()
            ->assertJsonPath('meta.current_page', 2)->assertJsonPath('meta.last_page', 3)->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/mecanico/inventario/productos?almacen_id=1')->assertOk();
        $this->getJson('/api/v1/mecanico/inventario/productos?per_page=')->assertOk()->assertJsonPath('meta.per_page', 25);
    }

    public function test_invalid_filters_or_attempts_to_change_warehouse_are_rejected(): void
    {
        foreach (['almacen_id=2', 'per_page=0', 'per_page=51', 'page=0', 'q[]=array', 'con_existencia=otro'] as $query) {
            $this->getJson('/api/v1/mecanico/inventario/productos?'.$query)->assertUnprocessable();
        }
        $this->getJson('/api/v1/mecanico/servicios/catalogos?maquina_id=17&vehiculo_id=7')->assertUnprocessable();
        $this->getJson('/api/v1/mecanico/servicios/catalogos?maquina_id=-1')->assertUnprocessable();
    }

    public function test_missing_or_inactive_giralda_is_not_replaced_with_another_warehouse(): void
    {
        DB::table('almacenes')->where('id', 1)->update(['activo' => false]);
        $this->getJson('/api/v1/mecanico/inventario/productos')->assertStatus(503);
        $this->getJson('/api/v1/mecanico/servicios/catalogos')->assertOk()
            ->assertJsonPath('data.almacen', null)->assertJsonPath('data.capacidades.inventario_giralda', false);
    }

    public function test_catalogs_return_only_active_ordered_types_and_explain_missing_vehicle_configuration(): void
    {
        $response = $this->getJson('/api/v1/mecanico/servicios/catalogos')->assertOk()
            ->assertJsonCount(2, 'data.servicios_preventivos.maquinaria')->assertJsonPath('data.servicios_preventivos.maquinaria.0.id', 2)
            ->assertJsonPath('data.servicios_preventivos.vehiculo', [])->assertJsonPath('data.lectura_sugerida', null)
            ->assertJsonPath('data.capacidades.tipos_vehiculo_configurados', false)
            ->assertJsonPath('data.capacidades.plantilla_materiales_vehiculo', false)
            ->assertJsonPath('data.categoria_mantenimiento.captura', 'texto_libre');
        $this->assertNotEmpty($response->json('data.avisos'));
    }

    public function test_machine_catalog_preserves_specific_template_precedence_and_global_fallback(): void
    {
        $this->getJson('/api/v1/mecanico/servicios/catalogos?maquina_id=17')->assertOk()
            ->assertJsonPath('data.lectura_sugerida.valor', 432.5)->assertJsonPath('data.lectura_sugerida.campo', 'horometro')
            ->assertJsonCount(1, 'data.materiales_por_servicio.1')->assertJsonPath('data.materiales_por_servicio.1.0.producto_id', 2)
            ->assertJsonPath('data.materiales_por_servicio.1.0.faltante', 3);
        $this->getJson('/api/v1/mecanico/servicios/catalogos?maquina_id=18')->assertOk()
            ->assertJsonPath('data.materiales_por_servicio.1.0.producto_id', 1)
            ->assertJsonPath('data.materiales_por_servicio.1.0.disponible', 7);
    }

    public function test_vehicle_uses_existing_kilometer_calculation_without_inventing_material_templates(): void
    {
        $this->getJson('/api/v1/mecanico/servicios/catalogos?vehiculo_id=7')->assertOk()
            ->assertJsonPath('data.equipo.ambito', 'vehiculo')->assertJsonPath('data.lectura_sugerida.campo', 'km_actuales')
            ->assertJsonPath('data.lectura_sugerida.valor', 12000)->assertJsonPath('data.capacidades.plantilla_materiales_vehiculo', false);
        $this->getJson('/api/v1/mecanico/servicios/catalogos?maquina_id=999')->assertNotFound();
        $this->getJson('/api/v1/mecanico/servicios/catalogos?vehiculo_id=999')->assertNotFound();
    }

    public function test_vehicle_types_are_returned_when_configuration_exists(): void
    {
        DB::table('empresa_servicio_preventivo_tipos')->insert([
            'id' => 4, 'ambito' => 'vehiculo', 'nombre' => 'Servicio 5000 km', 'codigo' => 'KM5000',
            'unidad' => 'km', 'intervalo_valor' => 5000, 'orden' => 1, 'activo' => true,
        ]);
        $this->getJson('/api/v1/mecanico/servicios/catalogos?vehiculo_id=7')->assertOk()
            ->assertJsonCount(1, 'data.servicios_preventivos.vehiculo')
            ->assertJsonPath('data.servicios_preventivos.vehiculo.0.unidad', 'km')
            ->assertJsonPath('data.capacidades.tipos_vehiculo_configurados', true)
            ->assertJsonPath('data.capacidades.plantilla_materiales_vehiculo', false);
    }

    public function test_both_routes_require_mechanic_permission_and_are_read_only(): void
    {
        $this->permisos = ['app.gerencial.access'];
        foreach (['inventario/productos', 'servicios/catalogos'] as $path) {
            $this->getJson('/api/v1/mecanico/'.$path)->assertForbidden();
            $this->postJson('/api/v1/mecanico/'.$path)->assertStatus(405);
        }
        auth('sanctum')->forgetUser();
        $this->getJson('/api/v1/mecanico/inventario/productos')->assertUnauthorized();
    }

    public function test_reads_do_not_reserve_stock_or_create_configuration(): void
    {
        $antes = DB::table('inventario_stock')->orderBy('id')->get()->toJson();
        $tipos = DB::table('empresa_servicio_preventivo_tipos')->count();
        $this->getJson('/api/v1/mecanico/inventario/productos')->assertOk();
        $this->getJson('/api/v1/mecanico/servicios/catalogos?maquina_id=17')->assertOk();
        $this->assertSame($antes, DB::table('inventario_stock')->orderBy('id')->get()->toJson());
        $this->assertSame($tipos, DB::table('empresa_servicio_preventivo_tipos')->count());
    }
}
