<?php

namespace App\Services;

use App\Models\ObraCivilInsumo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ObraCivilInsumoBalanceService
{
    public const EXCLUDED_STATES = ['CANCELADA'];

    public function summary(ObraCivilInsumo|int $insumo, ?int $excludeOrdenCompraId = null, ?int $excludeDetalleId = null): array
    {
        $insumoId = $insumo instanceof ObraCivilInsumo ? (int) $insumo->id : (int) $insumo;

        return $this->summaries([$insumoId], $excludeOrdenCompraId, $excludeDetalleId)
            ->get($insumoId, $this->emptySummary($insumoId));
    }

    public function summaries(iterable $insumos, ?int $excludeOrdenCompraId = null, ?int $excludeDetalleId = null): Collection
    {
        $ids = collect($insumos)
            ->map(fn ($insumo) => $insumo instanceof ObraCivilInsumo ? (int) $insumo->id : (int) $insumo)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $budgets = ObraCivilInsumo::query()
            ->whereIn('id', $ids)
            ->get(['id', 'cantidad_presupuestada', 'importe_importado', 'importe_calculado'])
            ->keyBy('id');

        $usedQuery = DB::table('orden_compra_detalles as d')
            ->join('ordenes_compra as oc', 'oc.id', '=', 'd.orden_compra_id')
            ->whereIn('d.obra_civil_insumo_id', $ids)
            ->where(function ($query) {
                $query->whereNull('oc.estado')
                    ->orWhereNotIn(DB::raw('UPPER(oc.estado)'), self::EXCLUDED_STATES);
            });

        if ($excludeOrdenCompraId !== null) {
            $usedQuery->where('d.orden_compra_id', '!=', $excludeOrdenCompraId);
        }

        if ($excludeDetalleId !== null) {
            $usedQuery->where('d.id', '!=', $excludeDetalleId);
        }

        $used = $usedQuery
            ->selectRaw('
                d.obra_civil_insumo_id,
                COALESCE(SUM(d.cantidad), 0) as used_quantity,
                COALESCE(SUM(d.importe), 0) as used_amount,
                COUNT(DISTINCT oc.id) as ordenes_count
            ')
            ->groupBy('d.obra_civil_insumo_id')
            ->get()
            ->keyBy('obra_civil_insumo_id');

        return $ids->mapWithKeys(function (int $id) use ($budgets, $used) {
            $insumo = $budgets->get($id);
            $spent = $used->get($id);

            $budgetQuantity = (float) ($insumo?->cantidad_presupuestada ?? 0);
            $budgetAmount = (float) ($insumo?->importe_importado ?? $insumo?->importe_calculado ?? 0);
            $usedQuantity = (float) ($spent->used_quantity ?? 0);
            $usedAmount = (float) ($spent->used_amount ?? 0);

            return [
                $id => [
                    'obra_civil_insumo_id' => $id,
                    'budget_quantity' => $budgetQuantity,
                    'budget_amount' => $budgetAmount,
                    'used_quantity' => $usedQuantity,
                    'used_amount' => $usedAmount,
                    'available_quantity' => $budgetQuantity - $usedQuantity,
                    'available_amount' => $budgetAmount - $usedAmount,
                    'ordenes_count' => (int) ($spent->ordenes_count ?? 0),
                ],
            ];
        });
    }

    public function hasAvailableQuantity(ObraCivilInsumo|int $insumo, float $quantity, ?int $excludeOrdenCompraId = null, ?int $excludeDetalleId = null): bool
    {
        return $this->summary($insumo, $excludeOrdenCompraId, $excludeDetalleId)['available_quantity'] >= $quantity;
    }

    public function hasAvailableAmount(ObraCivilInsumo|int $insumo, float $amount, ?int $excludeOrdenCompraId = null, ?int $excludeDetalleId = null): bool
    {
        return $this->summary($insumo, $excludeOrdenCompraId, $excludeDetalleId)['available_amount'] >= $amount;
    }

    private function emptySummary(int $insumoId): array
    {
        return [
            'obra_civil_insumo_id' => $insumoId,
            'budget_quantity' => 0.0,
            'budget_amount' => 0.0,
            'used_quantity' => 0.0,
            'used_amount' => 0.0,
            'available_quantity' => 0.0,
            'available_amount' => 0.0,
            'ordenes_count' => 0,
        ];
    }
}