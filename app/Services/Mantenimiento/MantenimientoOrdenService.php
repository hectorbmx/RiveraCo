<?php

namespace App\Services\Mantenimiento;

use App\Models\Mantenimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MantenimientoOrdenService
{
    public function __construct(private MantenimientoCatalogoService $catalogos, private MantenimientoBorradorService $borradores) {}

    public function generar(Mantenimiento $mantenimiento, int $usuarioId, ?int $empleadoId = null): Mantenimiento
    {
        return DB::transaction(function () use ($mantenimiento, $usuarioId, $empleadoId) {
            // Serializa generación y edición de borradores, incluso con modelos de ruta obsoletos.
            $actual = Mantenimiento::query()->lockForUpdate()->findOrFail($mantenimiento->id);
            if ($empleadoId !== null) {
                abort_unless($actual->tipo === 'programado', 404);
                abort_unless((int) $actual->mecanico_id === $empleadoId, 403);
            }
            if ($actual->orden_servicio_folio && $actual->estatus !== 'borrador') {
                // Repetir no regenera el snapshot ni revierte una etapa posterior.
                return $actual;
            }
            if ($actual->estatus !== 'borrador' || $actual->orden_servicio_folio
                || $actual->materialesOrden()->exists() || $actual->abastecimientos()->exists()) {
                throw new ConflictHttpException('La orden solo puede generarse desde un borrador sin orden, materiales ni solicitudes previas.');
            }
            if ((bool) $actual->maquina_id === (bool) $actual->vehiculo_id) {
                throw ValidationException::withMessages(['activo' => 'El servicio debe tener exactamente una máquina o un vehículo.']);
            }
            if ($error = $this->borradores->errorTipoPreventivo($actual->getAttributes())) {
                throw ValidationException::withMessages(['servicio_preventivo_tipo_id' => $error]);
            }
            $materiales = $actual->tipo === 'programado'
                ? ($this->catalogos->materialesPreventivosParaMaquina($actual->maquina_id)[$actual->servicio_preventivo_tipo_id] ?? [])
                : [];
            $actual->update([
                'orden_servicio_folio' => sprintf('OSM-%s-%06d', now()->format('y'), $actual->id),
                'orden_servicio_generada_at' => now(), 'orden_servicio_generada_por' => $usuarioId,
                'estatus' => 'orden_generada',
            ]);
            foreach ($materiales as $orden => $material) {
                $actual->materialesOrden()->create([
                    'material_preventivo_id' => $material['id'] ?? null,
                    'producto_id' => $material['producto_id'] ?? null,
                    'producto_nombre_snapshot' => ($material['producto'] ?? null) ?: 'Producto no disponible',
                    'producto_sku_snapshot' => $material['sku'] ?? null,
                    'unidad' => $material['unidad'] ?? null,
                    'cantidad_requerida' => $material['cantidad'] ?? 0,
                    'orden' => $orden, 'incluido' => true,
                ]);
            }

            return $actual->refresh();
        }, 3);
    }
}
