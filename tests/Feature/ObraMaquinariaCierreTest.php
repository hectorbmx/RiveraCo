<?php

namespace Tests\Feature;

use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\ObraController;
use App\Models\Maquina;
use App\Models\Obra;
use App\Models\ObraMaquina;
use App\Models\User;
use App\Services\Maquinas\MaquinaHorometroService;
use App\Services\Maquinas\PreventivoMaquinaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Mockery;
use Tests\TestCase;

class ObraMaquinariaCierreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Requiere pdo_sqlite.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        $this->travelTo(now()->setDate(2026, 11, 4)->startOfDay());

        Schema::create('obras', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('cliente_id'); $t->string('nombre'); $t->string('clave_obra');
            $t->string('tipo_obra'); $t->integer('area_id')->nullable(); $t->integer('estatus_nuevo');
            $t->boolean('usa_bentonita')->default(false); $t->date('fecha_fin_real')->nullable();
            $t->softDeletes(); $t->timestamps();
        });
        Schema::create('clientes', fn (Blueprint $t) => $t->id());
        Schema::create('obra_tipo_configuraciones', function (Blueprint $t) {
            $t->id(); $t->string('tipo_obra'); $t->string('label'); $t->integer('area_id'); $t->boolean('activo');
        });
        Schema::create('obra_empleado', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('obra_id'); $t->boolean('activo'); $t->date('fecha_baja')->nullable(); $t->timestamps();
        });
        Schema::create('maquinas', function (Blueprint $t) {
            $t->id(); $t->string('codigo'); $t->string('nombre'); $t->string('estado'); $t->string('ubicacion'); $t->timestamps();
        });
        Schema::create('obra_maquina', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maquina_id'); $t->unsignedBigInteger('obra_id');
            $t->string('estado'); $t->date('fecha_inicio'); $t->date('fecha_fin')->nullable();
            $t->decimal('horometro_inicio', 10, 2)->nullable(); $t->decimal('horometro_fin', 10, 2)->nullable();
            $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('obra_maquina_registros', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('obra_maquina_id'); $t->decimal('horometro_fin', 10, 2)->nullable(); $t->dateTime('fin');
        });
        Schema::create('maquina_movimientos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maquina_id'); $t->unsignedBigInteger('obra_id')->nullable();
            $t->unsignedBigInteger('obra_maquina_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('tipo'); $t->string('ubicacion_anterior')->nullable(); $t->string('ubicacion_nueva')->nullable();
            $t->string('motivo')->nullable(); $t->text('notas')->nullable(); $t->dateTime('fecha_evento'); $t->timestamps();
        });
        Schema::create('seguros', function (Blueprint $t) {
            $t->id(); $t->string('asegurable_type'); $t->unsignedBigInteger('asegurable_id'); $t->softDeletes();
        });
        Schema::create('empresa_config', fn (Blueprint $t) => $t->id());

        DB::table('clientes')->insert(['id' => 1]);
        DB::table('obra_tipo_configuraciones')->insert(['tipo_obra' => 'PILAS', 'label' => 'Pilas', 'area_id' => 1, 'activo' => true]);
        DB::table('obras')->insert(['id' => 7, 'cliente_id' => 1, 'nombre' => 'FRESCO VALLE IMPERIAL', 'clave_obra' => 'PI-7',
            'tipo_obra' => 'PILAS', 'area_id' => 1, 'estatus_nuevo' => Obra::ESTATUS_EJECUCION]);
        DB::table('maquinas')->insert(['id' => 16, 'codigo' => 'S3', 'nombre' => 'SPIRADRIL', 'estado' => 'operativa', 'ubicacion' => 'en_obra']);
        DB::table('obra_maquina')->insert(['id' => 7, 'maquina_id' => 16, 'obra_id' => 7, 'estado' => 'activa', 'fecha_inicio' => '2026-05-27']);
        DB::table('obra_empleado')->insert(['id' => 1, 'obra_id' => 7, 'activo' => true]);

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 80;
        $user->setRelation('empleado', null);
        $user->shouldReceive('hasAnyRole')->andReturn(true);
        $user->shouldReceive('hasRole')->andReturn(false);
        $user->shouldReceive('checkPermissionTo')->andReturn(false);
        $user->shouldReceive('hasDeniedPermission')->andReturn(false);
        $user->shouldReceive('can')->andReturnUsing(fn ($permission) => in_array($permission, ['baja_maquinas.access', 'maquinas.asignar_obra.access'], true));
        $this->actingAs($user);
        Gate::define('baja_maquinas.access', fn () => true);
        Gate::define('maquinas.asignar_obra.access', fn () => true);

        $this->mock(MaquinaHorometroService::class, function ($mock) {
            $mock->shouldReceive('horometroActual')->andReturn(7130.0);
            $mock->shouldReceive('fechaUltimaLectura')->andReturn(null);
        });
        Route::middleware('web')->put('/_test/obras/{obra}/cierre', [ObraController::class, 'update']);
    }

    private function cerrar(int $estatus, array $extra = [])
    {
        return $this->from('/obras/7/edit')->put('/_test/obras/7/cierre', array_replace([
            'cliente_id' => 1, 'nombre' => 'FRESCO VALLE IMPERIAL', 'clave_obra' => 'PI-7',
            'tipo_obra' => 'PILAS', 'estatus_nuevo' => $estatus, 'fecha_fin_real' => '2026-11-04',
        ], $extra));
    }

    public function test_obra_terminada_con_asignacion_abierta_muestra_nombre_y_opcion_de_retiro(): void
    {
        DB::table('obras')->where('id', 7)->update(['estatus_nuevo' => Obra::ESTATUS_TERMINADA]);
        $preventivos = Mockery::mock(PreventivoMaquinaService::class);
        $preventivos->shouldReceive('calcularParaColeccion')->andReturn([]);
        $view = app(MaquinaController::class)->index(Request::create('/maquinas'), $preventivos, app(MaquinaHorometroService::class));
        $data = $view->getData();
        $this->assertNull($data['maquinas']->first()->asignacionActiva);
        $this->assertSame('FRESCO VALLE IMPERIAL', $data['maquinas']->first()->asignacionAbierta->obra->nombre);
        $this->assertSame(1, $data['asignadas']);
        preg_match("/@section\('content'\)([\\s\\S]*?)@endsection/", file_get_contents(resource_path('views/maquinas/index.blade.php')), $matches);
        $html = Blade::render($matches[1], $data + ['errors' => new ViewErrorBag()]);
        $this->assertStringContainsString('En obra · FRESCO VALLE IMPERIAL', $html);
        $this->assertStringContainsString('openBajaModal(', $html);
        $this->assertStringNotContainsString('title="Asignar a una obra"', $html);
    }

    public function test_terminar_obra_libera_maquina_y_guarda_baja_con_actor(): void
    {
        $this->cerrar(Obra::ESTATUS_TERMINADA)->assertSessionHasNoErrors()->assertRedirect(route('obras.index'));
        $this->assertDatabaseHas('obra_maquina', ['id' => 7, 'estado' => 'finalizada', 'updated_by' => 80]);
        $this->assertSame('2026-11-04', ObraMaquina::findOrFail(7)->fecha_fin->toDateString());
        $this->assertDatabaseHas('maquinas', ['id' => 16, 'ubicacion' => 'en_patio']);
        $this->assertDatabaseHas('maquina_movimientos', ['tipo' => 'desasignacion', 'obra_id' => 7, 'obra_maquina_id' => 7,
            'ubicacion_anterior' => 'en_obra', 'ubicacion_nueva' => 'en_patio', 'user_id' => 80]);
        $this->assertDatabaseHas('obra_empleado', ['id' => 1, 'activo' => false]);
        $this->assertNull(Maquina::findOrFail(16)->asignacionAbierta);
        $this->cerrar(Obra::ESTATUS_TERMINADA)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('maquina_movimientos', 1);
    }

    public function test_cancelar_obra_libera_maquina_y_conserva_historial(): void
    {
        $this->cerrar(Obra::ESTATUS_CANCELADA)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('maquinas', ['id' => 16, 'ubicacion' => 'en_patio']);
        $this->assertSame(1, Obra::findOrFail(7)->maquinasAsignadas()->where('estado', 'finalizada')->count());
        $this->assertDatabaseHas('maquina_movimientos', ['obra_id' => 7, 'tipo' => 'desasignacion']);
    }

    public function test_guardar_obra_en_ejecucion_no_libera_maquinaria(): void
    {
        $this->cerrar(Obra::ESTATUS_EJECUCION, ['fecha_fin_real' => null])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('obra_maquina', ['id' => 7, 'estado' => 'activa', 'fecha_fin' => null]);
        $this->assertDatabaseHas('maquinas', ['id' => 16, 'ubicacion' => 'en_obra']);
        $this->assertDatabaseCount('maquina_movimientos', 0);
    }

    public function test_cierre_fallido_revierte_obra_y_todas_las_bajas_previas(): void
    {
        DB::table('maquinas')->insert(['id' => 17, 'codigo' => 'S4', 'nombre' => 'SPIRADRIL', 'estado' => 'operativa', 'ubicacion' => 'en_obra']);
        DB::table('obra_maquina')->insert(['id' => 8, 'maquina_id' => 17, 'obra_id' => 7, 'estado' => 'activa', 'fecha_inicio' => '2026-11-05']);
        $this->cerrar(Obra::ESTATUS_TERMINADA)->assertSessionHasErrors('fecha_fin');
        $this->assertDatabaseHas('obras', ['id' => 7, 'estatus_nuevo' => Obra::ESTATUS_EJECUCION, 'fecha_fin_real' => null]);
        $this->assertDatabaseHas('obra_maquina', ['id' => 7, 'estado' => 'activa', 'fecha_fin' => null]);
        $this->assertDatabaseHas('obra_maquina', ['id' => 8, 'estado' => 'activa', 'fecha_fin' => null]);
        $this->assertDatabaseHas('obra_empleado', ['id' => 1, 'activo' => true]);
        $this->assertDatabaseCount('maquina_movimientos', 0);
        $this->assertSame(2, Maquina::where('ubicacion', 'en_obra')->count());
    }

    public function test_pendiente_de_obra_ya_cerrada_se_puede_retirar_desde_maquinas(): void
    {
        DB::table('obras')->where('id', 7)->update(['estatus_nuevo' => Obra::ESTATUS_TERMINADA]);
        Route::middleware('web')->post('/_test/maquinas/{maquina}/baja', [MaquinaController::class, 'bajaDeObra']);
        $this->from('/maquinas')->post('/_test/maquinas/16/baja', ['fecha_fin' => '2026-11-04'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('obra_maquina', ['id' => 7, 'estado' => 'finalizada']);
        $this->assertDatabaseHas('maquinas', ['id' => 16, 'ubicacion' => 'en_patio']);
        $this->assertDatabaseHas('maquina_movimientos', ['obra_id' => 7, 'tipo' => 'desasignacion']);
    }

    public function test_guardar_obra_ya_cerrada_regulariza_pendientes_sin_duplicar_movimientos(): void
    {
        DB::table('obras')->where('id', 7)->update(['estatus_nuevo' => Obra::ESTATUS_TERMINADA]);
        $this->cerrar(Obra::ESTATUS_TERMINADA)->assertSessionHasNoErrors();
        $this->cerrar(Obra::ESTATUS_CANCELADA)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('obra_maquina', ['id' => 7, 'estado' => 'finalizada']);
        $this->assertDatabaseCount('maquina_movimientos', 1);
    }
}
