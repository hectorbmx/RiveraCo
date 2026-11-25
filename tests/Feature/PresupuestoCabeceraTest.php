<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Presupuesto;
use App\Models\User;
use App\Support\FoliosPresupuesto;
use App\Support\ImagenesTipoPresupuesto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PresupuestoCabeceraTest extends TestCase
{
    public function test_apupi_suma_jornales_exactos_y_redondea_los_escenarios_como_excel(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));
        $this->withoutExceptionHandling();
        $this->actingAs(User::factory()->make());

        DB::beginTransaction();
        try {
            $presupuesto = Presupuesto::create([
                'codigo_proyecto' => 'PRUEBA-APUPI-' . str()->uuid(),
                'nombre_cliente' => 'Cliente de prueba APUPI',
                'origen' => Presupuesto::ORIGEN_SIRICO,
            ]);
            $base = ['longitud_l0' => 7, 'pilas_por_jornal' => 8, 'avance_metros_por_jornal' => 56];
            $presupuesto->pilasVol()->createMany([
                $base + ['tipo' => 'P1', 'diametro' => 0.8, 'piezas' => 16],
                $base + ['tipo' => 'P2', 'diametro' => 0.6, 'piezas' => 4],
                $base + ['tipo' => 'P3', 'diametro' => 0.6, 'piezas' => 2],
            ]);

            $this->get(route('presupuesto.show', [$presupuesto, 'tab' => 'apupi']))
                ->assertOk()
                ->assertViewHas('duracionApupi', fn (array $duracion) => array_column($duracion, 'jornales') === [3.0, 2.0, 4.0]);
            $this->patchJson(route('presupuesto.apupi.update', [$presupuesto, 'apupi-38']), ['cantidad' => 1, 'precio_unitario' => 1200])
                ->assertOk()
                ->assertJsonPath('filas.apupi-38.realista', 3600)
                ->assertJsonPath('filas.apupi-38.optimista', 2400)
                ->assertJsonPath('filas.apupi-38.pesimista', 4800);
        } finally {
            DB::rollBack();
        }
    }

    public function test_el_alta_asigna_folios_mensuales_y_recupera_la_captura_en_el_modal(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));
        $this->withoutExceptionHandling([ValidationException::class]);
        $disco = Storage::fake('public');

        DB::beginTransaction();
        try {
            $this->travelTo(now('America/Mexico_City')->setDate(2031, 11, 25)->setTime(12, 0));
            $this->actingAs(User::factory()->make());
            $cliente = Cliente::create(['nombre_comercial' => 'Cliente de prueba para presupuesto']);
            Presupuesto::create(['codigo_proyecto' => 'MU-3111-09- R2', 'nombre_cliente' => $cliente->nombre_comercial, 'tipo' => 'MUROS']);
            $listado = route('presupuesto.index', ['buscar' => $cliente->nombre_comercial]);
            $datos = [
                'cliente_id' => $cliente->id,
                'nombre_obra' => 'Obra de prueba para presupuesto',
                'equipo_propuesto' => 'retro',
            ];

            foreach ([$datos, $datos + ['tipo' => 'OTRO']] as $capturaInvalida) {
                $this->from($listado)->post(route('presupuesto.store'), $capturaInvalida)
                    ->assertRedirect($listado)
                    ->assertSessionHasErrorsIn('datosPresupuesto', ['tipo']);
            }

            $this->from($listado)->post(route('presupuesto.store'), array_replace($datos, ['tipo' => 'MUROS', 'nombre_obra' => '']))
                ->assertRedirect($listado)
                ->assertSessionHasErrorsIn('datosPresupuesto', ['nombre_obra']);
            $this->get($listado)->assertOk()
                ->assertSee('x-init="$el.showModal()"', false)
                ->assertSee('value="MUROS" required x-model="tipoElegido" checked', false)
                ->assertSee('value="' . $cliente->id . '" :value="seleccionado', false)
                ->assertSee('MU3111-10')
                ->assertSee('images/SVG/Muros.svg');
            $this->assertDatabaseMissing('presupuesto_folios', ['tipo' => 'MUROS', 'periodo' => '2031-11']);

            $alta = $this->from($listado)->post(route('presupuesto.store'), $datos + ['tipo' => 'MUROS']);
            $presupuesto = Presupuesto::where('codigo_proyecto', 'MU3111-10')->sole();
            $this->assertSame('MUROS', $presupuesto->tipo);
            $this->assertNotNull($presupuesto->folio_serie_id);
            $alta->assertRedirect(route('presupuesto.show', $presupuesto) . '#datos-generales');

            $this->get(route('presupuesto.show', $presupuesto))->assertOk()->assertSee('MUROS');
            $this->get($listado)->assertOk()->assertSee('MU3111-10')->assertSee('MU3111-11');

            $this->patch(route('presupuesto.update', $presupuesto), $datos + ['tipo' => 'POZOS', 'codigo_proyecto' => 'OTRO'])
                ->assertSessionHasErrorsIn('datosPresupuesto', ['tipo', 'codigo_proyecto']);
            $this->patch(route('presupuesto.update', $presupuesto), $datos)->assertRedirect();
            $this->assertSame('MU3111-10', $presupuesto->fresh()->codigo_proyecto);

            $configuracion = route('empresa_config.edit', ['tab' => 'folios', 'folio_seccion' => 'presupuestos', 'folio_periodo' => '2031-11']);
            $imagenes = app(ImagenesTipoPresupuesto::class);
            $this->view('empresa_config.partials.folios-presupuesto', [
                'periodoFoliosPresupuesto' => '2031-11',
                'seriesPresupuesto' => app(FoliosPresupuesto::class)->resumen('2031-11'),
                'imagenesTipoPresupuesto' => $imagenes->rutas(),
            ])->assertSee('Subir imagen para ANCLAS')->assertSee('multipart/form-data')->assertSee('bg-[#0B265A] text-white', false);

            $ajuste = ['tipo' => 'MUROS', 'periodo' => '2031-11'];
            $this->from($configuracion)->patch(route('empresa_config.folios-presupuesto.update'), $ajuste + ['ultimo_consecutivo' => 9])
                ->assertRedirect($configuracion)
                ->assertSessionHasErrorsIn('folioPresupuesto', ['ultimo_consecutivo']);
            $this->patch(route('empresa_config.folios-presupuesto.update'), $ajuste + ['ultimo_consecutivo' => 15])
                ->assertRedirect($configuracion);
            $this->post(route('presupuesto.store'), $datos + ['tipo' => 'MUROS'])->assertRedirect();
            $this->assertDatabaseHas('presupuestos', ['codigo_proyecto' => 'MU3111-16']);

            $carga = $ajuste + ['_method' => 'PATCH'];
            $this->from($configuracion)->post(route('empresa_config.folios-presupuesto.update'),
                $carga + ['ultimo_consecutivo' => 20, 'imagen' => UploadedFile::fake()->create('imagen.svg', 5, 'image/svg+xml')])
                ->assertSessionHasErrorsIn('folioPresupuesto', ['imagen']);
            $this->assertDatabaseHas('presupuesto_folios', $ajuste + ['ultimo_consecutivo' => 16]);
            $this->from($configuracion)->post(route('empresa_config.folios-presupuesto.update'),
                $carga + ['ultimo_consecutivo' => 15, 'imagen' => UploadedFile::fake()->image('muros.png', 640, 320)])
                ->assertSessionHasErrorsIn('folioPresupuesto', ['ultimo_consecutivo']);
            $this->assertSame([], $disco->allFiles());
            $this->post(route('empresa_config.folios-presupuesto.update'),
                $carga + ['ultimo_consecutivo' => 16, 'imagen' => UploadedFile::fake()->image('muros.png', 640, 320)])
                ->assertRedirect($configuracion);
            $imagenGuardada = DB::table('presupuesto_tipo_imagenes')->where('tipo', 'MUROS')->value('archivo');
            $this->assertStringEndsWith('.webp', $imagenGuardada);
            $disco->assertExists($imagenGuardada);
            $tamano = getimagesizefromstring($disco->get($imagenGuardada));
            $this->assertSame([192, 96, IMAGETYPE_WEBP], array_slice($tamano, 0, 3));
            $this->get(route('presupuesto.show', $presupuesto))->assertOk()->assertSee('storage/' . $imagenGuardada);

            $altaPilas = $this->post(route('presupuesto.store'), $datos + ['tipo' => 'PILAS']);
            $pilas = Presupuesto::where('codigo_proyecto', 'PI3111-01')->sole();
            $altaPilas->assertRedirect(route('presupuesto.show', [$pilas, 'tab' => 'pilas']));

            $this->travelTo(now('America/Mexico_City')->addMonth());
            $this->assertSame('MU3112-01', app(FoliosPresupuesto::class)->resumen('2031-12')['MUROS']['siguiente']);
            $this->post(route('presupuesto.store'), $datos + ['tipo' => 'MUROS'])->assertRedirect();
            $this->assertDatabaseHas('presupuestos', ['codigo_proyecto' => 'MU3112-01']);
            $this->get(route('presupuesto.show', $presupuesto))->assertOk()->assertSee('storage/' . $imagenGuardada);

            $this->patch(route('empresa_config.folios-presupuesto.update'),
                $ajuste + ['ultimo_consecutivo' => 16, 'quitar_imagen' => '1'])
                ->assertRedirect($configuracion);
            $this->get(route('presupuesto.show', $presupuesto))->assertOk()
                ->assertDontSee('storage/' . $imagenGuardada)->assertDontSee('images/SVG/Muros.svg');
        } finally {
            $this->travelBack();
            DB::rollBack();
        }
    }
}
