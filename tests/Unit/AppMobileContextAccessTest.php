<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UsuarioApp;
use App\Services\Mobile\AppMobileContextService;
use Mockery;
use PHPUnit\Framework\TestCase;

class AppMobileContextAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function usuario(bool $residente = false, bool $admin = false, array $permisos = []): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->with('residente')->andReturn($residente);
        $user->shouldReceive('hasAnyRole')->andReturn($admin);
        $user->shouldReceive('can')->andReturnUsing(fn ($permiso) => in_array($permiso, $permisos, true));

        return $user;
    }

    public function test_residents_keep_access_without_the_new_permission(): void
    {
        $service = new AppMobileContextService();
        $user = $this->usuario(residente: true);

        $this->assertTrue($service->puedeVerPanelResidente($user));
        $this->assertFalse($service->puedeVerGerencial($user));
        $this->assertFalse($service->puedeVerTodasLasObrasResidente($user));
    }

    public function test_regular_permission_enables_only_the_regular_panel(): void
    {
        $service = new AppMobileContextService();
        $user = $this->usuario(permisos: ['app.regular.access']);

        $this->assertTrue($service->puedeVerPanelResidente($user));
        $this->assertFalse($service->puedeVerGerencial($user));
        $this->assertFalse($service->puedeVerTodasLasObrasResidente($user));
        $opciones = $service->opciones($user, new UsuarioApp());
        $this->assertSame(['residente'], array_column($opciones['paneles'], 'key'));
        $this->assertSame('residente', $opciones['defaults']['panel']);
        $this->assertNull($opciones['defaults']['obra_id']);
        $this->assertNull($service->contextoResidente($user, new UsuarioApp()));
    }

    public function test_no_role_or_permission_does_not_grant_access(): void
    {
        $service = new AppMobileContextService();
        $user = $this->usuario();

        $this->assertFalse($service->puedeVerPanelResidente($user));
        $this->assertFalse($service->puedeVerGerencial($user));
        $this->assertSame([], $service->opciones($user, new UsuarioApp())['paneles']);
    }

    public function test_manager_permission_does_not_enable_regular_access(): void
    {
        $service = new AppMobileContextService();
        $user = $this->usuario(permisos: ['app.gerencial.access']);

        $this->assertFalse($service->puedeVerPanelResidente($user));
        $this->assertTrue($service->puedeVerGerencial($user));
        $this->assertSame(['gerencial'], array_column($service->opciones($user, new UsuarioApp())['paneles'], 'key'));
    }

    public function test_administrators_keep_existing_regular_access(): void
    {
        $service = new AppMobileContextService();
        $user = $this->usuario(admin: true);

        $this->assertTrue($service->puedeVerPanelResidente($user));
        $this->assertTrue($service->puedeVerTodasLasObrasResidente($user));
    }

    public function test_users_with_both_permissions_can_choose_either_panel(): void
    {
        $service = new AppMobileContextService();
        $user = $this->usuario(permisos: ['app.regular.access', 'app.gerencial.access']);
        $opciones = $service->opciones($user, new UsuarioApp());

        $this->assertSame(['residente', 'gerencial'], array_column($opciones['paneles'], 'key'));
        $this->assertNull($opciones['defaults']['panel']);
        $this->assertFalse($service->puedeVerTodasLasObrasResidente($user));
    }
}
