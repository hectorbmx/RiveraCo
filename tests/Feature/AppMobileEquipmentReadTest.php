<?php

namespace Tests\Feature;

use App\Models\Maquina;
use App\Models\User;
use App\Models\UsuarioApp;
use App\Models\Vehiculo;
use App\Services\Mobile\AppMobileAccessService;
use App\Services\Mobile\Equipos\MaquinasConsultaService;
use App\Services\Mobile\Equipos\VehiculosConsultaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class AppMobileEquipmentReadTest extends TestCase
{
    private array $permisos = ['app.mecanico.access'];

    private bool $denegar = false;

    private array $requests = [];

    private array $payloads = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 80;
        $user->shouldReceive('hasRole')->andReturn(false);
        $user->shouldReceive('hasAnyRole')->andReturn(false);
        $user->shouldReceive('hasDeniedPermission')->andReturnUsing(fn () => $this->denegar);
        foreach (['app.regular.access', 'app.gerencial.access', 'app.mecanico.access'] as $permiso) {
            Gate::define($permiso, fn (User $user) => in_array($permiso, $this->permisos, true));
        }
        $this->actingAs($user, 'sanctum');
        $this->mock(AppMobileAccessService::class)->shouldReceive('usuarioActivo')
            ->andReturn(new UsuarioApp(['is_active' => true]));

        $asignacion = ['id' => 3, 'vehiculo_id' => 7, 'empleado_id' => 10,
            'fecha_asignacion' => '2026-10-01', 'fecha_fin' => null, 'km_inicial' => 1000, 'km_final' => null,
            'notas' => 'Nota administrativa', 'fotos' => [['url' => 'documento-interno']],
            'empleado' => ['id' => 10, 'nombre' => 'Operador', 'puesto' => 'Operador', 'celular' => 'privado']];
        $maquina = ['id' => 17, 'codigo' => 'M-17', 'nombre' => 'Equipo', 'estado' => 'operativa', 'asignada' => false, 'obra_activa' => null];
        $vehiculo = ['id' => 7, 'marca' => 'Marca', 'modelo' => 'Modelo', 'placas' => 'PRUEBA', 'asignado' => true,
            'asignacion_actual' => $asignacion, 'seguro_vigente' => ['poliza_numero' => 'POLIZA_INTERNA'],
            'tarjeta_circulacion' => ['archivo_url' => 'documento-interno']];
        $historial = [['id' => 9, 'tipo' => 'programado', 'estatus' => 'completado', 'costo_total' => 1250,
            'mecanico' => ['id' => 11, 'nombre' => 'Mecánico']]];
        $resumen = ['total' => 1, 'pendiente' => 0, 'en_proceso' => 0, 'completado' => 1, 'cancelado' => 0];
        $meta = ['current_page' => 2, 'last_page' => 3, 'per_page' => 2, 'total' => 6];
        foreach (['maquinas' => $maquina, 'vehiculos' => $vehiculo] as $tipo => $item) {
            $this->payloads[$tipo.'.index'] = ['ok' => true,
                'data' => new LengthAwarePaginator([$item], 6, 2, 2), 'meta' => $meta];
            $this->payloads[$tipo.'.show'] = ['ok' => true, 'data' => [
                $tipo === 'maquinas' ? 'maquina' : 'vehiculo' => $item,
                $tipo === 'maquinas' ? 'asignacion_activa' : 'asignacion_actual' => $tipo === 'maquinas' ? null : $asignacion,
                'mantenimientos_recientes' => collect($historial), 'mantenimientos_resumen' => $resumen,
                'asignaciones_recientes' => [$asignacion], 'seguro_vigente' => ['poliza_numero' => 'POLIZA_INTERNA'],
                'seguros_recientes' => [], 'documentos' => ['documento-interno'], 'documentos_resumen' => [],
            ]];
        }
        foreach (['maquinas' => MaquinasConsultaService::class, 'vehiculos' => VehiculosConsultaService::class] as $tipo => $clase) {
            $this->mock($clase, function ($mock) use ($tipo) {
                $mock->shouldReceive('index')->andReturnUsing(function ($request) use ($tipo) {
                    $this->requests[$tipo] = $request->query();

                    return $this->payloads[$tipo.'.index'];
                });
                $mock->shouldReceive('show')->andReturnUsing(fn () => $this->payloads[$tipo.'.show']);
            });
        }
        foreach (['maquina' => [Maquina::class, 17], 'vehiculo' => [Vehiculo::class, 7]] as $parametro => [$clase, $id]) {
            Route::bind($parametro, function ($value) use ($clase, $id) {
                if ((int) $value !== $id) {
                    throw (new ModelNotFoundException)->setModel($clase, [$value]);
                }
                $model = new $clase;
                $model->id = $id;

                return $model;
            });
        }
    }

    private function rutas(): array
    {
        return ['/api/v1/mecanico/maquinas', '/api/v1/mecanico/maquinas/17',
            '/api/v1/mecanico/vehiculos', '/api/v1/mecanico/vehiculos/7'];
    }

    public function test_mechanic_can_list_all_equipment_without_worksite_and_pass_filters_to_shared_queries(): void
    {
        foreach (['maquinas' => 'asignada', 'vehiculos' => 'asignado'] as $tipo => $filtro) {
            $query = ['q' => 'Equipo', 'estatus' => 'operativa', $filtro => 0, 'per_page' => 2, 'page' => 2];
            $response = $this->getJson('/api/v1/mecanico/'.$tipo.'?'.http_build_query($query))->assertOk()
                ->assertJsonPath('meta.current_page', 2)->assertJsonPath('meta.per_page', 2)
                ->assertJsonPath('meta.total', 6)->assertJsonCount(1, 'data')
                ->assertJsonMissingPath('data.0.seguro_vigente')->assertJsonMissingPath('data.0.tarjeta_circulacion');
            $this->assertEquals($query, $this->requests[$tipo]);
            $this->assertIsArray($response->json('data'));
        }
    }

    public function test_detail_exposes_history_and_costs_without_insurance_documents_or_contact_details(): void
    {
        foreach (['maquinas' => 17, 'vehiculos' => 7] as $tipo => $id) {
            $response = $this->getJson('/api/v1/mecanico/'.$tipo.'/'.$id)->assertOk()
                ->assertJsonPath('data.mantenimientos_recientes.0.id', 9)
                ->assertJsonPath('data.mantenimientos_recientes.0.costo_total', 1250)
                ->assertJsonPath('data.mantenimientos_resumen.total', 1)
                ->assertJsonMissingPath('data.seguros_recientes')->assertJsonMissingPath('data.seguro_vigente')
                ->assertJsonMissingPath('data.documentos')->assertJsonMissingPath('data.asignaciones_recientes');
            if ($tipo === 'vehiculos') {
                $response->assertJsonPath('data.asignacion_actual.empleado.nombre', 'Operador')
                    ->assertJsonMissingPath('data.asignacion_actual.empleado.celular')
                    ->assertJsonMissingPath('data.asignacion_actual.notas')->assertJsonMissingPath('data.asignacion_actual.fotos')
                    ->assertJsonMissingPath('data.vehiculo.asignacion_actual');
            }
        }
    }

    public function test_resident_or_management_permissions_alone_do_not_grant_mechanic_routes(): void
    {
        foreach ([[], ['app.regular.access'], ['app.gerencial.access']] as $permisos) {
            $this->permisos = $permisos;
            foreach ($this->rutas() as $ruta) {
                $this->getJson($ruta)->assertForbidden();
            }
        }
        $this->assertSame([], $this->requests);
    }

    public function test_denied_or_revoked_permission_is_checked_on_every_request(): void
    {
        $this->getJson('/api/v1/mecanico/maquinas')->assertOk();
        $this->denegar = true;
        $this->getJson('/api/v1/mecanico/maquinas')->assertForbidden();
        $this->denegar = false;
        $this->permisos = [];
        $this->getJson('/api/v1/mecanico/maquinas')->assertForbidden();
    }

    public function test_inactive_user_cannot_read_equipment(): void
    {
        $this->mock(AppMobileAccessService::class)->shouldReceive('usuarioActivo')
            ->andThrow(new HttpResponseException(response()->json(['ok' => false], 403)));
        foreach ($this->rutas() as $ruta) {
            $this->getJson($ruta)->assertForbidden();
        }
        $this->assertSame([], $this->requests);
    }

    public function test_unknown_equipment_returns_not_found_for_authorized_users(): void
    {
        $this->getJson('/api/v1/mecanico/maquinas/999')->assertNotFound();
        $this->getJson('/api/v1/mecanico/vehiculos/999')->assertNotFound();
    }

    public function test_mechanic_contracts_are_read_only(): void
    {
        $this->postJson('/api/v1/mecanico/maquinas')->assertStatus(405);
        $this->patchJson('/api/v1/mecanico/vehiculos/7')->assertStatus(405);
    }

    public function test_management_controllers_preserve_shared_payload_including_existing_pagination_shape(): void
    {
        $this->permisos = ['app.gerencial.access'];
        foreach (['maquinas' => 17, 'vehiculos' => 7] as $tipo => $id) {
            $this->getJson('/api/v1/gerencial/'.$tipo)->assertOk()
                ->assertExactJson(json_decode(json_encode($this->payloads[$tipo.'.index']), true));
            $this->getJson('/api/v1/gerencial/'.$tipo.'/'.$id)->assertOk()
                ->assertExactJson(json_decode(json_encode($this->payloads[$tipo.'.show']), true));
        }
    }

    public function test_guests_cannot_read_any_mechanic_equipment_route(): void
    {
        auth('sanctum')->forgetUser();
        foreach ($this->rutas() as $ruta) {
            $this->getJson($ruta)->assertUnauthorized();
        }
    }
}
