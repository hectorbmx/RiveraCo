<?php

namespace Tests\Feature;

use App\Models\Mantenimiento;
use App\Models\User;
use App\Models\UsuarioApp;
use App\Services\Contracts\ImageOptimizerInterface;
use App\Services\Mantenimiento\MantenimientoAbastecimientoService;
use App\Services\Mantenimiento\MantenimientoBorradorService;
use App\Services\Mantenimiento\MantenimientoEvidenciaService;
use App\Services\Mantenimiento\MantenimientoMaterialesService;
use App\Services\Mantenimiento\MantenimientoOrdenService;
use App\Services\Maquinas\MaquinaHorometroService;
use App\Services\Mobile\AppMobileAccessService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class AppMobileServiceDraftTest extends TestCase
{
    private User $user;

    private UsuarioApp $usuarioApp;

    private array $permisos = ['app.mecanico.access', 'maquinas.servicios.edit.access'];

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ejecutar con php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter=AppMobileServiceDraftTest');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->crearEsquema();
        DB::table('maquinas')->insert(['id' => 17, 'nombre' => 'Equipo']);
        DB::table('vehiculos')->insert(['id' => 7, 'marca' => 'Vehículo']);
        DB::table('empleados')->insert([['id_Empleado' => 11], ['id_Empleado' => 12]]);
        DB::table('obras')->insert(['id' => 9, 'nombre' => 'Obra']);
        DB::table('empresa_servicio_preventivo_tipos')->insert([
            ['id' => 1, 'ambito' => 'maquinaria', 'activo' => true],
            ['id' => 2, 'ambito' => 'vehiculo', 'activo' => true],
            ['id' => 3, 'ambito' => 'maquinaria', 'activo' => false],
        ]);
        $this->user = Mockery::mock(User::class)->makePartial();
        $this->user->id = 80;
        $this->user->email_verified_at = now();
        $this->user->shouldReceive('hasDeniedPermission')->andReturn(false);
        $this->user->shouldReceive('checkPermissionTo')->andReturn(false);
        foreach (['app.mecanico.access', 'maquinas.servicios.edit.access'] as $permiso) {
            Gate::define($permiso, fn (User $u) => in_array($permiso, $this->permisos, true));
        }
        $this->actingAs($this->user, 'sanctum');
        $this->usuarioApp = new UsuarioApp(['user_id' => 80, 'empleado_id' => 11, 'is_active' => true]);
        $this->mock(AppMobileAccessService::class)->shouldReceive('usuarioActivo')->andReturnUsing(fn () => $this->usuarioApp);
        $this->mock(MaquinaHorometroService::class)->shouldReceive('horometroActual')->andReturn(100.0);
    }

    private function prepararEjecucion(): Mantenimiento
    {
        $this->prepararConsulta();
        Schema::table('empleados', function (Blueprint $t) {
            $t->decimal('Sueldo_real')->nullable();
            $t->decimal('Sueldo')->nullable();
            $t->integer('Sueldo_tipo')->nullable();
        });
        DB::table('empleados')->where('id_Empleado', 11)->update(['Nombre' => 'Ana', 'Sueldo_real' => 5600, 'Sueldo_tipo' => 1]);
        $servicio = $this->crear();
        $servicio->update(['estatus' => 'orden_generada', 'orden_servicio_folio' => 'OS-T12']);

        return $servicio->refresh();
    }

    private function materialEjecucion(Mantenimiento $s, ?int $producto = 5, float $cantidad = 10, bool $incluido = true): void
    {
        DB::table('mantenimiento_materiales')->insert(['mantenimiento_id' => $s->id,
            'producto_id' => $producto, 'cantidad_requerida' => $cantidad, 'incluido' => $incluido]);
    }

    private function surtirEjecucion(Mantenimiento $s, float $cantidad): void
    {
        $id = DB::table('mantenimiento_abastecimientos')->insertGetId(['mantenimiento_id' => $s->id, 'tipo' => 'almacen', 'estado' => 'surtida']);
        DB::table('mantenimiento_abastecimiento_detalles')->insert(['abastecimiento_id' => $id, 'producto_id' => 5, 'cantidad_atendida' => $cantidad]);
    }

    public function test_execution_starts_with_pending_materials_and_completion_requires_actual_supply(): void
    {
        $s = $this->prepararEjecucion();
        $this->materialEjecucion($s);
        $url = "/api/v1/mecanico/servicios/{$s->id}";
        $this->postJson("$url/iniciar", ['fecha_inicio' => '2026-10-25T08:00:00-06:00'])->assertOk()
            ->assertJsonPath('data.estatus', 'en_proceso')->assertJsonPath('data.acciones_permitidas.completar', false);
        $datos = ['fecha_fin' => '2026-10-25T10:00:00-06:00', 'horometro' => 110, 'notas' => 'Servicio realizado'];
        $this->postJson("$url/completar", $datos)->assertStatus(409);
        $this->assertDatabaseHas('mantenimientos', ['id' => $s->id, 'estatus' => 'en_proceso', 'fecha_fin' => null, 'costo_total' => 0]);
        $this->surtirEjecucion($s, 10);
        $this->getJson($url)->assertOk()->assertJsonPath('data.acciones_permitidas.completar', true);
        $this->postJson("$url/completar", $datos)->assertOk()->assertJsonPath('data.estatus', 'completado')
            ->assertJsonPath('data.costo_mano_obra', 200)->assertJsonPath('data.notas', 'Servicio realizado');
        DB::table('empleados')->where('id_Empleado', 11)->update(['Sueldo_real' => 11200]);
        $this->postJson("$url/completar", $datos)->assertOk()->assertJsonPath('data.costo_mano_obra', 200);
        $this->postJson("$url/completar", array_merge($datos, ['horometro' => 111]))->assertStatus(409);
        $this->assertDatabaseCount('mantenimiento_detalles', 1);
    }

    public function test_execution_without_included_materials_and_start_retry(): void
    {
        $s = $this->prepararEjecucion();
        $this->materialEjecucion($s, null, 10, false);
        $url = "/api/v1/mecanico/servicios/{$s->id}";
        $inicio = ['fecha_inicio' => '2026-10-25 08:00:00'];
        $this->postJson("$url/iniciar", $inicio)->assertOk();
        $this->postJson("$url/iniciar", $inicio)->assertOk();
        $this->postJson("$url/iniciar", ['fecha_inicio' => '2026-10-25 09:00:00'])->assertStatus(409);
        $this->postJson("$url/completar", ['fecha_fin' => '2026-10-25 09:00:00', 'horometro' => 100])->assertOk();
    }

    public function test_execution_rejects_ownership_payload_dates_readings_and_invalid_states(): void
    {
        $s = $this->prepararEjecucion();
        $url = "/api/v1/mecanico/servicios/{$s->id}";
        $inicio = ['fecha_inicio' => '2026-10-25 08:00:00'];
        $fin = ['fecha_fin' => '2026-10-25 10:00:00', 'horometro' => 110];
        $this->postJson("$url/completar", $fin)->assertStatus(409);
        foreach (['costo_total', 'mecanico_id', 'estatus', 'maquina_id'] as $campo) {
            $this->postJson("$url/iniciar", array_merge($inicio, [$campo => 1]))->assertUnprocessable();
        }
        $s->update(['mecanico_id' => 12]);
        $this->postJson("$url/iniciar", $inicio)->assertForbidden();
        $s->update(['mecanico_id' => 11, 'estatus' => 'cancelado']);
        $this->postJson("$url/iniciar", $inicio)->assertStatus(409);
        $s->update(['estatus' => 'borrador']);
        $this->postJson("$url/iniciar", $inicio)->assertStatus(409);
        $s->update(['estatus' => 'orden_generada']);
        $this->postJson("$url/iniciar", $inicio)->assertOk();
        $this->postJson("$url/completar", array_merge($fin, ['horometro' => 99]))->assertUnprocessable();
        $this->postJson("$url/completar", array_merge($fin, ['fecha_fin' => '2026-10-25 08:00:00']))->assertUnprocessable();
        $this->postJson("$url/completar", ['fecha_fin' => '2026-10-25 10:00:00'])->assertUnprocessable();
        $this->postJson("$url/completar", array_merge($fin, ['costo_total' => 1]))->assertUnprocessable();
        $this->assertDatabaseHas('mantenimientos', ['id' => $s->id, 'estatus' => 'en_proceso', 'horometro' => 100]);
    }

    public function test_execution_groups_duplicate_products_and_rejects_unlinked_materials(): void
    {
        $s = $this->prepararEjecucion();
        $this->materialEjecucion($s); $this->materialEjecucion($s);
        $this->surtirEjecucion($s, 10);
        $service = app(\App\Services\Mantenimiento\MantenimientoEjecucionService::class);
        $this->assertFalse($service->surtidoCompleto($s->fresh()));
        $avance = app(MantenimientoAbastecimientoService::class)->calcular($s->fresh());
        $this->assertSame(10.0, (float) $avance->sum('surtido'));
        $this->assertSame(10.0, (float) $avance->sum('faltante'));
        $this->surtirEjecucion($s, 10);
        $this->assertTrue($service->surtidoCompleto($s->fresh()));
        $this->materialEjecucion($s, null, 1);
        $this->assertFalse($service->surtidoCompleto($s->fresh()));
    }

    public function test_web_execution_uses_same_material_gate_and_rolls_back_callback(): void
    {
        $s = $this->prepararEjecucion();
        $this->materialEjecucion($s);
        $service = app(\App\Services\Mantenimiento\MantenimientoEjecucionService::class);
        $inicio = ['estatus' => 'en_proceso', 'fecha_inicio' => '2026-10-25 08:00:00'];
        $service->guardarWeb($s, $inicio, fn ($actual) => $actual->update($inicio));
        try {
            $service->guardarWeb($s, ['estatus' => 'pendiente'], fn ($actual) => $actual->update(['estatus' => 'pendiente']));
            $this->fail('No debe retroceder una ejecución iniciada.');
        } catch (ConflictHttpException $e) {
            $this->assertStringContainsString('etapa anterior', $e->getMessage());
        }
        $fin = ['estatus' => 'completado', 'fecha_fin' => '2026-10-25 10:00:00', 'horometro' => 110];
        try {
            $service->guardarWeb($s, $fin, fn ($actual) => $actual->update($fin));
            $this->fail('No debe guardar un cierre pendiente de surtido.');
        } catch (ConflictHttpException $e) {
            $this->assertStringContainsString('surtidos', $e->getMessage());
        }
        $this->assertDatabaseHas('mantenimientos', ['id' => $s->id, 'estatus' => 'en_proceso', 'fecha_fin' => null]);
        $this->surtirEjecucion($s, 10);
        try {
            $service->guardarWeb($s, $fin, function ($actual) use ($fin) {
                $actual->update($fin);
                throw new \RuntimeException('fallo persistencia');
            });
            $this->fail('Debe propagar el fallo.');
        } catch (\RuntimeException $e) {
            $this->assertSame('fallo persistencia', $e->getMessage());
        }
        $this->assertDatabaseHas('mantenimientos', ['id' => $s->id, 'estatus' => 'en_proceso', 'fecha_fin' => null]);
        $service->guardarWeb($s, ['estatus' => 'en_proceso', 'mecanico_id' => 12], fn ($actual) => $actual->update(['mecanico_id' => 12]));
        $this->assertDatabaseHas('mantenimientos', ['id' => $s->id, 'mecanico_id' => 12]);
    }

    public function test_vehicle_completion_requires_integer_odometer_without_inventing_supply_or_salary(): void
    {
        $s = $this->prepararEjecucion();
        $s->update(['maquina_id' => null, 'vehiculo_id' => 7, 'servicio_preventivo_tipo_id' => 2, 'horometro' => null, 'km_actuales' => 12000]);
        DB::table('empleados')->where('id_Empleado', 11)->update(['Sueldo_real' => null, 'Sueldo' => null]);
        $url = "/api/v1/mecanico/servicios/{$s->id}";
        $this->postJson("$url/iniciar", ['fecha_inicio' => '2026-10-25 08:00:00'])->assertOk();
        foreach ([11999, 12001.5] as $km) {
            $this->postJson("$url/completar", ['fecha_fin' => '2026-10-25 10:00:00', 'km_actuales' => $km])->assertUnprocessable();
        }
        $this->postJson("$url/completar", ['fecha_fin' => '2026-10-25 10:00:00', 'km_actuales' => 12010])->assertOk()
            ->assertJsonPath('data.km_actuales', 12010)->assertJsonPath('data.costo_total', 0);
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 0);
        $this->assertDatabaseCount('mantenimiento_detalles', 0);
    }

    public function test_web_controller_shares_supply_gate_and_labor_cost_with_api(): void
    {
        $s = $this->prepararEjecucion();
        $this->materialEjecucion($s);
        $url = route('mantenimiento.mantenimientos.update', $s);
        $payload = $this->datos(['tipo' => 'programado', 'mecanico_id' => 11,
            'estatus' => 'en_proceso', 'fecha_inicio' => '2026-10-25 08:00:00', 'abastecimiento_tipo' => 'parcial']);
        $this->put($url, $payload)->assertRedirect();
        $this->assertDatabaseHas('mantenimientos', ['id' => $s->id, 'estatus' => 'en_proceso']);
        $payload = array_merge($payload, ['estatus' => 'completado', 'fecha_fin' => '2026-10-25 10:00:00', 'horometro' => 110]);
        $this->put($url, $payload)->assertStatus(409);
        $this->assertDatabaseHas('mantenimientos', ['id' => $s->id, 'estatus' => 'en_proceso', 'fecha_fin' => null]);
        $this->surtirEjecucion($s, 10);
        $this->put($url, $payload)->assertRedirect();
        $this->assertDatabaseHas('mantenimientos', ['id' => $s->id, 'estatus' => 'completado', 'costo_mano_obra' => 200]);
        $this->assertDatabaseCount('mantenimiento_detalles', 1);
    }

    public function test_completion_rolls_back_state_when_automatic_labor_fails(): void
    {
        $s = $this->prepararEjecucion();
        $service = app(\App\Services\Mantenimiento\MantenimientoEjecucionService::class);
        $service->ejecutar($s, 'iniciar', ['fecha_inicio' => '2026-10-25 08:00:00'], 11);
        DB::unprepared("CREATE TRIGGER fail_labor BEFORE INSERT ON mantenimiento_detalles BEGIN SELECT RAISE(ABORT, 'fallo mano obra'); END");
        try {
            $service->ejecutar($s, 'completar', ['fecha_fin' => '2026-10-25 10:00:00', 'horometro' => 110], 11);
            $this->fail('Debe revertir el cierre.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('fallo mano obra', $e->getMessage());
        }
        $this->assertDatabaseHas('mantenimientos', ['id' => $s->id, 'estatus' => 'en_proceso', 'fecha_fin' => null, 'horometro' => 100]);
        $this->assertDatabaseCount('mantenimiento_detalles', 0);
    }

    public function test_execution_normalizes_offsets_and_completed_service_drives_existing_preventive_calculator(): void
    {
        $s = $this->prepararEjecucion();
        Schema::table('empresa_servicio_preventivo_tipos', function (Blueprint $t) {
            $t->string('codigo')->nullable();
            foreach (['orden', 'intervalo_valor', 'intervalo_meses', 'alerta_valor'] as $campo) {
                $t->integer($campo)->default(0);
            }
        });
        DB::table('empresa_servicio_preventivo_tipos')->where('id', 1)->update(['intervalo_valor' => 250]);
        $maquina = \App\Models\Maquina::findOrFail(17);
        $preventivos = app(\App\Services\Maquinas\PreventivoMaquinaService::class);
        $antes = $preventivos->calcularParaColeccion(collect([$maquina]), new \App\Models\EmpresaConfig);
        $this->assertSame(250.0, $antes[17]['proximo_horometro']);
        $url = "/api/v1/mecanico/servicios/{$s->id}";
        $this->postJson("$url/iniciar", ['fecha_inicio' => '2026-10-25T14:00:00Z'])->assertOk();
        $this->assertSame('08:00', $s->fresh()->fecha_inicio->format('H:i'));
        $this->postJson("$url/iniciar", ['fecha_inicio' => '2026-10-25T08:00:00-06:00'])->assertOk();
        $this->postJson("$url/completar", ['fecha_fin' => '2026-10-25T16:00:00Z', 'horometro' => 110])->assertOk();
        $despues = $preventivos->calcularParaColeccion(collect([$maquina]), new \App\Models\EmpresaConfig);
        $this->assertSame(360.0, $despues[17]['proximo_horometro']);
        $this->assertDatabaseHas('maquinas', ['id' => 17, 'nombre' => 'Equipo']);
    }

    public function test_execution_rechecks_permission_employee_and_service_type(): void
    {
        $s = $this->prepararEjecucion();
        $url = "/api/v1/mecanico/servicios/{$s->id}/iniciar";
        $datos = ['fecha_inicio' => '2026-10-25 08:00:00'];
        $this->permisos = [];
        $this->postJson($url, $datos)->assertForbidden();
        $this->permisos = ['app.mecanico.access'];
        $this->usuarioApp->empleado_id = null;
        $this->postJson($url, $datos)->assertUnprocessable();
        $this->usuarioApp->empleado_id = 11;
        $s->update(['tipo' => 'emergencia']);
        $this->postJson($url, $datos)->assertNotFound();
    }

    private function crearEsquema(): void
    {
        foreach (['maquinas' => 'nombre', 'vehiculos' => 'marca', 'obras' => 'nombre'] as $tabla => $campo) {
            Schema::create($tabla, function (Blueprint $t) use ($campo) {
                $t->id();
                $t->string($campo);
            });
        }
        Schema::create('empleados', fn (Blueprint $t) => $t->unsignedBigInteger('id_Empleado')->primary());
        Schema::create('empresa_servicio_preventivo_tipos', function (Blueprint $t) {
            $t->id();
            $t->string('ambito');
            $t->boolean('activo');
        });
        Schema::create('mantenimientos', function (Blueprint $t) {
            $t->id();
            foreach (['maquina_id', 'vehiculo_id', 'obra_id', 'mecanico_id', 'servicio_preventivo_tipo_id', 'orden_servicio_generada_por'] as $campo) {
                $t->unsignedBigInteger($campo)->nullable();
            }
            foreach (['tipo', 'categoria_mantenimiento', 'descripcion', 'estatus', 'abastecimiento_tipo', 'abastecimiento_estado', 'orden_servicio_folio', 'notas'] as $campo) {
                $t->string($campo)->nullable();
            }
            foreach (['horometro', 'km_actuales', 'km_proximo_servicio'] as $campo) {
                $t->decimal($campo)->nullable();
            }
            foreach (['fecha_programada', 'fecha_inicio', 'fecha_fin', 'orden_servicio_generada_at'] as $campo) {
                $t->dateTime($campo)->nullable();
            }
            foreach (['costo_mano_obra', 'costo_refacciones', 'costo_total'] as $campo) {
                $t->decimal($campo)->default(0);
            }
            $t->timestamps();
        });
        Schema::create('mantenimiento_detalles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mantenimiento_id')->constrained('mantenimientos');
            $t->string('concepto');
            $t->string('tipo')->nullable();
            $t->decimal('cantidad');
            $t->decimal('costo_unitario');
            $t->decimal('costo_total');
            $t->timestamps();
        });
        foreach (['mantenimiento_abastecimientos', 'mantenimiento_materiales', 'mantenimiento_fotos'] as $tabla) {
            Schema::create($tabla, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('mantenimiento_id');
            });
        }
    }

    private function datos(array $cambios = []): array
    {
        return array_merge(['maquina_id' => 17, 'categoria_mantenimiento' => 'Preventivo',
            'servicio_preventivo_tipo_id' => 1, 'descripcion' => 'Servicio programado',
            'horometro' => 100, 'fecha_programada' => '2026-10-25'], $cambios);
    }

    private function crear(): Mantenimiento
    {
        $response = $this->postJson('/api/v1/mecanico/servicios', $this->datos())->assertCreated();

        return Mantenimiento::findOrFail($response->json('data.id'));
    }

    public function test_creates_machine_draft_without_worksite_and_assigns_current_employee(): void
    {
        $response = $this->postJson('/api/v1/mecanico/servicios', $this->datos())->assertCreated()
            ->assertJsonPath('data.tipo', 'programado')->assertJsonPath('data.estatus', 'borrador')
            ->assertJsonPath('data.mecanico_id', 11)->assertJsonPath('data.obra_id', null)
            ->assertJsonPath('data.abastecimiento_estado', 'pendiente')->assertJsonPath('data.orden_servicio_folio', null);
        $this->assertDatabaseHas('mantenimientos', ['id' => $response->json('data.id'), 'maquina_id' => 17, 'mecanico_id' => 11]);
        $this->assertDatabaseCount('mantenimiento_materiales', 0);
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 0);
    }

    public function test_creates_vehicle_draft_when_a_real_vehicle_type_is_configured(): void
    {
        $this->postJson('/api/v1/mecanico/servicios', $this->datos([
            'maquina_id' => null, 'vehiculo_id' => 7, 'horometro' => null,
            'servicio_preventivo_tipo_id' => 2, 'km_actuales' => 12000, 'obra_id' => 9,
        ]))->assertCreated()->assertJsonPath('data.vehiculo_id', 7)->assertJsonPath('data.obra_id', 9);
    }

    public function test_rejects_invalid_equipment_preventive_types_and_readings_without_persisting(): void
    {
        foreach ([
            ['maquina_id' => null], ['vehiculo_id' => 7], ['maquina_id' => 999], ['obra_id' => 999],
            ['servicio_preventivo_tipo_id' => null], ['servicio_preventivo_tipo_id' => 2],
            ['servicio_preventivo_tipo_id' => 3], ['horometro' => null], ['horometro' => 99], ['horometro' => -1],
            ['maquina_id' => [17]], ['servicio_preventivo_tipo_id' => [1]], ['km_actuales' => -1],
        ] as $cambios) {
            $this->postJson('/api/v1/mecanico/servicios', $this->datos($cambios))->assertUnprocessable();
        }
        $this->assertDatabaseCount('mantenimientos', 0);
    }

    public function test_requires_an_existing_employee_link(): void
    {
        foreach ([null, 999] as $empleadoId) {
            $this->usuarioApp->empleado_id = $empleadoId;
            $this->postJson('/api/v1/mecanico/servicios', $this->datos())->assertUnprocessable()->assertJsonValidationErrors('mecanico_id');
        }
        $this->assertDatabaseCount('mantenimientos', 0);
    }

    public function test_partial_edit_preserves_other_fields_and_optional_worksite_can_be_removed(): void
    {
        $servicio = $this->crear();
        $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, ['descripcion' => 'Descripción nueva', 'obra_id' => 9])
            ->assertOk()->assertJsonPath('data.descripcion', 'Descripción nueva')->assertJsonPath('data.maquina_id', 17)
            ->assertJsonPath('data.mecanico_id', 11)->assertJsonPath('data.estatus', 'borrador');
        $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, ['obra_id' => null])->assertOk()->assertJsonPath('data.obra_id', null);
        $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, ['horometro' => 90])->assertUnprocessable();
        $this->assertDatabaseHas('mantenimientos', ['id' => $servicio->id, 'horometro' => 100]);
    }

    public function test_cannot_edit_another_mechanic_service_or_an_emergency(): void
    {
        $servicio = $this->crear();
        $servicio->update(['mecanico_id' => 12]);
        $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, ['descripcion' => 'Cambio ajeno'])->assertForbidden();
        $servicio->update(['tipo' => 'emergencia', 'mecanico_id' => 11]);
        $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, ['descripcion' => 'Emergencia'])->assertNotFound();
        $this->patchJson('/api/v1/mecanico/servicios/999', [])->assertNotFound();
    }

    public function test_rejects_edits_after_order_generation_or_existing_material_documents(): void
    {
        $servicio = $this->crear();
        foreach (['orden_generada', 'pendiente', 'en_proceso', 'completado', 'cancelado'] as $estado) {
            $servicio->update(['estatus' => $estado]);
            $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, ['descripcion' => 'Cambio'])->assertStatus(409);
        }
        $servicio->update(['estatus' => 'borrador', 'orden_servicio_folio' => 'OSM-26-000001']);
        $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, [])->assertStatus(409);
        $servicio->update(['orden_servicio_folio' => null]);
        DB::table('mantenimiento_materiales')->insert(['mantenimiento_id' => $servicio->id]);
        $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, [])->assertStatus(409);
        DB::table('mantenimiento_materiales')->delete();
        DB::table('mantenimiento_abastecimientos')->insert(['mantenimiento_id' => $servicio->id]);
        $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, [])->assertStatus(409);
    }

    public function test_mobile_client_cannot_change_author_prices_or_workflow(): void
    {
        $servicio = $this->crear();
        foreach (['mecanico_id' => 12, 'tipo' => 'emergencia', 'estatus' => 'completado', 'costo_total' => 1,
            'costo_mano_obra' => 1, 'costo_refacciones' => 1, 'abastecimiento_tipo' => 'compra',
            'orden_servicio_folio' => 'INVENTADO', 'fecha_inicio' => '2026-10-25',
            'detalles' => [['concepto' => 'Inyección', 'cantidad' => 1, 'costo_unitario' => 999]]] as $campo => $valor) {
            $this->postJson('/api/v1/mecanico/servicios', $this->datos([$campo => $valor]))->assertUnprocessable();
            $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, [$campo => $valor])->assertUnprocessable();
        }
        $this->assertDatabaseCount('mantenimientos', 1);
        $this->assertDatabaseCount('mantenimiento_detalles', 0);
    }

    public function test_web_and_api_create_equivalent_programmed_drafts_and_web_can_edit_them(): void
    {
        $api = $this->crear();
        $this->actingAs($this->user, 'web');
        $this->post(route('mantenimiento.mantenimientos.store'), $this->datos(['tipo' => 'programado', 'mecanico_id' => 11]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $web = Mantenimiento::latest('id')->firstOrFail();
        $this->assertSame(Arr::except($api->getAttributes(), ['id', 'created_at', 'updated_at']), Arr::except($web->getAttributes(), ['id', 'created_at', 'updated_at']));
        $this->put(route('mantenimiento.mantenimientos.update', $web), $this->datos([
            'tipo' => 'programado', 'mecanico_id' => 11, 'estatus' => 'borrador', 'descripcion' => 'Editado en web',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mantenimientos', ['id' => $web->id, 'descripcion' => 'Editado en web', 'estatus' => 'borrador']);
    }

    public function test_web_keeps_emergency_creation_and_server_cost_calculation(): void
    {
        $this->actingAs($this->user, 'web');
        $this->post(route('mantenimiento.mantenimientos.store'), $this->datos([
            'tipo' => 'emergencia', 'servicio_preventivo_tipo_id' => null, 'mecanico_id' => 11,
            'detalles' => [['concepto' => 'Refacción', 'tipo' => 'refaccion', 'cantidad' => 2, 'costo_unitario' => 10],
                ['concepto' => 'Trabajo', 'tipo' => 'mano_obra', 'cantidad' => 1, 'costo_unitario' => 30]],
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mantenimientos', ['tipo' => 'emergencia', 'costo_total' => 50, 'costo_refacciones' => 20, 'costo_mano_obra' => 30]);
    }

    public function test_failed_detail_insert_rolls_back_new_header_and_costs(): void
    {
        DB::unprepared("CREATE TRIGGER fallo_detalle BEFORE INSERT ON mantenimiento_detalles BEGIN SELECT RAISE(ABORT, 'fallo_controlado'); END");
        try {
            app(MantenimientoBorradorService::class)->crear($this->datos([
                'tipo' => 'programado', 'mecanico_id' => 11,
                'detalles' => [['concepto' => 'Concepto', 'tipo' => 'refaccion', 'cantidad' => 1, 'costo_unitario' => 10]],
            ]));
            $this->fail('Debió fallar la persistencia del concepto.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('fallo_controlado', $exception->getMessage());
        }
        $this->assertDatabaseCount('mantenimientos', 0);
        $this->assertDatabaseCount('mantenimiento_detalles', 0);
    }

    public function test_service_rechecks_persisted_state_instead_of_trusting_the_route_model(): void
    {
        $stale = $this->crear();
        DB::table('mantenimientos')->where('id', $stale->id)->update(['estatus' => 'orden_generada']);
        $this->expectException(ConflictHttpException::class);
        app(MantenimientoBorradorService::class)->actualizar($stale, ['descripcion' => 'Modelo desactualizado'], 11);
    }

    public function test_failed_emergency_draft_edit_rolls_back_header_and_replaced_details(): void
    {
        $service = app(MantenimientoBorradorService::class);
        $draft = $service->crear($this->datos([
            'tipo' => 'emergencia', 'servicio_preventivo_tipo_id' => null, 'mecanico_id' => 11,
            'detalles' => [['concepto' => 'Original', 'cantidad' => 1, 'costo_unitario' => 10]],
        ]));
        DB::unprepared("CREATE TRIGGER fallo_reemplazo BEFORE INSERT ON mantenimiento_detalles BEGIN SELECT RAISE(ABORT, 'fallo_controlado'); END");
        try {
            $service->actualizar($draft, ['descripcion' => 'No debe persistir',
                'detalles' => [['concepto' => 'Nuevo', 'cantidad' => 2, 'costo_unitario' => 20]]]);
            $this->fail('Debió fallar el reemplazo de conceptos.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('fallo_controlado', $exception->getMessage());
        }
        $this->assertDatabaseHas('mantenimientos', ['id' => $draft->id, 'descripcion' => 'Servicio programado', 'costo_total' => 10]);
        $this->assertDatabaseHas('mantenimiento_detalles', ['mantenimiento_id' => $draft->id, 'concepto' => 'Original', 'costo_total' => 10]);
        $this->assertDatabaseCount('mantenimiento_detalles', 1);
    }

    public function test_mutations_require_mechanic_access(): void
    {
        $servicio = $this->crear();
        $this->permisos = [];
        $this->postJson('/api/v1/mecanico/servicios', $this->datos())->assertForbidden();
        $this->patchJson('/api/v1/mecanico/servicios/'.$servicio->id, [])->assertForbidden();
        auth('sanctum')->forgetUser();
        $this->postJson('/api/v1/mecanico/servicios', $this->datos())->assertUnauthorized();
    }

    private function prepararConsulta(): void
    {
        Schema::table('obras', fn (Blueprint $t) => $t->softDeletes());
        foreach (['maquinas' => ['codigo'], 'vehiculos' => ['modelo', 'placas'],
            'empleados' => ['Nombre', 'Apellidos'], 'empresa_servicio_preventivo_tipos' => ['nombre']] as $tabla => $campos) {
            Schema::table($tabla, function (Blueprint $t) use ($campos) {
                foreach ($campos as $campo) {
                    $t->string($campo)->nullable();
                }
            });
        }
        foreach (['mantenimiento_materiales', 'mantenimiento_abastecimiento_detalles'] as $tabla) {
            $crear = function (Blueprint $t) use ($tabla) {
                if ($tabla === 'mantenimiento_abastecimiento_detalles') {
                    $t->id();
                    $t->unsignedBigInteger('abastecimiento_id');
                    $t->decimal('cantidad_solicitada')->default(0);
                    $t->decimal('cantidad_atendida')->default(0);
                    $t->string('estado')->nullable();
                } else {
                    $t->integer('orden')->default(0);
                    $t->boolean('incluido')->default(true);
                    $t->string('motivo_exclusion')->nullable();
                }
                $t->unsignedBigInteger('producto_id')->nullable();
                $t->unsignedBigInteger('material_preventivo_id')->nullable();
                $t->string('producto_nombre_snapshot')->nullable();
                $t->string('producto_sku_snapshot')->nullable();
                $t->string('unidad')->nullable();
                $t->decimal('cantidad_requerida')->default(0);
            };
            $tabla === 'mantenimiento_materiales' ? Schema::table($tabla, $crear) : Schema::create($tabla, $crear);
        }
        Schema::table('mantenimiento_abastecimientos', function (Blueprint $t) {
            $t->string('folio')->nullable();
            $t->string('tipo')->nullable();
            $t->string('estado')->nullable();
            $t->timestamps();
        });
        Schema::table('mantenimiento_fotos', function (Blueprint $t) {
            $t->string('ruta')->nullable();
            $t->string('descripcion')->nullable();
        });
        Schema::create('inventario_documentos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('mantenimiento_abastecimiento_id');
            $t->unsignedBigInteger('orden_compra_id')->nullable();
            $t->string('folio');
            $t->string('tipo');
            $t->string('estado');
            $t->dateTime('fecha')->nullable();
            $t->softDeletes();
        });
        Schema::create('ordenes_compra', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('mantenimiento_abastecimiento_id');
            $t->string('folio');
            $t->string('estado');
            $t->string('estado_recepcion')->nullable();
            $t->date('fecha')->nullable();
        });
        Schema::create('almacenes', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('codigo');
            $t->boolean('activo');
        });
        Schema::create('inventario_stock', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('almacen_id');
            $t->unsignedBigInteger('producto_id');
            $t->decimal('stock_actual');
            $t->decimal('stock_reservado');
        });
        Schema::create('empresa_servicio_preventivo_materiales', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('servicio_preventivo_tipo_id');
            $t->unsignedBigInteger('maquina_id')->nullable();
            $t->boolean('activo');
            $t->integer('orden');
        });
    }

    public function test_reads_all_programmed_services_but_excludes_emergencies_and_limits_actions_to_owner(): void
    {
        $this->prepararConsulta();
        $own = $this->crear();
        $other = app(MantenimientoBorradorService::class)->crear($this->datos(['tipo' => 'programado', 'mecanico_id' => 12]));
        $emergency = app(MantenimientoBorradorService::class)->crear($this->datos(['tipo' => 'emergencia', 'servicio_preventivo_tipo_id' => null]));
        $this->getJson('/api/v1/mecanico/servicios')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.id', $other->id)
            ->assertJsonPath('data.0.acciones_permitidas.editar_borrador', false)
            ->assertJsonPath('data.1.acciones_permitidas.editar_borrador', true);
        $this->getJson('/api/v1/mecanico/servicios/'.$other->id)->assertOk()->assertJsonPath('data.es_propio', false);
        $this->getJson('/api/v1/mecanico/servicios/'.$emergency->id)->assertNotFound();
        $this->getJson('/api/v1/mecanico/servicios/999')->assertNotFound();
        $this->patchJson('/api/v1/mecanico/servicios/'.$other->id, ['descripcion' => 'No autorizado'])->assertForbidden();
        $this->getJson('/api/v1/mecanico/servicios/'.$own->id)->assertOk()->assertJsonPath('data.evidencias', [])
            ->assertJsonPath('data.acciones_permitidas.generar_orden', true);
    }

    public function test_filters_and_paginates_services_without_expanding_scope_through_search(): void
    {
        $this->prepararConsulta();
        $this->crear();
        $service = app(MantenimientoBorradorService::class)->crear($this->datos(['tipo' => 'programado', 'mecanico_id' => 12, 'obra_id' => 9]));
        $service->update(['orden_servicio_folio' => 'OSM-PRUEBA', 'estatus' => 'pendiente']);
        app(MantenimientoBorradorService::class)->crear($this->datos(['tipo' => 'emergencia', 'servicio_preventivo_tipo_id' => null]));
        foreach (['solo_mios=1', 'estatus=borrador', 'obra_id=9', 'q=OSM-PRUEBA', 'maquina_id=17&estatus=pendiente'] as $query) {
            $this->getJson('/api/v1/mecanico/servicios?'.$query)->assertOk()->assertJsonPath('meta.total', 1);
        }
        $this->getJson('/api/v1/mecanico/servicios?q=Equipo')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/mecanico/servicios?per_page=1&page=2')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/v1/mecanico/servicios?fecha_hasta=2026-10-24')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/mecanico/servicios?fecha_desde=2026-10-25&fecha_hasta=2026-10-25')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/mecanico/servicios?vehiculo_id=7')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/mecanico/servicios?page=99')->assertOk()->assertJsonPath('data', []);
    }

    public function test_rejects_invalid_read_filters(): void
    {
        foreach (['q[]=x', 'q='.str_repeat('x', 121), 'per_page=51', 'per_page=0', 'page=-1', 'estatus=otro',
            'maquina_id=17&vehiculo_id=7', 'solo_mios=si', 'obra_id=-1', 'fecha_desde=ayer',
            'fecha_desde=2026-10-25&fecha_hasta=2026-10-24'] as $query) {
            $this->getJson('/api/v1/mecanico/servicios?'.$query)->assertUnprocessable();
        }
    }

    public function test_read_only_actor_without_employee_does_not_own_unassigned_services(): void
    {
        $this->prepararConsulta();
        $service = $this->crear();
        $service->update(['mecanico_id' => null]);
        foreach ([null, 999] as $employee) {
            $this->usuarioApp->empleado_id = $employee;
            $this->getJson('/api/v1/mecanico/servicios')->assertOk()->assertJsonPath('data.0.es_propio', false)
                ->assertJsonPath('data.0.acciones_permitidas.editar_borrador', false);
            $this->getJson('/api/v1/mecanico/servicios?solo_mios=1')->assertOk()->assertJsonPath('data', []);
        }
    }

    public function test_read_actions_reject_every_non_draft_stage_and_frozen_drafts(): void
    {
        $this->prepararConsulta();
        $service = $this->crear();
        foreach (['orden_generada', 'pendiente', 'en_proceso', 'completado', 'cancelado'] as $status) {
            $service->update(['estatus' => $status]);
            $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()->assertJsonPath('data.acciones_permitidas.editar_borrador', false);
        }
        $service->update(['estatus' => 'borrador', 'orden_servicio_folio' => 'OSM-1']);
        $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()->assertJsonPath('data.acciones_permitidas.editar_borrador', false);
        $service->update(['orden_servicio_folio' => null]);
        DB::table('mantenimiento_materiales')->insert(['mantenimiento_id' => $service->id]);
        $this->getJson('/api/v1/mecanico/servicios')->assertOk()->assertJsonPath('data.0.acciones_permitidas.editar_borrador', false);
        DB::table('mantenimiento_materiales')->delete();
        DB::table('mantenimiento_abastecimientos')->insert(['mantenimiento_id' => $service->id]);
        $this->getJson('/api/v1/mecanico/servicios')->assertOk()->assertJsonPath('data.0.acciones_permitidas.editar_borrador', false);
    }

    public function test_detail_contains_only_linked_materials_documents_and_evidence_with_readonly_costs(): void
    {
        $this->prepararConsulta();
        $service = $this->crear();
        $other = $this->crear();
        $service->update(['estatus' => 'pendiente', 'orden_servicio_folio' => 'OSM-1', 'costo_total' => 123.45]);
        foreach ([$service->id, $other->id] as $id) {
            DB::table('mantenimiento_materiales')->insert(['mantenimiento_id' => $id, 'producto_id' => 5,
                'producto_nombre_snapshot' => 'Aceite', 'cantidad_requerida' => 10]);
            DB::table('mantenimiento_abastecimientos')->insert(['id' => $id, 'mantenimiento_id' => $id, 'folio' => 'SMA-'.$id, 'tipo' => 'almacen', 'estado' => 'parcial']);
            DB::table('mantenimiento_abastecimiento_detalles')->insert(['abastecimiento_id' => $id, 'producto_id' => 5,
                'cantidad_requerida' => 10, 'cantidad_solicitada' => 10, 'cantidad_atendida' => 4]);
            DB::table('inventario_documentos')->insert(['mantenimiento_abastecimiento_id' => $id, 'folio' => 'SAL-'.$id, 'tipo' => 'salida', 'estado' => 'aplicado']);
            DB::table('ordenes_compra')->insert(['mantenimiento_abastecimiento_id' => $id, 'folio' => 'OC-'.$id, 'estado' => 'AUTORIZADA']);
            DB::table('mantenimiento_fotos')->insert(['mantenimiento_id' => $id, 'ruta' => 'mantenimientos/'.$id.'/foto.webp', 'descripcion' => 'Evidencia']);
        }
        DB::table('almacenes')->insert(['id' => 1, 'codigo' => 'GL', 'nombre' => 'Giralda', 'activo' => true]);
        DB::table('inventario_stock')->insert(['almacen_id' => 1, 'producto_id' => 5, 'stock_actual' => 8, 'stock_reservado' => 3]);
        $response = $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()
            ->assertJsonPath('data.costo_total', 123.45)->assertJsonPath('data.orden.folio', 'OSM-1')
            ->assertJsonCount(1, 'data.materiales')->assertJsonCount(1, 'data.solicitudes')->assertJsonCount(1, 'data.evidencias')
            ->assertJsonPath('data.avance_materiales.0.requerido', 10)->assertJsonPath('data.avance_materiales.0.surtido', 4)
            ->assertJsonPath('data.avance_materiales.0.faltante', 6)->assertJsonPath('data.avance_materiales.0.disponible', 5)
            ->assertJsonPath('data.solicitudes.0.documentos_inventario.0.folio', 'SAL-'.$service->id)
            ->assertJsonPath('data.solicitudes.0.ordenes_compra.0.folio', 'OC-'.$service->id);
        $this->assertStringEndsWith('/storage/mantenimientos/'.$service->id.'/foto.webp', $response->json('data.evidencias.0.url'));
        $this->assertArrayNotHasKey('ruta', $response->json('data.evidencias.0'));
        $this->assertArrayNotHasKey('orden_servicio_generada_por', $response->json('data'));
        $this->assertDatabaseHas('inventario_stock', ['stock_actual' => 8, 'stock_reservado' => 3]);
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 2);
    }

    public function test_read_endpoints_require_mechanic_permission_and_authentication(): void
    {
        $service = $this->crear();
        $this->permisos = [];
        foreach (['', '/'.$service->id] as $suffix) {
            $this->getJson('/api/v1/mecanico/servicios'.$suffix)->assertForbidden();
        }
        auth('sanctum')->forgetUser();
        $this->getJson('/api/v1/mecanico/servicios')->assertUnauthorized();
        $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertUnauthorized();
    }

    public function test_vehicle_services_use_same_read_contract_and_keep_employee_personal_data_private(): void
    {
        $this->prepararConsulta();
        DB::table('empleados')->where('id_Empleado', 11)->update(['Nombre' => 'Juan', 'Apellidos' => 'Pérez']);
        $created = $this->postJson('/api/v1/mecanico/servicios', $this->datos([
            'maquina_id' => null, 'vehiculo_id' => 7, 'horometro' => null,
            'servicio_preventivo_tipo_id' => 2, 'km_actuales' => 12000,
        ]))->assertCreated();
        $response = $this->getJson('/api/v1/mecanico/servicios/'.$created->json('data.id'))->assertOk()
            ->assertJsonPath('data.maquina', null)->assertJsonPath('data.vehiculo.id', 7)
            ->assertJsonPath('data.mecanico.nombre', 'Juan Pérez')->assertJsonPath('data.km_actuales', 12000)
            ->assertJsonPath('data.avance_materiales', []);
        $this->assertSame(['id', 'nombre'], array_keys($response->json('data.mecanico')));
        $this->getJson('/api/v1/mecanico/servicios?vehiculo_id=7&q=Vehículo')->assertOk()->assertJsonPath('meta.total', 1);
    }

    private function prepararOrden(): void
    {
        $this->prepararConsulta();
        Schema::table('mantenimiento_materiales', fn (Blueprint $t) => $t->timestamps());
        Schema::table('empresa_servicio_preventivo_materiales', function (Blueprint $t) {
            $t->unsignedBigInteger('producto_id');
            $t->decimal('cantidad_requerida');
            $t->string('unidad')->nullable();
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('sku');
            $t->string('unidad');
        });
        DB::table('productos')->insert(['id' => 5, 'nombre' => 'Aceite original', 'sku' => 'ACE-5', 'unidad' => 'L']);
        DB::table('empresa_servicio_preventivo_materiales')->insert([
            'id' => 1, 'servicio_preventivo_tipo_id' => 1, 'maquina_id' => null, 'activo' => true,
            'orden' => 0, 'producto_id' => 5, 'cantidad_requerida' => 10, 'unidad' => null,
        ]);
    }

    public function test_generates_order_atomically_and_replays_without_replacing_snapshot_or_metadata(): void
    {
        $this->prepararOrden();
        $service = $this->crear();
        $endpoint = '/api/v1/mecanico/servicios/'.$service->id.'/generar-orden';
        $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()->assertJsonPath('data.acciones_permitidas.generar_orden', true);
        $first = $this->postJson($endpoint)->assertOk()->assertJsonPath('data.estatus', 'orden_generada')
            ->assertJsonPath('data.orden.folio', sprintf('OSM-%s-%06d', now()->format('y'), $service->id))
            ->assertJsonCount(1, 'data.materiales')->assertJsonPath('data.materiales.0.producto', 'Aceite original')
            ->assertJsonPath('data.materiales.0.cantidad_requerida', 10)->assertJsonPath('data.materiales.0.unidad', 'L')
            ->assertJsonPath('data.acciones_permitidas.generar_orden', false)->assertJsonPath('data.acciones_permitidas.editar_borrador', false);
        $metadata = $service->fresh()->only(['orden_servicio_folio', 'orden_servicio_generada_at', 'orden_servicio_generada_por', 'updated_at']);
        DB::table('productos')->where('id', 5)->update(['nombre' => 'Nombre nuevo']);
        DB::table('empresa_servicio_preventivo_materiales')->where('id', 1)->update(['cantidad_requerida' => 99]);
        $this->travel(2)->days();
        $second = $this->postJson($endpoint)->assertOk()->assertJsonPath('data.materiales.0.producto', 'Aceite original')
            ->assertJsonPath('data.materiales.0.cantidad_requerida', 10);
        $this->assertSame($first->json('data.materiales.0.id'), $second->json('data.materiales.0.id'));
        $this->assertEquals($metadata, $service->fresh()->only(array_keys($metadata)));
        $this->assertSame(80, (int) $service->fresh()->orden_servicio_generada_por);
        $this->assertDatabaseCount('mantenimiento_materiales', 1);
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 0);
        $this->patchJson('/api/v1/mecanico/servicios/'.$service->id, ['descripcion' => 'Tarde'])->assertConflict();
    }

    public function test_order_generation_rejects_ownership_scope_payload_permissions_and_missing_employee(): void
    {
        $this->prepararOrden();
        $own = $this->crear();
        $other = app(MantenimientoBorradorService::class)->crear($this->datos(['tipo' => 'programado', 'mecanico_id' => 12]));
        $emergency = app(MantenimientoBorradorService::class)->crear($this->datos(['tipo' => 'emergencia', 'servicio_preventivo_tipo_id' => null, 'mecanico_id' => 11]));
        $this->postJson('/api/v1/mecanico/servicios/'.$other->id.'/generar-orden')->assertForbidden();
        $this->postJson('/api/v1/mecanico/servicios/'.$emergency->id.'/generar-orden')->assertNotFound();
        $this->postJson('/api/v1/mecanico/servicios/999/generar-orden')->assertNotFound();
        $endpoint = '/api/v1/mecanico/servicios/'.$own->id.'/generar-orden';
        foreach (['orden_servicio_folio' => 'OSM-FALSO', 'mecanico_id' => 12, 'materiales' => [], 'estatus' => 'completado'] as $campo => $valor) {
            $this->postJson($endpoint, [$campo => $valor])->assertUnprocessable();
        }
        $this->usuarioApp->empleado_id = null;
        $this->postJson($endpoint)->assertUnprocessable()->assertJsonValidationErrors('mecanico_id');
        $this->permisos = [];
        $this->postJson($endpoint)->assertForbidden();
        auth('sanctum')->forgetUser();
        $this->postJson($endpoint)->assertUnauthorized();
        $this->assertDatabaseCount('mantenimiento_materiales', 0);
        $this->assertNull($own->fresh()->orden_servicio_folio);
    }

    public function test_order_rechecks_saved_state_and_rejects_inconsistent_drafts(): void
    {
        $this->prepararOrden();
        $service = $this->crear();
        $endpoint = '/api/v1/mecanico/servicios/'.$service->id.'/generar-orden';
        foreach (['orden_generada', 'pendiente', 'en_proceso', 'completado', 'cancelado'] as $status) {
            $service->update(['estatus' => $status]);
            $this->postJson($endpoint)->assertConflict();
        }
        $service->update(['estatus' => 'borrador', 'orden_servicio_folio' => 'OSM-INCONSISTENTE']);
        $this->postJson($endpoint)->assertConflict();
        $service->update(['orden_servicio_folio' => null]);
        DB::table('mantenimiento_materiales')->insert(['mantenimiento_id' => $service->id]);
        $this->postJson($endpoint)->assertConflict();
        DB::table('mantenimiento_materiales')->delete();
        DB::table('mantenimiento_abastecimientos')->insert(['mantenimiento_id' => $service->id]);
        $this->postJson($endpoint)->assertConflict();
        DB::table('mantenimiento_abastecimientos')->delete();
        DB::table('mantenimientos')->where('id', $service->id)->update(['estatus' => 'completado']);
        $this->expectException(ConflictHttpException::class);
        app(MantenimientoOrdenService::class)->generar($service, 80, 11);
    }

    public function test_existing_order_retry_preserves_later_stage_and_original_author(): void
    {
        $this->prepararOrden();
        $service = $this->crear();
        app(MantenimientoOrdenService::class)->generar($service, 90);
        $service->refresh()->update(['estatus' => 'en_proceso']);
        DB::table('empresa_servicio_preventivo_tipos')->where('id', 1)->update(['activo' => false]);
        $this->postJson('/api/v1/mecanico/servicios/'.$service->id.'/generar-orden')->assertOk()->assertJsonPath('data.estatus', 'en_proceso');
        $this->assertSame(90, (int) $service->fresh()->orden_servicio_generada_por);
        $this->assertDatabaseCount('mantenimiento_materiales', 1);
    }

    public function test_rejects_invalid_preventive_type_at_generation_and_updates_available_action(): void
    {
        $this->prepararOrden();
        $service = $this->crear();
        foreach ([null, 2, 3, 999] as $tipoId) {
            $service->update(['servicio_preventivo_tipo_id' => $tipoId]);
            $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()->assertJsonPath('data.acciones_permitidas.generar_orden', false);
            $this->postJson('/api/v1/mecanico/servicios/'.$service->id.'/generar-orden')->assertUnprocessable()->assertJsonValidationErrors('servicio_preventivo_tipo_id');
        }
        $this->assertNull($service->fresh()->orden_servicio_folio);
        $this->assertDatabaseCount('mantenimiento_materiales', 0);
    }

    public function test_failed_snapshot_insert_rolls_back_order_metadata_and_previous_material_rows(): void
    {
        $this->prepararOrden();
        $service = $this->crear();
        DB::table('empresa_servicio_preventivo_materiales')->insert([
            'id' => 2, 'servicio_preventivo_tipo_id' => 1, 'maquina_id' => null, 'activo' => true,
            'orden' => 1, 'producto_id' => 5, 'cantidad_requerida' => 2,
        ]);
        DB::unprepared("CREATE TRIGGER fallo_snapshot BEFORE INSERT ON mantenimiento_materiales WHEN NEW.material_preventivo_id = 2 BEGIN SELECT RAISE(ABORT, 'fallo_controlado'); END");
        try {
            app(MantenimientoOrdenService::class)->generar($service, 80, 11);
            $this->fail('Debió revertir la orden y sus materiales.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('fallo_controlado', $exception->getMessage());
        }
        $this->assertDatabaseHas('mantenimientos', ['id' => $service->id, 'estatus' => 'borrador',
            'orden_servicio_folio' => null, 'orden_servicio_generada_at' => null, 'orden_servicio_generada_por' => null]);
        $this->assertDatabaseCount('mantenimiento_materiales', 0);
    }

    public function test_web_uses_same_order_snapshot_and_retries_cannot_duplicate_it(): void
    {
        $this->prepararOrden();
        $service = $this->crear();
        $url = route('mantenimiento.mantenimientos.generar-orden-servicio', $service);
        $this->post($url)->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('mantenimiento_materiales', ['mantenimiento_id' => $service->id,
            'producto_nombre_snapshot' => 'Aceite original', 'cantidad_requerida' => 10]);
        $this->postJson('/api/v1/mecanico/servicios/'.$service->id.'/generar-orden')->assertOk()->assertJsonCount(1, 'data.materiales');
        $this->post($url)->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseCount('mantenimiento_materiales', 1);
        $this->permisos = ['app.mecanico.access'];
        $this->post($url)->assertForbidden();
    }

    public function test_vehicle_without_material_template_can_generate_order_without_claiming_supplies(): void
    {
        $this->prepararOrden();
        $created = $this->postJson('/api/v1/mecanico/servicios', $this->datos([
            'maquina_id' => null, 'vehiculo_id' => 7, 'horometro' => null, 'servicio_preventivo_tipo_id' => 2,
        ]))->assertCreated();
        $id = $created->json('data.id');
        $this->postJson('/api/v1/mecanico/servicios/'.$id.'/generar-orden')->assertOk()
            ->assertJsonPath('data.estatus', 'orden_generada')->assertJsonPath('data.materiales', [])
            ->assertJsonPath('data.avance_materiales', []);
        $this->postJson('/api/v1/mecanico/servicios/'.$id.'/generar-orden')->assertOk();
        $this->assertDatabaseCount('mantenimiento_materiales', 0);
    }

    private function ordenConMateriales(): Mantenimiento
    {
        $service = $this->crear();

        return app(MantenimientoOrdenService::class)->generar($service, 80, 11);
    }

    public function test_partially_adjusts_snapshot_and_preserves_product_quantity_order_and_costs(): void
    {
        $this->prepararOrden();
        $service = $this->ordenConMateriales();
        $material = $service->materialesOrden()->firstOrFail();
        $second = $service->materialesOrden()->create(['producto_id' => 5, 'producto_nombre_snapshot' => 'Segundo',
            'cantidad_requerida' => 2, 'orden' => 1, 'incluido' => true]);
        $url = '/api/v1/mecanico/servicios/'.$service->id.'/materiales';
        $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()->assertJsonPath('data.acciones_permitidas.ajustar_materiales', true);
        $payload = ['materiales' => [['id' => $material->id, 'incluido' => false, 'motivo_exclusion' => 'No requiere cambio']]];
        $this->patchJson($url, $payload)->assertOk()->assertJsonPath('data.materiales.0.incluido', false)
            ->assertJsonPath('data.materiales.0.motivo_exclusion', 'No requiere cambio')->assertJsonPath('data.materiales.1.incluido', true);
        $this->patchJson($url, $payload)->assertOk();
        $this->assertDatabaseHas('mantenimiento_materiales', ['id' => $material->id, 'producto_id' => 5,
            'producto_nombre_snapshot' => 'Aceite original', 'cantidad_requerida' => 10, 'orden' => 0]);
        $this->assertTrue($second->fresh()->incluido);
        $this->assertSame(0.0, (float) $service->fresh()->costo_total);
        $this->patchJson($url, ['materiales' => [['id' => $material->id, 'incluido' => false]]])->assertOk()
            ->assertJsonPath('data.materiales.0.motivo_exclusion', 'No requiere cambio');
        $this->patchJson($url, ['materiales' => [['id' => $material->id, 'incluido' => false, 'motivo_exclusion' => null]]])->assertOk()
            ->assertJsonPath('data.materiales.0.motivo_exclusion', null);
        $this->patchJson($url, $payload)->assertOk();
        $this->patchJson($url, ['materiales' => [['id' => $material->id, 'incluido' => true, 'motivo_exclusion' => 'Se ignora al incluir']]])
            ->assertOk()->assertJsonPath('data.materiales.0.incluido', true)->assertJsonPath('data.materiales.0.motivo_exclusion', null);
    }

    public function test_material_adjustment_rejects_foreign_or_missing_ids_before_writing_any_row(): void
    {
        $this->prepararOrden();
        $service = $this->ordenConMateriales();
        $other = $this->ordenConMateriales();
        $ownId = $service->materialesOrden()->firstOrFail()->id;
        $foreignId = $other->materialesOrden()->firstOrFail()->id;
        foreach ([$foreignId, 999] as $id) {
            $this->patchJson('/api/v1/mecanico/servicios/'.$service->id.'/materiales', ['materiales' => [
                ['id' => $ownId, 'incluido' => false], ['id' => $id, 'incluido' => false],
            ]])->assertUnprocessable()->assertJsonValidationErrors('materiales.1.id');
        }
        $this->assertDatabaseHas('mantenimiento_materiales', ['id' => $ownId, 'incluido' => true]);
        $this->assertDatabaseHas('mantenimiento_materiales', ['id' => $foreignId, 'incluido' => true]);
    }

    public function test_rejects_invalid_material_payloads_and_any_changes_to_frozen_fields(): void
    {
        $this->prepararOrden();
        $service = $this->ordenConMateriales();
        $id = $service->materialesOrden()->firstOrFail()->id;
        foreach ([[], ['materiales' => []], ['materiales' => 'x'],
            ['materiales' => [['id' => $id]]], ['materiales' => [['id' => $id, 'incluido' => 'si']]],
            ['materiales' => [['id' => $id, 'incluido' => false, 'motivo_exclusion' => str_repeat('x', 256)]]],
            ['materiales' => [['id' => $id, 'incluido' => true], ['id' => $id, 'incluido' => false]]],
            ['materiales' => [['id' => $id, 'incluido' => true, 'cantidad_requerida' => 99]]],
            ['materiales' => [['id' => $id, 'incluido' => true, 'producto_id' => 99]]],
            ['materiales' => [['id' => $id, 'incluido' => true]], 'costo_total' => 100],
        ] as $payload) {
            $this->patchJson('/api/v1/mecanico/servicios/'.$service->id.'/materiales', $payload)->assertUnprocessable();
        }
        $this->assertDatabaseHas('mantenimiento_materiales', ['id' => $id, 'incluido' => true, 'cantidad_requerida' => 10]);
    }

    public function test_material_adjustment_blocks_every_wrong_stage_and_any_existing_supply_request(): void
    {
        $this->prepararOrden();
        $service = $this->ordenConMateriales();
        $payload = ['materiales' => [['id' => $service->materialesOrden()->firstOrFail()->id, 'incluido' => false]]];
        $url = '/api/v1/mecanico/servicios/'.$service->id.'/materiales';
        foreach (['borrador', 'pendiente', 'en_proceso', 'completado', 'cancelado'] as $status) {
            $service->update(['estatus' => $status]);
            $this->patchJson($url, $payload)->assertConflict();
        }
        $service->update(['estatus' => 'orden_generada', 'orden_servicio_folio' => null]);
        $this->patchJson($url, $payload)->assertConflict();
        $service->update(['orden_servicio_folio' => 'OSM-1']);
        foreach (['pendiente', 'surtida', 'cancelada'] as $status) {
            DB::table('mantenimiento_abastecimientos')->insert(['mantenimiento_id' => $service->id, 'estado' => $status]);
            $this->patchJson($url, $payload)->assertConflict();
            $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()->assertJsonPath('data.acciones_permitidas.ajustar_materiales', false);
            DB::table('mantenimiento_abastecimientos')->delete();
        }
        DB::table('mantenimiento_abastecimientos')->insert(['mantenimiento_id' => $service->id]);
        $this->expectException(ConflictHttpException::class);
        app(MantenimientoMaterialesService::class)->actualizar($service, $payload, 11);
    }

    public function test_material_adjustment_checks_actor_and_scope_even_when_ids_are_valid(): void
    {
        $this->prepararOrden();
        $service = $this->ordenConMateriales();
        $url = '/api/v1/mecanico/servicios/'.$service->id.'/materiales';
        $payload = ['materiales' => [['id' => $service->materialesOrden()->firstOrFail()->id, 'incluido' => false]]];
        $service->update(['mecanico_id' => 12]);
        $this->patchJson($url, $payload)->assertForbidden();
        $service->update(['mecanico_id' => 11, 'tipo' => 'emergencia']);
        $this->patchJson($url, $payload)->assertNotFound();
        $this->patchJson('/api/v1/mecanico/servicios/999/materiales', $payload)->assertNotFound();
        $this->usuarioApp->empleado_id = null;
        $this->patchJson($url, $payload)->assertUnprocessable();
        $this->permisos = [];
        $this->patchJson($url, $payload)->assertForbidden();
        auth('sanctum')->forgetUser();
        $this->patchJson($url, $payload)->assertUnauthorized();
    }

    public function test_web_material_form_preserves_full_selection_and_rejects_foreign_references(): void
    {
        $this->prepararOrden();
        $service = $this->ordenConMateriales();
        $other = $this->ordenConMateriales();
        $id = $service->materialesOrden()->firstOrFail()->id;
        $foreignId = $other->materialesOrden()->firstOrFail()->id;
        $url = route('mantenimiento.mantenimientos.materiales-orden.update', $service);
        $this->post($url, ['incluidos' => [], 'motivos' => [$id => 'No aplica']])->assertRedirect()->assertSessionHas('success');
        $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()->assertJsonPath('data.materiales.0.incluido', false)
            ->assertJsonPath('data.materiales.0.motivo_exclusion', 'No aplica');
        $this->post($url, ['incluidos' => [$foreignId]])->assertRedirect()->assertSessionHasErrors('materiales');
        $this->post($url, ['motivos' => [$foreignId => 'Ajeno']])->assertRedirect()->assertSessionHasErrors('materiales');
        $this->post($url, ['incluidos' => [$id]])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('mantenimiento_materiales', ['id' => $id, 'incluido' => true, 'motivo_exclusion' => null]);
        $this->permisos = ['app.mecanico.access'];
        $this->post($url, ['incluidos' => []])->assertForbidden();
    }

    public function test_failed_material_update_rolls_back_all_prior_row_changes(): void
    {
        $this->prepararOrden();
        $service = $this->ordenConMateriales();
        $first = $service->materialesOrden()->firstOrFail();
        $second = $service->materialesOrden()->create(['producto_id' => 5, 'producto_nombre_snapshot' => 'Segundo',
            'cantidad_requerida' => 2, 'orden' => 1, 'incluido' => true]);
        DB::unprepared("CREATE TRIGGER fallo_ajuste BEFORE UPDATE ON mantenimiento_materiales WHEN NEW.id = $second->id BEGIN SELECT RAISE(ABORT, 'fallo_controlado'); END");
        try {
            app(MantenimientoMaterialesService::class)->actualizar($service, ['materiales' => [
                ['id' => $first->id, 'incluido' => false], ['id' => $second->id, 'incluido' => false],
            ]], 11);
            $this->fail('Debió revertir todos los cambios de inclusión.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('fallo_controlado', $exception->getMessage());
        }
        $this->assertTrue($first->fresh()->incluido);
        $this->assertTrue($second->fresh()->incluido);
    }

    private function prepararAbastecimiento(): void
    {
        $this->prepararOrden();
        Schema::table('mantenimiento_abastecimientos', function (Blueprint $t) {
            $t->unsignedBigInteger('created_by')->nullable();
            $t->string('observaciones')->nullable();
        });
        Schema::table('mantenimiento_abastecimiento_detalles', fn (Blueprint $t) => $t->timestamps());
        DB::table('almacenes')->insert(['id' => 1, 'nombre' => 'Giralda', 'codigo' => 'GL', 'activo' => true]);
        DB::table('inventario_stock')->insert(['almacen_id' => 1, 'producto_id' => 5, 'stock_actual' => 8, 'stock_reservado' => 3]);
    }

    public function test_creates_supply_request_and_replays_same_type_without_inventory_changes(): void
    {
        $this->prepararAbastecimiento();
        $service = $this->ordenConMateriales();
        $url = '/api/v1/mecanico/servicios/'.$service->id.'/abastecimientos';
        $this->getJson('/api/v1/mecanico/servicios')->assertOk()->assertJsonPath('data.0.acciones_permitidas.solicitar_almacen', true);
        $first = $this->postJson($url, ['tipo' => 'almacen'])->assertOk()->assertJsonPath('data.estatus', 'pendiente')
            ->assertJsonPath('data.abastecimiento_estado', 'solicitud_almacen')->assertJsonCount(1, 'data.solicitudes')
            ->assertJsonPath('data.solicitudes.0.materiales.0.solicitado', 10)
            ->assertJsonPath('data.avance_materiales.0.disponible', 5)->assertJsonPath('data.avance_materiales.0.surtido', 0)
            ->assertJsonPath('data.avance_materiales.0.faltante', 10)
            ->assertJsonPath('data.acciones_permitidas.solicitar_almacen', false)
            ->assertJsonPath('data.acciones_permitidas.solicitar_compra', true)
            ->assertJsonPath('data.acciones_permitidas.ajustar_materiales', false);
        $id = $first->json('solicitud_id');
        $this->assertSame(sprintf('SMA-%s-%06d', now()->format('y'), $id), $first->json('data.solicitudes.0.folio'));
        $this->postJson($url, ['tipo' => 'almacen'])->assertOk()->assertJsonPath('solicitud_id', $id)->assertJsonCount(1, 'data.solicitudes');
        $this->assertDatabaseHas('mantenimiento_abastecimientos', ['id' => $id, 'created_by' => 80]);
        $this->assertDatabaseHas('inventario_stock', ['stock_actual' => 8, 'stock_reservado' => 3]);
        $this->assertDatabaseCount('inventario_documentos', 0);
        $this->assertDatabaseCount('ordenes_compra', 0);
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 1);
        $materialId = $service->materialesOrden()->firstOrFail()->id;
        $this->patchJson('/api/v1/mecanico/servicios/'.$service->id.'/materiales',
            ['materiales' => [['id' => $materialId, 'incluido' => false]]])->assertConflict();
    }

    public function test_creates_purchase_request_using_current_included_snapshot_and_keeps_execution_stage(): void
    {
        $this->prepararAbastecimiento();
        $service = $this->ordenConMateriales();
        $excluded = $service->materialesOrden()->create(['producto_id' => 5, 'producto_nombre_snapshot' => 'Excluido',
            'cantidad_requerida' => 2, 'orden' => 1, 'incluido' => false]);
        $service->update(['estatus' => 'en_proceso']);
        $this->postJson('/api/v1/mecanico/servicios/'.$service->id.'/abastecimientos', ['tipo' => 'compra'])->assertOk()
            ->assertJsonPath('data.estatus', 'en_proceso')->assertJsonPath('data.abastecimiento_estado', 'solicitud_compra')
            ->assertJsonPath('data.solicitudes.0.tipo', 'compra')->assertJsonCount(1, 'data.solicitudes.0.materiales')
            ->assertJsonPath('data.solicitudes.0.materiales.0.solicitado', 10);
        $this->assertFalse($excluded->fresh()->incluido);
        $this->assertDatabaseCount('ordenes_compra', 0);
    }

    public function test_subsequent_closed_request_only_requests_shortfall_and_fully_supplied_service_is_blocked(): void
    {
        $this->prepararAbastecimiento();
        $service = $this->ordenConMateriales();
        $url = '/api/v1/mecanico/servicios/'.$service->id.'/abastecimientos';
        $first = $this->postJson($url, ['tipo' => 'almacen'])->assertOk()->json('solicitud_id');
        DB::table('mantenimiento_abastecimientos')->where('id', $first)->update(['estado' => 'cancelada']);
        DB::table('mantenimiento_abastecimiento_detalles')->where('abastecimiento_id', $first)->update(['cantidad_atendida' => 4]);
        $second = $this->postJson($url, ['tipo' => 'almacen'])->assertOk()->assertJsonPath('data.avance_materiales.0.surtido', 4)
            ->assertJsonPath('data.avance_materiales.0.faltante', 6)->json('solicitud_id');
        $this->assertNotSame($first, $second);
        $this->assertDatabaseHas('mantenimiento_abastecimiento_detalles', ['abastecimiento_id' => $second, 'cantidad_solicitada' => 6]);
        DB::table('mantenimiento_abastecimiento_detalles')->where('abastecimiento_id', $second)->update(['cantidad_atendida' => 6]);
        $this->postJson($url, ['tipo' => 'almacen'])->assertConflict();
        $this->postJson($url, ['tipo' => 'compra'])->assertConflict();
        $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()->assertJsonPath('data.avance_materiales.0.faltante', 0)
            ->assertJsonPath('data.acciones_permitidas.solicitar_almacen', false)->assertJsonPath('data.acciones_permitidas.solicitar_compra', false);
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 2);
    }

    public function test_supply_request_rejects_invalid_input_scope_actor_and_permissions(): void
    {
        $this->prepararAbastecimiento();
        $service = $this->ordenConMateriales();
        $url = '/api/v1/mecanico/servicios/'.$service->id.'/abastecimientos';
        foreach ([[], ['tipo' => 'otro'], ['tipo' => ['almacen']], ['tipo' => 'almacen', 'cantidad' => 99],
            ['tipo' => 'compra', 'materiales' => []], ['tipo' => 'almacen', 'created_by' => 12]] as $payload) {
            $this->postJson($url, $payload)->assertUnprocessable();
        }
        $service->update(['mecanico_id' => 12]);
        $this->postJson($url, ['tipo' => 'almacen'])->assertForbidden();
        $service->update(['mecanico_id' => 11, 'tipo' => 'emergencia']);
        $this->postJson($url, ['tipo' => 'almacen'])->assertNotFound();
        $this->postJson('/api/v1/mecanico/servicios/999/abastecimientos', ['tipo' => 'almacen'])->assertNotFound();
        $this->usuarioApp->empleado_id = null;
        $this->postJson($url, ['tipo' => 'almacen'])->assertUnprocessable();
        $this->permisos = [];
        $this->postJson($url, ['tipo' => 'almacen'])->assertForbidden();
        auth('sanctum')->forgetUser();
        $this->postJson($url, ['tipo' => 'almacen'])->assertUnauthorized();
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 0);
    }

    public function test_supply_rejects_invalid_stages_and_rereads_saved_service_state(): void
    {
        $this->prepararAbastecimiento();
        $service = $this->ordenConMateriales();
        foreach (['borrador', 'completado', 'cancelado'] as $status) {
            $service->update(['estatus' => $status]);
            $this->postJson('/api/v1/mecanico/servicios/'.$service->id.'/abastecimientos', ['tipo' => 'almacen'])->assertConflict();
        }
        $service->update(['estatus' => 'orden_generada', 'orden_servicio_folio' => null]);
        $this->postJson('/api/v1/mecanico/servicios/'.$service->id.'/abastecimientos', ['tipo' => 'almacen'])->assertConflict();
        DB::table('mantenimientos')->where('id', $service->id)->update(['estatus' => 'completado']);
        $this->expectException(ConflictHttpException::class);
        app(MantenimientoAbastecimientoService::class)->crearSolicitud($service, 'almacen', 80, 11);
    }

    public function test_supply_rejects_empty_frozen_order_even_if_a_template_is_added_after_generation(): void
    {
        $this->prepararAbastecimiento();
        DB::table('empresa_servicio_preventivo_materiales')->where('id', 1)->update(['activo' => false]);
        $service = $this->ordenConMateriales();
        DB::table('empresa_servicio_preventivo_materiales')->where('id', 1)->update(['activo' => true]);
        $this->postJson('/api/v1/mecanico/servicios/'.$service->id.'/abastecimientos', ['tipo' => 'almacen'])->assertConflict();
        $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()->assertJsonPath('data.avance_materiales', []);
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 0);
    }

    public function test_supply_rechecks_material_exclusions_and_missing_product_before_creating(): void
    {
        $this->prepararAbastecimiento();
        $service = $this->ordenConMateriales();
        $material = $service->materialesOrden()->firstOrFail();
        $url = '/api/v1/mecanico/servicios/'.$service->id.'/abastecimientos';
        $material->update(['incluido' => false]);
        $this->postJson($url, ['tipo' => 'compra'])->assertConflict();
        $material->update(['incluido' => true, 'producto_id' => null]);
        $this->postJson($url, ['tipo' => 'compra'])->assertConflict();
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 0);
    }

    public function test_web_and_api_reuse_same_supply_request_and_require_their_own_access(): void
    {
        $this->prepararAbastecimiento();
        $service = $this->ordenConMateriales();
        $url = route('mantenimiento.mantenimientos.abastecimientos.store', $service);
        $this->post($url, ['tipo' => 'almacen'])->assertRedirect()->assertSessionHas('success');
        $this->postJson('/api/v1/mecanico/servicios/'.$service->id.'/abastecimientos', ['tipo' => 'almacen'])->assertOk()->assertJsonCount(1, 'data.solicitudes');
        $this->post($url, ['tipo' => 'almacen'])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 1);
        $this->permisos = ['app.mecanico.access'];
        $this->post($url, ['tipo' => 'almacen'])->assertForbidden();
    }

    public function test_failed_supply_detail_insert_rolls_back_request_folio_details_and_service_state(): void
    {
        $this->prepararAbastecimiento();
        $service = $this->ordenConMateriales();
        $service->materialesOrden()->create(['producto_id' => 6, 'producto_nombre_snapshot' => 'Segundo',
            'cantidad_requerida' => 2, 'orden' => 1, 'incluido' => true]);
        DB::unprepared("CREATE TRIGGER fallo_solicitud BEFORE INSERT ON mantenimiento_abastecimiento_detalles WHEN NEW.producto_id = 6 BEGIN SELECT RAISE(ABORT, 'fallo_controlado'); END");
        try {
            app(MantenimientoAbastecimientoService::class)->crearSolicitud($service, 'almacen', 80, 11);
            $this->fail('Debió revertir la solicitud y sus detalles.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('fallo_controlado', $exception->getMessage());
        }
        $this->assertDatabaseCount('mantenimiento_abastecimientos', 0);
        $this->assertDatabaseCount('mantenimiento_abastecimiento_detalles', 0);
        $this->assertDatabaseHas('mantenimientos', ['id' => $service->id, 'estatus' => 'orden_generada', 'abastecimiento_estado' => 'pendiente']);
        $this->assertDatabaseHas('inventario_stock', ['stock_actual' => 8, 'stock_reservado' => 3]);
    }

    private function prepararEvidencias(): void
    {
        $this->prepararConsulta();
        Schema::table('mantenimiento_fotos', fn (Blueprint $t) => $t->timestamps());
        Storage::fake('public');
    }

    public function test_uploads_real_images_as_webp_with_bounded_dimensions_and_shared_evidence_urls(): void
    {
        $this->prepararEvidencias();
        $service = $this->crear();
        $response = $this->post('/api/v1/mecanico/servicios/'.$service->id.'/evidencias', [
            'fotos' => [UploadedFile::fake()->image('grande.png', 2500, 1000), UploadedFile::fake()->image('pequena.jpg', 120, 80)],
            'foto_descripciones' => ['Inspección de motor', 'Antes del cambio'],
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonCount(2, 'data.evidencias')
            ->assertJsonPath('data.evidencias.0.descripcion', 'Inspección de motor');
        $fotos = $service->fotos()->orderBy('id')->get();
        $this->assertCount(2, $fotos);
        foreach ($fotos as $foto) {
            $this->assertStringEndsWith('.webp', $foto->ruta);
            $info = getimagesizefromstring(Storage::disk('public')->get($foto->ruta));
            $this->assertSame('image/webp', $info['mime']);
            $this->assertLessThanOrEqual(1920, $info[0]);
            $this->assertLessThanOrEqual(1920, $info[1]);
        }
        $info = getimagesizefromstring(Storage::disk('public')->get($fotos[1]->ruta));
        $this->assertSame(120, $info[0]);
        $this->assertSame(80, $info[1]);
        $this->assertStringEndsWith('/storage/'.$fotos[0]->ruta, $response->json('data.evidencias.0.url'));
        $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()
            ->assertJsonPath('data.evidencias.0.url', $response->json('data.evidencias.0.url'));
    }

    public function test_enforces_cumulative_eight_photo_limit_and_updates_evidence_action(): void
    {
        $this->prepararEvidencias();
        $service = $this->crear();
        for ($i = 0; $i < 7; $i++) {
            $service->fotos()->create(['ruta' => 'existente-'.$i.'.webp']);
        }
        $url = '/api/v1/mecanico/servicios/'.$service->id.'/evidencias';
        $this->post($url, ['fotos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('fotos');
        $this->assertCount(0, Storage::disk('public')->allFiles());
        $this->post($url, ['fotos' => [UploadedFile::fake()->image('a.jpg')]], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.acciones_permitidas.agregar_evidencias', false);
        $this->post($url, ['fotos' => [UploadedFile::fake()->image('b.jpg')]], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->getJson('/api/v1/mecanico/servicios')->assertOk()->assertJsonPath('data.0.acciones_permitidas.agregar_evidencias', false);
        $this->assertDatabaseCount('mantenimiento_fotos', 8);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_rejects_invalid_evidence_formats_sizes_descriptions_and_protected_fields(): void
    {
        $this->prepararEvidencias();
        $service = $this->crear();
        $url = '/api/v1/mecanico/servicios/'.$service->id.'/evidencias';
        foreach ([[], ['fotos' => []], ['fotos' => [UploadedFile::fake()->create('archivo.pdf', 1, 'application/pdf')]],
            ['fotos' => [UploadedFile::fake()->image('grande.jpg')->size(10241)]],
            ['fotos' => [UploadedFile::fake()->image('no.gif')]],
            ['fotos' => [UploadedFile::fake()->image('a.jpg')], 'foto_descripciones' => [str_repeat('x', 201)]],
            ['fotos' => [UploadedFile::fake()->image('a.jpg')], 'foto_descripciones' => [1 => 'Sin foto']],
            ['fotos' => [UploadedFile::fake()->image('a.jpg')], 'estatus' => 'completado'],
            ['fotos' => array_map(fn () => UploadedFile::fake()->image('a.jpg'), range(1, 9))],
        ] as $payload) {
            $this->post($url, $payload, ['Accept' => 'application/json'])->assertUnprocessable();
        }
        $this->assertDatabaseCount('mantenimiento_fotos', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_evidence_upload_rechecks_owner_scope_employee_and_access_before_writing(): void
    {
        $this->prepararEvidencias();
        $service = $this->crear();
        $url = '/api/v1/mecanico/servicios/'.$service->id.'/evidencias';
        $datos = ['fotos' => [UploadedFile::fake()->image('a.jpg')]];
        $service->update(['mecanico_id' => 12]);
        $this->post($url, $datos, ['Accept' => 'application/json'])->assertForbidden();
        $service->update(['mecanico_id' => 11, 'tipo' => 'emergencia']);
        $this->post($url, $datos, ['Accept' => 'application/json'])->assertNotFound();
        $this->post('/api/v1/mecanico/servicios/999/evidencias', $datos, ['Accept' => 'application/json'])->assertNotFound();
        $this->usuarioApp->empleado_id = null;
        $this->post($url, $datos, ['Accept' => 'application/json'])->assertUnprocessable();
        $this->permisos = [];
        $this->post($url, $datos, ['Accept' => 'application/json'])->assertForbidden();
        auth('sanctum')->forgetUser();
        $this->post($url, $datos, ['Accept' => 'application/json'])->assertUnauthorized();
        $this->assertDatabaseCount('mantenimiento_fotos', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_evidence_upload_preserves_web_compatible_stage_rules_without_mutating_execution(): void
    {
        $this->prepararEvidencias();
        $service = $this->crear();
        foreach (['borrador', 'orden_generada', 'pendiente', 'en_proceso', 'completado', 'cancelado'] as $status) {
            $service->update(['estatus' => $status]);
            $this->post('/api/v1/mecanico/servicios/'.$service->id.'/evidencias',
                ['fotos' => [UploadedFile::fake()->image('a.jpg')]], ['Accept' => 'application/json'])->assertCreated()
                ->assertJsonPath('data.estatus', $status);
        }
        $this->assertDatabaseCount('mantenimiento_fotos', 6);
    }

    public function test_optimizer_failure_rolls_back_batch_and_removes_new_files_without_deleting_existing_evidence(): void
    {
        $this->prepararEvidencias();
        $service = $this->crear();
        $old = 'mantenimientos/evidencias/existente.webp';
        Storage::disk('public')->put($old, 'existente');
        $service->fotos()->create(['ruta' => $old]);
        $calls = 0;
        $this->mock(ImageOptimizerInterface::class)->shouldReceive('optimize')->andReturnUsing(function () use (&$calls) {
            if (++$calls === 2) {
                throw new \RuntimeException('fallo_imagen_controlado');
            }

            return 'contenido_de_prueba';
        });
        try {
            app(MantenimientoEvidenciaService::class)->agregar($service, ['fotos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]], 11);
            $this->fail('Debió revertir todo el lote.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('fallo_imagen_controlado', $exception->getMessage());
        }
        $this->assertDatabaseCount('mantenimiento_fotos', 1);
        $this->assertSame([$old], Storage::disk('public')->allFiles());
        Storage::disk('public')->assertExists($old);
    }

    public function test_sql_failure_cleans_new_files_and_rolls_back_photo_rows(): void
    {
        $this->prepararEvidencias();
        $service = $this->crear();
        DB::unprepared("CREATE TRIGGER fallo_foto BEFORE INSERT ON mantenimiento_fotos WHEN NEW.descripcion = 'fallar' BEGIN SELECT RAISE(ABORT, 'fallo_controlado'); END");
        try {
            app(MantenimientoEvidenciaService::class)->agregar($service, [
                'fotos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
                'foto_descripciones' => ['primera', 'fallar'],
            ], 11);
            $this->fail('Debió revertir las fotos y borrar sus archivos.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('fallo_controlado', $exception->getMessage());
        }
        $this->assertDatabaseCount('mantenimiento_fotos', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_web_uploads_same_webp_evidence_and_rolls_back_header_if_photo_persistence_fails(): void
    {
        $this->prepararEvidencias();
        $service = $this->crear();
        $url = route('mantenimiento.mantenimientos.update', $service);
        $payload = array_merge($this->datos(), ['tipo' => 'programado', 'estatus' => 'borrador',
            'descripcion' => 'Descripción web', 'fotos' => [UploadedFile::fake()->image('web.png')], 'foto_descripciones' => ['Web']]);
        $this->put($url, $payload)->assertRedirect()->assertSessionHas('success');
        $this->getJson('/api/v1/mecanico/servicios/'.$service->id)->assertOk()->assertJsonPath('data.evidencias.0.descripcion', 'Web');
        $this->assertDatabaseCount('mantenimiento_fotos', 1);
        DB::unprepared("CREATE TRIGGER fallo_foto BEFORE INSERT ON mantenimiento_fotos BEGIN SELECT RAISE(ABORT, 'fallo_controlado'); END");
        $this->withoutExceptionHandling();
        try {
            $this->put($url, array_merge($payload, ['descripcion' => 'No debe guardar', 'fotos' => [UploadedFile::fake()->image('fallo.png')]]));
            $this->fail('Debió revertir la cabecera y la foto.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('fallo_controlado', $exception->getMessage());
        }
        $this->assertSame('Descripción web', $service->fresh()->descripcion);
        $this->assertDatabaseCount('mantenimiento_fotos', 1);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_partial_disk_write_failure_is_detected_and_cleaned_without_creating_photo_rows(): void
    {
        $this->prepararEvidencias();
        $service = $this->crear();
        $disk = Storage::disk('public');
        $failedDisk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $failedDisk->shouldReceive('put')->once()->andReturnUsing(function ($path) use ($disk) {
            $disk->put($path, 'escritura parcial');

            return false;
        });
        $failedDisk->shouldReceive('delete')->once()->andReturnUsing(fn ($paths) => $disk->delete($paths));
        Storage::shouldReceive('disk')->with('public')->andReturn($failedDisk);
        try {
            app(MantenimientoEvidenciaService::class)->agregar($service, ['fotos' => [UploadedFile::fake()->image('a.jpg')]], 11);
            $this->fail('Debió detectar el fallo al escribir.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('No se pudo guardar la fotografía de evidencia.', $exception->getMessage());
        }
        $this->assertDatabaseCount('mantenimiento_fotos', 0);
        $this->assertCount(0, $disk->allFiles());
    }
}
