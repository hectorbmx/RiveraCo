<?php

namespace Tests\Unit;

use App\Models\Comision;
use App\Models\Empleado;
use App\Models\Obra;
use App\Models\User;
use App\Services\Comisiones\ComisionEtapasService;
use App\Services\Comisiones\ComisionesWebService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class ComisionesWebServiceTest extends TestCase
{
    private function user(bool $permission, bool $admin = false, ?Empleado $empleado = null): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('obras.edit')->andReturn($permission);
        $user->shouldReceive('hasAnyRole')->with(['super-admin', 'admin-rivera'])->andReturn($admin);
        $user->setRelation('empleado', $empleado);

        return $user;
    }

    public function test_web_can_continue_without_an_active_mobile_account(): void
    {
        $user = $this->user(true, true);
        $user->shouldNotReceive('usuarioApp');
        $obra = new Obra();
        $comision = new Comision();
        $payload = ['estado' => 'completada'];
        $etapas = Mockery::mock(ComisionEtapasService::class);
        $etapas->shouldReceive('updateEtapa')->once()->with($user, $obra, $comision, 'acero', $payload)->andReturn(['id' => 112]);

        $this->assertSame(['id' => 112], (new ComisionesWebService($etapas))->updateEtapaForUser($user, $obra, $comision, 'acero', $payload));
    }

    public function test_missing_permission_blocks_even_an_administrator(): void
    {
        $etapas = Mockery::mock(ComisionEtapasService::class);
        $etapas->shouldNotReceive('updateEtapa');
        $this->expectException(AuthorizationException::class);

        (new ComisionesWebService($etapas))->updateEtapaForUser($this->user(false, true), new Obra(), new Comision(), 'acero', []);
    }

    public function test_permission_does_not_grant_access_to_another_area(): void
    {
        $empleado = new Empleado();
        $empleado->forceFill(['Area' => 1, 'puesto_base' => 'ADMINISTRATIVO']);
        $obra = new Obra();
        $obra->area_id = 2;
        $etapas = Mockery::mock(ComisionEtapasService::class);
        $etapas->shouldNotReceive('updateEtapa');
        $this->expectException(AuthorizationException::class);

        (new ComisionesWebService($etapas))->updateEtapaForUser($this->user(true, false, $empleado), $obra, new Comision(), 'acero', []);
    }

    public function test_staff_in_the_same_area_can_use_the_shared_service(): void
    {
        $empleado = new Empleado();
        $empleado->forceFill(['Area' => 1, 'puesto_base' => 'ADMINISTRATIVO']);
        $obra = new Obra();
        $obra->area_id = 1;
        $comision = new Comision();
        $etapas = Mockery::mock(ComisionEtapasService::class);
        $etapas->shouldReceive('show')->once()->with($obra, $comision)->andReturn(['comision' => []]);

        $this->assertSame(['comision' => []], (new ComisionesWebService($etapas))->showForUser($this->user(true, false, $empleado), $obra, $comision));
    }

    public function test_responsible_resident_can_continue_without_area_access(): void
    {
        $empleado = new Empleado();
        $empleado->forceFill(['id_Empleado' => 12, 'puesto_base' => 'RESIDENTE']);
        $obra = new Obra();
        $obra->responsable_id = 12;
        $comision = new Comision();
        $etapas = Mockery::mock(ComisionEtapasService::class);
        $etapas->shouldReceive('show')->once()->with($obra, $comision)->andReturn(['comision' => []]);

        $this->assertSame(['comision' => []], (new ComisionesWebService($etapas))->showForUser($this->user(true, false, $empleado), $obra, $comision));
    }

    public function test_mobile_entrypoint_and_web_entrypoint_resolve_with_the_same_engine(): void
    {
        $this->assertInstanceOf(ComisionesWebService::class, $this->app->make(ComisionesWebService::class));
        $this->assertInstanceOf(\App\Services\Comisiones\ResidenteComisionesService::class, $this->app->make(\App\Services\Comisiones\ResidenteComisionesService::class));
    }

    private function escenarioAccion(?string $accion, string $estado = 'pendiente'): array
    {
        $obra = new Obra();
        $obra->id = 67;
        $comision = Mockery::mock(Comision::class)->makePartial();
        $comision->forceFill(['id' => 112, 'obra_id' => 67, 'estado' => $estado]);
        $comision->shouldReceive('loadMissing')->andReturnSelf();
        $etapas = Mockery::mock(ComisionEtapasService::class);
        $etapas->shouldReceive('mapComision')->with($comision)->andReturn(['etapas' => [
            ['etapa' => 'bentonita', 'accion' => $accion, 'estado' => $accion === 'activar' ? 'no_aplica' : 'pendiente'],
        ]]);
        $etapas->shouldReceive('produccionParaEtapa')->with($comision, 'bentonita')->andReturn([
            'campo' => 'vol_bentonita', 'valor' => 12.5,
        ]);

        return [$obra, $comision, $etapas];
    }

    public function test_activation_only_sends_the_optional_stage_activation_payload(): void
    {
        [$obra, $comision, $etapas] = $this->escenarioAccion('activar');
        $user = $this->user(true, true);
        $etapas->shouldReceive('updateEtapa')->once()->with($user, $obra, $comision, 'bentonita', [
            'estado' => 'pendiente', 'requiere_foto' => true,
        ])->andReturn(['id' => 112]);

        $this->assertSame(['id' => 112], (new ComisionesWebService($etapas))->activarEtapaForUser($user, $obra, $comision, 'bentonita'));
    }

    public function test_activation_cannot_bypass_the_shared_stage_order(): void
    {
        [$obra, $comision, $etapas] = $this->escenarioAccion(null);
        $etapas->shouldNotReceive('updateEtapa');
        $this->expectException(ValidationException::class);

        (new ComisionesWebService($etapas))->activarEtapaForUser($this->user(true, true), $obra, $comision, 'bentonita');
    }

    public function test_unknown_stage_is_rejected_before_writing(): void
    {
        [$obra, $comision, $etapas] = $this->escenarioAccion('activar');
        $etapas->shouldNotReceive('updateEtapa');
        $this->expectException(ValidationException::class);

        (new ComisionesWebService($etapas))->activarEtapaForUser($this->user(true, true), $obra, $comision, 'inexistente');
    }

    public function test_cancelled_commission_cannot_be_activated(): void
    {
        [$obra, $comision, $etapas] = $this->escenarioAccion('activar', 'cancelada');
        $etapas->shouldNotReceive('updateEtapa');
        $this->expectException(ValidationException::class);

        (new ComisionesWebService($etapas))->activarEtapaForUser($this->user(true, true), $obra, $comision, 'bentonita');
    }

    public function test_closed_commission_cannot_be_reopened_by_activation(): void
    {
        [$obra, $comision, $etapas] = $this->escenarioAccion('activar', 'cerrada');
        $etapas->shouldNotReceive('updateEtapa');
        $this->expectException(ValidationException::class);

        (new ComisionesWebService($etapas))->activarEtapaForUser($this->user(true, true), $obra, $comision, 'bentonita');
    }

    public function test_selection_does_not_write_any_stage_data(): void
    {
        [$obra, $comision, $etapas] = $this->escenarioAccion('continuar');
        $etapas->shouldNotReceive('updateEtapa');
        $selected = (new ComisionesWebService($etapas))->seleccionarEtapaForUser($this->user(true, true), $obra, $comision, 'bentonita');

        $this->assertSame('continuar', $selected['accion']);
    }

    public function test_selection_cannot_target_a_commission_in_another_worksite(): void
    {
        [$obra, $comision, $etapas] = $this->escenarioAccion('registrar');
        $obra->id = 41;
        $etapas->shouldNotReceive('updateEtapa');
        $this->expectException(AuthorizationException::class);

        (new ComisionesWebService($etapas))->seleccionarEtapaForUser($this->user(true, true), $obra, $comision, 'bentonita');
    }

    public function test_read_only_user_has_no_capture_actions(): void
    {
        $service = new ComisionesWebService(Mockery::mock(ComisionEtapasService::class));
        $this->assertFalse($service->puedeCapturar($this->user(false), new Obra()));
    }
}
