<?php

namespace App\Services\Mantenimiento;

use App\Models\EmpresaServicioPreventivoTipo;
use App\Models\Mantenimiento;
use App\Models\Maquina;
use App\Services\Maquinas\MaquinaHorometroService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MantenimientoBorradorService
{
    public function __construct(private MaquinaHorometroService $horometros) {}

    public function reglas(bool $creacion = true): array
    {
        return [
            'vehiculo_id' => 'nullable|required_without:maquina_id|integer|min:1|exists:vehiculos,id',
            'maquina_id' => 'nullable|required_without:vehiculo_id|integer|min:1|exists:maquinas,id',
            'obra_id' => 'nullable|integer|min:1|exists:obras,id',
            'tipo' => 'required|in:programado,emergencia',
            'categoria_mantenimiento' => ($creacion ? 'required' : 'nullable').'|string|max:100',
            'servicio_preventivo_tipo_id' => 'nullable|integer|min:1|exists:empresa_servicio_preventivo_tipos,id',
            'descripcion' => $creacion ? 'nullable|string|min:3' : 'nullable|string',
            'km_actuales' => 'nullable|integer|min:0',
            'km_proximo_servicio' => 'nullable|integer|min:0',
            'horometro' => 'nullable|numeric|min:0',
            'detalles' => 'nullable|array',
            'detalles.*.concepto' => 'required|string|max:150',
            'detalles.*.tipo' => 'nullable|in:refaccion,consumible,mano_obra,servicio_externo',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.costo_unitario' => 'nullable|numeric|min:0',
            'mecanico_id' => 'nullable|integer|exists:empleados,id_Empleado',
            'fecha_programada' => ($creacion ? 'required' : 'nullable').'|date',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
        ];
    }

    public function validarCabecera(array $datos, bool $creacion = false, bool $bloquearEquipo = false): void
    {
        if ((bool) ($datos['maquina_id'] ?? null) === (bool) ($datos['vehiculo_id'] ?? null)) {
            throw ValidationException::withMessages(['activo' => 'Selecciona solo un vehiculo o una maquina, no ambos.']);
        }
        if (! empty($datos['maquina_id'])) {
            if ($creacion && ! isset($datos['horometro'])) {
                throw ValidationException::withMessages(['horometro' => 'Captura el horómetro de la máquina al registrar el servicio.']);
            }
            if (isset($datos['horometro'])) {
                $query = Maquina::query();
                if ($bloquearEquipo) {
                    $query->lockForUpdate();
                }
                $actual = $this->horometros->horometroActual($query->findOrFail($datos['maquina_id']));
                if ($actual !== null && (float) $datos['horometro'] < $actual) {
                    throw ValidationException::withMessages(['horometro' => 'El horómetro no puede ser menor al horómetro consolidado actual ('.number_format($actual, 2).').']);
                }
            }
        }
        if ($error = $this->errorTipoPreventivo($datos)) {
            throw ValidationException::withMessages(['servicio_preventivo_tipo_id' => $error]);
        }
    }

    public function errorTipoPreventivo(array $datos): ?string
    {
        $tipoId = $datos['servicio_preventivo_tipo_id'] ?? null;
        if (($datos['tipo'] ?? null) === 'programado' && ! $tipoId) {
            return 'Selecciona el tipo de servicio preventivo para un servicio programado.';
        }
        if (($datos['tipo'] ?? null) === 'emergencia' && $tipoId) {
            return 'Un servicio de emergencia no debe tener tipo de servicio preventivo.';
        }
        if (! $tipoId) {
            return null;
        }
        $ambito = ! empty($datos['maquina_id']) ? EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA
            : (! empty($datos['vehiculo_id']) ? EmpresaServicioPreventivoTipo::AMBITO_VEHICULO : null);
        if (! $ambito) {
            return 'Selecciona un vehiculo o una maquina antes de elegir el tipo de servicio.';
        }

        return EmpresaServicioPreventivoTipo::query()->whereKey($tipoId)->where('ambito', $ambito)->where('activo', true)->exists()
            ? null : 'El tipo de servicio seleccionado no corresponde al activo o no esta activo.';
    }

    public function crear(array $datos): Mantenimiento
    {
        $datos = Validator::make($datos, $this->reglas())->validate();

        return DB::transaction(function () use ($datos) {
            $this->validarCabecera($datos, true, true);
            $detalles = $this->normalizarDetalles($datos['detalles'] ?? []);
            $mantenimiento = Mantenimiento::create(array_merge(Arr::except($datos, 'detalles'), [
                'estatus' => 'borrador', 'abastecimiento_estado' => 'pendiente',
            ]));
            if ($detalles->isNotEmpty()) {
                $mantenimiento->detalles()->createMany($detalles->all());
                $this->actualizarCostos($mantenimiento, $detalles);
            }

            return $mantenimiento->refresh();
        });
    }

    public function actualizar(Mantenimiento $mantenimiento, array $cambios, ?int $empleadoId = null): Mantenimiento
    {
        return DB::transaction(function () use ($mantenimiento, $cambios, $empleadoId) {
            $actual = Mantenimiento::query()->lockForUpdate()->findOrFail($mantenimiento->id);
            if ($empleadoId !== null) {
                abort_unless($actual->tipo === 'programado', 404);
                abort_unless((int) $actual->mecanico_id === $empleadoId, 403);
            }
            if ($actual->estatus !== 'borrador' || $actual->orden_servicio_folio
                || $actual->abastecimientos()->exists() || $actual->materialesOrden()->exists()) {
                throw new ConflictHttpException('Solo se puede editar un borrador sin orden ni solicitudes de abastecimiento.');
            }
            $datos = array_merge(Arr::only($actual->getAttributes(), array_keys($this->reglas(false))), $cambios);
            $datos = Validator::make($datos, $this->reglas(false))->validate();
            $this->validarCabecera($datos, false, true);
            $actual->update(Arr::except($datos, 'detalles'));
            // La web conserva los conceptos de programados; el snapshot se genera al crear la orden.
            if ($actual->tipo === 'emergencia') {
                $detalles = $this->normalizarDetalles($datos['detalles'] ?? []);
                $actual->detalles()->delete();
                $actual->detalles()->createMany($detalles->all());
                $this->actualizarCostos($actual, $detalles);
            }

            return $actual->refresh();
        });
    }

    private function normalizarDetalles(array $detalles): Collection
    {
        return collect($detalles)->map(fn (array $detalle) => [
            'concepto' => $detalle['concepto'], 'tipo' => $detalle['tipo'] ?? null,
            'cantidad' => (float) $detalle['cantidad'], 'costo_unitario' => (float) ($detalle['costo_unitario'] ?? 0),
            'costo_total' => round((float) $detalle['cantidad'] * (float) ($detalle['costo_unitario'] ?? 0), 2),
        ]);
    }

    private function actualizarCostos(Mantenimiento $mantenimiento, Collection $detalles): void
    {
        $mantenimiento->update([
            'costo_mano_obra' => $detalles->where('tipo', 'mano_obra')->sum('costo_total'),
            'costo_refacciones' => $detalles->whereIn('tipo', ['refaccion', 'consumible'])->sum('costo_total'),
            'costo_total' => $detalles->sum('costo_total'),
        ]);
    }
}
