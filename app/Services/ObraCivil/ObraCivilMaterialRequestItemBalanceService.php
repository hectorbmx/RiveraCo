<?php

namespace App\Services\ObraCivil;

use App\Models\ObraCivilMaterialRequestItem;
use App\Models\OrdenCompraDetalle;
use Illuminate\Support\Collection;

class ObraCivilMaterialRequestItemBalanceService
{
    private const FINAL_ORDER_STATES = ['AUTORIZADA', 'VERIFICADA'];

    private const DRAFT_ORDER_STATES = ['BORRADOR', 'PROGRAMADA'];

    /**
     * @param  Collection<int, ObraCivilMaterialRequestItem>|array<int, ObraCivilMaterialRequestItem|int>  $items
     * @return Collection<int, array<string, float>>
     */
    public function summaries(Collection|array $items): Collection
    {
        $collection = collect($items);
        $itemIds = $collection
            ->map(fn ($item) => $item instanceof ObraCivilMaterialRequestItem ? $item->id : $item)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($itemIds->isEmpty()) {
            return collect();
        }

        $ordered = $this->sumByOrderStates($itemIds, self::FINAL_ORDER_STATES);
        $draft = $this->sumByOrderStates($itemIds, self::DRAFT_ORDER_STATES);

        return $itemIds->mapWithKeys(function (int $itemId) use ($ordered, $draft) {
            $orderedQuantity = (float) ($ordered->get($itemId) ?? 0);
            $draftQuantity = (float) ($draft->get($itemId) ?? 0);

            return [
                $itemId => [
                    'ordered_quantity' => round($orderedQuantity, 4),
                    'draft_quantity' => round($draftQuantity, 4),
                ],
            ];
        });
    }

    public function summary(ObraCivilMaterialRequestItem $item): array
    {
        return $this->summaries([$item])->get($item->id, [
            'ordered_quantity' => 0.0,
            'draft_quantity' => 0.0,
        ]);
    }

    /**
     * @param  Collection<int, int>  $itemIds
     * @param  array<int, string>  $states
     * @return Collection<int, float>
     */
    private function sumByOrderStates(Collection $itemIds, array $states): Collection
    {
        return OrdenCompraDetalle::query()
            ->join('ordenes_compra as oc', 'oc.id', '=', 'orden_compra_detalles.orden_compra_id')
            ->whereIn('orden_compra_detalles.obra_civil_material_request_item_id', $itemIds->all())
            ->whereIn('oc.estado', $states)
            ->selectRaw('orden_compra_detalles.obra_civil_material_request_item_id as item_id, SUM(orden_compra_detalles.cantidad) as quantity')
            ->groupBy('orden_compra_detalles.obra_civil_material_request_item_id')
            ->pluck('quantity', 'item_id')
            ->map(fn ($quantity) => (float) $quantity);
    }
}
