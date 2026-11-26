<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Obra;
use App\Models\ObraEmpleado;
use App\Models\User;
use App\Models\UsuarioApp;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardGerencialEmpleadosTest extends TestCase
{
    public function test_agrupar_asignaciones_activas_por_obra_coincide_con_el_conteo_del_dashboard(): void
    {
        $this->assertSame('rivera_v2_testing', DB::connection()->getDatabaseName());
        $this->assertSame('127.0.0.1', DB::connection()->getConfig('host'));

        DB::beginTransaction();
        try {
            $ruta = '/api/v1/gerencial/dashboard';
            $this->getJson("$ruta/empleados-en-obra")->assertUnauthorized();

            $usuario = User::factory()->create();
            $inicio = 1 + (int) DB::table('empleados')->max('id_Empleado');
            foreach (['Ana', 'Carlos', 'Beatriz', 'Diego'] as $indice => $nombre) {
                DB::table('empleados')->insert([
                    'id_Empleado' => $inicio + $indice, 'Nombre' => $nombre, 'Apellidos' => 'Prueba',
                ]);
            }
            UsuarioApp::create(['user_id' => $usuario->id, 'empleado_id' => $inicio, 'is_active' => true]);
            $this->actingAs($usuario, 'sanctum');
            $this->getJson("$ruta/empleados-en-obra")->assertForbidden();
            $usuario->givePermissionTo(Permission::findOrCreate('app.gerencial.access', 'web'));

            $cliente = Cliente::create(['nombre_comercial' => 'Cliente del resumen gerencial']);
            $crearObra = fn (string $nombre) => Obra::create([
                'nombre' => $nombre, 'clave_obra' => 'EMP-' . str()->random(10),
                'cliente_id' => $cliente->id, 'estatus_nuevo' => Obra::ESTATUS_EJECUCION,
            ]);
            $norte = $crearObra('Obra Norte');
            $sur = $crearObra('Obra Sur');
            $asignar = fn (Obra $obra, int $indice, bool $activo = true, ?string $baja = null) => ObraEmpleado::create([
                'obra_id' => $obra->id, 'empleado_id' => $inicio + $indice,
                'activo' => $activo, 'fecha_alta' => '2026-11-01', 'fecha_baja' => $baja,
            ]);
            $asignar($norte, 1);
            $asignar($norte, 0);
            $asignar($sur, 2);
            $asignar($sur, 3, false);
            $asignar($sur, 3, true, '2026-11-02');

            $total = $this->getJson($ruta)->assertOk()->json('data.empleados_presentes');
            $obras = collect($this->getJson("$ruta/empleados-en-obra")->assertOk()->json('data'))->keyBy('id');
            $this->assertSame($total, $obras->sum(fn (array $obra) => count($obra['empleados'])));
            $this->assertSame(['Ana Prueba', 'Carlos Prueba'], array_column($obras[$norte->id]['empleados'], 'nombre'));
            $this->assertSame(['Beatriz Prueba'], array_column($obras[$sur->id]['empleados'], 'nombre'));
        } finally {
            DB::rollBack();
        }
    }
}
