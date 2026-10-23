<?php

namespace Tests\Feature;

use App\Models\Maquina;
use App\Models\MaquinaFoto;
use App\Services\Image\InterventionImageOptimizer;
use App\Services\Maquinas\MaquinaFotoService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MaquinaFotoServiceTest extends TestCase
{
    public function test_converts_supported_formats_to_webp_and_preserves_proportions(): void
    {
        Storage::fake('public');
        DB::shouldReceive('transaction')->times(3)->andReturnUsing(fn ($callback) => $callback());
        $maquina = Mockery::mock(Maquina::class)->makePartial();
        $maquina->id = 17;
        $relacion = Mockery::mock(HasMany::class);
        $maquina->shouldReceive('fotos')->times(3)->andReturn($relacion);
        $relacion->shouldReceive('create')->times(3)->andReturnUsing(fn ($datos) => new MaquinaFoto($datos));
        $service = new MaquinaFotoService(new InterventionImageOptimizer());

        foreach (['jpg', 'png', 'webp'] as $extension) {
            $fotos = $service->guardar($maquina, [
                'fotos' => [UploadedFile::fake()->image('equipo.'.$extension, 2400, 1200)],
                'foto_descripciones' => ['Vista general'],
            ], 8);

            $foto = $fotos->first();
            Storage::disk('public')->assertExists($foto->ruta);
            $info = getimagesizefromstring(Storage::disk('public')->get($foto->ruta));
            $this->assertSame('image/webp', $info['mime']);
            $this->assertSame([1920, 960], [$info[0], $info[1]]);
            $this->assertStringStartsWith('maquinas/17/fotos/', $foto->ruta);
            $this->assertStringEndsWith('.webp', $foto->ruta);
            $this->assertSame('Vista general', $foto->descripcion);
            $this->assertSame(8, $foto->uploaded_by);
        }
    }

    public function test_rejects_invalid_batches_before_writing_files(): void
    {
        Storage::fake('public');
        DB::shouldReceive('transaction')->never();
        $service = new MaquinaFotoService(new InterventionImageOptimizer());
        $imagen = UploadedFile::fake()->image('foto.jpg');

        foreach ([
            [],
            ['fotos' => array_fill(0, 6, $imagen)],
            ['fotos' => [UploadedFile::fake()->create('archivo.pdf', 10, 'application/pdf')]],
            ['fotos' => [UploadedFile::fake()->image('grande.jpg')->size(5121)]],
            ['fotos' => [$imagen], 'foto_descripciones' => [str_repeat('a', 201)]],
        ] as $datos) {
            try {
                $service->guardar(new Maquina(), $datos, null);
                $this->fail('Se esperaba un error de validación.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_cleans_entire_batch_when_a_database_write_fails(): void
    {
        Storage::fake('public');
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());
        $maquina = Mockery::mock(Maquina::class)->makePartial();
        $maquina->id = 17;
        $relacion = Mockery::mock(HasMany::class);
        $maquina->shouldReceive('fotos')->twice()->andReturn($relacion);
        $relacion->shouldReceive('create')->once()->ordered()->andReturn(new MaquinaFoto());
        $relacion->shouldReceive('create')->once()->ordered()->andThrow(new RuntimeException('Fallo de base de datos'));

        try {
            (new MaquinaFotoService(new InterventionImageOptimizer()))->guardar($maquina, [
                'fotos' => [UploadedFile::fake()->image('uno.jpg'), UploadedFile::fake()->image('dos.png')],
            ], null);
            $this->fail('Se esperaba un error de guardado.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fallo de base de datos', $exception->getMessage());
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_does_not_register_a_photo_when_storage_rejects_the_write(): void
    {
        $disk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        Storage::shouldReceive('disk')->once()->with('public')->andReturn($disk);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->with(Mockery::on(fn ($rutas) => count($rutas) === 1))->andReturn(true);
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());
        $maquina = Mockery::mock(Maquina::class)->makePartial();
        $maquina->id = 17;
        $maquina->shouldNotReceive('fotos');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No se pudo guardar la foto de la máquina.');

        (new MaquinaFotoService(new InterventionImageOptimizer()))->guardar($maquina, [
            'fotos' => [UploadedFile::fake()->image('equipo.jpg')],
        ], null);
    }
}
