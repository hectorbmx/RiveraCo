<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureMobileAppAccess;
use App\Models\User;
use App\Models\UsuarioApp;
use App\Services\Mobile\AppMobileContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Tests\TestCase;

class AppMobileMechanicAccessTest extends TestCase
{
    private User $user;
    private ?UsuarioApp $usuarioApp;
    private array $permisos = ['app.mecanico.access'];
    private array $denegados = [];
    private bool $residente = false;
    private bool $admin = false;
    private string $rol = 'operador';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->user = Mockery::mock(User::class)->makePartial();
        $this->user->id = 80;
        $this->user->email = 'mecanico@example.test';
        $this->user->name = 'Mecánico de prueba';
        $this->user->password = Hash::make('clave-de-prueba');
        $this->user->shouldReceive('hasRole')->with('residente')->andReturnUsing(fn () => $this->residente);
        $this->user->shouldReceive('hasAnyRole')->andReturnUsing(fn () => $this->admin);
        $this->user->shouldReceive('hasDeniedPermission')->andReturnUsing(fn ($p) => in_array($p, $this->denegados, true));
        $this->user->shouldReceive('getRoleNames')->andReturnUsing(fn () => collect($this->residente ? ['residente'] : [$this->rol]));
        $this->user->shouldReceive('getAllPermissions')->andReturnUsing(fn () => collect($this->permisos)->map(fn ($p) => (object) ['name' => $p]));
        $usersQuery = Mockery::mock(Builder::class);
        $usersQuery->shouldReceive('where')->with('email', 'mecanico@example.test')->andReturnSelf();
        $usersQuery->shouldReceive('first')->andReturn($this->user);
        $this->user->shouldReceive('newQuery')->andReturn($usersQuery);
        $this->app->instance(User::class, $this->user);

        $this->usuarioApp = new UsuarioApp(['user_id' => 80, 'is_active' => true]);
        $this->usuarioApp->id = 20;
        $apps = Mockery::mock(UsuarioApp::class)->makePartial();
        $appsQuery = Mockery::mock(Builder::class);
        $appsQuery->shouldReceive('where')->with('user_id', 80)->andReturnSelf();
        $appsQuery->shouldReceive('first')->andReturnUsing(fn () => $this->usuarioApp);
        $apps->shouldReceive('newQuery')->andReturn($appsQuery);
        $this->app->instance(UsuarioApp::class, $apps);

        foreach (['app.regular.access', 'app.gerencial.access', 'app.mecanico.access'] as $permiso) {
            Gate::define($permiso, fn (User $user) => in_array($permiso, $this->permisos, true));
        }

