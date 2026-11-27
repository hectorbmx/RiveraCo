<?php

namespace Tests\Feature;

use App\Models\CatalogoPila;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\ObraTipoConfiguracion;
use App\Models\Presupuesto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ObraPerforacionesPresupuestoTest extends TestCase
{
    public function test_traslada_las_perforaciones_aprobadas_y_alimenta_el_avance_de_la_obra(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));

        DB::beginTransaction();
        try {
            $usuario = User::factory()->create();
            $usuario->assignRole(Role::findOrCreate('super-admin', 'web'));
            $usuario->givePermissionTo(Permission::findOrCreate('presupuestos.approve.access', 'web'));
            $this->actingAs($usuario);
            ObraTipoConfiguracion::updateOrCreate(['tipo_obra' => 'PILAS'], [
                'label' => 'Pilas', 'prefijo' => 'PI', 'activo' => true,
            ]);
            $tipos = [['P1', 100, 24, 16.4], ['P2', 120, 135, 16.9], ['P3', 150, 100, 17.2]];
            foreach ($tipos as [, $diametro]) {
                CatalogoPila::updateOrCreate(['codigo' => 'PI' . $diametro], [
                    'descripcion' => 'Pila de ' . $diametro . ' cm', 'diametro_cm' => $diametro, 'activa' => true,
                ]);
            }
            $cliente = Cliente::create(['nombre_comercial' => 'Cliente de perforaciones']);
            $this->post(route('presupuesto.store'), [
                'tipo' => 'PILAS', 'cliente_id' => $cliente->id, 'nombre_obra' => 'Obra desde VOL',
            ])->assertRedirect();
            $presupuesto = Presupuesto::where('cliente_id', $cliente->id)->sole();
            $presupuesto->forceFill(['consecutivo_cliente' => 1])->save();
            $version = $presupuesto->versiones()->create([
                'revision' => 0, 'creado_por' => $usuario->id, 'total_venta' => '1000.00',
                'datos' => [
                    'cliente' => ['id' => $cliente->id],
                    'presupuesto' => ['nombre_obra' => 'Obra desde VOL'],
                    'presentacion' => ['subtotal' => '1000.00'],
                    'vol' => [
                        'filas' => array_map(fn ($fila) => [
                            'pila' => ['tipo' => $fila[0], 'diametro' => $fila[1] / 100, 'piezas' => $fila[2]],
                            'vol' => ['longitud' => $fila[3]],
                        ], $tipos),
                        'totales' => ['metros_lineales' => 4395.10],
                    ],
                ],
            ]);

            $aprobar = route('presupuesto.versiones.aprobar', [$presupuesto, $version]);
            $this->post($aprobar, ['tipo_obra' => 'PILAS'])->assertRedirect();
            $obra = $presupuesto->fresh()->obraGenerada;
            $this->assertInstanceOf(Obra::class, $obra);
            $this->assertSame(3, $obra->pilas()->count());
            $this->post($aprobar, ['tipo_obra' => 'PILAS'])->assertRedirect(route('obras.edit', $obra));
            $this->assertSame(3, $obra->pilas()->count());

            $obra->pilas()->delete();
            $tabPilas = route('obras.edit', ['obra' => $obra, 'tab' => 'pilas']);
            $this->get($tabPilas)->assertOk()->assertSee('Cargar perforaciones del presupuesto aprobado');
            $cargar = route('obras.pilas.presupuesto', $obra);
            $this->post($cargar)->assertRedirect($tabPilas);
            $this->postJson($cargar)->assertUnprocessable()->assertJsonValidationErrors('perforaciones');

            $asignadas = $obra->pilas()->orderBy('numero_pila')->get();
            $this->assertSame([1, 2, 3], $asignadas->pluck('numero_pila')->map(fn ($numero) => (int) $numero)->all());
            $this->assertSame(['PI100', 'PI120', 'PI150'], $asignadas->pluck('tipo')->all());
            $this->assertSame([24, 135, 100], $asignadas->pluck('cantidad_programada')->all());
            $this->assertSame(['16.40', '16.90', '17.20'], $asignadas->pluck('profundidad_proyecto')->all());
            $this->assertStringContainsString('Tipo VOL P1', $asignadas->first()->notas);
            $this->get($tabPilas)->assertOk()->assertSee('PI120')->assertDontSee('Cargar perforaciones del presupuesto aprobado');
            $this->get(route('obras.edit', $obra))->assertOk()
                ->assertSee('0.00 / 4,395.10 m (0%)')->assertSee('0 / 259 pilas (0%)');
        } finally {
            DB::rollBack();
        }
    }
}
