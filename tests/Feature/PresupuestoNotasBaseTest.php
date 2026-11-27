<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Presupuesto;
use App\Models\User;
use App\Services\Presupuestos\CalculoPresupuestoPorPartidas;
use App\Services\Presupuestos\PdfPresupuesto;
use App\Support\PlantillasNotasPresupuesto;
use App\Support\PresentacionPresupuesto;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PresupuestoNotasBaseTest extends TestCase
{
    public function test_la_plantilla_se_copia_al_borrador_y_el_pdf_de_version_usa_el_texto_guardado(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));
        DB::beginTransaction();
        try {
            $usuario = User::factory()->create();
            $this->actingAs($usuario);
            $this->get(route('presupuesto.notas.edit'))->assertForbidden();
            $usuario->givePermissionTo(Permission::findOrCreate('empresa.access', 'web'));
            $this->get(route('presupuesto.index'))->assertOk()->assertSee('aria-label="Notas base"', false);
            $this->get(route('presupuesto.notas.edit'))->assertOk()
                ->assertSee('Pilas')->assertSee('Pozos')->assertSee('Muros y Anclas');
            $this->get(route('presupuesto.notas.edit', 'POZOS'))->assertOk()->assertSee('Nota 1 · Patente');
            $this->putJson(route('presupuesto.notas.update', 'POZOS'), [
                'notas' => json_encode([['regla' => 'exclusiones']]),
            ])->assertUnprocessable();
            $this->guardarPlantilla('POZOS', [['texto' => 'Condición original de Pozos.']]);

            $cliente = Cliente::create(['nombre_comercial' => 'Cliente de plantillas']);
            $presupuesto = $this->crearPresupuesto($cliente, 'POZOS', 'Primera cotización');
            $calculo = app(CalculoPresupuestoPorPartidas::class);
            $estado = $this->patchJson(route('presupuesto.partidas.update', $presupuesto), [
                'revision' => $calculo->estadoEditor($presupuesto)['revision'],
                'tasa_iva' => '16',
                'partidas' => [['nombre' => 'Pozos', 'subpartidas' => [
                    ['concepto' => 'Pozo de infiltración', 'unidad' => 'PZA', 'cantidad' => '1', 'precio_unitario' => '1000'],
                ]]],
            ])->assertOk()->json('data');
            $this->assertSame(['Condición original de Pozos.'], $estado['notas']);
            $this->post(route('presupuesto.versiones.store', $presupuesto), [
                'revision' => 0, 'revision_partidas' => $estado['revision'],
            ])->assertRedirect();
            $version = $presupuesto->versiones()->sole();
            $notasEmitidas = [PlantillasNotasPresupuesto::PATENTE_POZOS, 'Condición original de Pozos.'];
            $this->assertSame($notasEmitidas, $version->datos['presentacion']['notas']);
            $this->get(route('presupuesto.versiones.pdf', [$presupuesto, $version]))
                ->assertOk()->assertHeader('Content-Type', 'application/pdf');

            $this->guardarPlantilla('POZOS', [['texto' => 'Condición nueva de Pozos.']]);
            $this->assertSame($notasEmitidas, $calculo->presentacion($presupuesto->fresh())['notas']);
            $nuevo = $this->crearPresupuesto($cliente, 'POZOS', 'Cotización nueva');
            $this->assertSame([PlantillasNotasPresupuesto::PATENTE_POZOS, 'Condición nueva de Pozos.'],
                $calculo->presentacion($nuevo)['notas']);
            $this->get(route('presupuesto.show', $presupuesto))->assertOk()
                ->assertViewHas('notasBaseVigentes', ['Condición nueva de Pozos.']);

            $this->mock(PdfPresupuesto::class)->shouldReceive('deVersion')
                ->once()
                ->withArgs(fn ($p, $v, $presentacion) => $p->id === $presupuesto->id && $v->id === $version->id
                    && $presentacion['notas'] === $notasEmitidas)
                ->andReturn(response('PDF'));
            $this->app['router']->getRoutes()->getByName('presupuesto.versiones.pdf')->flushController();
            $this->get(route('presupuesto.versiones.pdf', [$presupuesto, $version]))->assertOk();
        } finally {
            DB::rollBack();
        }
    }

    public function test_las_reglas_de_pilas_se_resuelven_con_el_equipo_del_borrador(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));
        DB::beginTransaction();
        try {
            $usuario = User::factory()->create();
            $usuario->givePermissionTo(Permission::findOrCreate('empresa.access', 'web'));
            $this->actingAs($usuario);
            $this->guardarPlantilla('PILAS', [['regla' => 'peso_acceso'], ['texto' => 'Condición de Pilas.']]);
            $cliente = Cliente::create(['nombre_comercial' => 'Cliente de Pilas']);
            $presupuesto = $this->crearPresupuesto($cliente, 'PILAS', 'Prueba de reglas');
            $preparar = fn () => PresentacionPresupuesto::preparar(
                $presupuesto, collect(), ['propuesta' => ['filas' => []]], ['realista' => ['semanas' => 1]]
            )['notas'];
            $presupuesto->equipo_propuesto = 'bauer_bg12';
            $this->assertSame(['El terreno y sus accesos deberán soportar aproximadamente 60 toneladas.', 'Condición de Pilas.'], $preparar());
            $presupuesto->equipo_propuesto = 'retro';
            $this->assertStringContainsString('30 toneladas', $preparar()[0]);

            $this->guardarPlantilla('PILAS', [['texto' => 'Condición actualizada de Pilas.']]);
            $this->assertSame('Condición de Pilas.', $preparar()[1]);
            $this->post(route('presupuesto.presentacion.notas-base', $presupuesto), ['tab' => 'presupuesto'])
                ->assertRedirect(route('presupuesto.show', [$presupuesto, 'tab' => 'presupuesto']));
            $presupuesto->refresh();
            $this->assertSame(['Condición actualizada de Pilas.'], $preparar());
        } finally {
            DB::rollBack();
        }
    }

    private function guardarPlantilla(string $grupo, array $notas): void
    {
        $this->put(route('presupuesto.notas.update', $grupo), ['notas' => json_encode($notas)])
            ->assertRedirect(route('presupuesto.notas.edit', $grupo));
    }

    private function crearPresupuesto(Cliente $cliente, string $tipo, string $nombre): Presupuesto
    {
        $this->post(route('presupuesto.store'), [
            'tipo' => $tipo, 'cliente_id' => $cliente->id, 'nombre_obra' => $nombre,
        ])->assertRedirect();

        return Presupuesto::where('cliente_id', $cliente->id)->where('nombre_obra', $nombre)->sole();
    }
}
