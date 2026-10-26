<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\User;
use App\Models\GiraldaHoraExtra;
use App\Services\Empleados\EmpleadoHoraExtraTarifaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmpleadoHoraExtraTarifaTest extends TestCase
{
    private Empleado $empleado;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ejecutar PHP con -d extension=pdo_sqlite.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('empleados', function (Blueprint $table) {
            $table->integer('id_Empleado')->primary();
            $table->string('Nombre');
            $table->string('Apellidos');
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_26_000002_create_empleado_hora_extra_tarifas_table.php'))->up();
        $this->empleado = Empleado::create(['id_Empleado' => 3729, 'Nombre' => 'Empleado', 'Apellidos' => 'Prueba']);
        $usuario = User::forceCreate(['name' => 'Usuario de prueba', 'email' => 'prueba@example.test', 'email_verified_at' => now()]);
        $this->actingAs($usuario);
    }

    private function guardar(array $cambios = [])
    {
        return $this->from(route('empleados.edit', ['empleado' => 3729, 'tab' => 'datos']))
            ->post(route('empleados.hora-extra-tarifas.store', 3729), array_merge([
                'modalidad' => 'cuota_fija', 'costo_hora_extra' => '100.1234',
                'vigente_desde' => '2026-10-26', 'motivo' => 'Acuerdo de tarifa',
            ], $cambios));
    }

    public function test_guarda_tarifa_con_usuario_autenticado_y_precision(): void
    {
        $this->guardar(['registrado_por' => 999])->assertSessionHasNoErrors()
            ->assertRedirect(route('empleados.edit', ['empleado' => 3729, 'tab' => 'datos']).'#hora-extra-tarifa');
        $tarifa = $this->empleado->horaExtraTarifas()->firstOrFail();
        $this->assertSame('100.1234', $tarifa->costo_hora_extra);
        $this->assertSame(auth()->id(), $tarifa->registrado_por);
        $this->assertNull($tarifa->vigente_hasta);
    }

    public function test_cierra_vigencia_y_permite_regresar_a_automatico(): void
    {
        $this->guardar()->assertSessionHasNoErrors();
        $this->guardar(['modalidad' => 'automatica', 'vigente_desde' => '2026-11-01'])
            ->assertSessionHasNoErrors();
        $tarifas = $this->empleado->horaExtraTarifas()->get();
        $this->assertCount(2, $tarifas);
        $this->assertNull($tarifas[0]->costo_hora_extra);
        $this->assertSame('2026-10-31', $tarifas[1]->vigente_hasta->toDateString());
        $this->assertSame('100.1234', $tarifas[1]->costo_hora_extra);
    }

    public function test_rechaza_fechas_superpuestas_sin_modificar_historial(): void
    {
        $this->guardar()->assertSessionHasNoErrors();
        foreach (['2026-10-26', '2026-10-25'] as $fecha) {
            $this->guardar(['vigente_desde' => $fecha])->assertSessionHasErrors('vigente_desde', null, 'horaExtraTarifa');
            $this->assertDatabaseCount('empleado_hora_extra_tarifas', 1);
            $this->assertNull($this->empleado->horaExtraTarifas()->first()->vigente_hasta);
        }
    }

    public function test_valida_cuota_fija_fecha_motivo_y_modalidad(): void
    {
        foreach ([
            ['costo_hora_extra' => null], ['costo_hora_extra' => '-1'],
            ['costo_hora_extra' => '0'], ['costo_hora_extra' => '1.12345'],
            ['costo_hora_extra' => '100000000'], ['vigente_desde' => 'fecha-invalida'],
            ['motivo' => ''], ['modalidad' => 'otra'],
        ] as $cambio) {
            $this->guardar($cambio)->assertSessionHasErrors(array_key_first($cambio), null, 'horaExtraTarifa');
            $this->assertDatabaseCount('empleado_hora_extra_tarifas', 0);
        }
    }

    public function test_automatico_no_requiere_cuota_y_no_modifica_otro_empleado(): void
    {
        $otro = Empleado::create(['id_Empleado' => 3730, 'Nombre' => 'Otro', 'Apellidos' => 'Empleado']);
        $this->guardar(['modalidad' => 'automatica', 'costo_hora_extra' => null])->assertSessionHasNoErrors();
        $this->assertNull($this->empleado->horaExtraTarifas()->firstOrFail()->costo_hora_extra);
        $this->assertSame(0, $otro->horaExtraTarifas()->count());
    }

    public function test_vista_distingue_vigente_programada_e_historial(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 26)->startOfDay());
        $this->guardar()->assertSessionHasNoErrors();
        $this->guardar(['modalidad' => 'automatica', 'vigente_desde' => '2026-11-01'])->assertSessionHasNoErrors();
        $view = $this->view('empleados.partials._hora_extra_tarifa', ['empleado' => $this->empleado]);
        $view->assertSee('Cuota fija vigente')->assertSee('100.1234')->assertSee('Programada')->assertSee('Usuario de prueba');
        $view->assertSee('form="hora-extra-tarifa-form"', false);
    }

    public function test_sin_tarifas_muestra_automatico_sin_crear_registros(): void
    {
        $this->view('empleados.partials._hora_extra_tarifa', ['empleado' => $this->empleado])
            ->assertSee('Automático por sueldo')->assertSee('Sin cambios registrados');
        $this->assertDatabaseCount('empleado_hora_extra_tarifas', 0);
    }

    public function test_no_permite_guardar_sin_sesion(): void
    {
        auth()->logout();
        $this->guardar()->assertRedirect(route('login'));
        $this->assertDatabaseCount('empleado_hora_extra_tarifas', 0);
    }

    public function test_costo_automatico_prioriza_sueldo_real_y_admite_complemento(): void
    {
        $service = app(EmpleadoHoraExtraTarifaService::class);
        $this->empleado->forceFill(['Sueldo_real' => 2800, 'Sueldo' => 1400, 'Complemento' => 700]);
        $this->assertSame(100.0, $service->costoAutomatico($this->empleado));
        $this->empleado->Sueldo_real = 0;
        $this->assertSame(75.0, $service->costoAutomatico($this->empleado));
    }

    public function test_tarifa_de_captura_respeta_fechas_y_regreso_a_automatico(): void
    {
        $this->empleado->Sueldo_real = 2800;
        $this->guardar(['costo_hora_extra' => 150])->assertSessionHasNoErrors();
        $this->guardar(['modalidad' => 'automatica', 'vigente_desde' => '2026-11-01'])->assertSessionHasNoErrors();
        $service = app(EmpleadoHoraExtraTarifaService::class);
        $this->assertSame(100.0, $service->costoParaFecha($this->empleado, '2026-10-25'));
        $this->assertSame(150.0, $service->costoParaFecha($this->empleado, '2026-10-26'));
        $this->assertSame(150.0, $service->costoParaFecha($this->empleado, '2026-10-31'));
        $this->assertSame(100.0, $service->costoParaFecha($this->empleado, '2026-11-01'));
    }

    public function test_acumulado_suma_cada_registro_con_su_tarifa_y_no_con_una_tarifa_unica(): void
    {
        $this->empleado->Sueldo_real = 2800;
        $this->guardar(['costo_hora_extra' => 150])->assertSessionHasNoErrors();
        $registros = [
            new GiraldaHoraExtra(['fecha' => '2026-10-25', 'total_horas' => 2, 'costo_hora_extra' => 100, 'importe' => 200, 'snapshot_at' => now(), 'origen_tarifa' => 'automatica', 'snapshot_fuente' => 'captura']),
            new GiraldaHoraExtra(['fecha' => '2026-10-26', 'total_horas' => 3, 'costo_hora_extra' => 150, 'importe' => 450, 'snapshot_at' => now(), 'origen_tarifa' => 'cuota_fija', 'snapshot_fuente' => 'captura']),
        ];
        $this->assertSame(650.0, app(EmpleadoHoraExtraTarifaService::class)->importeAcumulado($this->empleado, $registros));
    }

    public function test_sin_sueldo_no_inventa_un_costo_cero_y_admite_cuota_fija(): void
    {
        $service = app(EmpleadoHoraExtraTarifaService::class);
        $this->assertNull($service->costoParaFecha($this->empleado, '2026-10-25'));
        $this->assertSame(0.0, $service->importeAcumulado($this->empleado, []));
        $this->assertNull($service->importeAcumulado($this->empleado, [new GiraldaHoraExtra(['fecha' => '2026-10-25', 'total_horas' => 1])]));
        $this->guardar(['costo_hora_extra' => 150])->assertSessionHasNoErrors();
        $this->empleado->unsetRelation('horaExtraTarifas');
        $this->assertSame(150.0, $service->costoParaFecha($this->empleado, '2026-10-26'));
    }

    public function test_modal_muestra_importe_acumulado_y_vista_previa_sin_registrar_horas(): void
    {
        $this->empleado->setAttribute('hora_extra_datos_captura', ['automatica' => 100, 'vigencias' => []]);
        $this->empleado->setAttribute('hora_extra_importe_acumulado', 650);
        $this->view('giralda.partials._modal_horas_extra', [
            'empleado' => $this->empleado, 'areaGiralda' => null,
            'autorizadoresHorasExtras' => collect(), 'puedeIgnorarCandadoHorasExtras' => false,
        ])->assertSee('Costo acumulado:')->assertSee('$650.00')->assertSee('Costo por hora extra')
            ->assertSee('Importe de esta captura')->assertSee('importes guardados');
    }
}
