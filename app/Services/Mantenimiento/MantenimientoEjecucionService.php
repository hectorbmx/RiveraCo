<?php

namespace App\Services\Mantenimiento;

use App\Models\Mantenimiento;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MantenimientoEjecucionService
{
    public function __construct(private MantenimientoBorradorService $borradores, private MantenimientoManoObraService $manoObra) {}

    public function surtidoCompleto(Mantenimiento $servicio): bool
    {
        $servicio->loadMissing(['materialesOrden', 'abastecimientos.detalles']);
        $surtidos = $servicio->abastecimientos->flatMap(fn ($s) => $s->detalles)->groupBy('producto_id')
            ->map(fn ($filas) => (float) $filas->sum('cantidad_atendida'));
        foreach ($servicio->materialesOrden->where('incluido', true)->groupBy('producto_id') as $producto => $filas) {
            $requerido = (float) $filas->sum('cantidad_requerida');
            if ($requerido > 0 && (! $producto || $requerido - (float) $surtidos->get($producto, 0) > 0.0005)) {
                return false;
            }
        }

        return true;
    }

    public function permiteEvidencias(Mantenimiento $servicio): bool
    {
        return (bool) $servicio->orden_servicio_folio
            && $servicio->fecha_inicio !== null
            && in_array($servicio->estatus, ['en_proceso', 'completado'], true)
            && $this->surtidoCompleto($servicio);
    }

    public function ejecutar(Mantenimiento $servicio, string $accion, array $datos, int $empleadoId): Mantenimiento
    {
        $campos = $accion === 'iniciar' ? ['fecha_inicio'] : ['fecha_fin', 'horometro', 'km_actuales', 'notas'];
        if (array_diff(array_keys($datos), $campos)) {
            throw ValidationException::withMessages(['payload' => 'La acción solo admite fechas, lectura final y notas de ejecución.']);
        }

        return DB::transaction(function () use ($servicio, $accion, $datos, $empleadoId) {
            $actual = Mantenimiento::query()->lockForUpdate()->findOrFail($servicio->id);
            abort_unless($actual->tipo === 'programado', 404);
            abort_unless((int) $actual->mecanico_id === $empleadoId, 403);
            $reglas = $accion === 'iniciar' ? ['fecha_inicio' => 'required|date'] : [
                'fecha_fin' => 'required|date', 'notas' => 'sometimes|nullable|string|max:5000',
                'horometro' => $actual->maquina_id ? 'required|numeric|min:0' : 'prohibited',
                'km_actuales' => $actual->vehiculo_id ? 'required|integer|min:0' : 'prohibited',
            ];
            $datos = Validator::make($datos, $reglas)->validate();
            foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
                if (isset($datos[$campo])) {
                    $datos[$campo] = Carbon::parse($datos[$campo])->setTimezone(config('app.timezone'))->toDateTimeString();
                }
            }
            $destino = $accion === 'iniciar' ? 'en_proceso' : 'completado';
            if ($actual->estatus === $destino) {
                foreach ($datos as $campo => $valor) {
                    $igual = str_starts_with($campo, 'fecha_')
                        ? Carbon::parse($valor)->equalTo($actual->$campo)
                        : (string) $valor === (string) $actual->$campo;
                    if (! $igual) {
                        throw new ConflictHttpException('La ejecución ya fue registrada con otros datos.');
                    }
                }

                return $actual;
            }
            $this->validar($actual, array_merge($datos, ['estatus' => $destino]));
            $actual->update(array_merge($datos, ['estatus' => $destino]));
            if ($destino === 'completado') {
                $this->manoObra->actualizar($actual->fresh());
            }

            return $actual->refresh();
        }, 3);
    }

    public function guardarWeb(Mantenimiento $servicio, array $datos, Closure $guardar): void
    {
        DB::transaction(function () use ($servicio, $datos, $guardar) {
            $actual = Mantenimiento::query()->lockForUpdate()->findOrFail($servicio->id);
            if (in_array($actual->estatus, ['completado', 'cancelado'], true)) {
                throw new ConflictHttpException('El servicio está cerrado y sus datos son de solo consulta.');
            }
            if ($actual->tipo === 'programado') {
                $this->validar($actual, $datos);
            }
            $guardar($actual);
        }, 3);
    }

    private function validar(Mantenimiento $actual, array $datos): void
    {
        $destino = $datos['estatus'];
        if (in_array($actual->estatus, ['completado', 'cancelado'], true) && $actual->estatus !== $destino) {
            throw new ConflictHttpException('Un servicio finalizado no puede reabrirse.');
        }
        if (($actual->estatus === 'en_proceso' && ! in_array($destino, ['en_proceso', 'completado', 'cancelado'], true))
            || ($actual->estatus === 'pendiente' && in_array($destino, ['borrador', 'orden_generada'], true))
            || ($actual->estatus === 'orden_generada' && $destino === 'borrador')) {
            throw new ConflictHttpException('La ejecución no puede regresar a una etapa anterior.');
        }
        if (! in_array($destino, ['en_proceso', 'completado'], true)) {
            return;
        }
        $origenes = $destino === 'en_proceso' ? ['orden_generada', 'pendiente', 'en_proceso'] : ['en_proceso', 'completado'];
        if (! $actual->orden_servicio_folio || ! in_array($actual->estatus, $origenes, true)) {
            throw new ConflictHttpException('La orden no está en un estado válido para esta acción.');
        }
        foreach (['maquina_id', 'vehiculo_id', 'servicio_preventivo_tipo_id', 'tipo'] as $campo) {
            if (array_key_exists($campo, $datos) && (string) $datos[$campo] !== (string) $actual->$campo) {
                throw new ConflictHttpException('La ejecución debe conservar el activo y tipo de la orden emitida.');
            }
        }
        $candidato = array_merge($actual->getAttributes(), $datos);
        if (empty($candidato['mecanico_id']) || ! \App\Models\Empleado::query()->whereKey($candidato['mecanico_id'])->exists()) {
            throw ValidationException::withMessages(['mecanico_id' => 'La ejecución requiere un mecánico existente.']);
        }
        Validator::make($candidato, ['fecha_inicio' => 'required|date'])->validate();
        if ($destino === 'completado') {
            $campo = $actual->maquina_id ? 'horometro' : 'km_actuales';
            Validator::make($candidato, ['fecha_fin' => 'required|date|after:fecha_inicio',
                $campo => 'required|'.($campo === 'horometro' ? 'numeric' : 'integer').'|min:'.max(0, (float) $actual->$campo)])->validate();
            $this->borradores->validarCabecera($candidato);
            if (! $this->surtidoCompleto($actual)) {
                throw new ConflictHttpException('Todos los materiales incluidos deben estar surtidos para completar el servicio.');
            }
        }
    }
}
