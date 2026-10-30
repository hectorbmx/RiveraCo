<?php

namespace Tests\Feature;

use App\Models\Maquina;
use App\Services\Mobile\Equipos\EquipoMecanicoPresenter;
use App\Services\Mobile\Equipos\MaquinasConsultaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppMobileMachineOperationalReadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Requiere pdo_sqlite.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'app.timezone' => 'America/Mexico_City']);
        DB::purge('sqlite');
        $this->travelTo(now()->setDate(2026, 10, 29)->startOfDay());

        Schema::create('maquinas', function (Blueprint $t) {
            $t->id();
            foreach (['codigo', 'nombre', 'placas', 'estado', 'marca', 'modelo', 'tipo', 'ubicacion'] as $campo) {
                $t->string($campo)->nullable();
            }
            $t->decimal('horometro_base')->nullable();
        });
        Schema::create('obra_maquina', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maquina_id'); $t->unsignedBigInteger('obra_id');
            $t->string('estado'); $t->date('fecha_inicio')->nullable(); $t->date('fecha_fin')->nullable();
            $t->decimal('horometro_inicio')->nullable(); $t->decimal('horometro_fin')->nullable();
        });
        Schema::create('obras', function (Blueprint $t) {
            $t->id(); $t->softDeletes();
            foreach (['nombre', 'clave_obra', 'estatus_nuevo', 'ubicacion'] as $campo) {
                $t->string($campo)->nullable();
            }
        });
        Schema::create('obra_maquina_registros', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maquina_id');
            $t->decimal('horometro_fin')->nullable(); $t->dateTime('fin')->nullable();
        });
        Schema::create('maquina_horometro_ajustes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maquina_id'); $t->decimal('horometro_nuevo')->nullable();
            $t->dateTime('fecha_registro')->nullable(); $t->timestamps();
        });
        Schema::create('mantenimientos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maquina_id');
            $t->unsignedBigInteger('servicio_preventivo_tipo_id')->nullable();
            $t->unsignedBigInteger('mecanico_id')->nullable(); $t->decimal('horometro')->nullable();
            $t->string('tipo'); $t->string('estatus');
            $t->dateTime('fecha_programada')->nullable(); $t->dateTime('fecha_inicio')->nullable();
            $t->dateTime('fecha_fin')->nullable(); $t->timestamps();
        });
        Schema::create('empresa_config', function (Blueprint $t) {
            $t->id(); $t->integer('maquinaria_servicio_horas')->default(250);
            $t->integer('maquinaria_servicio_meses')->default(6); $t->integer('maquinaria_alerta_horas')->default(20);
        });
        Schema::create('empresa_servicio_preventivo_tipos', function (Blueprint $t) {
            $t->id(); $t->string('ambito'); $t->string('nombre'); $t->string('codigo');
            $t->integer('intervalo_valor'); $t->integer('intervalo_meses')->default(6);
            $t->integer('alerta_valor')->default(20); $t->boolean('activo')->default(true); $t->integer('orden')->default(0);
        });
        Schema::create('seguros', function (Blueprint $t) {
            $t->id(); $t->string('asegurable_type'); $t->unsignedBigInteger('asegurable_id');
            $t->date('vigencia_hasta')->nullable(); $t->softDeletes();
        });
        DB::table('maquinas')->insert(['id' => 17, 'codigo' => 'M-17', 'nombre' => 'Grúa',
            'horometro_base' => 100, 'estado' => 'operativa', 'ubicacion' => 'en_obra']);
    }

    private function listado(array $params = []): array
    {
        $request = Request::create('/', 'GET', $params);
        $this->app->instance('request', $request);

        return $this->serializar(app(EquipoMecanicoPresenter::class)->listado(
            app(MaquinasConsultaService::class)->index($request), 'maquina'
        ));
    }

    private function detalle(int $id = 17): array
    {
        return $this->serializar(app(EquipoMecanicoPresenter::class)->detalle(
            app(MaquinasConsultaService::class)->show(Request::create('/'), Maquina::findOrFail($id)), 'maquina'
        ));
    }

    private function serializar(array $data): array
    {
        return json_decode(json_encode($data, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private function tipos(): void
    {
        foreach ([[1, 'General', 500, 'maquinaria', true], [2, 'Mayor', 1000, 'maquinaria', true],
            [3, 'Inactivo', 50, 'maquinaria', false], [4, 'Vehículo', 100, 'vehiculo', true]] as [$id, $nombre, $intervalo, $ambito, $activo]) {
            DB::table('empresa_servicio_preventivo_tipos')->insert(['id' => $id, 'nombre' => $nombre,
                'codigo' => 'T-'.$id, 'intervalo_valor' => $intervalo, 'ambito' => $ambito, 'activo' => $activo]);
        }
    }

    private function mantenimiento(int $tipo, float $horometro, string $fecha): void
    {
        DB::table('mantenimientos')->insert(['maquina_id' => 17, 'servicio_preventivo_tipo_id' => $tipo,
            'tipo' => 'programado', 'estatus' => 'completado', 'horometro' => $horometro, 'fecha_fin' => $fecha]);
    }

    public function test_list_and_detail_use_current_reading_and_all_active_machine_preventives(): void
    {
        $this->tipos();
        $this->mantenimiento(1, 1000, '2026-10-01 10:00:00');
        $this->mantenimiento(2, 1000, '2026-10-01 10:00:00');
        DB::table('obras')->insert(['id' => 9, 'nombre' => 'Obra prueba', 'estatus_nuevo' => 'activa']);
        DB::table('obra_maquina')->insert(['maquina_id' => 17, 'obra_id' => 9, 'estado' => 'activa',
            'fecha_inicio' => '2026-10-02', 'horometro_inicio' => 1100, 'horometro_fin' => 1200]);
        DB::table('obra_maquina_registros')->insert(['maquina_id' => 17, 'horometro_fin' => 1400, 'fin' => '2026-10-27 10:00:00']);
        DB::table('maquina_horometro_ajustes')->insert(['maquina_id' => 17, 'horometro_nuevo' => 1480,
            'fecha_registro' => '2026-10-28 10:00:00']);

        $fila = $this->listado()['data'][0];
        $detalle = $this->detalle()['data']['maquina'];
        foreach (['ubicacion', 'horometro_actual', 'fecha_ultima_lectura', 'preventivo'] as $campo) {
            $this->assertSame($fila[$campo], $detalle[$campo]);
        }
        $this->assertSame('en_obra', $fila['ubicacion']);
        $this->assertEquals(100, $fila['horometro_base']);
        $this->assertEquals(1480, $fila['horometro_actual']);
        $this->assertSame('2026-10-28T00:00:00-06:00', $fila['fecha_ultima_lectura']);
        $this->assertSame('Obra prueba', $fila['obra_activa']['nombre']);
        $this->assertSame('General', $fila['preventivo']['servicio_nombre']);
        $this->assertSame('proximo', $fila['preventivo']['estado']);
        $this->assertEquals(20, $fila['preventivo']['horas_restantes']);
        $this->assertEquals(1500, $fila['preventivo']['proximo_horometro']);
        $this->assertSame('2027-04-01', $fila['preventivo']['proximo_fecha']);
        $this->assertSame(['General', 'Mayor'], array_column($fila['preventivo']['servicios'], 'servicio_nombre'));
    }

    public function test_missing_readings_are_null_and_zero_is_a_valid_reading(): void
    {
        $this->tipos();
        DB::table('maquinas')->where('id', 17)->update(['horometro_base' => null, 'ubicacion' => null]);
        $fila = $this->listado()['data'][0];
        $this->assertNull($fila['ubicacion']);
        $this->assertNull($fila['horometro_actual']);
        $this->assertNull($fila['fecha_ultima_lectura']);
        $this->assertSame('sin_datos', $fila['preventivo']['estado']);
        $this->assertNull($fila['preventivo']['proximo_fecha']);
        $this->assertCount(2, $fila['preventivo']['servicios']);

        DB::table('maquinas')->where('id', 17)->update(['horometro_base' => 0]);
        $maquina = $this->detalle()['data']['maquina'];
        $this->assertEquals(0, $maquina['horometro_actual']);
        $this->assertNull($maquina['fecha_ultima_lectura']);
        $this->assertSame('ok', $maquina['preventivo']['estado']);
    }

    public function test_calendar_expiration_takes_priority_over_remaining_hours(): void
    {
        $this->tipos();
        $this->mantenimiento(1, 1000, '2026-10-01');
        $this->mantenimiento(2, 1000, '2026-01-01');
        $fila = $this->listado()['data'][0];
        $this->assertEquals(1000, $fila['horometro_actual']);
        $this->assertSame('Mayor', $fila['preventivo']['servicio_nombre']);
        $this->assertSame('vencido', $fila['preventivo']['estado']);
        $this->assertSame('2026-07-01', $fila['preventivo']['proximo_fecha']);
        $this->assertEquals(1000, $fila['preventivo']['horas_restantes']);
    }

    public function test_legacy_configuration_is_used_when_no_active_types_exist(): void
    {
        DB::table('empresa_config')->insert(['maquinaria_servicio_horas' => 300, 'maquinaria_alerta_horas' => 25]);
        DB::table('maquina_horometro_ajustes')->insert(['maquina_id' => 17, 'horometro_nuevo' => 380,
            'created_at' => '2026-10-28 09:00:00']);
        $fila = $this->listado()['data'][0];
        $this->assertEquals(380, $fila['horometro_actual']);
        $this->assertSame('2026-10-28T09:00:00-06:00', $fila['fecha_ultima_lectura']);
        $this->assertSame('proximo', $fila['preventivo']['estado']);
        $this->assertEquals(300, $fila['preventivo']['intervalo_horas']);
        $this->assertEquals(20, $fila['preventivo']['horas_restantes']);
        $this->assertNull($fila['preventivo']['servicio_id']);
    }

    public function test_pagination_filters_and_empty_results_keep_existing_shape(): void
    {
        DB::table('maquinas')->insert(['id' => 18, 'nombre' => 'Otra', 'estado' => 'en_reparacion', 'ubicacion' => 'en_patio']);
        $pagina = $this->listado(['per_page' => 1, 'page' => 2]);
        $this->assertSame(['current_page' => 2, 'last_page' => 2, 'per_page' => 1, 'total' => 2], $pagina['meta']);
        $this->assertSame(18, $pagina['data'][0]['id']);
        $filtrado = $this->listado(['estatus' => 'operativa', 'asignada' => 0]);
        $this->assertCount(1, $filtrado['data']);
        $this->assertSame(17, $filtrado['data'][0]['id']);
        $vacio = $this->listado(['q' => 'Sin coincidencias']);
        $this->assertSame([], $vacio['data']);
        $this->assertSame(0, $vacio['meta']['total']);
    }
}
