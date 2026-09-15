<?php

namespace Tests\Feature\Api;

use App\Models\Maquina;
use App\Models\Obra;
use App\Models\ObraMaquina;
use App\Models\User;
use App\Models\UsuarioApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaquinaRegistroAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_machine_records_without_direct_obra_assignment(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        UsuarioApp::create([
            'user_id' => $user->id,
            'empleado_id' => null,
            'is_active' => true,
        ]);

        $obra = Obra::create([
            'nombre' => 'Obra de prueba',
            'clave_obra' => 'PRUEBA-001',
            'tipo_obra' => 'PILAS',
            'estatus_nuevo' => Obra::ESTATUS_EJECUCION,
        ]);

        $maquina = Maquina::create([
            'codigo' => 'M-001',
            'nombre' => 'Excavadora 1',
            'estado' => Maquina::ESTADO_OPERATIVA,
            'ubicacion' => Maquina::UBIC_EN_OBRA,
        ]);

        $obraMaquina = ObraMaquina::create([
            'obra_id' => $obra->id,
            'maquina_id' => $maquina->id,
            'estado' => 'activa',
            'fecha_inicio' => now()->toDateString(),
            'horometro_inicio' => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/maquinas/{$obraMaquina->id}/registros");

        $response->assertOk();
        $response->assertJsonPath('ok', true);
    }
}
