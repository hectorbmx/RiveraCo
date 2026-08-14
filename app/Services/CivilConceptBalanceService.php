<?php

namespace App\Services;

use App\Models\CivilConcept;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CivilConceptBalanceService
{
    public const COMMITTED_STATES = ['AUTORIZADA', 'VERIFICADA'];

    public function summary(CivilConcept|int $concept, ?int $excludeOrdenCompraId = null): array
    {
        $conceptId = $concept instanceof CivilConcept ? (int) $concept->id : (int) $concept;

        return $this->summaries([$conceptId], $excludeOrdenCompraId)->get($conceptId, $this->emptySummary($conceptId));
    }

    public function summaries(iterable $concepts, ?int $excludeOrdenCompraId = null): Collection
    {
        $ids = collect($concepts)
            ->map(fn ($concept) => $concept instanceof CivilConcept ? (int) $concept->id : (int) $concept)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $budgets = CivilConcept::query()
            ->whereIn('id', $ids)
            ->get(['id', 'budget_quantity', 'budget_amount'])
            ->keyBy('id');

        $used = DB::table('orden_compra_detalles as d')
            ->join('ordenes_compra as oc', 'oc.id', '=', 'd.orden_compra_id')
            ->whereIn('d.civil_concept_id', $ids)
            ->whereIn('oc.estado', self::COMMITTED_STATES)
            ->when($excludeOrdenCompraId, fn ($query) => $query->where('oc.id', '!=', $excludeOrdenCompraId))
            ->selectRaw('
                d.civil_concept_id,
                COALESCE(SUM(d.cantidad), 0) as used_quantity,
                COALESCE(SUM(d.importe), 0) as used_amount,
                COUNT(DISTINCT oc.id) as orders_count
            ')
            ->groupBy('d.civil_concept_id')
            ->get()
            ->keyBy('civil_concept_id');

        return $ids->mapWithKeys(function (int $id) use ($budgets, $used) {
            $concept = $budgets->get($id);
            $spent = $used->get($id);

            $budgetQuantity = (float) ($concept?->budget_quantity ?? 0);
            $budgetAmount = (float) ($concept?->budget_amount ?? 0);
            $usedQuantity = (float) ($spent->used_quantity ?? 0);
            $usedAmount = (float) ($spent->used_amount ?? 0);

            return [
                $id => [
                    'civil_concept_id' => $id,
                    'budget_quantity' => $budgetQuantity,
                    'budget_amount' => $budgetAmount,
                    'used_quantity' => $usedQuantity,
                    'used_amount' => $usedAmount,
                    'available_quantity' => $budgetQuantity - $usedQuantity,
                    'available_amount' => $budgetAmount - $usedAmount,
                    'orders_count' => (int) ($spent->orders_count ?? 0),
                ],
            ];
        });
    }

    public function hasAvailableQuantity(CivilConcept|int $concept, float $quantity, ?int $excludeOrdenCompraId = null): bool
    {
        return $this->summary($concept, $excludeOrdenCompraId)['available_quantity'] >= $quantity;
    }

    public function hasAvailableAmount(CivilConcept|int $concept, float $amount, ?int $excludeOrdenCompraId = null): bool
    {
        return $this->summary($concept, $excludeOrdenCompraId)['available_amount'] >= $amount;
    }

    private function emptySummary(int $conceptId): array
    {
        return [
            'civil_concept_id' => $conceptId,
            'budget_quantity' => 0.0,
            'budget_amount' => 0.0,
            'used_quantity' => 0.0,
            'used_amount' => 0.0,
            'available_quantity' => 0.0,
            'available_amount' => 0.0,
            'orders_count' => 0,
        ];
    }
}