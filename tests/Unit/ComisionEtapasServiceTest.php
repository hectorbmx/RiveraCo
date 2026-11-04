<?php

namespace Tests\Unit;

use App\Models\Comision;
use App\Models\ComisionEtapa;
use App\Models\Obra;
use App\Models\User;
use App\Services\Comisiones\ComisionEtapasService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class ComisionEtapasServiceTest extends TestCase
{
    private function comision(array $estados): Comision
    {
        $comision = new Comision(['obra_id' => 67, 'estado' => 'pendiente']);
        $etapas = [];
        foreach (array_combine(['perforacion', 'bentonita', 'ademe', 'acero', 'colado'], $estados) as $key => $estado) {
            $etapa = new ComisionEtapa(['etapa' => $key, 'estado' => $estado, 'orden' => count($etapas) + 1]);
            $etapa->setRelation('fotos', new Collection());
            $etapa->setRelation('personal', new Collection());
            $etapa->setRelation('comision', $comision);
            $etapas[] = $etapa;
        }
        $comision->setRelation('etapas', new Collection($etapas));
        $comision->setRelation('pila', null);

        return $comision;
    }

    public function test_next_step_skips_optional_stages_and_preserves_mobile_payload(): void
    {
        $comision = $this->comision(['completada', 'no_aplica', 'no_aplica', 'pendiente', 'pendiente']);
        $data = (new ComisionEtapasService())->mapComision($comision);

        $this->assertSame(33, $data['avance']['porcentaje']);
        $this->assertSame('acero', $data['siguiente_etapa']['etapa']);
        $this->assertSame('registrar', $data['siguiente_etapa']['accion']);
        $this->assertSame('ver', $data['etapas'][0]['accion']);
        $this->assertSame('activar', $data['etapas'][1]['accion']);
        $this->assertNull($data['etapas'][4]['accion']);
    }

    public function test_in_progress_stage_can_be_continued_but_later_stages_are_blocked(): void
    {
        $comision = $this->comision(['en_proceso', 'no_aplica', 'no_aplica', 'pendiente', 'pendiente']);
        $data = (new ComisionEtapasService())->mapComision($comision);

        $this->assertSame(0, $data['avance']['porcentaje']);
        $this->assertSame('continuar', $data['siguiente_etapa']['accion']);
        $this->assertNull($data['etapas'][1]['accion']);
        $this->assertNull($data['etapas'][3]['accion']);
    }

    public function test_finishing_all_applicable_stages_has_no_next_step(): void
    {
        $comision = $this->comision(['completada', 'no_aplica', 'no_aplica', 'completada', 'completada']);
        $comision->estado = 'cerrada';
        $data = (new ComisionEtapasService())->mapComision($comision);

        $this->assertSame(100, $data['avance']['porcentaje']);
        $this->assertNull($data['siguiente_etapa']);
        $this->assertSame('Completa', $data['estatus_visual']['label']);
    }

    public function test_shared_service_rejects_a_commission_from_another_worksite_before_writing(): void
    {
        $obra = new Obra();
        $obra->id = 41;
        $comision = new Comision(['obra_id' => 67]);
        $this->expectException(AuthorizationException::class);

        (new ComisionEtapasService())->updateEtapa(new User(), $obra, $comision, 'acero', []);
    }

    public function test_shared_transition_validation_blocks_skipping_a_stage(): void
    {
        $comision = $this->comision(['en_proceso', 'no_aplica', 'no_aplica', 'pendiente', 'pendiente']);
        $method = new \ReflectionMethod(ComisionEtapasService::class, 'validarCambioEtapa');
        $this->expectException(ValidationException::class);

        $method->invoke(new ComisionEtapasService(), $comision, $comision->etapas[3], ['estado' => 'completada']);
    }

    public function test_mandatory_stage_cannot_be_disabled(): void
    {
        $comision = $this->comision(['completada', 'no_aplica', 'no_aplica', 'pendiente', 'pendiente']);
        $method = new \ReflectionMethod(ComisionEtapasService::class, 'validarCambioEtapa');
        $this->expectException(ValidationException::class);

        $method->invoke(new ComisionEtapasService(), $comision, $comision->etapas[3], ['estado' => 'no_aplica']);
    }

    public function test_completing_the_last_stage_saves_hours_and_closes_the_same_commission(): void
    {
        $comision = Mockery::mock(Comision::class)->makePartial();
        $comision->forceFill(['id' => 112, 'obra_id' => 67, 'estado' => 'pendiente']);
        $comision->setDateFormat('Y-m-d H:i:s');
        $etapa = Mockery::mock(ComisionEtapa::class)->makePartial();
        $etapa->setDateFormat('Y-m-d H:i:s');
        $etapa->fill(['etapa' => 'colado', 'estado' => 'pendiente', 'orden' => 5]);
        $etapa->setRelation('comision', $comision);
        $anteriores = $this->comision(['completada', 'no_aplica', 'no_aplica', 'completada', 'pendiente'])->etapas;
        $anteriores[4] = $etapa;
        $comision->setRelation('etapas', $anteriores);

        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('where')->with('etapa', 'colado')->once()->andReturnSelf();
        $relation->shouldReceive('firstOrFail')->once()->andReturn($etapa);
        $relation->shouldReceive('get')->once()->andReturn($anteriores);
        $comision->shouldReceive('etapas')->twice()->andReturn($relation);
        $comision->shouldReceive('save')->twice()->andReturnTrue();
        $comision->shouldReceive('fresh')->once()->andReturnSelf();
        $etapa->shouldReceive('save')->once()->andReturnTrue();
        // La notificacion posterior no forma parte de esta prueba de persistencia.
        $etapa->shouldReceive('fresh')->once()->andReturnNull();
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());

        $obra = new Obra();
        $obra->id = 67;
        $service = Mockery::mock(ComisionEtapasService::class)->makePartial();
        $service->shouldReceive('show')->once()->with($obra, $comision)->andReturn(['comision' => ['id' => 112]]);

        $result = $service->updateEtapa(new User(), $obra, $comision, 'colado', [
            'estado' => 'completada', 'hora_inicio' => '14:00', 'hora_fin' => '15:00',
        ]);

        $this->assertSame(['id' => 112], $result);
        $this->assertSame('completada', $etapa->estado);
        $this->assertSame('14:00', $etapa->hora_inicio);
        $this->assertSame('15:00', $etapa->hora_fin);
        $this->assertSame('cerrada', $comision->estado);
        $this->assertNotNull($comision->cerrada_at);
    }
}
