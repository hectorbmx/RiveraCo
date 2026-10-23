<?php

namespace App\Services\Maquinas;

use App\Models\Maquina;
use App\Services\Contracts\ImageOptimizerInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MaquinaFotoService
{
    public function __construct(private ImageOptimizerInterface $imageOptimizer)
    {
    }

    public function guardar(Maquina $maquina, array $datos, ?int $usuarioId): Collection
    {
        $datos = Validator::make($datos, [
            'fotos' => ['required', 'array', 'min:1', 'max:5'],
            'fotos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'foto_descripciones' => ['nullable', 'array', 'max:5'],
            'foto_descripciones.*' => ['nullable', 'string', 'max:200'],
        ], [
            'fotos.required' => 'Selecciona al menos una foto.',
            'fotos.array' => 'Las fotos deben enviarse como una lista de archivos.',
            'fotos.min' => 'Selecciona al menos una foto.',
            'fotos.max' => 'Puedes subir hasta 5 fotos por envío.',
            'fotos.*.required' => 'Selecciona un archivo válido para cada foto.',
            'fotos.*.image' => 'Cada foto debe ser una imagen válida.',
            'fotos.*.mimes' => 'Solo se permiten fotos JPG, PNG o WebP.',
            'fotos.*.max' => 'Cada foto debe pesar como máximo 5 MB.',
            'foto_descripciones.array' => 'Las descripciones deben enviarse como una lista.',
            'foto_descripciones.max' => 'Puedes enviar hasta 5 descripciones.',
            'foto_descripciones.*.string' => 'La descripción debe ser texto.',
            'foto_descripciones.*.max' => 'La descripción no debe superar los 200 caracteres.',
        ])->validate();

        $disk = Storage::disk('public');
        $rutas = [];

        try {
            return DB::transaction(function () use ($maquina, $datos, $usuarioId, $disk, &$rutas) {
                $fotos = collect();

                foreach ($datos['fotos'] as $indice => $archivo) {
                    $contenido = $this->imageOptimizer->optimize($archivo, [
                        'format' => 'webp',
                        'quality' => 80,
                        'max_width' => 1920,
                        'max_height' => 1920,
                    ]);

                    $ruta = 'maquinas/'.$maquina->getKey().'/fotos/'.Str::uuid().'.webp';
                    $rutas[] = $ruta;

                    if (! $disk->put($ruta, $contenido)) {
                        throw new RuntimeException('No se pudo guardar la foto de la máquina.');
                    }

                    $fotos->push($maquina->fotos()->create([
                        'ruta' => $ruta,
                        'descripcion' => $datos['foto_descripciones'][$indice] ?? null,
                        'uploaded_by' => $usuarioId,
                    ]));
                }

                return $fotos;
            });
        } catch (Throwable $exception) {
            if ($rutas !== []) {
                try {
                    if (! $disk->delete($rutas)) {
                        report(new RuntimeException('No se pudieron limpiar las fotos de máquina tras un error de carga.'));
                    }
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }
    }
}
