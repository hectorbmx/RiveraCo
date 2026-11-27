<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Presupuesto;
use App\Models\User;
use App\Services\Presupuestos\CalculoPresupuestoPorPartidas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use App\Models\UsuarioApp;
use Tests\TestCase;

class PresupuestoPorPartidasTest extends TestCase
{
    public function test_el_excel_estandar_previsualiza_conceptos_sin_modificar_la_cabecera_ni_el_borrador(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));
        $archivo = tmpfile();
        $ruta = stream_get_meta_data($archivo)['uri'];

        DB::beginTransaction();
        try {
            $this->actingAs(User::factory()->create());
            $cliente = Cliente::create(['nombre_comercial' => 'Cliente de importación']);
            $this->post(route('presupuesto.store'), [
                'tipo' => 'MUROS', 'cliente_id' => $cliente->id, 'nombre_obra' => 'Obra capturada manualmente',
            ])->assertRedirect();
            $presupuesto = Presupuesto::where('cliente_id', $cliente->id)->sole();
            $rutaImportacion = route('presupuesto.partidas.importacion', $presupuesto);

            $libro = new Spreadsheet();
            $hoja = $libro->getActiveSheet();
            $hoja->fromArray([
                ['Código', 'Concepto', 'Unidad', 'Cantidad', 'P. Unitario', 'Importe'],
                [null, 'Pilas de cimentación'],
                [null, 'Muro guía'],
                ['SIOP-001', 'Trazo con equipo', 'M2', 2.11, 15.68, 33.08],
                [null, 'y nivelación del terreno.', null, 0, 0, 0],
                [null, 'Pilas secantes'],
                ['SIOP-002', 'Acarreo', 'M3/KM', 3, 10, 30],
                ['TOTAL DEL PRESUPUESTO MOSTRADO SIN IVA:', null, null, null, null, 63.09],
            ], null, 'A16', true);
            (new Xlsx($libro))->save($ruta);
            $respuesta = $this->withHeader('Accept', 'application/json')->post($rutaImportacion, [
                'archivo' => new UploadedFile($ruta, 'Estándar.xlsx', null, null, true),
            ])->assertOk()
                ->assertJsonPath('data.contexto.0', 'Pilas de cimentación')
                ->assertJsonPath('data.partidas.0.nombre', 'Muro guía')
                ->assertJsonPath('data.partidas.0.subpartidas.0.fila', 19)
                ->assertJsonPath('data.partidas.0.subpartidas.0.codigo', 'SIOP-001')
                ->assertJsonPath('data.partidas.0.subpartidas.0.concepto', 'Trazo con equipo y nivelación del terreno.')
                ->assertJsonPath('data.partidas.1.subpartidas.0.unidad', 'M3/KM')
                ->assertJsonPath('data.cantidad_conceptos', 2)
                ->assertJsonPath('data.subtotal', '63.08')
                ->assertJsonPath('data.subtotal_excel', '63.09');
            $this->assertFalse($presupuesto->partidas()->exists());

            $partidas = array_map(fn ($partida) => [
                'nombre' => $partida['nombre'],
                'subpartidas' => array_map(fn ($fila) => Arr::except($fila, ['fila', 'codigo']), $partida['subpartidas']),
            ], $respuesta->json('data.partidas'));
            $this->patchJson(route('presupuesto.partidas.update', $presupuesto), [
                'revision' => app(CalculoPresupuestoPorPartidas::class)->estadoEditor($presupuesto)['revision'],
                'tasa_iva' => '16', 'partidas' => $partidas,
            ])->assertOk()->assertJsonPath('data.subtotal', '63.08');
            $this->assertSame('Obra capturada manualmente', $presupuesto->fresh()->nombre_obra);
            $this->assertSame(2, $presupuesto->partidas()->count());

            $hoja->setCellValue('E19', '=5+5');
            (new Xlsx($libro))->setPreCalculateFormulas(false)->save($ruta);
            $this->post($rutaImportacion, [
                'archivo' => new UploadedFile($ruta, 'Estándar.xlsx', null, null, true),
            ])->assertUnprocessable()->assertJsonValidationErrors('archivo');
        } finally {
            DB::rollBack();
            fclose($archivo);
        }
    }

    public function test_las_versiones_por_partidas_se_consultan_exportan_restauran_y_duplican(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));

        DB::beginTransaction();
        try {
            $usuario = User::factory()->create();
            $empleado = 1 + (int) DB::table('empleados')->max('id_Empleado');
            DB::table('empleados')->insert(['id_Empleado' => $empleado, 'Nombre' => 'Prueba partidas']);
            UsuarioApp::create(['user_id' => $usuario->id, 'empleado_id' => $empleado, 'is_active' => true]);
            $usuario->givePermissionTo(Permission::findOrCreate('app.gerencial.access', 'web'));
            $this->actingAs($usuario);
            $cliente = Cliente::create(['nombre_comercial' => 'Cliente de la versión por partidas']);
            $this->post(route('presupuesto.store'), [
                'tipo' => 'MUROS', 'cliente_id' => $cliente->id, 'nombre_obra' => 'Real Center de prueba',
            ])->assertRedirect();
            $presupuesto = Presupuesto::where('cliente_id', $cliente->id)->sole();
            $calculo = app(CalculoPresupuestoPorPartidas::class);
            $guardarTabla = route('presupuesto.partidas.update', $presupuesto);
            $guardarVersion = route('presupuesto.versiones.store', $presupuesto);
            $listado = route('presupuesto.index', ['buscar' => $presupuesto->codigo_proyecto]);
            $api = '/api/v1/gerencial/presupuestos/' . $presupuesto->id;
            $fila = ['concepto' => 'Trazo y nivelación', 'unidad' => 'M2', 'cantidad' => '2', 'precio_unitario' => '50'];
            $tabla = [
                ['nombre' => 'Preliminares', 'subpartidas' => [$fila]],
                ['nombre' => 'Anclas', 'subpartidas' => [array_replace($fila, ['concepto' => 'Anclaje', 'unidad' => 'JORNADA', 'cantidad' => '0'])]],
            ];
            $adicionales = [
                ['insert' => 'Pago', 'attributes' => ['bold' => true]],
                ['insert' => " semanal.\n"],
            ];
            $inicial = $calculo->estadoEditor($presupuesto);
            $this->assertCount(11, $inicial['notas']);
            $this->assertSame([], $inicial['nota_ejecucion']);

            $this->get(route('presupuesto.pdf', $presupuesto))->assertRedirect(route('presupuesto.show', $presupuesto));
            $estado = $this->patchJson($guardarTabla, [
                'revision' => $inicial['revision'], 'tasa_iva' => '16', 'partidas' => $tabla,
                'nota_ejecucion' => json_encode($adicionales), 'notas' => ['Vigencia de prueba de 30 días.'],
            ])->assertOk()->assertJsonPath('data.nota_ejecucion', $adicionales)->json('data');
            $this->get(route('presupuesto.show', $presupuesto))->assertOk()
                ->assertSee('value="FLETE"', false)->assertSee('value="HRS"', false)
                ->assertSee('value="TON"', false)->assertSee('value="M"', false)
                ->assertSee('value="JORNADA"', false);
            $this->get($listado)->assertOk()->assertSee('$116.00');
            $this->getJson($api)->assertOk()->assertJsonPath('data.total', 116);
            $this->post($guardarVersion, ['revision' => 0, 'revision_partidas' => $estado['revision']])->assertRedirect();
            $primera = $presupuesto->versiones()->where('revision', 0)->sole();
            $this->assertSame('116.00', $primera->datos['presentacion']['total']);
            $this->assertSame($adicionales, $primera->datos['presentacion']['nota_ejecucion']);
            $this->assertSame(['Vigencia de prueba de 30 días.'], $primera->datos['presentacion']['notas']);
            $this->getJson($api)->assertOk()->assertJsonPath('data.version.id', $primera->id);

            $tabla[0]['id'] = $estado['partidas'][0]['id'];
            $tabla[0]['subpartidas'][0]['id'] = $estado['partidas'][0]['subpartidas'][0]['id'];
            $tabla[0]['subpartidas'][0]['precio_unitario'] = '125';
            $tabla[1]['id'] = $estado['partidas'][1]['id'];
            $tabla[1]['subpartidas'][0]['id'] = $estado['partidas'][1]['subpartidas'][0]['id'];
            $estadoNuevo = $this->patchJson($guardarTabla, [
                'revision' => $estado['revision'], 'tasa_iva' => '16', 'partidas' => $tabla,
            ])->assertOk()->assertJsonPath('data.total', '290.00')
                ->assertJsonPath('data.nota_ejecucion', $adicionales)->json('data');
            $soloNotas = ['revision' => $estadoNuevo['revision'], 'tasa_iva' => '16', 'partidas' => $tabla];
            $this->patchJson($guardarTabla, $soloNotas + [
                'nota_ejecucion' => json_encode([['insert' => 'Inválida', 'attributes' => ['color' => 'invalido']]]),
            ])->assertUnprocessable()->assertJsonValidationErrors('nota_ejecucion.0.attributes.color');
            $notasActualizadas = $this->patchJson($guardarTabla, $soloNotas + ['nota_ejecucion' => '[]', 'notas' => []])
                ->assertOk()->assertJsonPath('data.total', '290.00')->assertJsonPath('data.notas', [])->json('data');
            $this->assertNotSame($estadoNuevo['revision'], $notasActualizadas['revision']);
            $this->patchJson($guardarTabla, $soloNotas)->assertConflict();
            $this->get($listado)->assertOk()->assertSee('$116.00')->assertDontSee('$290.00');
            $this->post($guardarVersion, ['revision' => 1, 'revision_partidas' => $estadoNuevo['revision']])
                ->assertSessionHasErrorsIn('versionPresupuesto', ['revision_partidas']);
            $this->post($guardarVersion, ['revision' => 1, 'revision_partidas' => $notasActualizadas['revision']])->assertRedirect();
            $segunda = $presupuesto->versiones()->where('revision', 1)->sole();
            $this->assertSame('290.00', $segunda->datos['presentacion']['total']);
            $this->assertSame([], $segunda->datos['presentacion']['notas']);
            $this->get($listado)->assertOk()->assertSee('$290.00');

            $this->get(route('presupuesto.versiones.show', [$presupuesto, $primera]))->assertOk()
                ->assertSee('A. Preliminares')->assertSee('B. Anclas')->assertSee('$116.00')->assertDontSee('Pilas / VOL')
                ->assertSee('Notas adicionales')->assertSee('Vigencia de prueba de 30 días.');
            $this->get(route('presupuesto.versiones.index', $presupuesto))->assertOk()->assertSee('$116.00')->assertSee('$290.00');
            $detalle = $this->getJson("$api/versiones/{$primera->id}")->assertOk()
                ->assertJsonPath('data.partidas.0.nombre', 'Preliminares')
                ->assertJsonPath('data.partidas.0.subtotal', 100)
                ->assertJsonPath('data.partidas.1.conceptos.0.importe', 0)
                ->assertJsonPath('data.total', 116);
            $pdf = $this->get($detalle->json('data.pdf_url'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $pdf->getContent());
            $this->get(route('presupuesto.versiones.pdf', [$presupuesto, $primera]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->get(route('presupuesto.pdf', [$presupuesto, 'borrador' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');

            $this->post(route('presupuesto.duplicar', $presupuesto), [
                'version' => $segunda->id, 'cliente_id' => $cliente->id, 'nombre_obra' => 'Copia por partidas',
            ])->assertRedirect();
            $copia = Presupuesto::where('cliente_id', $cliente->id)->where('nombre_obra', 'Copia por partidas')->sole();
            $this->assertTrue($copia->usaEditorPorPartidas());
            $this->assertSame('290.00', $calculo->calcular($copia)['total']);
            $this->assertNotSame($tabla[0]['id'], $copia->partidas()->first()->id);

            $this->post(route('presupuesto.versiones.revisar', [$presupuesto, $primera]), [
                'ultima_revision' => 1, 'motivo' => 'Recuperar la tabla inicial',
            ])->assertRedirect();
            $this->assertSame($primera->id, $presupuesto->fresh()->version_origen_borrador_id);
            $this->assertSame('116.00', $calculo->calcular($presupuesto->fresh())['total']);
            $this->assertSame($adicionales, $calculo->estadoEditor($presupuesto->fresh())['nota_ejecucion']);
            $this->assertSame('290.00', $segunda->fresh()->datos['presentacion']['total']);
        } finally {
            DB::rollBack();
        }
    }

    public function test_el_alta_abre_el_editor_correcto_y_el_guardado_conserva_orden_e_identidad(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));

        DB::beginTransaction();
        try {
            $this->actingAs(User::factory()->create());
            $cliente = Cliente::create(['nombre_comercial' => 'Cliente del editor de partidas']);
            foreach (['PILAS', 'POZOS', 'MUROS', 'ANCLAS'] as $tipo) {
                $this->post(route('presupuesto.store'), [
                    'tipo' => $tipo, 'cliente_id' => $cliente->id, 'nombre_obra' => 'Obra de prueba',
                ])->assertRedirect();
                $presupuesto = Presupuesto::where('cliente_id', $cliente->id)->where('tipo', $tipo)->sole();
                $porPartidas = in_array($tipo, ['MUROS', 'ANCLAS'], true);
                $this->get(route('presupuesto.show', $presupuesto))->assertOk()
                    ->assertViewIs($porPartidas ? 'presupuesto.editor-partidas' : 'presupuesto.show');
                if (! $porPartidas) {
                    $this->patchJson(route('presupuesto.partidas.update', $presupuesto))->assertNotFound();
                }
            }

            $calculo = app(CalculoPresupuestoPorPartidas::class);
            $editor = route('presupuesto.partidas.update', $presupuesto);
            $revision = $calculo->estadoEditor($presupuesto)['revision'];
            $renglon = ['concepto' => 'Trazo y nivelación', 'unidad' => 'M2', 'cantidad' => '11465.67', 'precio_unitario' => '14.28'];
            $partidas = [
                ['nombre' => 'Preliminares', 'subpartidas' => [$renglon]],
                ['nombre' => 'Anclas', 'subpartidas' => []],
            ];
            $datos = ['revision' => $revision, 'tasa_iva' => '16', 'partidas' => $partidas];
            $respuesta = $this->patchJson($editor, $datos)->assertOk()
                ->assertJsonPath('data.partidas.0.subpartidas.0.importe', '163729.77')
                ->assertJsonPath('data.total', '189926.53');
            $this->patchJson($editor, $datos)->assertConflict();
            $guardadas = $respuesta->json('data.partidas');
            $primera = $guardadas[0]['id'];
            $segunda = $guardadas[1]['id'];
            $concepto = $guardadas[0]['subpartidas'][0]['id'];
            $revision = $respuesta->json('data.revision');

            $reordenadas = [
                ['id' => $segunda, 'nombre' => 'Anclas', 'subpartidas' => []],
                ['id' => $primera, 'nombre' => 'Preliminares', 'subpartidas' => [$renglon + ['id' => $concepto]]],
            ];
            $datos = ['revision' => $revision, 'tasa_iva' => '0', 'partidas' => $reordenadas];
            $movidas = $this->patchJson($editor, $datos)->assertOk()
                ->assertJsonPath('data.partidas.0.id', $segunda)
                ->assertJsonPath('data.partidas.1.subpartidas.0.id', $concepto)
                ->assertJsonPath('data.total', '163729.77');
            $this->get(route('presupuesto.show', $presupuesto))->assertOk()
                ->assertSee('Preliminares')->assertSee('163729.77');
            $this->postJson(route('presupuesto.versiones.store', $presupuesto), ['revision' => 0])->assertUnprocessable();
            $this->assertSame(0, $presupuesto->versiones()->count());

            $datos['revision'] = $movidas->json('data.revision');
            $datos['partidas'][0]['subpartidas'][] = $renglon + ['id' => $concepto];
            $datos['partidas'][1]['subpartidas'] = [];
            $this->patchJson($editor, $datos)->assertUnprocessable();
            $this->assertDatabaseHas('presupuesto_subpartidas', ['id' => $concepto, 'presupuesto_partida_id' => $primera]);

            $this->patchJson($editor, ['revision' => $datos['revision'], 'tasa_iva' => '0', 'partidas' => []])
                ->assertOk()->assertJsonPath('data.partidas', [])->assertJsonPath('data.total', '0.00');
            $this->assertDatabaseMissing('presupuesto_subpartidas', ['id' => $concepto]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_estructura_y_calculo_reproducen_los_importes_del_excel_de_real_center(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));

        DB::beginTransaction();
        try {
            $presupuesto = Presupuesto::create([
                'codigo_proyecto' => 'PARTIDAS-' . str()->uuid(),
                'tipo' => 'MUROS',
                'nombre_cliente' => 'Cliente de prueba',
                'origen' => Presupuesto::ORIGEN_SIRICO,
                'estatus' => 'borrador',
            ]);
            $datos = [
                ['PRELIMINARES', [
                    ['11465.67', '14.28'], ['2', '25960'],
                ]],
                ['EXCAVACIÓN Y MOVIMIENTOS DE TIERRAS', [
                    ['300', '408.78'], ['12203.95', '245.75'], ['20', '182.38'],
                    ['1800', '280.64'], ['17.89', '69.95'], ['22.22', '434.03'],
                    ['2', '8902.36'], ['0', '185.25'], ['1756.5', '473.5'],
                ]],
                ['MUROS DE CONTENCIÓN', [
                    ['1756.5', '272.39'], ['16.51', '42188.67'], ['4.12', '42188.67'],
                    ['4.95', '42188.67'], ['1793.96', '1211.84'], ['1246.23', '615.99'],
                    ['1756.5', '543.5'], ['851.26', '97.27'],
                ]],
                ['ANCLAS', [
                    ['48', '32314.07'], ['48', '29695.30'], ['48', '26671.25'],
                    ['48', '23795.55'], ['192', '1366.92'],
                ]],
            ];

            foreach (array_reverse($datos, true) as $orden => [$nombre, $conceptos]) {
                $partida = $presupuesto->partidas()->create(['nombre' => $nombre, 'orden' => $orden]);
                foreach (array_reverse($conceptos, true) as $posicion => [$cantidad, $precio]) {
                    $partida->subpartidas()->create([
                        'concepto' => 'Concepto ' . ($posicion + 1),
                        'unidad' => 'M2',
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'orden' => $posicion,
                    ]);
                }
            }

            $calculo = app(CalculoPresupuestoPorPartidas::class);
            $resultado = $calculo->calcular($presupuesto);
            $this->assertSame(array_column($datos, 0), array_column($resultado['partidas'], 'nombre'));
            $this->assertSame(['215649.77', '4490957.33', '5536756.73', '5661304.80'], array_column($resultado['partidas'], 'subtotal'));
            $this->assertSame('Concepto 1', $resultado['partidas'][0]['subpartidas'][0]['concepto']);
            $this->assertSame('163729.77', $resultado['partidas'][0]['subpartidas'][0]['importe']);
            $this->assertSame('0.00', $resultado['partidas'][1]['subpartidas'][7]['importe']);
            $this->assertSame('15904668.63', $resultado['subtotal']);
            $this->assertSame('16.00', $resultado['tasa_iva']);
            $this->assertSame('2544746.98', $resultado['iva']);
            $this->assertSame('18449415.61', $resultado['total']);

            $presupuesto->presentacion_comercial = ['iva' => 0];
            $this->assertSame('15904668.63', $calculo->calcular($presupuesto)['total']);

            $partida = $presupuesto->partidas->first();
            $this->assertSame($presupuesto->id, $partida->presupuesto->id);
            $this->assertSame($partida->id, $partida->subpartidas->first()->partida->id);
            $partida->delete();
            $this->assertDatabaseMissing('presupuesto_subpartidas', ['presupuesto_partida_id' => $partida->id]);
        } finally {
            DB::rollBack();
        }
    }
}
