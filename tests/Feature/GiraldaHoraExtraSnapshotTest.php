<?php

namespace Tests\Feature;

use App\Http\Controllers\Nomina\NominaCorridaController;
use App\Models\Empleado;
use App\Models\GiraldaHoraExtra;
use App\Models\User;
use App\Services\Empleados\EmpleadoHoraExtraTarifaService;
use App\Services\Empleados\GiraldaHoraExtraSnapshotService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class GiraldaHoraExtraSnapshotTest extends TestCase
{
    private Empleado $empleado;
    private GiraldaHoraExtraSnapshotService $service;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ejecutar PHP con -d extension=pdo_sqlite.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->travelTo(now()->setDate(2026, 10, 26)->startOfDay());
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Usuario prueba']);
        Schema::create('empleados', function (Blueprint $table) {
            $table->integer('id_Empleado')->primary();
            $table->string('Nombre'); $table->string('Apellidos');
            $table->integer('Area')->nullable();
            $table->decimal('Sueldo_real', 14, 2)->nullable();
            $table->decimal('Sueldo', 14, 2)->nullable();
            $table->decimal('Complemento', 14, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('areas', function (Blueprint $table) {
            $table->id(); $table->string('nombre'); $table->string('codigo');
        });
        DB::table('areas')->insert(['id' => 1, 'nombre' => 'Giralda', 'codigo' => 'GL']);
        Schema::create('nomina_corridas', function (Blueprint $table) {
            $table->id(); $table->string('status');
        });
        Schema::create('nomina_recibos', function (Blueprint $table) {
            $table->id(); $table->integer('empleado_id'); $table->unsignedBigInteger('corrida_id')->nullable();
            $table->date('fecha_inicio')->nullable(); $table->date('fecha_fin')->nullable(); $table->string('status');
        });
        Schema::create('documento_firmantes', function (Blueprint $table) {
            $table->id(); $table->string('documento'); $table->boolean('activo');
        });
        foreach ([
            '2026_10_26_000002_create_empleado_hora_extra_tarifas_table.php',
            '2026_08_03_130000_create_giralda_horas_extras_table.php',
            '2026_10_26_000003_add_snapshot_to_giralda_horas_extras_table.php',
        ] as $archivo) {
            (require database_path('migrations/'.$archivo))->up();
        }
        $this->empleado = Empleado::create(['id_Empleado' => 3729, 'Nombre' => 'Empleado', 'Apellidos' => 'Prueba', 'Area' => 1, 'Sueldo_real' => 2800]);
        $this->service = app(GiraldaHoraExtraSnapshotService::class);
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => 1, 'name' => 'Usuario prueba', 'email_verified_at' => now()]);
        $user->shouldReceive('hasDeniedPermission')->andReturn(false);
        $user->shouldReceive('checkPermissionTo')->andReturn(false);
        $user->shouldReceive('hasRole')->andReturn(false);
        $user->shouldReceive('hasAnyRole')->andReturn(false);
        foreach (['giralda.access', 'giralda.horas_extras.edit.access'] as $permiso) {
            Gate::define($permiso, fn () => true);
        }
        $this->actingAs($user);
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'empleado_id' => 3729, 'fecha' => '2026-10-26', 'hora_inicio' => '17:30', 'hora_fin' => '19:30',
            'total_horas' => 2, 'motivo' => 'Trabajo extra', 'responsable_solicita' => 'Solicitante', 'estado' => 'pendiente',
        ], $extra);
    }

    private function fija(): void
    {
        app(EmpleadoHoraExtraTarifaService::class)->registrar($this->empleado, [
            'modalidad' => 'cuota_fija', 'costo_hora_extra' => 150,
            'vigente_desde' => '2026-10-26', 'motivo' => 'Cuota acordada',
        ], 1);
    }

    public function test_captura_http_calcula_en_servidor_y_congela_sueldo_e_importe(): void
    {
        $this->post(route('giralda.horas-extras.store'), $this->datos(['importe' => 1, 'costo_hora_extra' => 1]))
            ->assertSessionHasNoErrors()->assertRedirect(route('giralda.empleados', ['tab' => 'horas_extras']));
        $registro = GiraldaHoraExtra::firstOrFail();
        $this->assertSame('100.0000', $registro->costo_hora_extra);
        $this->assertSame('200.00', $registro->importe);
        $this->assertSame('2800.00', $registro->sueldo_base_snapshot);
        $this->empleado->update(['Sueldo_real' => 5600]);
        $this->assertSame(200.0, $this->service->resumen([$registro->fresh()])['monto']);
    }

    public function test_cuota_fija_se_conserva_y_editar_horas_no_usa_la_tarifa_nueva(): void
    {
        $this->fija();
        $registro = $this->service->crear($this->datos(), 1);
        app(EmpleadoHoraExtraTarifaService::class)->registrar($this->empleado, [
            'modalidad' => 'cuota_fija', 'costo_hora_extra' => 300, 'vigente_desde' => '2026-10-27', 'motivo' => 'Aumento',
        ], 1);
        $this->put(route('giralda.horas-extras.update', $registro), $this->datos(['total_horas' => 3]))->assertSessionHasNoErrors();
        $this->assertSame('450.00', $registro->fresh()->importe);
        $this->assertSame('150.0000', $registro->fresh()->costo_hora_extra);
        $this->assertNull($registro->fresh()->sueldo_base_snapshot);
        $this->put(route('giralda.horas-extras.update', $registro), $this->datos(['fecha' => '2026-10-27']))->assertSessionHasErrors('fecha');
        $this->assertSame('2026-10-26', $registro->fresh()->fecha->toDateString());
    }

    public function test_sin_tarifa_no_crea_un_importe_cero(): void
    {
        $this->empleado->update(['Sueldo_real' => null]);
        $this->post(route('giralda.horas-extras.store'), $this->datos())->assertSessionHasErrors('total_horas');
        $this->assertDatabaseCount('giralda_horas_extras', 0);
    }

    public function test_llenado_fijo_simula_aplica_y_no_sobrescribe(): void
    {
        $this->fija();
        $registro = GiraldaHoraExtra::create($this->datos());
        $this->artisan('giralda:horas-extras-snapshots')->assertSuccessful();
        $this->assertNull($registro->fresh()->snapshot_at);
        $updated = $registro->updated_at->toDateTimeString();
        $this->artisan('giralda:horas-extras-snapshots', ['--aplicar' => true, '--lote' => 1])->assertSuccessful();
        $guardado = $registro->fresh()->getAttributes();
        $this->assertSame('300.00', $registro->fresh()->importe);
        $this->assertSame('reconstruido_cuota_fija', $registro->fresh()->snapshot_fuente);
        $this->assertSame($updated, $registro->fresh()->updated_at->toDateTimeString());
        $this->artisan('giralda:horas-extras-snapshots', ['--aplicar' => true])->assertSuccessful();
        $this->assertEquals($guardado, $registro->fresh()->getAttributes());
    }

    public function test_automatico_historico_requiere_opcion_explicita_y_queda_identificado(): void
    {
        $registro = GiraldaHoraExtra::create($this->datos());
        $this->artisan('giralda:horas-extras-snapshots', ['--aplicar' => true])->assertSuccessful();
        $this->assertNull($registro->fresh()->importe);
        $this->artisan('giralda:horas-extras-snapshots', ['--aplicar' => true, '--estimar-sueldo-actual' => true])->assertSuccessful();
        $this->assertSame('estimado_sueldo_actual', $registro->fresh()->snapshot_fuente);
        $this->assertSame('200.00', $registro->fresh()->importe);
        $this->assertSame(1, $this->service->resumen([$registro->fresh()])['estimados']);
    }

    public function test_no_completa_periodos_pagados_ni_snapshots_parciales(): void
    {
        $this->fija();
        $pagado = GiraldaHoraExtra::create($this->datos());
        DB::table('nomina_corridas')->insert(['id' => 1, 'status' => 'pagada']);
        DB::table('nomina_recibos')->insert(['empleado_id' => 3729, 'corrida_id' => 1, 'fecha_inicio' => '2026-10-26', 'fecha_fin' => '2026-11-01', 'status' => 'pendiente']);
        $this->assertSame('nomina_protegida', $this->service->completar($pagado, true, true));
        $this->assertNull($pagado->fresh()->importe);
        $parcial = GiraldaHoraExtra::create($this->datos(['importe' => 99]));
        $this->assertSame('parcial_revisar', $this->service->completar($parcial, true, true));
        $this->assertSame('99.00', $parcial->fresh()->importe);
    }

    public function test_filtros_de_comando_y_validacion(): void
    {
        $this->fija();
        $primero = GiraldaHoraExtra::create($this->datos());
        $despues = GiraldaHoraExtra::create($this->datos(['fecha' => '2026-10-27']));
        $this->artisan('giralda:horas-extras-snapshots', ['--aplicar' => true, '--empleado' => 3729, '--hasta' => '2026-10-26'])->assertSuccessful();
        $this->assertNotNull($primero->fresh()->importe);
        $this->assertNull($despues->fresh()->importe);
        $this->artisan('giralda:horas-extras-snapshots', ['--lote' => 0])->assertExitCode(2);
    }

    public function test_impresion_y_nomina_usan_snapshots_con_tarifas_distintas(): void
    {
        $this->service->crear($this->datos(), 1);
        $this->fija();
        $this->service->crear($this->datos(), 1);
        $this->empleado->update(['Sueldo_real' => 5600]);
        $method = new \ReflectionMethod(NominaCorridaController::class, 'calcularHorasExtraGiraldaPorEmpleado');
        $result = $method->invoke(app(NominaCorridaController::class), '2026-10-26', '2026-11-01', [$this->empleado]);
        $this->assertSame(500.0, $result->get(3729)->monto);
        $this->get(route('giralda.horas-extras.print', ['desde' => '2026-10-26', 'hasta' => '2026-11-01']))
            ->assertOk()->assertSee('500.00')->assertSee('Tarifas múltiples');
    }

    public function test_nomina_rechaza_pendientes_sin_inventar_importes(): void
    {
        GiraldaHoraExtra::create($this->datos());
        $method = new \ReflectionMethod(NominaCorridaController::class, 'calcularHorasExtraGiraldaPorEmpleado');
        $this->expectException(ValidationException::class);
        $method->invoke(app(NominaCorridaController::class), '2026-10-26', '2026-11-01', [$this->empleado]);
    }

    public function test_no_modifica_snapshot_en_periodo_pagado(): void
    {
        $registro = $this->service->crear($this->datos(), 1);
        DB::table('nomina_recibos')->insert(['empleado_id' => 3729, 'fecha_inicio' => '2026-10-26', 'fecha_fin' => '2026-11-01', 'status' => 'pagado']);
        $this->put(route('giralda.horas-extras.update', $registro), $this->datos(['total_horas' => 3]))->assertSessionHasErrors('total_horas');
        $this->assertSame('200.00', $registro->fresh()->importe);
    }

    public function test_recibo_cerrado_sin_fechas_se_conserva_para_revision(): void
    {
        $this->fija();
        $registro = GiraldaHoraExtra::create($this->datos());
        DB::table('nomina_recibos')->insert(['empleado_id' => 3729, 'status' => 'cerrado']);
        $this->assertSame('nomina_protegida', $this->service->completar($registro, true, true));
        $this->assertNull($registro->fresh()->snapshot_at);
    }

    public function test_impresion_incompleta_no_muestra_un_total_cero(): void
    {
        GiraldaHoraExtra::create($this->datos());
        $this->get(route('giralda.horas-extras.print', ['desde' => '2026-10-26', 'hasta' => '2026-11-01']))
            ->assertOk()->assertSee('Pendiente de completar');
    }

    public function test_impresion_sin_filtros_solo_suma_la_semana_mostrada(): void
    {
        $this->service->crear($this->datos(['fecha' => '2026-10-01']), 1);
        $this->service->crear($this->datos(), 1);
        $this->get(route('giralda.horas-extras.print'))->assertOk()
            ->assertViewHas('totalPesosPeriodo', 200.0)->assertViewHas('totalPeriodo', 2.0);
    }

    public function test_migracion_conserva_registros_previos_y_se_puede_revertir(): void
    {
        $registro = GiraldaHoraExtra::create($this->datos());
        $migration = require database_path('migrations/2026_10_26_000003_add_snapshot_to_giralda_horas_extras_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('giralda_horas_extras', 'importe'));
        $this->assertDatabaseHas('giralda_horas_extras', ['id' => $registro->id, 'total_horas' => 2]);
        $migration->up();
        $this->assertNull($registro->fresh()->importe);
        $this->assertSame('2.00', $registro->fresh()->total_horas);
    }

    public function test_semana_completa_incluye_dos_empleados_y_limpia_urls_anteriores(): void
    {
        $this->fija();
        $this->service->crear($this->datos(), 1);
        $otro = Empleado::create(['id_Empleado' => 3698, 'Nombre' => 'Otro', 'Apellidos' => 'Empleado', 'Area' => 1, 'Sueldo_real' => 5600]);
        $this->service->crear($this->datos(['empleado_id' => $otro->getKey(), 'total_horas' => 1]), 1);
        $parametros = ['desde' => '2026-10-26', 'hasta' => '2026-11-01'];
        $this->get(route('giralda.horas-extras.print', $parametros))->assertOk()
            ->assertViewHas('filas', fn ($filas) => $filas->count() === 2)
            ->assertViewHas('totalPesosPeriodo', 500.0);
        $this->get(route('giralda.horas-extras.print', $parametros + ['empleado_id' => 3698]))
            ->assertRedirect(route('giralda.horas-extras.print', $parametros));
        $this->followingRedirects()->get(route('giralda.horas-extras.print', $parametros + ['empleado_id' => 3698]))
            ->assertOk()->assertViewHas('filas', fn ($filas) => $filas->count() === 2);
    }
}
