<?php

namespace Tests\Feature;

use App\Http\Controllers\MaquinaPreventivoReferenciaController;
use App\Models\Maquina;
use App\Models\User;
use App\Services\Maquinas\MaquinaHorometroService;
use App\Services\Maquinas\MaquinaPreventivoReferenciaService;
use App\Services\Maquinas\PreventivoMaquinaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Mockery;
use Tests\TestCase;

class MaquinaPreventivoReferenciaTest extends TestCase
{
    private bool $permitido = true;
    private bool $denegado = false;
    private ?float $actual = 6474.0;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) $this->markTestSkipped('Requiere pdo_sqlite.');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        $this->travelTo(now()->setDate(2026, 11, 4)->setTime(12, 0));
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('maquinas', function (Blueprint $t) {
            $t->id(); $t->string('codigo'); $t->string('nombre'); $t->decimal('horometro_base', 10, 2); $t->timestamps();
        });
        Schema::create('empresa_config', fn (Blueprint $t) => $t->id());
        Schema::create('empresa_servicio_preventivo_tipos', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->string('codigo'); $t->string('ambito'); $t->boolean('activo');
            $t->integer('orden')->default(1); $t->integer('intervalo_valor'); $t->integer('intervalo_meses')->default(6); $t->integer('alerta_valor')->default(20);
        });
        Schema::create('mantenimientos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maquina_id'); $t->unsignedBigInteger('servicio_preventivo_tipo_id')->nullable();
            $t->string('tipo'); $t->string('estatus'); $t->decimal('horometro', 10, 2)->nullable();
            $t->dateTime('fecha_fin')->nullable(); $t->dateTime('fecha_programada')->nullable(); $t->timestamps();
        });
        (require database_path('migrations/2026_11_04_150000_create_maquina_preventivo_referencias_table.php'))->up();
        (require database_path('migrations/2026_11_04_160000_add_meta_to_maquina_preventivo_referencias.php'))->up();
        DB::table('users')->insert(['id' => 80, 'name' => 'Responsable autorizado']);
        DB::table('maquinas')->insert(['id' => 16, 'codigo' => 'TEREX', 'nombre' => 'GRUA TEREX', 'horometro_base' => 0]);
        DB::table('empresa_servicio_preventivo_tipos')->insert([
            ['id' => 1, 'nombre' => 'Servicio 250', 'codigo' => '250', 'ambito' => 'maquinaria', 'activo' => true, 'intervalo_valor' => 250],
            ['id' => 2, 'nombre' => 'Servicio 3000', 'codigo' => '3000', 'ambito' => 'maquinaria', 'activo' => true, 'intervalo_valor' => 3000],
        ]);
        DB::table('mantenimientos')->insert(['maquina_id' => 16, 'servicio_preventivo_tipo_id' => 1,
            'tipo' => 'programado', 'estatus' => 'completado', 'horometro' => 4285, 'fecha_fin' => '2026-09-01 10:00:00']);
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 80;
        $user->shouldReceive('hasRole')->andReturn(false);
        $user->shouldReceive('checkPermissionTo')->andReturnUsing(fn ($permission) => $permission === MaquinaPreventivoReferenciaService::PERMISO && $this->permitido);
        $user->shouldReceive('hasDeniedPermission')->andReturnUsing(fn () => $this->denegado);
        $this->actingAs($user);
        Gate::define(MaquinaPreventivoReferenciaService::PERMISO, fn () => $this->permitido);
        $this->mock(MaquinaHorometroService::class)->shouldReceive('horometroActual')->andReturnUsing(fn () => $this->actual);
        // Se ejecutan los controladores reales; la autorización también se exige sin el middleware de permisos.
        Route::middleware('web')->get('/_test/maquinas/{maquina}/referencia', [MaquinaPreventivoReferenciaController::class, 'edit']);
        Route::middleware('web')->post('/_test/maquinas/{maquina}/referencia', [MaquinaPreventivoReferenciaController::class, 'store']);
    }

    private function guardar(array $extra = [])
    {
        return $this->from('/_test/maquinas/16/referencia')->post('/_test/maquinas/16/referencia', array_replace([
            'servicio_preventivo_tipo_id' => 1, 'horometro_esperado' => 6474, 'meta_horometro' => 6724, 'motivo' => 'Inicio de control en el sistema',
        ], $extra));
    }

    private function servicio(?int $tipo = 1): array
    {
        $data = app(PreventivoMaquinaService::class)->calcularParaColeccion(collect([Maquina::findOrFail(16)]))[16];
        return collect($data['servicios'])->firstWhere('servicio_id', $tipo);
    }

    public function test_sin_permiso_no_se_puede_abrir_ni_guardar(): void
    {
        $this->permitido = false;
        $this->get('/_test/maquinas/16/referencia')->assertForbidden();
        $this->guardar()->assertForbidden();
        $this->assertDatabaseCount('maquina_preventivo_referencias', 0);
        foreach (['maquinas.preventivo.edit', 'maquinas.preventivo.store'] as $route) {
            $this->assertContains('permission:ajuste_horario_servicio.access', Route::getRoutes()->getByName($route)->gatherMiddleware());
        }
    }

    public function test_denegacion_individual_prevalece_sobre_permiso(): void
    {
        $this->denegado = true;
        $this->guardar()->assertForbidden();
        $this->assertDatabaseCount('maquina_preventivo_referencias', 0);
    }

    public function test_ajuste_conserva_horometro_e_historial_real_y_calcula_nueva_meta(): void
    {
        $this->assertSame(4535.0, $this->servicio()['proximo_horometro']);
        $this->guardar()->assertSessionHasNoErrors()->assertRedirect(route('maquinas.preventivo.edit', 16));
        $servicio = $this->servicio();
        $this->assertSame(6724.0, $servicio['proximo_horometro']);
        $this->assertSame(250.0, $servicio['horas_restantes']);
        $this->assertEquals(0, $servicio['horas_usadas']);
        $this->assertSame('ok', $servicio['estado']);
        $this->assertSame('2027-05-04', $servicio['proximo_fecha']->toDateString());
        $this->assertDatabaseHas('maquinas', ['id' => 16, 'horometro_base' => 0]);
        $this->assertSame(6474.0, $this->actual);
        $this->assertDatabaseCount('mantenimientos', 1);
        $this->assertDatabaseHas('maquina_preventivo_referencias', ['maquina_id' => 16, 'servicio_preventivo_tipo_id' => 1,
            'horometro' => 6474, 'created_by' => 80, 'motivo' => 'Inicio de control en el sistema']);
        $this->assertSame('vencido', $this->servicio(2)['estado']);
    }

    public function test_servicio_real_posterior_reanuda_el_ciclo_normal(): void
    {
        $this->guardar()->assertSessionHasNoErrors();
        $this->travelTo(now()->addDay());
        DB::table('mantenimientos')->insert(['maquina_id' => 16, 'servicio_preventivo_tipo_id' => 1,
            'tipo' => 'programado', 'estatus' => 'completado', 'horometro' => 6520, 'fecha_fin' => now()]);
        $this->actual = 6520.0;
        $this->assertSame(6770.0, $this->servicio()['proximo_horometro']);
        $this->assertDatabaseCount('maquina_preventivo_referencias', 1);
    }

    public function test_no_guarda_si_la_lectura_cambio_o_no_existe(): void
    {
        $this->actual = 6475.0;
        $this->guardar()->assertSessionHasErrors('horometro_esperado');
        $this->actual = null;
        $this->guardar()->assertSessionHasErrors('horometro_esperado');
        $this->assertDatabaseCount('maquina_preventivo_referencias', 0);
    }

    public function test_exige_motivo_y_servicio_activo_de_maquinaria(): void
    {
        $this->guardar(['motivo' => '   '])->assertSessionHasErrors('motivo');
        $this->guardar(['servicio_preventivo_tipo_id' => null])->assertSessionHasErrors('servicio_preventivo_tipo_id');
        DB::table('empresa_servicio_preventivo_tipos')->where('id', 1)->update(['ambito' => 'vehiculo']);
        $this->guardar()->assertSessionHasErrors('servicio_preventivo_tipo_id');
        DB::table('empresa_servicio_preventivo_tipos')->where('id', 1)->update(['ambito' => 'maquinaria', 'activo' => false]);
        $this->guardar()->assertSessionHasErrors('servicio_preventivo_tipo_id');
        $this->assertDatabaseCount('maquina_preventivo_referencias', 0);
    }

    public function test_doble_envio_no_duplica_referencia_y_lectura_posterior_se_cuenta(): void
    {
        $this->guardar()->assertSessionHasNoErrors();
        $this->guardar()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('maquina_preventivo_referencias', 1);
        $this->actual = 6500.0;
        $this->assertSame(26.0, $this->servicio()['horas_usadas']);
        $this->assertSame(224.0, $this->servicio()['horas_restantes']);
    }

    public function test_catalogo_vacio_permite_referencia_del_preventivo_general(): void
    {
        DB::table('mantenimientos')->delete();
        DB::table('empresa_servicio_preventivo_tipos')->delete();
        $this->guardar(['servicio_preventivo_tipo_id' => null])->assertSessionHasNoErrors();
        $this->assertSame(6724.0, $this->servicio(null)['proximo_horometro']);
    }

    public function test_formulario_muestra_metas_e_historial_del_ajuste(): void
    {
        $this->guardar()->assertSessionHasNoErrors();
        $view = app(MaquinaPreventivoReferenciaController::class)->edit(request(), Maquina::findOrFail(16), app(PreventivoMaquinaService::class));
        preg_match("/@section\('content'\)([\\s\\S]*?)@endsection/", file_get_contents(resource_path('views/maquinas/ajuste-servicio.blade.php')), $matches);
        $html = Blade::render($matches[1], $view->getData() + ['errors' => new ViewErrorBag()]);
        $this->assertStringContainsString('6,474.00 h', $html);
        $this->assertStringContainsString('6,724.00 h', $html);
        $this->assertStringContainsString('Responsable autorizado', $html);
        $this->assertStringContainsString('Inicio de control en el sistema', $html);
        $this->assertStringContainsString('name="meta_horometro"', $html);
    }

    public function test_meta_personalizada_se_conserva_con_nuevas_lecturas_y_cambios_de_intervalo(): void
    {
        $this->guardar(['meta_horometro' => 6600])->assertSessionHasNoErrors();
        $this->assertSame(6600.0, $this->servicio()['proximo_horometro']);
        $this->assertSame(126.0, $this->servicio()['horas_restantes']);
        $this->actual = 6500.0;
        DB::table('empresa_servicio_preventivo_tipos')->where('id', 1)->update(['intervalo_valor' => 500]);
        $servicio = $this->servicio();
        $this->assertSame(6600.0, $servicio['proximo_horometro']);
        $this->assertSame(100.0, $servicio['horas_restantes']);
        $this->assertSame(126.0, $servicio['horas_ciclo']);
        $this->assertEqualsWithDelta(26 / 126 * 100, $servicio['porcentaje'], 0.001);
        $this->assertDatabaseHas('maquina_preventivo_referencias', ['meta_horometro' => 6600, 'horometro' => 6474]);
        $this->assertDatabaseHas('maquinas', ['horometro_base' => 0]);
    }

    public function test_corregir_meta_en_el_mismo_dia_guarda_nuevo_registro(): void
    {
        $this->guardar(['meta_horometro' => 6600])->assertSessionHasNoErrors();
        $this->guardar(['meta_horometro' => 6650])->assertSessionHasNoErrors();
        $this->guardar(['meta_horometro' => 6650])->assertSessionHasNoErrors();
        $this->assertSame(6650.0, $this->servicio()['proximo_horometro']);
        $this->assertDatabaseCount('maquina_preventivo_referencias', 2);
        $this->assertDatabaseHas('maquina_preventivo_referencias', ['meta_horometro' => 6600]);
    }

    public function test_meta_alcanzada_o_rebasada_se_muestra_vencida_sin_dividir_por_cero(): void
    {
        $this->guardar(['meta_horometro' => 6474])->assertSessionHasNoErrors();
        $this->assertSame('Servicio requerido', $this->servicio()['label']);
        $this->assertSame(100, $this->servicio()['porcentaje']);
        $this->guardar(['meta_horometro' => 6400])->assertSessionHasNoErrors();
        $this->assertSame('vencido', $this->servicio()['estado']);
        $this->assertSame(-74.0, $this->servicio()['horas_restantes']);
    }

    public function test_meta_requiere_valor_numerico_valido(): void
    {
        foreach ([null, -1, 'abc', '6600.123', 100000000] as $meta) {
            $this->guardar(['meta_horometro' => $meta])->assertSessionHasErrors('meta_horometro');
        }
        $this->assertDatabaseCount('maquina_preventivo_referencias', 0);
    }

    public function test_referencias_anteriores_sin_meta_siguen_funcionando(): void
    {
        DB::table('maquina_preventivo_referencias')->insert(['maquina_id' => 16, 'servicio_preventivo_tipo_id' => 1,
            'horometro' => 6474, 'fecha_referencia' => now(), 'created_by' => 80, 'motivo' => 'Referencia anterior']);
        $this->assertSame(6724.0, $this->servicio()['proximo_horometro']);
    }
}