        // Ruta de prueba: el grupo productivo se agregará cuando existan contratos de equipos/servicios.
        Route::middleware(['api', 'auth:sanctum', EnsureMobileAppAccess::class.':mecanico'])
            ->get('/_test/mobile-mecanico', fn () => response()->json(['ok' => true]));
        Route::middleware(['api', 'auth:sanctum', EnsureMobileAppAccess::class.':legacy'])
            ->get('/_test/mobile-legacy', fn () => response()->json(['ok' => true]));
    }

    private function login()
    {
        return $this->postJson('/api/v1/login', ['email' => $this->user->email, 'password' => 'clave-de-prueba']);
    }

    public function test_mechanic_without_worksite_can_login_read_session_and_options(): void
    {
        $this->user->shouldReceive('createToken')->once()->with('mobile')
            ->andReturn(new NewAccessToken(new PersonalAccessToken(), 'TOKEN_DE_PRUEBA'));
        $this->login()->assertOk()->assertJsonPath('token', 'TOKEN_DE_PRUEBA')
            ->assertJsonPath('defaults.panel', 'mecanico')->assertJsonPath('contexto', null)
            ->assertJsonPath('obras_residente', [])->assertJsonMissingPath('gerencial');

        $this->actingAs($this->user, 'sanctum');
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('paneles_disponibles.0.key', 'mecanico')
            ->assertJsonPath('defaults.obra_id', null)->assertJsonMissingPath('token');
        $this->getJson('/api/v1/app/contexto-opciones')->assertOk()
            ->assertJsonPath('paneles.0.key', 'mecanico')->assertJsonMissingPath('paneles_disponibles');
    }

    public function test_permission_works_without_a_mechanic_role_and_role_alone_is_insufficient(): void
    {
        $this->actingAs($this->user, 'sanctum');
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('authz.roles.0', 'operador')
            ->assertJsonPath('defaults.panel', 'mecanico');
        $this->residente = true;
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('defaults.panel', null);
        $this->residente = false;
        $this->rol = 'mecanico';
        $this->permisos = [];
        $this->getJson('/api/v1/me')->assertForbidden();
        $this->getJson('/api/v1/app/contexto-opciones')->assertForbidden();
        $this->login()->assertForbidden();
    }

    public function test_multiple_panels_keep_stable_order_and_require_a_selector(): void
    {
        $this->actingAs($this->user, 'sanctum');
        foreach ([
            [false, ['app.gerencial.access'], ['gerencial'], 'gerencial'],
            [false, ['app.gerencial.access', 'app.mecanico.access'], ['gerencial', 'mecanico'], null],
            [true, ['app.mecanico.access'], ['residente', 'mecanico'], null],
            [true, ['app.gerencial.access'], ['residente', 'gerencial'], null],
            [true, ['app.gerencial.access', 'app.mecanico.access'], ['residente', 'gerencial', 'mecanico'], null],
        ] as [$residente, $permisos, $paneles, $default]) {
            $this->residente = $residente;
            $this->permisos = $permisos;
            $response = $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('defaults.panel', $default);
            $this->assertSame($paneles, array_column($response->json('paneles_disponibles'), 'key'));
        }
    }

    public function test_resident_only_still_requires_a_worksite_unless_context_is_explicitly_omitted(): void
    {
        $this->permisos = [];
        $this->residente = true;
        $this->login()->assertForbidden()->assertJsonPath('message', 'No tienes una obra activa asignada.');
        $this->actingAs($this->user, 'sanctum');
        $this->getJson('/api/v1/me')->assertForbidden();
        $this->getJson('/api/v1/me?with_context=0')->assertOk()->assertJsonPath('defaults.panel', 'residente')
            ->assertJsonPath('contexto', null);
    }

    public function test_mechanic_is_rejected_by_every_registered_legacy_and_management_route_before_binding(): void
    {
        $this->actingAs($this->user, 'sanctum');
        $count = 0;
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/') || str_starts_with($route->uri(), 'api/v1/mecanico/')) {
                continue;
            }
            if (! collect($route->gatherMiddleware())->contains(fn ($m) => str_starts_with($m, EnsureMobileAppAccess::class.':'))) {
                continue;
            }
            $uri = '/'.preg_replace('/\{[^}]+\}/', '999999', $route->uri());
            $this->json($route->methods()[0], $uri)->assertForbidden();
            $count++;
        }
        $this->assertGreaterThan(30, $count);
    }

    public function test_inactive_or_missing_app_user_is_rejected_even_with_existing_authentication(): void
    {
        $this->actingAs($this->user, 'sanctum');
        foreach ([false, null] as $active) {
            if ($active === null) {
                $this->usuarioApp = null;
            } else {
                $this->usuarioApp->is_active = false;
            }
            $this->login()->assertForbidden();
            foreach (['/api/v1/me', '/api/v1/app/contexto-opciones', '/_test/mobile-mecanico', '/api/v1/attendance/logs', '/api/v1/gerencial/maquinas'] as $uri) {
                $this->getJson($uri)->assertForbidden();
            }
        }
    }

    public function test_revocation_and_explicit_denial_take_effect_on_the_next_request(): void
    {
        $this->actingAs($this->user, 'sanctum');
        $this->getJson('/_test/mobile-mecanico')->assertOk();
        $this->denegados = ['app.mecanico.access'];
        $this->getJson('/_test/mobile-mecanico')->assertForbidden();
        $this->getJson('/api/v1/me')->assertForbidden();
        $this->denegados = [];
        $this->permisos = [];
        $this->getJson('/_test/mobile-mecanico')->assertForbidden();
        $this->permisos = ['app.gerencial.access'];
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('defaults.panel', 'gerencial');
    }

    public function test_existing_access_is_preserved_for_residents_admins_and_managers_with_multiple_permissions(): void
    {
        $this->actingAs($this->user, 'sanctum');
        $this->getJson('/_test/mobile-legacy')->assertForbidden();
        $this->residente = true;
        $this->getJson('/_test/mobile-legacy')->assertOk();
        $this->residente = false;
        $this->admin = true;
        $this->getJson('/_test/mobile-legacy')->assertOk();
        $this->admin = false;
        $this->permisos[] = 'app.gerencial.access';
        $this->getJson('/_test/mobile-legacy')->assertOk();
    }

    public function test_guests_and_invalid_credentials_are_rejected(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->getJson('/api/v1/attendance/logs')->assertUnauthorized();
        $this->postJson('/api/v1/login', ['email' => $this->user->email, 'password' => 'incorrecta'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}
