<?php

namespace Tests\Feature;

use App\Models\Maquina;
use App\Models\MaquinaFoto;
use App\Models\User;
use App\Services\Maquinas\MaquinaFotoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class MaquinaFotoControllerTest extends TestCase
{
    private Maquina $maquina;
    private MaquinaFoto $foto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->maquina = new Maquina();
        $this->maquina->id = 17;
        $this->foto = Mockery::mock(MaquinaFoto::class)->makePartial();
        $this->foto->id = 4;
        $this->foto->maquina_id = 17;
        $this->foto->ruta = 'maquinas/17/fotos/prueba.webp';
        Route::bind('maquina', fn () => $this->maquina);
        Route::bind('foto', fn () => $this->foto);
    }

    private function autenticar(array $permisos): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 8;
        $user->email_verified_at = now();
        $user->shouldReceive('canAny')->andReturnUsing(
            fn ($abilities) => count(array_intersect((array) $abilities, $permisos)) > 0
        );
        $this->actingAs($user);
    }

    public function test_guests_cannot_upload_photos(): void
    {
        $this->post(route('maquinas.fotos.store', 17))->assertRedirect(route('login'));
    }

    public function test_each_action_requires_its_own_permission(): void
    {
        $this->autenticar([]);
        $this->post(route('maquinas.fotos.store', 17))->assertForbidden();
        $this->put(route('maquinas.fotos.update', [17, 4]))->assertForbidden();
        $this->delete(route('maquinas.fotos.destroy', [17, 4]))->assertForbidden();

        $this->autenticar(['fotos_maquinas_create.access']);
        $this->put(route('maquinas.fotos.update', [17, 4]))->assertForbidden();
        $this->delete(route('maquinas.fotos.destroy', [17, 4]))->assertForbidden();
    }

    public function test_photos_of_other_machines_cannot_be_edited_or_deleted(): void
    {
        $this->autenticar(['fotos_maquinas_edit.access', 'fotos_maquinas_delete.access']);
        $this->foto->maquina_id = 99;
        $this->foto->shouldNotReceive('update');
        $this->foto->shouldNotReceive('delete');
        $this->put(route('maquinas.fotos.update', [17, 4]), ['descripcion' => 'Otra máquina'])->assertNotFound();
        $this->delete(route('maquinas.fotos.destroy', [17, 4]))->assertNotFound();
    }

    public function test_upload_uses_authenticated_author_and_returns_to_photos(): void
    {
        $this->autenticar(['fotos_maquinas_create.access']);
        $this->mock(MaquinaFotoService::class, function ($mock) {
            $mock->shouldReceive('guardar')->once()->with($this->maquina, [
                'fotos' => [], 'foto_descripciones' => null,
            ], 8)->andReturn(collect());
        });

        $this->post(route('maquinas.fotos.store', 17))
            ->assertRedirect(route('maquinas.show', ['maquina' => 17, 'tab' => 'fotos']))
            ->assertSessionHas('success');
    }

    public function test_upload_validation_errors_return_to_photos(): void
    {
        $this->autenticar(['fotos_maquinas_create.access']);
        $this->mock(MaquinaFotoService::class, function ($mock) {
            $mock->shouldReceive('guardar')->once()->andThrow(ValidationException::withMessages(['fotos' => 'Selecciona una foto.']));
        });

        $this->from(route('maquinas.show', ['maquina' => 17, 'tab' => 'general']))
            ->post(route('maquinas.fotos.store', 17))
            ->assertRedirect(route('maquinas.show', ['maquina' => 17, 'tab' => 'fotos']))
            ->assertSessionHasErrors('fotos');
    }

    public function test_edit_updates_only_description(): void
    {
        $this->autenticar(['fotos_maquinas_edit.access']);
        $this->foto->shouldReceive('update')->once()->with(['descripcion' => 'Vista lateral'])->andReturn(true);

        $this->put(route('maquinas.fotos.update', [17, 4]), [
            'descripcion' => 'Vista lateral', 'maquina_id' => 99, 'uploaded_by' => 99, 'ruta' => 'otra.webp',
        ])->assertRedirect(route('maquinas.show', ['maquina' => 17, 'tab' => 'fotos']))->assertSessionHas('success');
    }

    public function test_edit_rejects_long_descriptions(): void
    {
        $this->autenticar(['fotos_maquinas_edit.access']);
        $this->foto->shouldNotReceive('update');
        $this->put(route('maquinas.fotos.update', [17, 4]), ['descripcion' => str_repeat('a', 201)])
            ->assertRedirect(route('maquinas.show', ['maquina' => 17, 'tab' => 'fotos']))
            ->assertSessionHasErrors('descripcion');
    }

    public function test_delete_removes_record_and_file(): void
    {
        $this->autenticar(['fotos_maquinas_delete.access']);
        Storage::fake('public');
        Storage::disk('public')->put($this->foto->ruta, 'contenido');
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());
        $this->foto->shouldReceive('delete')->once()->andReturn(true);

        $this->delete(route('maquinas.fotos.destroy', [17, 4]))
            ->assertRedirect(route('maquinas.show', ['maquina' => 17, 'tab' => 'fotos']))
            ->assertSessionHas('success');

        Storage::disk('public')->assertMissing($this->foto->ruta);
    }
}
