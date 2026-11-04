<?php

namespace Tests\Feature;

use App\Models\Comision;
use App\Models\ComisionEtapa;
use App\Models\ComisionEtapaFoto;
use App\Models\Obra;
use App\Models\User;
use App\Services\Comisiones\ComisionEtapasService;
use App\Services\Comisiones\ComisionesWebService;
use App\Services\Comisiones\ComisionEtapaCorreccionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ComisionEtapaWebGuardadoTest extends TestCase
{
    private Obra $obra;
    private Comision $comision;
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ejecutar PHP con -d extension=pdo_sqlite.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        Storage::fake('public');
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->timestamp('email_verified_at')->nullable(); $t->timestamps();
        });
        Schema::create('obras', function (Blueprint $t) { $t->id(); $t->string('nombre')->nullable(); $t->integer('area_id')->nullable(); $t->softDeletes(); $t->timestamps(); });
        Schema::create('obras_pilas', function (Blueprint $t) { $t->id(); });
        Schema::create('obra_maquina', function (Blueprint $t) { $t->id(); });
        Schema::create('empleados', function (Blueprint $t) {
            $t->integer('id_Empleado')->primary(); $t->string('Nombre'); $t->string('Apellidos'); $t->timestamps();
        });
        Schema::create('catalogo_roles', function (Blueprint $t) { $t->id(); $t->string('nombre'); $t->timestamps(); });
        Schema::create('catalogo_actividades_comision', function (Blueprint $t) { $t->id(); });
        Schema::create('obra_empleado', function (Blueprint $t) {
            $t->id(); $t->integer('obra_id'); $t->integer('empleado_id'); $t->integer('rol_id')->nullable();
            $t->boolean('activo')->default(true); $t->date('fecha_baja')->nullable(); $t->timestamps();
        });
        Schema::create('comisiones', function (Blueprint $t) {
            $t->id(); $t->integer('obra_id'); $t->integer('pila_id'); $t->date('fecha'); $t->string('estado')->default('pendiente');
            $t->timestamp('cerrada_at')->nullable(); $t->timestamp('cancelada_at')->nullable();
            $t->integer('created_by')->nullable(); $t->integer('updated_by')->nullable(); $t->timestamps();
        });
        (require database_path('migrations/2025_12_04_194732_create_comision_detalles_table.php'))->up();
        (require database_path('migrations/2025_12_19_191636_add_ademe_bauer_and_campana_to_comision_detalles_table.php'))->up();
        (require database_path('migrations/2026_06_01_000004_create_comision_etapas_tables.php'))->up();
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('guard_name'); $t->timestamps(); });
        Schema::create('model_has_roles', function (Blueprint $t) { $t->integer('role_id'); $t->integer('model_id'); $t->string('model_type'); });
        DB::table('roles')->insert([
            ['name' => 'super-admin', 'guard_name' => 'web'], ['name' => 'admin-rivera', 'guard_name' => 'web'],
        ]);
        $stored = User::forceCreate(['name' => 'Prueba', 'email' => 'comisiones@example.test', 'email_verified_at' => now()]);
        $this->usuario = Mockery::mock(User::class)->makePartial();
        $this->usuario->setRawAttributes($stored->getAttributes());
        $this->usuario->shouldReceive('can')->with('obras.edit')->andReturn(true)->byDefault();
        foreach (['editar_perforacion.access', 'editar_acero.access', 'editar_colado.access', 'borrar_perforacion.access', 'borrar_acero.access', 'borrar_colado.access', 'borrar_fotos_perforacion.access', 'borrar_fotos_acero.access', 'borrar_fotos_colado.access', 'borrar_foto_comision.acces'] as $permiso) {
            $this->usuario->shouldReceive('can')->with($permiso)->andReturn(false)->byDefault();
        }
        $this->usuario->shouldReceive('canAny')->andReturn(true)->byDefault();
        $this->usuario->shouldReceive('hasAnyRole')->andReturn(true);
        $this->actingAs($this->usuario);
        $this->obra = Obra::forceCreate(['nombre' => 'Obra de prueba', 'area_id' => 1]);
        DB::table('obras_pilas')->insert(['id' => 1]);
        DB::table('catalogo_roles')->insert([['id' => 1, 'nombre' => 'Ayudante'], ['id' => 2, 'nombre' => 'Residente']]);
        DB::table('catalogo_actividades_comision')->insert(['id' => 1]);
        foreach ([1, 2, 3, 4, 5] as $id) {
            DB::table('empleados')->insert(['id_Empleado' => $id, 'Nombre' => 'Persona', 'Apellidos' => (string) $id]);
            DB::table('obra_empleado')->insert([
                'id' => $id, 'obra_id' => $id === 4 ? 999 : $this->obra->id, 'empleado_id' => $id,
                'rol_id' => $id === 5 ? 2 : 1, 'activo' => $id !== 3, 'fecha_baja' => $id === 3 ? '2026-01-01' : null,
            ]);
        }
        $this->comision = Comision::create(['obra_id' => $this->obra->id, 'pila_id' => 1, 'fecha' => '2026-11-04']);
        foreach (['perforacion' => 'completada', 'bentonita' => 'no_aplica', 'ademe' => 'no_aplica', 'acero' => 'en_proceso', 'colado' => 'pendiente'] as $key => $estado) {
            $this->comision->etapas()->create([
                'obra_id' => $this->obra->id, 'pila_id' => 1, 'etapa' => $key, 'estado' => $estado,
                'orden' => $this->comision->etapas()->count() + 1, 'requiere_foto' => true,
            ]);
        }
        $this->comision->detalles()->create(['cantidad' => 1, 'metros_sujetos_comision' => 20, 'kg_acero' => 100, 'vol_concreto' => 0]);
        foreach ([1, 3] as $id) {
            $this->acero()->personal()->create([
                'obra_empleado_id' => $id, 'empleado_id' => $id, 'rol_id' => 1, 'actividad_id' => 1,
                'importe_comision' => 125.50, 'comisiona' => false, 'notas' => 'Acuerdo anterior',
            ]);
        }
    }

    private function acero(): ComisionEtapa { return $this->comision->etapas()->where('etapa', 'acero')->firstOrFail(); }

    private function guardar(array $data = [], string $etapa = 'acero')
    {
        return $this->post(route('obras.comisiones.etapas.guardar', [$this->obra, $this->comision, $etapa]), array_merge([
            'etapa_formulario' => $etapa, 'hora_inicio' => '13:00', 'hora_fin' => '14:00',
            'observaciones' => 'Trabajo terminado', 'kg_acero' => '150.25', 'personal_ids' => [1, 2],
        ], $data));
    }

    private function prepararAlta(): array
    {
        Schema::table('obras', function (Blueprint $t) { $t->integer('estatus_nuevo')->default(2); });
        Schema::table('obras_pilas', function (Blueprint $t) { $t->integer('obra_id')->nullable(); $t->boolean('activo')->default(true); });
        DB::table('obras_pilas')->where('id', 1)->update(['obra_id' => $this->obra->id]);
        DB::table('obras_pilas')->insert([
            ['id' => 2, 'obra_id' => 999, 'activo' => true], ['id' => 3, 'obra_id' => $this->obra->id, 'activo' => false],
        ]);
        Schema::table('obra_maquina', function (Blueprint $t) { $t->integer('obra_id'); $t->string('estado'); $t->date('fecha_fin')->nullable(); });
        DB::table('obra_maquina')->insert([
            ['id' => 1, 'obra_id' => $this->obra->id, 'estado' => 'activa', 'fecha_fin' => null],
            ['id' => 2, 'obra_id' => 999, 'estado' => 'activa', 'fecha_fin' => null],
            ['id' => 3, 'obra_id' => $this->obra->id, 'estado' => 'finalizada', 'fecha_fin' => null],
            ['id' => 4, 'obra_id' => $this->obra->id, 'estado' => 'activa', 'fecha_fin' => '2026-01-01'],
        ]);
        Schema::table('obra_maquina', function (Blueprint $t) { $t->integer('maquina_id')->nullable(); });
        Schema::create('maquinas', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->string('codigo'); $t->string('tipo'); $t->softDeletes(); $t->timestamps();
        });
        DB::table('maquinas')->insert([
            ['id' => 1, 'nombre' => 'Equipo auxiliar', 'codigo' => 'AUX-01', 'tipo' => 'GRUA'],
            ['id' => 5, 'nombre' => 'Perforadora de prueba', 'codigo' => 'PER-05', 'tipo' => 'PERFORADORA'],
        ]);
        DB::table('obra_maquina')->update(['maquina_id' => 1]);
        Schema::table('catalogo_roles', function (Blueprint $t) { $t->string('rol_key')->nullable(); });
        DB::table('catalogo_roles')->where('id', 2)->update(['rol_key' => 'RESIDENTE']);
        Schema::table('comisiones', function (Blueprint $t) {
            $t->integer('residente_id')->nullable(); $t->integer('tarifario_id')->nullable(); $t->integer('trabajo_id')->nullable();
            $t->string('numero_formato')->nullable(); $t->string('cliente_nombre')->nullable(); $t->text('observaciones')->nullable();
        });
        Schema::create('comision_tarifarios', function (Blueprint $t) { $t->id(); $t->string('estado'); $t->date('vigente_desde')->nullable(); $t->timestamps(); });
        DB::table('comision_tarifarios')->insert([
            ['id' => 1, 'estado' => 'vigente', 'vigente_desde' => '2026-01-01'],
            ['id' => 2, 'estado' => 'vigente', 'vigente_desde' => '2026-02-01'],
            ['id' => 3, 'estado' => 'borrador', 'vigente_desde' => '2026-03-01'],
        ]);
        Schema::create('catalogo_trabajos_comision', function (Blueprint $t) { $t->id(); });
        DB::table('catalogo_trabajos_comision')->insert(['id' => 1]);
        Schema::create('comision_personal', function (Blueprint $t) { $t->id(); $t->integer('comision_id'); });
        Schema::create('comision_perforaciones', function (Blueprint $t) { $t->id(); $t->integer('comision_id'); });
        $this->obra->refresh();

        return [
            'fecha' => '2026-11-04', 'pila_id' => 1, 'obra_maquina_id' => 1, 'trabajo_id' => 1,
            'numero_formato' => 'PRUEBA', 'cliente_nombre' => 'Cliente de prueba',
            'perforacion' => ['hora_inicio' => '08:00', 'diametro' => 1.2, 'profundidad' => 15, 'metros_comision' => 14.5, 'personal_ids' => [1]],
        ];
    }

    private function prepararDatosGenerales(): void
    {
        $this->prepararAlta();
        DB::table('obra_maquina')->insert([
            ['id' => 5, 'obra_id' => $this->obra->id, 'maquina_id' => 5, 'estado' => 'activa'],
            ['id' => 6, 'obra_id' => $this->obra->id, 'maquina_id' => 999, 'estado' => 'activa'],
        ]);
        Schema::table('obras_pilas', function (Blueprint $t) {
            $t->string('tipo')->nullable(); $t->decimal('diametro_proyecto')->nullable(); $t->decimal('profundidad_proyecto')->nullable();
        });
        DB::table('obras_pilas')->where('id', 1)->update(['tipo' => 'Pila de prueba', 'diametro_proyecto' => 1.2, 'profundidad_proyecto' => 15]);
        DB::table('obras_pilas')->insert(['id' => 4, 'obra_id' => $this->obra->id, 'activo' => true, 'tipo' => 'Segunda pila']);
    }

    public function test_datos_generales_solo_ofrecen_referencias_activas_de_la_obra(): void
    {
        $this->prepararDatosGenerales();
        $data = $this->app->make(ComisionesWebService::class)->datosGeneralesForUser($this->usuario, $this->obra);
        $this->assertSame([1, 4], $data['pilas']->pluck('id')->all());
        $this->assertSame([1, 5], $data['asignacionesMaquinas']->pluck('id')->all());
        $this->assertSame(5, $data['maquinaInicialId']);
        $this->assertSame(5, $data['residenteAsignado']->empleado_id);
        $this->assertTrue($data['puedeContinuarAlta']);
        $this->assertSame(1, Comision::count());
    }

    public function test_datos_generales_detectan_falta_de_asignaciones(): void
    {
        $this->prepararDatosGenerales();
        DB::table('obras_pilas')->update(['activo' => false]);
        DB::table('obra_maquina')->update(['estado' => 'finalizada']);
        DB::table('obra_empleado')->where('id', 5)->update(['activo' => false]);
        $data = $this->app->make(ComisionesWebService::class)->datosGeneralesForUser($this->usuario, $this->obra);
        $this->assertFalse($data['puedeContinuarAlta']);
        $this->assertNull($data['residenteAsignado']);
        $this->assertNull($data['maquinaInicialId']);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $html = view('obras.comisiones.partials.datos-generales', ['obra' => $this->obra] + $data)->render();
        $this->assertStringContainsString('La obra no tiene pilas activas', $html);
        $this->assertStringContainsString('no tiene máquinas con asignación activa', $html);
        $this->assertStringContainsString('Sin residente activo asignado', $html);
    }

    public function test_alta_ofrece_solo_personal_disponible_y_recupera_seleccion_vacia(): void
    {
        $this->prepararDatosGenerales();
        // Una asignación con baja no es seleccionable aunque conserve activo=true.
        DB::table('obra_empleado')->where('id', 2)->update(['fecha_baja' => '2026-11-01']);
        $data = $this->app->make(ComisionesWebService::class)->datosGeneralesForUser($this->usuario, $this->obra);
        $this->assertSame([1], $data['personalDisponibleAlta']->pluck('id')->all());
        $this->app['request']->setLaravelSession(session()->driver());
        $render = fn () => view('obras.comisiones.partials.personal-fotos-inicial', $data)->render();
        $checkboxes = function ($html) {
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            return (new \DOMXPath($dom))->query('//input[@type="checkbox"][@checked]');
        };
        $html = $render();
        $this->assertSame(1, $checkboxes($html)->length);
        $this->assertStringContainsString('name="perforacion[personal_ids][]"', $html);
        $this->assertStringContainsString('name="fotos[]"', $html);
        session()->flashInput(['perforacion' => ['personal_presente' => '1']]);
        $this->assertSame(0, $checkboxes($render())->length);
        session()->flashInput(['perforacion' => ['personal_presente' => '1', 'personal_ids' => [1, 4, 5]]]);
        $this->assertSame(1, $checkboxes($render())->length);
        DB::table('obra_empleado')->where('id', 1)->update(['activo' => false]);
        $data = $this->app->make(ComisionesWebService::class)->datosGeneralesForUser($this->usuario, $this->obra);
        $this->assertStringContainsString('No hay personal con asignación activa', view('obras.comisiones.partials.personal-fotos-inicial', $data)->render());
        $this->assertSame(1, Comision::count());
    }

    public function test_datos_generales_respetan_permiso_de_captura(): void
    {
        $this->usuario->shouldReceive('can')->with('obras.edit')->andReturn(false);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->app->make(ComisionesWebService::class)->datosGeneralesForUser($this->usuario, $this->obra);
    }

    public function test_datos_generales_recuperan_valores_y_residente_no_es_editable(): void
    {
        $this->prepararDatosGenerales();
        $data = $this->app->make(ComisionesWebService::class)->datosGeneralesForUser($this->usuario, $this->obra);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        session()->flashInput(['pila_id' => 4, 'obra_maquina_id' => 1, 'numero_formato' => 'FORMATO-PRUEBA', 'fecha' => '2026-11-01']);
        $this->app['request']->setLaravelSession(session()->driver());
        $html = view('obras.comisiones.partials.datos-generales', ['obra' => $this->obra] + $data)->render();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame('4', $xpath->query('//select[@name="pila_id"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('1', $xpath->query('//select[@name="obra_maquina_id"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//input[@id="alta-residente"][@readonly][not(@name)]')->length);
        $this->assertStringContainsString('FORMATO-PRUEBA', $html);
        $this->assertStringContainsString('2026-11-01', $html);
    }

    public function test_alta_compartida_web_genera_cinco_etapas_y_perforacion_en_proceso(): void
    {
        $input = $this->prepararAlta();
        $input += ['residente_id' => 999, 'tarifario_id' => 999, 'estado' => 'cerrada'];
        $input['perforacion'] += ['estado' => 'completada', 'cantidad' => 9, 'kg_acero' => 500, 'vol_concreto' => 20, 'importe_comision' => 999];
        $nueva = $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $input);

        $this->assertSame('pendiente', $nueva->estado);
        $this->assertSame(5, $nueva->etapas()->count());
        $this->assertSame(['perforacion', 'bentonita', 'ademe', 'acero', 'colado'], $nueva->etapas()->pluck('etapa')->all());
        $this->assertSame(['en_proceso', 'no_aplica', 'no_aplica', 'pendiente', 'pendiente'], $nueva->etapas()->pluck('estado')->all());
        $this->assertEquals(5, $nueva->residente_id);
        $this->assertEquals(2, $nueva->tarifario_id);
        $this->assertSame($this->usuario->id, $nueva->created_by);
        $detalle = $nueva->detalles()->firstOrFail();
        $this->assertSame(1, $detalle->cantidad);
        $this->assertEquals(1, $detalle->obra_maquina_id);
        $this->assertEquals(14.5, $detalle->metros_sujetos_comision);
        $this->assertEquals(0, $nueva->detalles()->sum('kg_acero'));
        $this->assertEquals(0, $nueva->detalles()->sum('vol_concreto'));
        $etapa = $nueva->etapas()->where('etapa', 'perforacion')->firstOrFail();
        $this->assertNull($etapa->hora_fin);
        $this->assertSame(1, $etapa->personal()->count());
        $this->assertSame('0.00', $etapa->personal()->firstOrFail()->importe_comision);
        $this->assertSame(0, DB::table('comision_personal')->count());
        $resumen = $this->app->make(ComisionEtapasService::class)->mapComision($nueva->fresh(['etapas', 'pila']));
        $this->assertSame(0, $resumen['avance']['porcentaje']);
        $this->assertSame('continuar', $resumen['siguiente_etapa']['accion']);
    }

    public function test_alta_con_fin_completa_solo_perforacion_y_habilita_acero(): void
    {
        $input = $this->prepararAlta();
        $input['perforacion']['hora_fin'] = '09:00';
        $nueva = $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $input);
        $resumen = $this->app->make(ComisionEtapasService::class)->mapComision($nueva->fresh(['etapas', 'pila']));
        $this->assertSame('pendiente', $resumen['estado']);
        $this->assertSame(33, $resumen['avance']['porcentaje']);
        $this->assertSame('acero', $resumen['siguiente_etapa']['etapa']);
        $this->assertSame(1, $nueva->etapas()->where('estado', 'completada')->count());
    }

    public function test_alta_con_opcionales_sigue_con_bentonita_y_avance_20(): void
    {
        $input = $this->prepararAlta();
        $input['perforacion']['hora_fin'] = '09:00';
        $input['opcionales'] = ['bentonita' => true, 'ademe' => true];
        $nueva = $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $input);
        $resumen = $this->app->make(ComisionEtapasService::class)->mapComision($nueva->fresh(['etapas', 'pila']));
        $this->assertSame(20, $resumen['avance']['porcentaje']);
        $this->assertSame('bentonita', $resumen['siguiente_etapa']['etapa']);
    }

    public function test_alta_rechaza_referencias_inactivas_o_de_otra_obra_sin_crear_registros(): void
    {
        $input = $this->prepararAlta();
        foreach ([['pila_id', 2], ['pila_id', 3], ['obra_maquina_id', 2], ['obra_maquina_id', 3], ['obra_maquina_id', 4], ['trabajo_id', 999]] as [$campo, $id]) {
            try {
                $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, array_replace($input, [$campo => $id]));
                $this->fail('La referencia debía ser rechazada.');
            } catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey($campo, $e->errors()); }
        }
        $this->assertSame(1, Comision::count());
    }

    public function test_alta_requiere_inicio_y_residente_asignado(): void
    {
        $input = $this->prepararAlta();
        $sinInicio = $input;
        unset($sinInicio['perforacion']['hora_inicio']);
        try {
            $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $sinInicio);
            $this->fail('El inicio debía ser obligatorio.');
        } catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('perforacion.hora_inicio', $e->errors()); }
        DB::table('obra_empleado')->where('id', 5)->update(['activo' => false]);
        try {
            $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $input);
            $this->fail('El residente debía ser obligatorio.');
        } catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('residente', $e->errors()); }
        $this->assertSame(1, Comision::count());
    }

    public function test_alta_rechaza_personal_no_disponible(): void
    {
        $input = $this->prepararAlta();
        foreach ([3, 4, 5] as $id) {
            $input['perforacion']['personal_ids'] = [$id];
            try {
                $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $input);
                $this->fail('El participante debía ser rechazado.');
            } catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('perforacion.personal_ids', $e->errors()); }
        }
        $this->assertSame(1, Comision::count());
    }

    public function test_alta_rechaza_obra_cerrada_y_usuario_sin_permiso(): void
    {
        $input = $this->prepararAlta();
        $this->obra->estatus_nuevo = Obra::ESTATUS_TERMINADA;
        try {
            $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $input);
            $this->fail('La obra terminada debía ser rechazada.');
        } catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('obra', $e->errors()); }
        $this->usuario->shouldReceive('can')->with('obras.edit')->andReturn(false);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $input);
    }

    public function test_creador_movil_conserva_respuesta_y_cantidades_adelantadas(): void
    {
        $input = $this->prepararAlta();
        $result = $this->app->make(ComisionEtapasService::class)->create($this->usuario, $this->obra, [
            'fecha' => $input['fecha'], 'pila_id' => 1,
            'perforacion' => ['hora_inicio' => '08:00', 'hora_fin' => '09:00', 'cantidad' => 1,
                'metros_comision' => 14.5, 'kg_acero' => 25, 'vol_concreto' => 2],
        ], 5);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('etapas', $result);
        $this->assertSame('pendiente', $result['estado']);
        $this->assertSame(33, $result['avance']['porcentaje']);
        $this->assertSame('acero', $result['siguiente_etapa']['etapa']);
        $this->assertEquals(25, Comision::findOrFail($result['id'])->detalles()->sum('kg_acero'));
    }

    public function test_fallo_al_crear_etapas_revierte_alta_completa(): void
    {
        $input = $this->prepararAlta();
        DB::unprepared("CREATE TRIGGER fallo_alta_etapa BEFORE INSERT ON comision_etapas WHEN NEW.etapa = 'acero' BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");
        try {
            $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $input);
            $this->fail('Se esperaba una falla al crear las etapas.');
        } catch (\Illuminate\Database\QueryException $e) { $this->assertStringContainsString('fallo simulado', $e->getMessage()); }
        $this->assertSame(1, Comision::count());
        $this->assertSame(5, ComisionEtapa::count());
    }

    public function test_post_alta_guarda_captura_completa_y_redirige_al_seguimiento(): void
    {
        $input = $this->prepararAlta();
        $input['perforacion']['personal_ids'] = [1, 2];
        $input['fotos'] = [UploadedFile::fake()->image('uno.png'), UploadedFile::fake()->image('dos.jpg')];
        $response = $this->post(route('obras.comisiones.store-etapas', [$this->obra]), $input);
        $nueva = Comision::orderByDesc('id')->firstOrFail();
        $response->assertSessionHasNoErrors()->assertRedirect(route('obras.comisiones.show', [$this->obra, $nueva]) . '#resultado-guardado');
        $this->assertNotSame($this->comision->id, $nueva->id);
        $this->assertSame('pendiente', $nueva->estado);
        $etapa = $nueva->etapas()->where('etapa', 'perforacion')->firstOrFail();
        $this->assertSame('en_proceso', $etapa->estado);
        $this->assertSame([1, 2], $etapa->personal()->orderBy('obra_empleado_id')->pluck('obra_empleado_id')->all());
        $this->assertSame(2, $etapa->fotos()->count());
        foreach ($etapa->fotos as $foto) {
            Storage::disk('public')->assertExists($foto->path);
            $this->assertSame('image/webp', $foto->mime_type);
            $this->assertSame($this->usuario->id, $foto->uploaded_by);
        }
        $detalle = $this->app->make(ComisionEtapasService::class)->show($this->obra, $nueva)['comision'];
        $this->assertSame('perforacion', $detalle['siguiente_etapa']['etapa']);
        $this->assertSame(0, $detalle['avance']['porcentaje']);
        $this->assertSame(0, DB::table('comision_personal')->count());
    }

    public function test_post_alta_con_fin_avanza_y_solo_adjunta_personal_a_perforacion(): void
    {
        $input = $this->prepararAlta();
        $input['perforacion']['hora_fin'] = '09:00';
        $input['opcionales'] = ['bentonita' => '1', 'ademe' => '0'];
        $this->post(route('obras.comisiones.store-etapas', [$this->obra]), $input)->assertSessionHasNoErrors();
        $nueva = Comision::orderByDesc('id')->firstOrFail();
        $detalle = $this->app->make(ComisionEtapasService::class)->show($this->obra, $nueva)['comision'];
        $this->assertSame(25, $detalle['avance']['porcentaje']);
        $this->assertSame('bentonita', $detalle['siguiente_etapa']['etapa']);
        $this->assertSame('pendiente', $nueva->estado);
        $this->assertSame(0, $nueva->etapas()->where('etapa', '!=', 'perforacion')->get()->sum(fn ($etapa) => $etapa->personal()->count()));
    }

    public function test_alta_sin_ademe_ni_campana_guarda_ceros_con_esquema_real(): void
    {
        $input = $this->prepararAlta();
        $this->post(route('obras.comisiones.store-etapas', [$this->obra]), $input)->assertSessionHasNoErrors();
        $nueva = Comision::orderByDesc('id')->firstOrFail();
        $detalle = $nueva->detalles()->firstOrFail();
        $this->assertEquals(0, $detalle->ml_ademe_bauer);
        $this->assertEquals(0, $detalle->campana_pzas);
        // Continuar otra etapa no debe borrar la producción registrada.
        $detalle->update(['ml_ademe_bauer' => 12.5, 'campana_pzas' => 2]);
        $this->app->make(ComisionesWebService::class)->completarEtapaForUser($this->usuario, $this->obra, $nueva, 'perforacion', [
            'hora_inicio' => '08:00', 'hora_fin' => '09:00', 'metros_comision' => 14.5,
        ]);
        $this->assertEquals(12.5, $detalle->fresh()->ml_ademe_bauer);
        $this->assertEquals(2, $detalle->fresh()->campana_pzas);
    }

    public function test_error_de_base_de_datos_muestra_motivo_y_referencia_sin_sql(): void
    {
        $input = $this->prepararAlta();
        DB::unprepared("CREATE TRIGGER fallo_alta BEFORE INSERT ON comision_detalles BEGIN SELECT RAISE(ABORT, 'detalle sensible de base de datos'); END");
        \Illuminate\Support\Facades\Log::spy();
        $this->post(route('obras.comisiones.store-etapas', [$this->obra]), $input)
            ->assertRedirect(route('obras.comisiones.create', [$this->obra]))->assertSessionHasErrors('guardado');
        $mensaje = session('errors')->first('guardado');
        $this->assertStringContainsString('La base de datos rechazó', $mensaje);
        $this->assertMatchesRegularExpression('/Referencia: [A-F0-9]{8}/', $mensaje);
        $this->assertStringNotContainsString('SQLSTATE', $mensaje);
        $this->assertStringNotContainsString('detalle sensible', $mensaje);
        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->once()->with('Fallo al crear comisión por etapas.', Mockery::on(
            fn ($contexto) => str_contains($mensaje, $contexto['referencia'])
                && $contexto['obra_id'] === $this->obra->id
                && $contexto['exception'] instanceof \Illuminate\Database\QueryException
        ));
        $this->assertSame(1, Comision::count());
    }

    public function test_post_alta_invalida_recupera_datos_sin_guardar_archivos(): void
    {
        $input = $this->prepararAlta();
        $input['perforacion']['personal_presente'] = '1';
        $input['perforacion']['personal_ids'] = [];
        $input['perforacion']['hora_inicio'] = '';
        $input['fotos'] = [UploadedFile::fake()->image('foto.png')];
        $this->post(route('obras.comisiones.store-etapas', [$this->obra]), $input)
            ->assertRedirect(route('obras.comisiones.create', [$this->obra]))
            ->assertSessionHasErrors('perforacion.hora_inicio')
            ->assertSessionHasInput('numero_formato', 'PRUEBA')
            ->assertSessionHasInput('perforacion.personal_presente', '1');
        $this->assertArrayNotHasKey('fotos', session()->getOldInput());
        $this->assertSame(1, Comision::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_post_alta_rechaza_archivos_invalidos_y_maquina_eliminada(): void
    {
        $input = $this->prepararAlta();
        foreach ([UploadedFile::fake()->create('archivo.txt', 1, 'text/plain'), UploadedFile::fake()->image('grande.png')->size(8193)] as $foto) {
            $this->post(route('obras.comisiones.store-etapas', [$this->obra]), $input + ['fotos' => [$foto]])
                ->assertSessionHasErrors('fotos.0');
        }
        DB::table('maquinas')->where('id', 1)->delete();
        $this->post(route('obras.comisiones.store-etapas', [$this->obra]), $input)->assertSessionHasErrors('obra_maquina_id');
        $this->assertSame(1, Comision::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_post_alta_exige_permiso_y_rechaza_participantes_ajenos(): void
    {
        $input = $this->prepararAlta();
        $input['perforacion']['personal_ids'] = [4];
        $this->post(route('obras.comisiones.store-etapas', [$this->obra]), $input)->assertSessionHasErrors('perforacion.personal_ids');
        $this->usuario->shouldReceive('can')->with('obras.edit')->andReturn(false);
        $this->post(route('obras.comisiones.store-etapas', [$this->obra]), $input)->assertForbidden();
        $this->assertSame(1, Comision::count());
    }

    public function test_fallo_en_segunda_foto_del_alta_revierte_y_limpia_la_primera(): void
    {
        $input = $this->prepararAlta();
        $input['perforacion']['hora_fin'] = '09:00';
        $input['fotos'] = [UploadedFile::fake()->image('uno.png'), UploadedFile::fake()->image('dos.png')];
        \Illuminate\Support\Facades\Notification::fake();
        $engine = new class extends ComisionEtapasService {
            private int $intentos = 0;
            public function storeFoto(User $user, Obra $obra, Comision $comision, string $etapaKey, UploadedFile $foto, ?string $comentario = null): array
            {
                if (++$this->intentos === 2) { throw new \RuntimeException('Fallo simulado de alta'); }
                return parent::storeFoto($user, $obra, $comision, $etapaKey, $foto, $comentario);
            }
        };
        $this->app->instance(ComisionEtapasService::class, $engine);
        $this->post(route('obras.comisiones.store-etapas', [$this->obra]), $input)
            ->assertRedirect(route('obras.comisiones.create', [$this->obra]))
            ->assertSessionHasErrors('guardado')->assertSessionHasInput('numero_formato', 'PRUEBA');
        $this->assertSame(1, Comision::count());
        $this->assertSame(5, ComisionEtapa::count());
        $this->assertSame(2, DB::table('comision_etapa_personal')->count());
        $this->assertSame(0, ComisionEtapaFoto::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_notificacion_de_alta_espera_commit_exterior(): void
    {
        $input = $this->prepararAlta();
        $input['perforacion']['hora_fin'] = '09:00';
        $destinatario = User::forceCreate(['name' => 'Oficina', 'email' => 'oficina@example.test']);
        DB::table('model_has_roles')->insert(['role_id' => 1, 'model_id' => $destinatario->id, 'model_type' => User::class]);
        \Illuminate\Support\Facades\Notification::fake();
        DB::beginTransaction();
        $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $input);
        \Illuminate\Support\Facades\Notification::assertNothingSent();
        DB::commit();
        \Illuminate\Support\Facades\Notification::assertSentTo($destinatario, \App\Notifications\ObraOperacionNotification::class);
    }

    public function test_rollback_exterior_no_deja_alta_ni_notificacion(): void
    {
        $input = $this->prepararAlta();
        $input['perforacion']['hora_fin'] = '09:00';
        \Illuminate\Support\Facades\Notification::fake();
        DB::beginTransaction();
        $this->app->make(ComisionesWebService::class)->createForUser($this->usuario, $this->obra, $input);
        DB::rollBack();
        $this->assertSame(1, Comision::count());
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_guarda_etapa_personal_fotos_y_conserva_metadatos(): void
    {
        $this->withoutExceptionHandling();
        $this->guardar(['fotos' => [UploadedFile::fake()->image('evidencia.png')], 'vol_concreto' => 999, 'estado' => 'cancelada'])
            ->assertSessionHasNoErrors()->assertRedirect(route('obras.comisiones.show', [$this->obra, $this->comision]) . '#resultado-guardado');
        $this->assertSame('completada', $this->acero()->estado);
        $this->assertSame('13:00', $this->acero()->hora_inicio);
        $this->assertSame('pendiente', $this->comision->fresh()->estado);
        $this->assertEquals(150.25, $this->comision->detalles()->sum('kg_acero'));
        $this->assertEquals(0, $this->comision->detalles()->sum('vol_concreto'));
        $this->assertEquals(20, $this->comision->detalles()->sum('metros_sujetos_comision'));
        $this->assertSame(3, $this->acero()->personal()->count());
        foreach ([1, 3] as $id) {
            $previo = $this->acero()->personal()->where('obra_empleado_id', $id)->firstOrFail();
            $this->assertSame('125.50', $previo->importe_comision);
            $this->assertSame('Acuerdo anterior', $previo->notas);
            $this->assertSame(1, $previo->actividad_id);
            $this->assertFalse($previo->comisiona);
        }
        $foto = $this->acero()->fotos()->firstOrFail();
        Storage::disk('public')->assertExists($foto->path);
        $this->assertSame('image/webp', $foto->mime_type);
        $this->assertSame($this->usuario->id, $foto->uploaded_by);
    }

    public function test_ultima_etapa_cierra_comision(): void
    {
        $this->guardar()->assertSessionHasNoErrors();
        $this->guardar(['vol_concreto' => 8.25], 'colado')->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('cerrada', $this->comision->fresh()->estado);
        $this->assertNotNull($this->comision->fresh()->cerrada_at);
        $this->assertEquals(8.25, $this->comision->detalles()->sum('vol_concreto'));
    }

    public function test_cada_etapa_y_activacion_confirman_el_resultado_visible(): void
    {
        $this->prepararAlta();
        $this->comision->etapas()->where('etapa', 'perforacion')->update(['estado' => 'en_proceso']);
        $this->comision->etapas()->where('etapa', 'acero')->update(['estado' => 'pendiente']);
        $etapas = [
            'perforacion' => ['Perforación', 'metros_comision', 20],
            'bentonita' => ['Bentonita', 'vol_bentonita', 3],
            'ademe' => ['Ademe', 'ml_ademe_bauer', 2],
            'acero' => ['Colocación de acero', 'kg_acero', 150],
            'colado' => ['Colado', 'vol_concreto', 8],
        ];
        $destino = route('obras.comisiones.show', [$this->obra, $this->comision]) . '#resultado-guardado';
        foreach ($etapas as $key => [$nombre, $campo, $valor]) {
            if (in_array($key, ['bentonita', 'ademe'], true)) {
                $this->post(route('obras.comisiones.etapas.activar', [$this->obra, $this->comision, $key]))
                    ->assertSessionHasNoErrors()->assertRedirect($destino)
                    ->assertSessionHas('success', $nombre . ' activada correctamente. Ya puedes registrar esta etapa.');
            }
            $this->guardar([$campo => $valor], $key)->assertSessionHasNoErrors()->assertRedirect($destino);
            $this->assertStringContainsString($nombre . ' completada correctamente.', session('success'));
            $html = view('obras.comisiones.partials.resultado-guardado')->render();
            $this->assertStringContainsString('Guardado correcto', $html);
            $this->assertStringContainsString($nombre . ' completada correctamente.', $html);
            $this->assertStringContainsString('id="resultado-guardado"', $html);
        }
        $this->assertStringContainsString('la comisión quedó cerrada', session('success'));
        $this->assertSame('cerrada', $this->comision->fresh()->estado);
    }

    public function test_error_de_campos_recupera_formulario_sin_guardar(): void
    {
        $this->guardar(['hora_fin' => '', 'personal_ids' => []])->assertSessionHasErrors('hora_fin')
            ->assertRedirect(route('obras.comisiones.show', [$this->obra, $this->comision, 'etapa' => 'acero']))
            ->assertSessionHasInput('personal_ids', []);
        $this->assertSame('en_proceso', $this->acero()->estado);
        $this->assertEquals(100, $this->comision->detalles()->sum('kg_acero'));
    }

    public function test_no_permite_saltar_etapas(): void
    {
        $this->guardar(['vol_concreto' => 8], 'colado')->assertSessionHasErrors('etapa');
        $this->assertSame('pendiente', $this->comision->etapas()->where('etapa', 'colado')->value('estado'));
    }

    public function test_cero_concreto_no_completa_colado(): void
    {
        $this->guardar()->assertSessionHasNoErrors();
        $this->guardar(['vol_concreto' => 0], 'colado')->assertSessionHasErrors('vol_concreto');
        $this->assertSame('pendiente', $this->comision->fresh()->estado);
    }

    public function test_no_acepta_personal_de_otra_obra_inactivo_o_residente(): void
    {
        foreach ([3, 4, 5, 999] as $id) {
            $this->guardar(['personal_ids' => [$id]])->assertSessionHasErrors('personal_ids');
            $this->assertSame('en_proceso', $this->acero()->estado);
        }
    }

    public function test_sin_personal_se_quita_seleccion_activa_y_se_conserva_historial(): void
    {
        $this->guardar(['personal_ids' => []])->assertSessionHasNoErrors();
        $this->assertSame([3], $this->acero()->personal()->pluck('obra_empleado_id')->all());
    }

    public function test_rechaza_archivos_invalidos_antes_de_guardar(): void
    {
        $this->guardar(['fotos' => [UploadedFile::fake()->create('no-es-foto.txt', 1, 'text/plain')]])->assertSessionHasErrors('fotos.0');
        $this->guardar(['fotos' => [UploadedFile::fake()->image('grande.png')->size(8193)]])->assertSessionHasErrors('fotos.0');
        $this->assertSame('en_proceso', $this->acero()->estado);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_sin_permiso_no_escribe(): void
    {
        $this->usuario->shouldReceive('canAny')->andReturn(false);
        $this->guardar()->assertForbidden();
        $this->assertSame('en_proceso', $this->acero()->estado);
    }

    public function test_segundo_envio_no_duplica_fotos_ni_personal(): void
    {
        $foto = fn () => UploadedFile::fake()->image('evidencia.png');
        $this->guardar(['fotos' => [$foto()]])->assertSessionHasNoErrors();
        $this->guardar(['fotos' => [$foto()]])->assertSessionHasErrors('etapa');
        $this->assertSame(1, $this->acero()->fotos()->count());
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_totales_no_duplican_otros_renglones_de_produccion(): void
    {
        $this->comision->detalles()->create(['cantidad' => 1, 'kg_acero' => 50]);
        $this->guardar(['kg_acero' => 175])->assertSessionHasNoErrors();
        $this->assertEquals(175, $this->comision->detalles()->sum('kg_acero'));
        $this->assertEquals([125, 50], $this->comision->detalles()->orderBy('id')->pluck('kg_acero')->map(fn ($n) => (float) $n)->all());
    }

    public function test_obra_ajena_no_permite_guardar_la_comision(): void
    {
        $otra = Obra::forceCreate(['nombre' => 'Otra obra']);
        $this->post(route('obras.comisiones.etapas.guardar', [$otra, $this->comision, 'acero']), [])->assertForbidden();
        $this->assertSame('en_proceso', $this->acero()->estado);
    }

    public function test_sin_sesion_no_permite_guardar(): void
    {
        $this->app['auth']->forgetGuards();
        $this->guardar()->assertRedirect(route('login'));
        $this->assertSame('en_proceso', $this->acero()->estado);
    }

    public function test_fotos_previas_se_conservan_al_agregar_evidencia(): void
    {
        Storage::disk('public')->put('evidencia-previa.webp', 'contenido previo');
        $anterior = $this->acero()->fotos()->create(['disk' => 'public', 'path' => 'evidencia-previa.webp']);
        $this->guardar(['fotos' => [UploadedFile::fake()->image('nueva.png')]])->assertSessionHasNoErrors();
        $this->assertSame(2, $this->acero()->fotos()->count());
        $this->assertSame('evidencia-previa.webp', $anterior->fresh()->path);
        $this->assertSame('contenido previo', Storage::disk('public')->get($anterior->path));
    }

    public function test_produccion_invalida_no_modifica_la_etapa(): void
    {
        foreach ([-1, '12.345', '10000000000'] as $monto) {
            $this->guardar(['kg_acero' => $monto])->assertSessionHasErrors('kg_acero');
        }
        $this->assertSame('en_proceso', $this->acero()->estado);
        $this->assertEquals(100, $this->comision->detalles()->sum('kg_acero'));
    }

    public function test_no_reduce_el_total_por_debajo_de_otros_renglones(): void
    {
        $this->comision->detalles()->create(['cantidad' => 1, 'kg_acero' => 50]);
        $this->guardar(['kg_acero' => 40])->assertSessionHasErrors('kg_acero');
        $this->assertEquals(150, $this->comision->detalles()->sum('kg_acero'));
        $this->assertSame('en_proceso', $this->acero()->estado);
    }

    public function test_fallo_de_escritura_de_archivo_no_completa_la_etapa(): void
    {
        $disco = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disco->shouldReceive('put')->once()->andReturn(false);
        $disco->shouldReceive('delete')->once()->andReturn(true);
        Storage::shouldReceive('disk')->with('public')->andReturn($disco);
        try {
            $this->app->make(ComisionesWebService::class)->completarEtapaForUser($this->usuario, $this->obra, $this->comision, 'acero', [
                'hora_inicio' => '13:00', 'hora_fin' => '14:00', 'kg_acero' => 200,
                'fotos' => [UploadedFile::fake()->image('evidencia.png')],
            ]);
            $this->fail('Se esperaba una falla de escritura.');
        } catch (\RuntimeException $exception) { $this->assertSame('No se pudo guardar la fotografía.', $exception->getMessage()); }
        $this->assertSame('en_proceso', $this->acero()->estado);
        $this->assertSame(0, ComisionEtapaFoto::count());
    }

    public function test_fallo_en_segunda_foto_revierte_datos_y_limpia_archivos_nuevos(): void
    {
        $engine = new class extends ComisionEtapasService {
            private int $intentos = 0;
            public function storeFoto(User $user, Obra $obra, Comision $comision, string $etapaKey, UploadedFile $foto, ?string $comentario = null): array
            {
                if (++$this->intentos === 2) { throw new \RuntimeException('Fallo simulado'); }
                return parent::storeFoto($user, $obra, $comision, $etapaKey, $foto, $comentario);
            }
        };
        $service = new ComisionesWebService($engine);
        try {
            $service->completarEtapaForUser($this->usuario, $this->obra, $this->comision, 'acero', [
                'hora_inicio' => '13:00', 'hora_fin' => '14:00', 'kg_acero' => 200, 'personal_ids' => [2],
                'fotos' => [UploadedFile::fake()->image('uno.png'), UploadedFile::fake()->image('dos.png')],
            ]);
            $this->fail('Se esperaba una falla de almacenamiento.');
        } catch (\RuntimeException $exception) { $this->assertSame('Fallo simulado', $exception->getMessage()); }
        $this->assertSame('en_proceso', $this->acero()->estado);
        $this->assertEquals(100, $this->comision->detalles()->sum('kg_acero'));
        $this->assertSame([1, 3], $this->acero()->personal()->orderBy('obra_empleado_id')->pluck('obra_empleado_id')->all());
        $this->assertSame(0, ComisionEtapaFoto::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function permitir(string $permiso): void
    {
        $this->usuario->shouldReceive('can')->with($permiso)->andReturn(true);
    }

    private function corregir(string $etapa, array $datos = [])
    {
        return $this->put(route('obras.comisiones.etapas.actualizar', [$this->obra, $this->comision, $etapa]), $datos + [
            'hora_inicio' => '10:00', 'hora_fin' => $etapa === 'acero' ? null : '11:00',
            'observaciones' => 'Corrección autorizada', 'metros_comision' => 22, 'kg_acero' => 175, 'personal_ids' => [1, 2],
        ]);
    }

    public function test_correcciones_y_fotos_exigen_sus_permisos_especificos(): void
    {
        foreach (['perforacion', 'acero', 'colado'] as $key) {
            $this->get(route('obras.comisiones.etapas.editar', [$this->obra, $this->comision, $key]))->assertForbidden();
            $this->corregir($key)->assertForbidden();
            $this->delete(route('obras.comisiones.etapas.borrar', [$this->obra, $this->comision, $key]))->assertForbidden();
        }
        $foto = $this->acero()->fotos()->create(['disk' => 'public', 'path' => 'protegida.webp']);
        $this->delete(route('obras.comisiones.etapas.fotos.borrar', [$this->obra, $this->comision, 'acero', $foto]))->assertForbidden();
        $this->permitir('editar_acero.access');
        $this->corregir('perforacion')->assertForbidden();
        $this->delete(route('obras.comisiones.etapas.borrar', [$this->obra, $this->comision, 'acero']))->assertForbidden();
        $this->assertSame('en_proceso', $this->acero()->estado);
        $this->assertSame(1, ComisionEtapaFoto::count());
    }

    public function test_edita_perforacion_completada_sin_cambiar_estado_ni_historial(): void
    {
        $this->permitir('editar_perforacion.access');
        // La asignación del permiso puntual basta; no requiere el permiso de alta.
        $this->usuario->shouldReceive('can')->with('obras.edit')->andReturn(false);
        $this->comision->forceFill(['estado' => 'cerrada', 'cerrada_at' => now()])->save();
        $this->corregir('perforacion', [
            'diametro' => 1.5, 'profundidad' => 23, 'campana_pzas' => 2, 'adicional' => 'Campana revisada',
            'estado' => 'pendiente', 'importe_comision' => 9999,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $perforacion = $this->comision->etapas()->where('etapa', 'perforacion')->firstOrFail();
        $detalle = $this->comision->detalles()->firstOrFail();
        $this->assertSame('completada', $perforacion->estado);
        $this->assertSame('cerrada', $this->comision->fresh()->estado);
        $this->assertSame('10:00', substr($perforacion->hora_inicio, 0, 5));
        $this->assertEquals(22, $detalle->metros_sujetos_comision);
        $this->assertEquals(100, $detalle->kg_acero);
        $this->assertEquals(1.5, $detalle->diametro);
        $this->assertEquals(23, $detalle->profundidad);
        $this->assertEquals(2, $detalle->campana_pzas);
        $this->assertSame([1, 2], $perforacion->personal()->orderBy('obra_empleado_id')->pluck('obra_empleado_id')->all());
        $this->assertEquals(0, $perforacion->personal()->sum('importe_comision'));
    }

    public function test_edita_acero_sin_completarlo_y_conserva_metadatos_previos(): void
    {
        $this->permitir('editar_acero.access');
        $this->corregir('acero')->assertSessionHasNoErrors();
        $this->assertSame('en_proceso', $this->acero()->estado);
        $this->assertNull($this->acero()->hora_fin);
        $this->assertEquals(175, $this->comision->detalles()->sum('kg_acero'));
        $this->assertEquals(20, $this->comision->detalles()->sum('metros_sujetos_comision'));
        $previo = $this->acero()->personal()->where('obra_empleado_id', 1)->firstOrFail();
        $this->assertEquals(125.5, $previo->importe_comision);
        $this->assertFalse($previo->comisiona);
        $this->assertSame([1, 2, 3], $this->acero()->personal()->orderBy('obra_empleado_id')->pluck('obra_empleado_id')->all());
        $this->corregir('acero', ['hora_fin' => '11:00'])->assertSessionHasErrors('hora_fin');
        $this->assertSame('en_proceso', $this->acero()->estado);
    }

    public function test_edicion_valida_datos_personal_y_no_sobrescribe_otros_renglones(): void
    {
        $this->permitir('editar_perforacion.access');
        $this->corregir('perforacion', ['hora_fin' => ''])->assertSessionHasErrors('hora_fin');
        $this->corregir('perforacion', ['personal_ids' => [4]])->assertSessionHasErrors('personal_ids');
        $this->corregir('perforacion', ['metros_comision' => -1])->assertSessionHasErrors('metros_comision');
        $this->comision->detalles()->create(['metros_sujetos_comision' => 30]);
        $this->corregir('perforacion', ['metros_comision' => 25])->assertSessionHasErrors('metros_comision');
        $this->assertEquals(50, $this->comision->detalles()->sum('metros_sujetos_comision'));
        $this->corregir('perforacion', ['metros_comision' => 45])->assertSessionHasNoErrors();
        $this->assertEquals(45, $this->comision->detalles()->sum('metros_sujetos_comision'));
        $this->assertEquals(30, $this->comision->detalles()->orderByDesc('id')->first()->metros_sujetos_comision);
    }

    public function test_borrar_acero_limpia_su_captura_y_archivos_y_lo_deja_pendiente(): void
    {
        $this->permitir('borrar_acero.access');
        Storage::disk('public')->put('acero-borrar.webp', 'imagen');
        $this->acero()->fotos()->create(['disk' => 'public', 'path' => 'acero-borrar.webp']);
        $this->delete(route('obras.comisiones.etapas.borrar', [$this->obra, $this->comision, 'acero']))
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('pendiente', $this->acero()->estado);
        $this->assertNull($this->acero()->hora_inicio);
        $this->assertNull($this->acero()->hora_fin);
        $this->assertSame(0, $this->acero()->personal()->count());
        $this->assertSame(0, $this->acero()->fotos()->count());
        $this->assertEquals(0, $this->comision->detalles()->sum('kg_acero'));
        $this->assertEquals(20, $this->comision->detalles()->sum('metros_sujetos_comision'));
        $this->assertSame(5, $this->comision->etapas()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_no_borra_perforacion_si_una_etapa_posterior_ya_inicio(): void
    {
        $this->permitir('borrar_perforacion.access');
        $this->delete(route('obras.comisiones.etapas.borrar', [$this->obra, $this->comision, 'perforacion']))->assertSessionHasErrors('etapa');
        $this->assertEquals(20, $this->comision->detalles()->sum('metros_sujetos_comision'));
        $this->assertSame('completada', $this->comision->etapas()->where('etapa', 'perforacion')->value('estado'));
        $this->comision->etapas()->where('etapa', 'acero')->update(['estado' => 'pendiente']);
        $this->comision->detalles()->update(['campana_pzas' => 3, 'adicional' => 'Detalle']);
        $this->delete(route('obras.comisiones.etapas.borrar', [$this->obra, $this->comision, 'perforacion']))->assertSessionHasNoErrors();
        $this->assertSame('pendiente', $this->comision->etapas()->where('etapa', 'perforacion')->value('estado'));
        $this->assertEquals(0, $this->comision->detalles()->sum('metros_sujetos_comision'));
        $this->assertEquals(0, $this->comision->detalles()->sum('campana_pzas'));
        $this->assertNull($this->comision->detalles()->first()->adicional);
        $this->assertEquals(100, $this->comision->detalles()->sum('kg_acero'));
    }

    public function test_borrar_foto_verifica_comision_etapa_y_permiso_y_respeta_su_disco(): void
    {
        $this->permitir('borrar_fotos_acero.access');
        Storage::fake('evidencias');
        Storage::disk('evidencias')->put('foto.webp', 'imagen');
        $foto = $this->acero()->fotos()->create(['disk' => 'evidencias', 'path' => 'foto.webp']);
        $this->delete(route('obras.comisiones.etapas.fotos.borrar', [$this->obra, $this->comision, 'perforacion', $foto]))->assertForbidden();
        $this->permitir('borrar_fotos_perforacion.access');
        $this->delete(route('obras.comisiones.etapas.fotos.borrar', [$this->obra, $this->comision, 'perforacion', $foto]))->assertNotFound();
        Storage::disk('evidencias')->assertExists('foto.webp');
        $otraObra = Obra::forceCreate(['nombre' => 'Ajena']);
        $this->delete(route('obras.comisiones.etapas.fotos.borrar', [$otraObra, $this->comision, 'acero', $foto]))->assertForbidden();
        $this->delete(route('obras.comisiones.etapas.fotos.borrar', [$this->obra, $this->comision, 'acero', $foto]))
            ->assertSessionHasNoErrors()->assertSessionHas('success', 'Fotografía borrada correctamente.');
        $this->assertSame(0, ComisionEtapaFoto::count());
        $this->assertSame([], Storage::disk('evidencias')->allFiles());
        $this->assertSame('en_proceso', $this->acero()->estado);
    }

    public function test_fallo_en_base_de_datos_restaura_la_foto_retirada(): void
    {
        $this->permitir('borrar_acero.access');
        Storage::disk('public')->put('no-perder.webp', 'imagen');
        $this->acero()->fotos()->create(['disk' => 'public', 'path' => 'no-perder.webp']);
        DB::unprepared("CREATE TRIGGER fallo_borrar BEFORE DELETE ON comision_etapa_fotos BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");
        try {
            $this->app->make(ComisionEtapaCorreccionService::class)->borrar($this->usuario, $this->obra, $this->comision, 'acero');
            $this->fail('Debió fallar y restaurar la captura.');
        } catch (\Illuminate\Database\QueryException $exception) { $this->assertStringContainsString('fallo simulado', $exception->getMessage()); }
        $this->assertSame('en_proceso', $this->acero()->estado);
        $this->assertSame(1, $this->acero()->fotos()->count());
        $this->assertEquals(100, $this->comision->detalles()->sum('kg_acero'));
        Storage::disk('public')->assertExists('no-perder.webp');
        $this->assertSame(['no-perder.webp'], Storage::disk('public')->allFiles());
    }

    public function test_vista_de_gestion_oculta_acciones_sin_permiso_y_muestra_solo_las_autorizadas(): void
    {
        \Illuminate\Support\Facades\Gate::partialMock()->shouldReceive('check')
            ->andReturnUsing(fn ($permiso) => $this->usuario->can($permiso));
        $service = $this->app->make(ComisionEtapaCorreccionService::class);
        $render = fn () => view('obras.comisiones.partials.gestion-etapa', [
            'obra' => $this->obra, 'comision' => $this->comision, 'etapa' => $this->acero(),
            'gestionEtapas' => $service->permisos($this->usuario, $this->obra),
        ])->render();
        $this->assertStringNotContainsString('Editar etapa', $render());
        $this->assertStringNotContainsString('Borrar captura', $render());
        $this->permitir('editar_acero.access');
        $this->assertStringContainsString('Editar etapa', $render());
        $this->assertStringNotContainsString('Borrar captura', $render());
        $this->permitir('borrar_acero.access');
        $this->assertStringContainsString('Borrar captura', $render());
    }

    public function test_fallo_al_retirar_archivo_no_borra_la_captura(): void
    {
        $this->permitir('borrar_acero.access');
        $this->acero()->fotos()->create(['disk' => 'public', 'path' => 'no-se-puede-mover.webp']);
        $disco = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disco->shouldReceive('exists')->with('no-se-puede-mover.webp')->andReturn(true);
        $disco->shouldReceive('move')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disco);
        $this->delete(route('obras.comisiones.etapas.borrar', [$this->obra, $this->comision, 'acero']))->assertSessionHasErrors('etapa');
        $this->assertSame('en_proceso', $this->acero()->estado);
        $this->assertSame(1, $this->acero()->fotos()->count());
        $this->assertEquals(100, $this->comision->detalles()->sum('kg_acero'));
    }

    public function test_permiso_de_edicion_no_da_acceso_a_comisiones_de_otra_obra(): void
    {
        $this->permitir('editar_acero.access');
        $this->permitir('borrar_acero.access');
        $otra = Obra::forceCreate(['nombre' => 'Otra obra']);
        $this->put(route('obras.comisiones.etapas.actualizar', [$otra, $this->comision, 'acero']), [])->assertForbidden();
        $this->delete(route('obras.comisiones.etapas.borrar', [$otra, $this->comision, 'acero']))->assertForbidden();
        $this->assertEquals(100, $this->comision->detalles()->sum('kg_acero'));
    }

    public function test_edicion_recupera_formulario_con_selector_de_archivos(): void
    {
        $this->permitir('editar_acero.access');
        $seleccion = $this->app->make(ComisionEtapaCorreccionService::class)->formulario($this->usuario, $this->obra, $this->comision, 'acero');
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $html = view('obras.comisiones.partials.formulario-etapa', [
            'obra' => $this->obra, 'comision' => $this->comision, 'etapa' => $this->acero(),
            'etapaSeleccionada' => $seleccion, 'esEdicion' => true,
            'etapaLabels' => ['acero' => 'Colocación de acero'],
            'personalDisponibleEtapa' => $this->app->make(ComisionEtapasService::class)->personalDisponible($this->obra),
        ])->render();
        $this->assertStringContainsString('Guardar cambios', $html);
        $this->assertStringContainsString('value="PUT"', $html);
        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('name="hora_fin" type="time"', $html);
        $this->assertStringContainsString('readonly', $html);
    }

    public function test_edita_colado_cerrado_sin_reabrir_y_valida_concreto_positivo(): void
    {
        $this->permitir('editar_colado.access');
        $this->comision->etapas()->whereIn('etapa', ['acero', 'colado'])->update(['estado' => 'completada']);
        $this->comision->forceFill(['estado' => 'cerrada', 'cerrada_at' => now()])->save();
        $cerrada = $this->comision->cerrada_at->toDateTimeString();
        $this->corregir('colado', ['vol_concreto' => 8.5])->assertSessionHasNoErrors();
        $this->assertSame('cerrada', $this->comision->fresh()->estado);
        $this->assertSame($cerrada, $this->comision->fresh()->cerrada_at->toDateTimeString());
        $this->assertEquals(8.5, $this->comision->detalles()->sum('vol_concreto'));
        $this->assertEquals(100, $this->comision->detalles()->sum('kg_acero'));
        $this->assertSame('completada', $this->comision->etapas()->where('etapa', 'colado')->value('estado'));
        $this->corregir('colado', ['vol_concreto' => 0])->assertSessionHasErrors('vol_concreto');
        $this->corregir('colado', ['vol_concreto' => 9, 'hora_fin' => null])->assertSessionHasErrors('hora_fin');
        $this->assertEquals(8.5, $this->comision->detalles()->sum('vol_concreto'));
        $this->corregir('perforacion')->assertForbidden();
        $this->corregir('acero')->assertForbidden();
    }

    public function test_borra_colado_y_reabre_el_trabajo_sin_borrar_las_otras_etapas(): void
    {
        $this->prepararAlta();
        $this->permitir('borrar_colado.access');
        $this->comision->etapas()->whereIn('etapa', ['acero', 'colado'])->update(['estado' => 'completada']);
        $this->comision->forceFill(['estado' => 'cerrada', 'cerrada_at' => now()])->save();
        $this->comision->detalles()->update(['vol_concreto' => 8]);
        $colado = $this->comision->etapas()->where('etapa', 'colado')->firstOrFail();
        $colado->personal()->create(['obra_empleado_id' => 2, 'empleado_id' => 2]);
        Storage::disk('public')->put('colado-borrar.webp', 'imagen');
        $colado->fotos()->create(['disk' => 'public', 'path' => 'colado-borrar.webp']);
        $this->delete(route('obras.comisiones.etapas.borrar', [$this->obra, $this->comision, 'colado']))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('pendiente', $this->comision->fresh()->estado);
        $this->assertNull($this->comision->fresh()->cerrada_at);
        $this->assertSame('pendiente', $colado->fresh()->estado);
        $this->assertSame(0, $colado->personal()->count());
        $this->assertSame(0, $colado->fotos()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertEquals(0, $this->comision->detalles()->sum('vol_concreto'));
        $this->assertEquals(100, $this->comision->detalles()->sum('kg_acero'));
        $this->assertEquals(20, $this->comision->detalles()->sum('metros_sujetos_comision'));
        $resumen = $this->app->make(ComisionEtapasService::class)->show($this->obra, $this->comision->fresh())['comision'];
        $this->assertSame(67, $resumen['avance']['porcentaje']);
        $this->assertSame('colado', $resumen['siguiente_etapa']['etapa']);
        $this->delete(route('obras.comisiones.etapas.borrar', [$this->obra, $this->comision, 'acero']))->assertForbidden();
    }

    public static function seccionesConFotos(): array
    {
        return [['perforacion'], ['acero'], ['colado']];
    }

    /** @dataProvider seccionesConFotos */
    public function test_permiso_de_fotos_es_independiente_para_cada_seccion(string $key): void
    {
        $this->permitir('borrar_fotos_' . $key . '.access');
        \Illuminate\Support\Facades\Gate::partialMock()->shouldReceive('check')->andReturnUsing(fn ($permiso) => $this->usuario->can($permiso));
        $service = $this->app->make(ComisionEtapaCorreccionService::class);
        foreach (['perforacion', 'acero', 'colado'] as $otra) {
            $etapa = $this->comision->etapas()->where('etapa', $otra)->firstOrFail();
            Storage::disk('public')->put($otra . '.webp', 'imagen');
            $foto = $etapa->fotos()->create(['disk' => 'public', 'path' => $otra . '.webp']);
            $html = view('obras.comisiones.partials.borrar-foto', [
                'obra' => $this->obra, 'comision' => $this->comision, 'etapa' => $etapa, 'foto' => $foto,
                'gestionEtapas' => $service->permisos($this->usuario, $this->obra),
            ])->render();
            $response = $this->delete(route('obras.comisiones.etapas.fotos.borrar', [$this->obra, $this->comision, $otra, $foto]));
            if ($otra === $key) {
                $this->assertStringContainsString('Borrar foto', $html);
                $response->assertSessionHasNoErrors();
                Storage::disk('public')->assertMissing($otra . '.webp');
            } else {
                $this->assertStringNotContainsString('Borrar foto', $html);
                $response->assertForbidden();
                Storage::disk('public')->assertExists($otra . '.webp');
            }
        }
        $this->assertSame(2, ComisionEtapaFoto::count());
    }

    public function test_permiso_general_anterior_no_autoriza_borrar_fotos_de_las_secciones(): void
    {
        $this->permitir('borrar_foto_comision.acces');
        $foto = $this->acero()->fotos()->create(['disk' => 'public', 'path' => 'protegida.webp']);
        $this->delete(route('obras.comisiones.etapas.fotos.borrar', [$this->obra, $this->comision, 'acero', $foto]))->assertForbidden();
        $this->assertSame(1, ComisionEtapaFoto::count());
    }

    private function prepararEdicionFotos(string $key): ComisionEtapa
    {
        $this->permitir('editar_' . $key . '.access');
        $this->comision->etapas()->whereIn('etapa', ['perforacion', 'acero', 'colado'])
            ->update(['estado' => 'completada', 'hora_inicio' => '08:00', 'hora_fin' => '09:00']);
        $this->comision->forceFill(['estado' => 'cerrada', 'cerrada_at' => now()])->save();
        $this->comision->detalles()->update(['vol_concreto' => 8]);
        return $this->comision->etapas()->where('etapa', $key)->firstOrFail();
    }

    /** @dataProvider seccionesConFotos */
    public function test_edicion_admite_fotos_nuevas_y_conserva_existentes_en_cada_etapa(string $key): void
    {
        $etapa = $this->prepararEdicionFotos($key);
        Storage::disk('public')->put('anterior.webp', 'imagen');
        $anterior = $etapa->fotos()->create(['disk' => 'public', 'path' => 'anterior.webp']);
        $this->corregir($key, ['hora_fin' => '11:00', 'vol_concreto' => 8.5,
            'fotos' => [UploadedFile::fake()->image('nueva.png'), UploadedFile::fake()->image('otra.jpg')],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(3, $etapa->fotos()->count());
        $this->assertNotNull($anterior->fresh());
        Storage::disk('public')->assertExists('anterior.webp');
        foreach ($etapa->fotos()->where('id', '!=', $anterior->id)->get() as $foto) {
            $this->assertSame('image/webp', $foto->mime_type);
            $this->assertSame($this->usuario->id, $foto->uploaded_by);
            Storage::disk('public')->assertExists($foto->path);
        }
        $this->assertSame('completada', $etapa->fresh()->estado);
        $this->assertSame('cerrada', $this->comision->fresh()->estado);
    }

    /** @dataProvider seccionesConFotos */
    public function test_edicion_agrega_y_quita_fotos_en_una_sola_operacion(string $key): void
    {
        $etapa = $this->prepararEdicionFotos($key);
        $this->permitir('borrar_fotos_' . $key . '.access');
        Storage::disk('public')->put('quitar.webp', 'imagen');
        Storage::disk('public')->put('conservar.webp', 'imagen');
        $quitar = $etapa->fotos()->create(['disk' => 'public', 'path' => 'quitar.webp']);
        $conservar = $etapa->fotos()->create(['disk' => 'public', 'path' => 'conservar.webp']);
        $this->corregir($key, ['hora_fin' => '11:00', 'vol_concreto' => 8.5,
            'fotos_borrar' => [$quitar->id], 'fotos' => [UploadedFile::fake()->image('nueva.png')],
        ])->assertSessionHasNoErrors();
        $this->assertNull($quitar->fresh());
        $this->assertNotNull($conservar->fresh());
        $this->assertSame(2, $etapa->fotos()->count());
        Storage::disk('public')->assertMissing('quitar.webp');
        Storage::disk('public')->assertExists('conservar.webp');
        $this->assertSame(2, count(Storage::disk('public')->allFiles()));
        $this->assertSame('completada', $etapa->fresh()->estado);
        $this->assertSame('cerrada', $this->comision->fresh()->estado);
    }

    public function test_editar_no_permite_borrar_fotos_sin_el_permiso_de_su_etapa(): void
    {
        $etapa = $this->prepararEdicionFotos('perforacion');
        $this->permitir('borrar_fotos_acero.access');
        Storage::disk('public')->put('protegida.webp', 'imagen');
        $foto = $etapa->fotos()->create(['disk' => 'public', 'path' => 'protegida.webp']);
        $this->corregir('perforacion', ['fotos_borrar' => [$foto->id], 'fotos' => [UploadedFile::fake()->image('nueva.png')]])->assertForbidden();
        $this->assertNotNull($foto->fresh());
        $this->assertSame(['protegida.webp'], Storage::disk('public')->allFiles());
        $this->assertEquals(20, $this->comision->detalles()->sum('metros_sujetos_comision'));
    }

    public function test_editar_rechaza_fotos_ajenas_y_archivos_invalidos_sin_cambios_parciales(): void
    {
        $this->prepararEdicionFotos('perforacion');
        $this->permitir('borrar_fotos_perforacion.access');
        $ajena = $this->acero()->fotos()->create(['disk' => 'public', 'path' => 'otra-etapa.webp']);
        $this->corregir('perforacion', ['fotos_borrar' => [$ajena->id]])->assertSessionHasErrors('fotos_borrar');
        $this->assertNotNull($ajena->fresh());
        foreach ([UploadedFile::fake()->create('texto.txt', 1, 'text/plain'), UploadedFile::fake()->image('grande.png')->size(8193)] as $foto) {
            $this->corregir('perforacion', ['fotos' => [$foto]])->assertSessionHasErrors('fotos.0');
        }
        $this->assertEquals(20, $this->comision->detalles()->sum('metros_sujetos_comision'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_error_al_guardar_edicion_restaura_fotos_retiradas_y_limpia_las_nuevas(): void
    {
        $etapa = $this->prepararEdicionFotos('perforacion');
        $this->permitir('borrar_fotos_perforacion.access');
        Storage::disk('public')->put('restaurar.webp', 'imagen');
        $anterior = $etapa->fotos()->create(['disk' => 'public', 'path' => 'restaurar.webp']);
        DB::unprepared("CREATE TRIGGER fallo_editar BEFORE UPDATE ON comisiones BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");
        $this->corregir('perforacion', ['fotos_borrar' => [$anterior->id], 'fotos' => [UploadedFile::fake()->image('nueva.png')]])
            ->assertSessionHasErrors('etapa')->assertSessionHasInput('fotos_borrar', [$anterior->id]);
        $this->assertNotNull($anterior->fresh());
        $this->assertSame(1, ComisionEtapaFoto::count());
        $this->assertSame(['restaurar.webp'], Storage::disk('public')->allFiles());
        $this->assertEquals(20, $this->comision->detalles()->sum('metros_sujetos_comision'));
        $this->assertSame('cerrada', $this->comision->fresh()->estado);
    }

    /** @dataProvider seccionesConFotos */
    public function test_formulario_edicion_muestra_fotos_y_respeta_permiso_para_quitarlas(string $key): void
    {
        $etapa = $this->prepararEdicionFotos($key);
        $etapa->fotos()->create(['disk' => 'public', 'path' => 'existente.webp']);
        \Illuminate\Support\Facades\Gate::partialMock()->shouldReceive('check')->andReturnUsing(fn ($permiso) => $this->usuario->can($permiso));
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $service = $this->app->make(ComisionEtapaCorreccionService::class);
        $motor = $this->app->make(ComisionEtapasService::class);
        $render = fn () => view('obras.comisiones.partials.formulario-etapa', [
            'obra' => $this->obra, 'comision' => $this->comision, 'etapa' => $etapa,
            'etapaSeleccionada' => $service->formulario($this->usuario, $this->obra, $this->comision, $key),
            'esEdicion' => true, 'etapaLabels' => [$key => $key], 'personalDisponibleEtapa' => $motor->personalDisponible($this->obra),
            'gestionEtapas' => $service->permisos($this->usuario, $this->obra),
        ])->render();
        $html = $render();
        $this->assertStringContainsString('existente.webp', $html);
        $this->assertStringContainsString('name="fotos[]"', $html);
        $this->assertStringNotContainsString('name="fotos_borrar[]"', $html);
        $this->permitir('borrar_fotos_' . $key . '.access');
        $this->assertStringContainsString('name="fotos_borrar[]"', $render());
        $this->assertStringContainsString('Quitar al guardar', $render());
    }
}
