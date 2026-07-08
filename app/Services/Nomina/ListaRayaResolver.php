<?php

namespace App\Services\Nomina;

use App\Models\Empleado;
use App\Models\NominaListaRaya;
use App\Models\Obra;

class ListaRayaResolver
{
    public function syncObrasVivas(): void
    {
        Obra::query()
            ->where('estatus_nuevo', '!=', Obra::ESTATUS_CANCELADA)
            ->orderBy('clave_obra')
            ->chunkById(100, function ($obras) {
                foreach ($obras as $obra) {
                    NominaListaRaya::updateOrCreate(
                        [
                            'tipo' => NominaListaRaya::TIPO_OBRA,
                            'obra_id' => $obra->id,
                        ],
                        [
                            'nombre' => trim(($obra->clave_obra ? $obra->clave_obra . ' - ' : '') . ($obra->nombre ?? 'Obra #' . $obra->id)),
                            'area_id' => $obra->area_id,
                            'almacen_id' => null,
                            'activo' => true,
                            'es_automatica' => true,
                            'orden' => 100,
                        ]
                    );
                }
            });
    }

    public function resolverParaEmpleado(Empleado $empleado): ?NominaListaRaya
    {
        $obraActiva = $this->obraVivaDelEmpleado($empleado);

        if ($obraActiva) {
            return NominaListaRaya::firstOrCreate(
                [
                    'tipo' => NominaListaRaya::TIPO_OBRA,
                    'obra_id' => $obraActiva->id,
                ],
                [
                    'nombre' => trim(($obraActiva->clave_obra ? $obraActiva->clave_obra . ' - ' : '') . ($obraActiva->nombre ?? 'Obra #' . $obraActiva->id)),
                    'area_id' => $obraActiva->area_id,
                    'almacen_id' => null,
                    'activo' => true,
                    'es_automatica' => true,
                    'orden' => 100,
                ]
            );
        }

        if ($empleado->lista_raya_principal_id) {
            $principal = NominaListaRaya::query()
                ->whereKey($empleado->lista_raya_principal_id)
                ->where('activo', true)
                ->first();

            if ($principal) {
                return $principal;
            }
        }

        if ($empleado->Area) {
            $porArea = NominaListaRaya::query()
                ->where('tipo', NominaListaRaya::TIPO_AREA)
                ->where('area_id', $empleado->Area)
                ->where('activo', true)
                ->first();

            if ($porArea) {
                return $porArea;
            }
        }

        return NominaListaRaya::query()
            ->where('nombre', 'Sin clasificar')
            ->where('activo', true)
            ->first();
    }

    public function resolverParaObra(?int $obraId): ?NominaListaRaya
    {
        if (!$obraId) {
            return null;
        }

        $obra = Obra::query()
            ->whereKey($obraId)
            ->where('estatus_nuevo', '!=', Obra::ESTATUS_CANCELADA)
            ->first();

        if (!$obra) {
            return null;
        }

        return NominaListaRaya::firstOrCreate(
            [
                'tipo' => NominaListaRaya::TIPO_OBRA,
                'obra_id' => $obra->id,
            ],
            [
                'nombre' => trim(($obra->clave_obra ? $obra->clave_obra . ' - ' : '') . ($obra->nombre ?? 'Obra #' . $obra->id)),
                'area_id' => $obra->area_id,
                'almacen_id' => null,
                'activo' => true,
                'es_automatica' => true,
                'orden' => 100,
            ]
        );
    }
    public function obraVivaDelEmpleado(Empleado $empleado): ?Obra
    {
        return $empleado->obras()
            ->wherePivot('activo', 1)
            ->wherePivotNull('fecha_baja')
            ->where('obras.estatus_nuevo', '!=', Obra::ESTATUS_CANCELADA)
            ->orderByDesc('obra_empleado.fecha_alta')
            ->orderByDesc('obra_empleado.id')
            ->first();
    }
}
