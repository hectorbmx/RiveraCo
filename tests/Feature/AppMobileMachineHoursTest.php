<?php

namespace Tests\Feature;

use App\Http\Controllers\MaquinaController;
use App\Models\User;
use App\Models\UsuarioApp;
use App\Services\Mobile\AppMobileAccessService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AppMobileMachineHoursTest extends TestCase
{
    private array $permisos = ['app.mecanico.access', 'maquinas.horas.create.access'];
    private bool $denegarHoras = false;
    private string $url = '/api/v1/mecanico/maquinas/17';

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Requiere pdo_sqlite.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.timezone' => 'America/Mexico_City']);
        DB::purge('sqlite');
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->travelTo(now()->setDate(2026, 10, 29)->startOfDay());
        $this->esquema();
        $this->migracion()->up();
        foreach (['app.mecanico.access', 'app.regular.access', 'app.gerencial.access', 'maquinas.horas.create.access'] as $permiso) {
            Gate::define($permiso, fn () => in_array($permiso, $this->permisos, true));
        }
        $this->actingAs($this->usuario(), 'sanctum');
        $this->mock(AppMobileAccessService::class)->shouldReceive('usuarioActivo')->andReturn(new UsuarioApp(['is_active' => true]));
        DB::table('maquinas')->insert(['id' => 17, 'codigo' => 'M-17', 'nombre' => 'Grúa',
            'horometro_base' => 100, 'estado' => 'operativa', 'ubicacion' => 'en_patio']);
        foreach ([[9, 2, null], [10, 5, null], [11, 4, null], [12, 2, '2026-10-01']] as [$id, $estado, $deleted]) {
            DB::table('obras')->insert(['id' => $id, 'nombre' => 'Obra '.$id, 'clave_obra' => 'OB-'.$id, 'estatus_nuevo' => $estado, 'deleted_at' => $deleted]);
        }
    }

    private function usuario(int $id = 80): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;
        $user->shouldReceive('hasRole')->andReturn(false);
        $user->shouldReceive('hasAnyRole')->andReturn(false);
        $user->shouldReceive('checkPermissionTo')->andReturn(false);
        $user->shouldReceive('hasDeniedPermission')->andReturnUsing(fn ($permiso) => $permiso === 'maquinas.horas.create.access' && $this->denegarHoras);

        return $user;
    }

    private function migracion(): object
    {
        return require database_path('migrations/2026_10_29_120000_add_solicitud_hash_to_maquina_lecturas.php');
    }

    private function esquema(): void
    {
        Schema::create('maquinas', function (Blueprint $t) {
            $t->id(); $t->decimal('horometro_base', 10, 2)->nullable(); $t->timestamps();
            foreach (['codigo', 'nombre', 'placas', 'estado', 'marca', 'modelo', 'tipo', 'ubicacion'] as $campo) { $t->string($campo)->nullable(); }
        });
        Schema::create('obras', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->string('clave_obra')->nullable(); $t->integer('estatus_nuevo')->nullable();
            $t->string('ubicacion')->nullable(); $t->softDeletes();
        });
        Schema::create('obra_maquina', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maquina_id'); $t->unsignedBigInteger('obra_id');
            $t->string('estado'); $t->date('fecha_inicio')->nullable(); $t->date('fecha_fin')->nullable();
            $t->decimal('horometro_inicio', 10, 2)->nullable(); $t->decimal('horometro_fin', 10, 2)->nullable(); $t->timestamps();
        });
        Schema::create('obra_maquina_registros', function (Blueprint $t) {
            $t->id();
            foreach (['maquina_id', 'obra_maquina_id', 'obra_id'] as $campo) { $t->unsignedBigInteger($campo); }
            $t->dateTime('inicio'); $t->dateTime('fin');
            foreach (['horometro_inicio', 'horometro_fin', 'horas'] as $campo) { $t->decimal($campo, 10, 2); }
            $t->text('notas')->nullable(); $t->string('origen', 20)->nullable();
            $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('maquina_horometro_ajustes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maquina_id'); $t->unsignedBigInteger('obra_id')->nullable();
            $t->unsignedBigInteger('obra_maquina_id')->nullable(); $t->date('fecha_registro')->nullable();
            foreach (['horometro_anterior', 'horometro_nuevo', 'diferencia'] as $campo) { $t->decimal($campo, 10, 2)->nullable(); }
            $t->string('tipo'); $t->text('notas')->nullable(); $t->string('origen')->nullable();
            $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('mantenimientos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maquina_id'); $t->unsignedBigInteger('servicio_preventivo_tipo_id')->nullable();
            $t->unsignedBigInteger('mecanico_id')->nullable(); $t->decimal('horometro', 10, 1)->nullable();
            $t->string('tipo'); $t->string('estatus'); $t->dateTime('fecha_programada')->nullable();
            $t->dateTime('fecha_inicio')->nullable(); $t->dateTime('fecha_fin')->nullable(); $t->timestamps();
        });
        Schema::create('empresa_config', function (Blueprint $t) { $t->id(); });
        Schema::create('empresa_servicio_preventivo_tipos', function (Blueprint $t) {
            $t->id(); $t->string('ambito'); $t->boolean('activo'); $t->integer('orden'); $t->integer('intervalo_valor'); $t->string('nombre');
        });
        Schema::create('seguros', function (Blueprint $t) {
            $t->id(); $t->string('asegurable_type'); $t->unsignedBigInteger('asegurable_id'); $t->date('vigencia_hasta')->nullable(); $t->softDeletes();
        });
    }

    private function ajuste(array $extra = []): array
    {
        return array_replace(['asignacion_esperada_id' => null, 'horometro_esperado' => 100,
            'horometro_fin' => 115, 'fecha_registro' => '2026-10-29', 'notas' => 'Lectura verificada'], $extra);
    }

    private function asignar(): void
    {
        DB::table('obra_maquina')->insert(['id' => 81, 'maquina_id' => 17, 'obra_id' => 9,
            'estado' => 'activa', 'fecha_inicio' => '2026-10-01', 'horometro_inicio' => 100]);
        DB::table('maquinas')->where('id', 17)->update(['ubicacion' => 'en_obra']);
    }

    private function registro(array $extra = []): array
    {
        return array_replace(['asignacion_esperada_id' => 81, 'horometro_esperado' => 100, 'horometro_fin' => 105,
            'inicio' => '2026-10-29T08:00:00-06:00', 'fin' => '2026-10-29T10:00:00-06:00', 'notas' => 'Horas en obra'], $extra);
    }

    public function test_adjustment_is_a_snapshot_with_actor_and_refreshed_operational_data(): void
    {
        $this->postJson($this->url.'/horas', $this->ajuste(['obra_id' => 11]))->assertCreated()
            ->assertJsonPath('data.operacion.tipo', 'ajuste_horometro')->assertJsonPath('data.operacion.diferencia', 15)
            ->assertJsonPath('data.operacion.reutilizada', false)->assertJsonPath('data.maquina.horometro_actual', 115)
            ->assertJsonPath('data.maquina.horometro_base', 100)->assertJsonPath('data.maquina.preventivo.horas_usadas', 15)
            ->assertJsonPath('data.contexto.horometro_esperado', 115)->assertJsonMissingPath('data.maquina.solicitud_hash');
        $this->assertDatabaseHas('maquina_horometro_ajustes', ['obra_id' => 11, 'created_by' => 80, 'origen' => 'app_mecanico',
            'horometro_anterior' => 100, 'horometro_nuevo' => 115, 'diferencia' => 15]);
        $this->assertNotNull(DB::table('maquina_horometro_ajustes')->value('solicitud_hash'));
        $this->assertDatabaseCount('obra_maquina_registros', 0);
        $this->assertDatabaseCount('obra_maquina', 0);
    }

    public function test_active_assignment_records_hours_and_normalizes_offset(): void
    {
        $this->asignar();
        $this->postJson($this->url.'/horas', $this->registro(['fin' => '2026-10-29T11:00:00-05:00']))->assertCreated()
            ->assertJsonPath('data.operacion.tipo', 'registro_horas')->assertJsonPath('data.operacion.asignacion_id', 81)
            ->assertJsonPath('data.operacion.diferencia', 5)->assertJsonPath('data.maquina.fecha_ultima_lectura', '2026-10-29T10:00:00-06:00')
            ->assertJsonPath('data.asignacion_activa.obra.id', 9);
        $this->assertDatabaseHas('obra_maquina_registros', ['obra_id' => 9, 'horometro_inicio' => 100, 'horometro_fin' => 105,
            'horas' => 5, 'created_by' => 80, 'origen' => 'app_mecanico', 'fin' => '2026-10-29 10:00:00']);
        $this->assertDatabaseCount('maquina_horometro_ajustes', 0);
    }

    public function test_retries_are_exact_persisted_and_return_original_difference_after_later_readings(): void
    {
        $payload = $this->ajuste();
        $primera = $this->postJson($this->url.'/horas', $payload)->assertCreated()->json('data.operacion.id');
        $this->postJson($this->url.'/horas', $this->ajuste(['horometro_esperado' => 115, 'horometro_fin' => 120, 'fecha_registro' => '2026-10-30']))->assertCreated();
        $this->postJson($this->url.'/horas', $payload)->assertOk()->assertJsonPath('data.operacion.id', $primera)
            ->assertJsonPath('data.operacion.reutilizada', true)->assertJsonPath('data.operacion.diferencia', 15)
            ->assertJsonPath('data.maquina.horometro_actual', 120);
        $this->postJson($this->url.'/horas', $this->ajuste(['notas' => 'Otra captura']))->assertStatus(409)
            ->assertJsonPath('code', 'contexto_maquina_cambio');
        $this->assertDatabaseCount('maquina_horometro_ajustes', 2);
    }

    public function test_record_retry_survives_retirement_but_new_capture_cannot_switch_to_adjustment(): void
    {
        $this->asignar(); $payload = $this->registro();
        $id = $this->postJson($this->url.'/horas', $payload)->assertCreated()->json('data.operacion.id');
        DB::table('obra_maquina')->where('id', 81)->update(['estado' => 'finalizada', 'fecha_fin' => '2026-10-29']);
        $this->postJson($this->url.'/horas', $payload)->assertOk()->assertJsonPath('data.operacion.id', $id)
            ->assertJsonPath('data.operacion.reutilizada', true)->assertJsonPath('data.asignacion_activa', null);
        $this->postJson($this->url.'/horas', $this->registro(['horometro_fin' => 106]))->assertStatus(409);
        $this->assertDatabaseCount('obra_maquina_registros', 1); $this->assertDatabaseCount('maquina_horometro_ajustes', 0);
    }

    public function test_other_actor_and_null_versus_zero_context_do_not_reuse_a_capture(): void
    {
        DB::table('maquinas')->where('id', 17)->update(['horometro_base' => null]);
        $payload = $this->ajuste(['horometro_esperado' => null, 'horometro_fin' => 10]);
        $this->postJson($this->url.'/horas', $payload)->assertCreated();
        $this->postJson($this->url.'/horas', array_replace($payload, ['horometro_esperado' => 0]))->assertStatus(409);
        $this->actingAs($this->usuario(90), 'sanctum');
        $this->postJson($this->url.'/horas', $payload)->assertStatus(409);
        $this->assertDatabaseCount('maquina_horometro_ajustes', 1);
    }

    public function test_stale_reading_or_assignment_never_writes(): void
    {
        $payload = $this->ajuste(); $this->asignar();
        $this->postJson($this->url.'/horas', $payload)->assertStatus(409);
        DB::table('maquinas')->where('id', 17)->update(['horometro_base' => 104]);
        $this->postJson($this->url.'/horas', $this->registro())->assertStatus(409);
        $this->assertDatabaseCount('maquina_horometro_ajustes', 0); $this->assertDatabaseCount('obra_maquina_registros', 0);
    }

    public function test_invalid_readings_precision_dates_and_internal_fields_are_rejected(): void
    {
        foreach ([100, 99, -1, 100.001, 100000000, 'INF'] as $lectura) {
            $this->postJson($this->url.'/horas', $this->ajuste(['horometro_fin' => $lectura]))->assertUnprocessable()->assertJsonValidationErrors('horometro_fin');
        }
        foreach (['2026-02-30', '29/10/2026', '2026-10-29T12:00:00-06:00'] as $fecha) {
            $this->postJson($this->url.'/horas', $this->ajuste(['fecha_registro' => $fecha]))->assertUnprocessable();
        }
        foreach (['created_by' => 90, 'origen' => 'web', 'tipo' => 'ajuste', 'solicitud_hash' => 'falso', 'inicio' => null] as $campo => $valor) {
            $this->postJson($this->url.'/horas', $this->ajuste([$campo => $valor]))->assertUnprocessable()->assertJsonValidationErrors($campo);
        }
        $this->assertDatabaseCount('maquina_horometro_ajustes', 0);
    }

    public function test_missing_context_cancelled_related_worksite_and_old_date_are_rejected(): void
    {
        $payload = $this->ajuste(); unset($payload['horometro_esperado']);
        $this->postJson($this->url.'/horas', $payload)->assertUnprocessable()->assertJsonValidationErrors('horometro_esperado');
        foreach ([10, 12, 999] as $obra) {
            $this->postJson($this->url.'/horas', $this->ajuste(['obra_id' => $obra]))->assertUnprocessable()->assertJsonValidationErrors('obra_id');
        }
        $this->postJson($this->url.'/horas', $this->ajuste())->assertCreated();
        $this->postJson($this->url.'/horas', $this->ajuste(['horometro_esperado' => 115, 'horometro_fin' => 120, 'fecha_registro' => '2026-10-28']))->assertUnprocessable();
        $this->assertDatabaseCount('maquina_horometro_ajustes', 1);
    }

    public function test_record_requires_real_dates_with_offset_and_no_client_worksite(): void
    {
        $this->asignar();
        foreach ([['inicio' => null], ['inicio' => '2026-10-29T08:00:00'], ['fin' => '2026-02-30T10:00:00-06:00'],
            ['fin' => '2026-10-29T07:00:00-06:00'], ['obra_id' => 11], ['fecha_registro' => '2026-10-29']] as $extra) {
            $this->postJson($this->url.'/horas', $this->registro($extra))->assertUnprocessable();
        }
        $this->assertDatabaseCount('obra_maquina_registros', 0);
    }

    public function test_capabilities_and_paginated_related_worksites_exclude_future_actions_and_private_data(): void
    {
        $this->getJson($this->url.'/opciones-operacion')->assertOk()
            ->assertJsonPath('data.acciones_permitidas.ajustar_horometro', true)->assertJsonPath('data.acciones_permitidas.registrar_horas', false)
            ->assertJsonPath('data.acciones_permitidas.asignar_obra', false)->assertJsonPath('data.acciones_permitidas.retirar_obra', false)
            ->assertJsonPath('data.acciones_permitidas.cambiar_ubicacion', false)->assertJsonMissingPath('data.obras')
            ->assertJsonMissingPath('data.seguros_recientes');
        $this->getJson($this->url.'/opciones-operacion?uso=ajuste&per_page=1&page=2')->assertOk()
            ->assertJsonPath('meta.total', 2)->assertJsonPath('meta.current_page', 2)->assertJsonCount(1, 'data.obras');
        $this->getJson($this->url.'/opciones-operacion?uso=ajuste&q=OB-9')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.obras.0.id', 9);
        $this->asignar();
        $this->getJson($this->url.'/opciones-operacion')->assertOk()->assertJsonPath('data.contexto.asignacion_esperada_id', 81)
            ->assertJsonPath('data.acciones_permitidas.registrar_horas', true)->assertJsonPath('data.acciones_permitidas.ajustar_horometro', false);
        $this->getJson($this->url.'/opciones-operacion?uso=asignacion')->assertUnprocessable()->assertJsonPath('ok', false);
    }

    public function test_permission_is_enforced_before_model_resolution_including_retries(): void
    {
        $this->postJson($this->url.'/horas', $this->ajuste())->assertCreated();
        $this->permisos = ['app.mecanico.access'];
        $this->postJson('/api/v1/mecanico/maquinas/999/horas', [])->assertForbidden();
        $this->postJson($this->url.'/horas', $this->ajuste())->assertForbidden();
        $this->getJson('/api/v1/mecanico/maquinas/999/opciones-operacion?uso=ajuste')->assertForbidden();
        $this->getJson($this->url.'/opciones-operacion')->assertOk()->assertJsonPath('data.acciones_permitidas.ajustar_horometro', false);
        $this->permisos[] = 'maquinas.horas.create.access'; $this->denegarHoras = true;
        $this->postJson($this->url.'/horas', $this->ajuste())->assertForbidden();
        $this->denegarHoras = false;
        $this->postJson('/api/v1/mecanico/maquinas/999/horas', $this->ajuste())->assertNotFound();
    }

    public function test_resident_management_inactive_and_guest_cannot_write(): void
    {
        foreach (['app.regular.access', 'app.gerencial.access'] as $panel) {
            $this->permisos = [$panel, 'maquinas.horas.create.access'];
            $this->postJson($this->url.'/horas', $this->ajuste())->assertForbidden();
        }
        $this->permisos = ['app.mecanico.access', 'maquinas.horas.create.access'];
        $this->mock(AppMobileAccessService::class)->shouldReceive('usuarioActivo')
            ->andThrow(new HttpResponseException(response()->json(['ok' => false], 403)));
        $this->postJson($this->url.'/horas', $this->ajuste())->assertForbidden();
        auth('sanctum')->forgetUser();
        $this->postJson($this->url.'/horas', $this->ajuste())->assertUnauthorized();
        $this->assertDatabaseCount('maquina_horometro_ajustes', 0);
    }

    public function test_hash_failure_rolls_back_the_reading(): void
    {
        DB::unprepared("CREATE TRIGGER fallo_hash BEFORE UPDATE ON maquina_horometro_ajustes WHEN NEW.solicitud_hash IS NOT NULL BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");
        $this->withoutExceptionHandling();
        try {
            $this->postJson($this->url.'/horas', $this->ajuste());
            $this->fail('Debió fallar el guardado de huella.');
        } catch (QueryException $error) {
            $this->assertStringContainsString('fallo simulado', $error->getMessage());
        }
        $this->assertDatabaseCount('maquina_horometro_ajustes', 0);
        $this->assertDatabaseHas('maquinas', ['id' => 17, 'horometro_base' => 100]);
    }

    public function test_web_shares_coordination_and_preserves_optional_dates(): void
    {
        Route::middleware('web')->post('/prueba-horas/{maquina}', [MaquinaController::class, 'guardarHoras']);
        $this->actingAs($this->usuario(), 'web');
        $this->post('/prueba-horas/17', ['horometro_fin' => 115, 'fecha_registro' => '2026-10-29'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('maquina_horometro_ajustes', ['horometro_nuevo' => 115, 'origen' => 'web_maquinas', 'solicitud_hash' => null]);
        $this->asignar();
        $this->post('/prueba-horas/17', ['horometro_fin' => 120])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('obra_maquina_registros', ['horas' => 5, 'origen' => 'web_maquinas', 'created_by' => 80]);
    }

    public function test_migration_keeps_historical_rows_nullable_and_is_reversible(): void
    {
        $migration = $this->migracion(); $migration->down();
        DB::table('maquina_horometro_ajustes')->insert(['maquina_id' => 17, 'tipo' => 'ajuste', 'horometro_nuevo' => 50]);
        $migration->up();
        $this->assertNull(DB::table('maquina_horometro_ajustes')->value('solicitud_hash'));
        $this->assertDatabaseCount('maquina_horometro_ajustes', 1);
        $this->assertTrue(Schema::hasColumn('obra_maquina_registros', 'solicitud_hash'));
    }

    public function test_explicit_web_and_api_adjustments_preserve_the_same_business_values(): void
    {
        $this->postJson($this->url.'/horas', $this->ajuste(['obra_id' => 9]))->assertCreated();
        $campos = ['obra_id', 'fecha_registro', 'horometro_anterior', 'horometro_nuevo', 'diferencia', 'tipo', 'notas', 'created_by'];
        $movil = DB::table('maquina_horometro_ajustes')->where('maquina_id', 17)->first($campos);
        DB::table('maquinas')->insert(['id' => 18, 'nombre' => 'Equipo web', 'horometro_base' => 100]);
        Route::middleware('web')->post('/prueba-paridad/{maquina}', [MaquinaController::class, 'guardarHoras']);
        $this->actingAs($this->usuario(), 'web');
        $this->post('/prueba-paridad/18', ['obra_id' => 9, 'horometro_fin' => 115, 'fecha_registro' => '2026-10-29',
            'notas' => 'Lectura verificada'])->assertRedirect()->assertSessionHasNoErrors();
        $web = DB::table('maquina_horometro_ajustes')->where('maquina_id', 18)->first($campos);
        $this->assertEquals($movil, $web);
    }
}
