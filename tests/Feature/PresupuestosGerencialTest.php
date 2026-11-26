<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Presupuesto;
use App\Models\PresupuestoVersion;
use App\Models\User;
use App\Models\UsuarioApp;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PresupuestosGerencialTest extends TestCase
{
    private const API = '/api/v1/gerencial/presupuestos';

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));
        DB::beginTransaction();
        $this->beforeApplicationDestroyed(fn () => DB::rollBack());
    }

    public function test_listado_y_detalle_usan_los_mismos_importes_que_la_web(): void
    {
        $usuario = User::factory()->create();
        $this->habilitarApp($usuario);
        $usuario->givePermissionTo(Permission::findOrCreate('app.gerencial.access', 'web'));
        $this->actingAs($usuario, 'web');

        $folio = 'PRUEBA-GER-' . str()->uuid();
        $cliente = Cliente::create(['nombre_comercial' => 'Cliente ' . $folio]);
        $excel = Presupuesto::create([
            'codigo_proyecto' => "$folio-EXCEL", 'nombre_cliente' => 'Cliente de Excel',
            'origen' => Presupuesto::ORIGEN_EXCEL, 'estatus' => 'Sincronizado',
            'total_costo_directo' => 130, 'total_presupuesto' => 232,
        ]);
        $excel->resumenes()->create([
            'partida' => 'Pilas', 'concepto' => 'Perforación importada', 'unidad' => 'm',
            'cantidad' => 2, 'precio_unitario' => 116, 'importe' => 232,
        ]);
        $crearBorrador = fn (string $sufijo) => Presupuesto::create([
            'codigo_proyecto' => "$folio-$sufijo", 'cliente_id' => $cliente->id,
            'nombre_cliente' => $cliente->nombre_comercial, 'nombre_obra' => 'Obra en borrador',
            'tipo' => 'PILAS', 'origen' => Presupuesto::ORIGEN_SIRICO, 'estatus' => 'borrador',
        ]);
        $borrador = $crearBorrador('BORRADOR');
        $borrador->forceFill(['ajustes_propuesta_apupi' => ['venta-218' => ['cantidad' => 1, 'precio_unitario' => 500]]])->save();
        $borradorVacio = $crearBorrador('VACIO');
        $unico = $crearBorrador('UNICO');
        $versionUnica = $this->guardarVersion($unico, $usuario, 0, 100);
        $multiple = $crearBorrador('MULTIPLE');
        $primera = $this->guardarVersion($multiple, $usuario, 0, 200);
        $segunda = $this->guardarVersion($multiple, $usuario, 1, 300);
        $aprobado = $crearBorrador('APROBADO');
        $versionAprobada = $this->guardarVersion($aprobado, $usuario, 0, 400);
        $this->guardarVersion($aprobado, $usuario, 1, 600);
        $aprobado->forceFill(['version_aprobada_id' => $versionAprobada->id, 'estatus' => 'aprobado'])->save();

        $importesWeb = $this->get(route('presupuesto.index', ['buscar' => $folio]))
            ->assertOk()->viewData('importes');
        $listado = $this->getJson(self::API . '?buscar=' . $folio)->assertOk()->assertJsonPath('meta.total', 6);
        $filas = collect($listado->json('data'))->keyBy('id');
        foreach ([
            [$excel, 'excel', null],
            [$borrador, 'borrador', null],
            [$borradorVacio, 'borrador', null],
            [$unico, 'version', $versionUnica],
            [$multiple, 'borrador', null],
            [$aprobado, 'version', $versionAprobada],
        ] as [$presupuesto, $fuente, $version]) {
            $fila = $filas[$presupuesto->id];
            $this->assertSame($fuente, $fila['fuente']);
            $this->assertSame($version?->id, $fila['version']['id'] ?? null);
            foreach (['directo' => 'costo_directo', 'total' => 'total'] as $web => $api) {
                $esperado = $importesWeb[$presupuesto->id][$web];
                $this->assertSame($esperado === null ? null : (float) $esperado, $fila[$api] === null ? null : (float) $fila[$api]);
            }
            $this->getJson(self::API . "/{$presupuesto->id}")->assertOk()
                ->assertJsonPath('data.fuente', $fuente)->assertJsonPath('data.total', $fila['total']);
        }

        $this->getJson(self::API . '?buscar=' . $folio . '&tipo=PILAS&per_page=2&page=2')
            ->assertOk()->assertJsonPath('meta.total', 5)->assertJsonPath('meta.current_page', 2)->assertJsonCount(2, 'data');
        $this->getJson(self::API . '?tipo=OTRO')->assertUnprocessable()->assertJsonValidationErrors('tipo');
        $this->getJson(self::API . "/{$excel->id}")->assertOk()->assertJsonPath('data.subtotal', null)
            ->assertJsonPath('data.iva', null)->assertJsonPath('data.conceptos.0.importe', 232)
            ->assertJsonPath('data.pdf_disponible', false);
        $detalleBorrador = $this->getJson(self::API . "/{$borrador->id}")->assertOk()
            ->assertJsonPath('data.total', 580)->assertJsonPath('data.pdf_disponible', true);
        $this->get($detalleBorrador->json('data.pdf_url'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->getJson(self::API . "/{$borradorVacio->id}/pdf")->assertUnprocessable();
        $this->getJson(self::API . "/{$multiple->id}/versiones")->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $segunda->id)->assertJsonPath('data.1.ultimo_evento.tipo', 'guardado');
        $this->getJson(self::API . "/{$unico->id}/versiones/{$primera->id}")->assertNotFound();
        $this->getJson(self::API . "/{$unico->id}/versiones/{$primera->id}/pdf")->assertNotFound();
        $detalle = $this->getJson(self::API . "/{$multiple->id}/versiones/{$primera->id}")
            ->assertOk()->assertJsonPath('data.obra', 'Propuesta guardada')
            ->assertJsonPath('data.subtotal', 200)->assertJsonPath('data.iva', 32)->assertJsonPath('data.total', 232);
        $pdf = $this->get($detalle->json('data.pdf_url'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertStringContainsString('Presupuesto_', $pdf->headers->get('Content-Disposition'));
        $this->get(route('presupuesto.versiones.pdf', [$multiple, $primera->id]))->assertOk()
            ->assertHeader('Content-Disposition', $pdf->headers->get('Content-Disposition'));
        $this->getJson(self::API . "/{$aprobado->id}/pdf?borrador=1")->assertConflict();
    }

    public function test_las_rutas_de_consulta_y_pdf_exigen_acceso_gerencial_activo(): void
    {
        $usuario = User::factory()->create();
        $acceso = $this->habilitarApp($usuario);
        $cliente = Cliente::create(['nombre_comercial' => 'Cliente de prueba de acceso']);
        $presupuesto = Presupuesto::create([
            'codigo_proyecto' => 'PERFILES-' . str()->uuid(), 'cliente_id' => $cliente->id,
            'nombre_cliente' => $cliente->nombre_comercial, 'origen' => Presupuesto::ORIGEN_SIRICO,
            'estatus' => 'borrador', 'tipo' => 'PILAS',
        ]);
        $version = $this->guardarVersion($presupuesto, $usuario, 0, 100);
        $base = "/{$presupuesto->id}";
        $rutas = ['', $base, "$base/pdf", "$base/versiones", "$base/versiones/{$version->id}", "$base/versiones/{$version->id}/pdf"];
        $consultar = function (string $ruta) {
            $this->app['auth']->forgetGuards();

            return $this->getJson(self::API . $ruta);
        };

        foreach ($rutas as $ruta) {
            $consultar($ruta)->assertUnauthorized();
        }

        $usuario->givePermissionTo(Permission::findOrCreate('app.regular.access', 'web'));
        $this->withToken($usuario->createToken('Prueba de acceso móvil')->plainTextToken);
        foreach ($rutas as $ruta) {
            $consultar($ruta)->assertForbidden();
        }

        $permisoGerencial = Permission::findOrCreate('app.gerencial.access', 'web');
        $usuario->givePermissionTo($permisoGerencial);
        foreach ($rutas as $ruta) {
            $respuesta = $consultar($ruta)->assertOk();
            if (str_ends_with($ruta, '/pdf')) {
                $respuesta->assertHeader('Content-Type', 'application/pdf');
            }
        }

        $usuario->revokePermissionTo($permisoGerencial);
        foreach ($rutas as $ruta) {
            $consultar($ruta)->assertForbidden();
        }

        $usuario->givePermissionTo($permisoGerencial);
        $acceso->update(['is_active' => false]);
        foreach ($rutas as $ruta) {
            $consultar($ruta)->assertForbidden();
        }
    }

    private function habilitarApp(User $usuario): UsuarioApp
    {
        $empleadoId = 1 + (int) DB::table('empleados')->max('id_Empleado');
        DB::table('empleados')->insert(['id_Empleado' => $empleadoId, 'Nombre' => 'Prueba gerencial']);

        return UsuarioApp::create(['user_id' => $usuario->id, 'empleado_id' => $empleadoId, 'is_active' => true]);
    }

    private function guardarVersion(Presupuesto $presupuesto, User $usuario, int $revision, int $subtotal): PresupuestoVersion
    {
        $presupuesto->asignarConsecutivoCliente();
        $iva = $subtotal * 0.16;
        $version = $presupuesto->versiones()->create([
            'revision' => $revision,
            'creado_por' => $usuario->id,
            'total_venta' => $subtotal,
            'datos' => [
                'cliente' => ['nombre_comercial' => $presupuesto->nombre_cliente],
                'presupuesto' => [
                    'codigo_proyecto' => $presupuesto->codigo_proyecto, 'nombre_obra' => 'Propuesta guardada',
                    'equipo' => null, 'total_costo_directo' => 80,
                ],
                'presentacion' => [
                    'filas' => [[
                        'partida' => 'traslado', 'concepto' => 'Traslado de equipo', 'unidad' => 'servicio',
                        'cantidad' => 1, 'precio_unitario' => $subtotal, 'importe' => $subtotal,
                    ]],
                    'subtotal' => $subtotal, 'tasa_iva' => 16, 'iva' => $iva, 'total' => $subtotal + $iva,
                ],
            ],
        ]);
        $version->eventos()->create(['tipo' => 'guardado', 'registrado_por' => $usuario->id]);

        return $version;
    }
}
