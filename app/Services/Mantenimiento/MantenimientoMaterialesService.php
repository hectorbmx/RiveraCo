<?php

namespace App\Services\Mantenimiento;

use App\Models\Mantenimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MantenimientoMaterialesService
{
    public function actualizar(Mantenimiento $servicio, array $datos, ?int $empleadoId = null, bool $formularioWeb = false): Mantenimiento
    {
        $reglas = $formularioWeb ? [
            'incluidos' => ['nullable', 'array'], 'incluidos.*' => ['integer', 'min:1', 'distinct'],
            'motivos' => ['nullable', 'array'], 'motivos.*' => ['nullable', 'string', 'max:255'],
        ] : [
            'materiales' => ['required', 'array', 'min:1', 'max:500'],
            'materiales.*' => ['required', 'array:id,incluido,motivo_exclusion'],
            'materiales.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'materiales.*.incluido' => ['required', 'boolean'],
            'materiales.*.motivo_exclusion' => ['nullable', 'string', 'max:255'],
        ];
        if (array_diff(array_keys($datos), $formularioWeb ? ['incluidos', 'motivos'] : ['materiales'])) {
            throw ValidationException::withMessages(['materiales' => 'Solo se permite ajustar inclusión y motivo de exclusión de los materiales existentes.']);
        }
        $datos = Validator::make($datos, $reglas)->validate();

        return DB::transaction(function () use ($servicio, $datos, $empleadoId, $formularioWeb) {
            $actual = Mantenimiento::query()->lockForUpdate()->findOrFail($servicio->id);
            if ($empleadoId !== null) {
                abort_unless($actual->tipo === 'programado', 404);
                abort_unless((int) $actual->mecanico_id === $empleadoId, 403);
            }
            if ($actual->estatus !== 'orden_generada' || ! $actual->orden_servicio_folio || $actual->abastecimientos()->exists()) {
                throw new ConflictHttpException('Los materiales solo pueden ajustarse con orden generada y antes de cualquier solicitud de abastecimiento.');
            }
            $materiales = $actual->materialesOrden()->lockForUpdate()->get()->keyBy('id');
            if ($formularioWeb) {
                $incluidos = array_map('intval', $datos['incluidos'] ?? []);
                $motivos = $datos['motivos'] ?? [];
                foreach (array_merge($incluidos, array_keys($motivos)) as $id) {
                    if (! ctype_digit((string) $id) || ! $materiales->has((int) $id)) {
                        throw ValidationException::withMessages(['materiales' => 'Todos los materiales deben pertenecer a esta orden de servicio.']);
                    }
                }
                $filas = $materiales->map(fn ($m) => ['id' => $m->id, 'incluido' => in_array($m->id, $incluidos, true),
                    'motivo_exclusion' => $motivos[$m->id] ?? null])->values()->all();
            } else {
                $filas = $datos['materiales'];
            }
            // Verificar todos los IDs antes de escribir la primera fila.
            foreach ($filas as $indice => $fila) {
                if (! $materiales->has((int) $fila['id'])) {
                    throw ValidationException::withMessages(["materiales.$indice.id" => 'El material no pertenece a esta orden de servicio.']);
                }
            }
            // Validar todos los motivos antes de modificar la primera partida.
            foreach ($filas as $indice => $fila) {
                $material = $materiales->get((int) $fila['id']);
                $motivo = (bool) $fila['incluido'] ? null : trim((string) (array_key_exists('motivo_exclusion', $fila)
                    ? $fila['motivo_exclusion'] : $material->motivo_exclusion));
                if (! (bool) $fila['incluido'] && $motivo === '') {
                    $campo = $formularioWeb ? 'motivos.'.$material->id : "materiales.$indice.motivo_exclusion";
                    throw ValidationException::withMessages([$campo => 'Indica por qué no se aplicará '.$material->producto_nombre_snapshot.' en este servicio.']);
                }
                $filas[$indice]['motivo_exclusion'] = $motivo;
            }
            foreach ($filas as $fila) {
                $material = $materiales->get((int) $fila['id']);
                $incluido = (bool) $fila['incluido'];
                $material->update(['incluido' => $incluido,
                    'motivo_exclusion' => $fila['motivo_exclusion']]);
            }

            return $actual->refresh();
        }, 3);
    }
}
