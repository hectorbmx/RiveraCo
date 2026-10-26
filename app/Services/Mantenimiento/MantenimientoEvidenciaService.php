<?php

namespace App\Services\Mantenimiento;

use App\Models\Mantenimiento;
use App\Services\Contracts\ImageOptimizerInterface;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class MantenimientoEvidenciaService
{
    public function __construct(private ImageOptimizerInterface $imagenes) {}

    public function agregar(Mantenimiento $servicio, array $datos, ?int $empleadoId = null, ?Closure $guardarCabecera = null): Mantenimiento
    {
        if (array_diff(array_keys($datos), ['fotos', 'foto_descripciones'])) {
            throw ValidationException::withMessages(['fotos' => 'Solo se reciben fotografías nuevas y sus descripciones.']);
        }
        $datos = Validator::make($datos, [
            'fotos' => ['required', 'array', 'min:1', 'max:8'],
            'fotos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'foto_descripciones' => ['nullable', 'array', 'max:8'],
            'foto_descripciones.*' => ['nullable', 'string', 'max:200'],
        ])->validate();
        foreach (array_keys($datos['foto_descripciones'] ?? []) as $indice) {
            if (! array_key_exists($indice, $datos['fotos'])) {
                throw ValidationException::withMessages(['foto_descripciones' => 'Cada descripción debe corresponder al índice de una foto enviada.']);
            }
        }
        $rutas = [];
        try {
            // Sin reintentos automáticos: esta transacción tiene efectos de archivos.
            return DB::transaction(function () use ($servicio, $datos, $empleadoId, $guardarCabecera, &$rutas) {
                $actual = Mantenimiento::query()->lockForUpdate()->findOrFail($servicio->id);
                if ($empleadoId !== null) {
                    abort_unless($actual->tipo === 'programado', 404);
                    abort_unless((int) $actual->mecanico_id === $empleadoId, 403);
                }
                if ($actual->fotos()->count() + count($datos['fotos']) > 8) {
                    throw ValidationException::withMessages(['fotos' => 'Un servicio puede tener como máximo 8 fotografías de evidencia.']);
                }
                // La edición web guarda su cabecera/costos en la misma transacción del lote.
                if ($guardarCabecera !== null) {
                    $guardarCabecera($actual);
                }
                foreach ($datos['fotos'] as $indice => $foto) {
                    $contenido = $this->imagenes->optimize($foto, [
                        'max_width' => 1920, 'max_height' => 1920, 'quality' => 80, 'format' => 'webp',
                    ]);
                    $ruta = 'mantenimientos/evidencias/'.Str::uuid().'.webp';
                    // Registrar antes de escribir para limpiar también escrituras parciales.
                    $rutas[] = $ruta;
                    if (! Storage::disk('public')->put($ruta, $contenido)) {
                        throw new RuntimeException('No se pudo guardar la fotografía de evidencia.');
                    }
                    $actual->fotos()->create(['ruta' => $ruta, 'descripcion' => $datos['foto_descripciones'][$indice] ?? null]);
                }

                return $actual;
            });
        } catch (Throwable $error) {
            if ($rutas !== []) {
                try {
                    if (! Storage::disk('public')->delete($rutas)) {
                        Log::error('No se pudieron limpiar archivos de evidencia tras un fallo.', ['rutas' => $rutas]);
                    }
                } catch (Throwable $limpieza) {
                    report($limpieza);
                }
            }
            throw $error;
        }
    }
}
