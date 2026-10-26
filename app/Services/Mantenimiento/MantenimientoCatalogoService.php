<?php

namespace App\Services\Mantenimiento;

use App\Models\Almacen;
use App\Models\EmpresaServicioPreventivoMaterial;
use App\Models\EmpresaServicioPreventivoTipo;
use App\Models\InventarioStock;

class MantenimientoCatalogoService
{
    public function serviciosPreventivosPorAmbito(string $ambito)
    {
        return EmpresaServicioPreventivoTipo::query()
            ->where('ambito', $ambito)
            ->where('activo', true)
            ->ordenados()
            ->get();
    }

    public function materialesPreventivosParaMaquina(?int $maquinaId): array
    {
        if (! $maquinaId) {
            return [];
        }

        $materiales = EmpresaServicioPreventivoMaterial::query()
            ->where('activo', true)
            ->whereHas('servicioPreventivoTipo', function ($query) {
                $query->where('ambito', EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA)
                    ->where('activo', true);
            })
            ->where(function ($query) use ($maquinaId) {
                $query->whereNull('maquina_id')
                    ->orWhere('maquina_id', $maquinaId);
            })
            ->with('producto:id,nombre,sku,unidad')
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->groupBy('servicio_preventivo_tipo_id');

        $almacenGiralda = $this->almacenGiralda();

        $stockPorProducto = $almacenGiralda
            ? InventarioStock::query()
                ->where('almacen_id', $almacenGiralda->id)
                ->get()
                ->keyBy('producto_id')
            : collect();

        return $materiales->map(function ($items) use ($maquinaId, $stockPorProducto, $almacenGiralda) {
            $especificos = $items->where('maquina_id', $maquinaId);
            $efectivos = $especificos->isNotEmpty()
                ? $especificos
                : $items->whereNull('maquina_id');

            return $efectivos->values()->map(function (EmpresaServicioPreventivoMaterial $material) use ($stockPorProducto, $almacenGiralda) {
                $stock = $stockPorProducto->get($material->producto_id);
                $requerido = (float) $material->cantidad_requerida;
                $disponible = max(0, (float) ($stock?->stock_actual ?? 0) - (float) ($stock?->stock_reservado ?? 0));
                $faltante = max(0, $requerido - $disponible);

                return [
                    'id' => $material->id,
                    'producto_id' => $material->producto_id,
                    'producto' => $material->producto?->nombre,
                    'sku' => $material->producto?->sku,
                    'cantidad' => $requerido,
                    'unidad' => $material->unidad ?: $material->producto?->unidad,
                    'especifico' => $material->maquina_id !== null,
                    'disponible' => $disponible,
                    'faltante' => $faltante,
                    'estado' => $faltante <= 0 ? 'disponible' : ($disponible > 0 ? 'parcial' : 'sin_existencia'),
                    'almacen' => $almacenGiralda?->nombre,
                ];
            })->all();
        })->all();
    }

    public function almacenGiralda(): ?Almacen
    {
        return Almacen::query()
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereRaw('LOWER(codigo) = ?', ['gl'])
                    ->orWhereRaw('LOWER(nombre) like ?', ['%giralda%']);
            })
            ->orderByRaw("CASE WHEN LOWER(codigo) = 'gl' THEN 0 ELSE 1 END")
            ->first();
    }
}
