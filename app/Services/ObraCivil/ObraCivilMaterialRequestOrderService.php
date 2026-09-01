<?php

namespace App\Services\ObraCivil;

use App\Models\Obra;
use App\Models\ObraCivilInsumo;
use App\Models\ObraCivilMaterialRequest;
use App\Models\ObraCivilMaterialRequestItem;
use App\Models\ObraCivilMaterialRequestOrderLink;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\User;
use App\Services\OrdenCompraTotalesService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ObraCivilMaterialRequestOrderService
{
    /**
     * @var array<int, string>
     */
    private const ORDERABLE_STATUSES = [
        ObraCivilMaterialRequest::STATUS_APROBADA,
        ObraCivilMaterialRequest::STATUS_APROBADA_PARCIAL,
    ];

    public function __construct(
        private readonly ObraCivilMaterialRequestItemBalanceService $itemBalanceService
    ) {
    }

    /**
     * @return Collection<int, ObraCivilMaterialRequestItem>
     */
    public function approvedPendingItemsForObra(Obra $obra): Collection
    {
        if (! $this->isCivil($obra)) {
            return collect();
        }

        $items = ObraCivilMaterialRequestItem::query()
            ->where('approved_quantity', '>', 0)
            ->whereHas('request', function ($query) use ($obra) {
                $query->where('obra_id', $obra->id)
                    ->whereIn('status', self::ORDERABLE_STATUSES);
            })
            ->whereHas('insumo', function ($query) use ($obra) {
                $query->where('obra_id', $obra->id)
                    ->where('is_active', true)
                    ->where('tipo', 'material');
            })
            ->with(['request.empleado', 'request.user', 'insumo.import'])
            ->orderByDesc(
                ObraCivilMaterialRequest::query()
                    ->select('reviewed_at')
                    ->whereColumn('obra_civil_material_requests.id', 'obra_civil_material_request_items.obra_civil_material_request_id')
                    ->limit(1)
            )
            ->orderByDesc('id')
            ->get();

        $balances = $this->itemBalanceService->summaries($items);

        return $items
            ->filter(function (ObraCivilMaterialRequestItem $item) use ($balances) {
                $approvedQuantity = (float) ($item->approved_quantity ?? 0);
                $balance = $balances->get($item->id, []);
                $orderedQuantity = (float) ($balance['ordered_quantity'] ?? 0);
                $draftQuantity = (float) ($balance['draft_quantity'] ?? 0);

                return ($approvedQuantity - $orderedQuantity - $draftQuantity) > 0.0001;
            })
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function approvedPendingItemOptions(Obra $obra): array
    {
        $items = $this->approvedPendingItemsForObra($obra);
        $balances = $this->itemBalanceService->summaries($items);

        return $items
            ->map(fn (ObraCivilMaterialRequestItem $item) => $this->itemOptionPayload($item, $balances->get($item->id, [])))
            ->values()
            ->all();
    }

    /**
     * Compatibilidad temporal con el endpoint anterior.
     *
     * @return array<int, array<string, mixed>>
     */
    public function approvedPendingOptions(Obra $obra): array
    {
        return $this->approvedPendingItemOptions($obra);
    }

    /**
     * @param  array<int, array{id:int|string, quantity:int|float|string|null, price?:int|float|string|null}>  $selectedItems
     * @return Collection<int, OrdenCompraDetalle>
     */
    public function attachApprovedItemsToOrder(array $selectedItems, OrdenCompra $orden, ?User $user = null): Collection
    {
        return DB::transaction(function () use ($selectedItems, $orden, $user) {
            $normalizedItems = $this->normalizeSelectedItems($selectedItems);

            if ($normalizedItems->isEmpty()) {
                return collect();
            }

            $orden = OrdenCompra::query()
                ->lockForUpdate()
                ->findOrFail($orden->id);

            $items = ObraCivilMaterialRequestItem::query()
                ->with(['request.orderLinks', 'request.empleado', 'request.user', 'insumo.import'])
                ->whereIn('id', $normalizedItems->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->count() !== $normalizedItems->count()) {
                throw ValidationException::withMessages([
                    'obra_civil_material_request_items' => ['Uno o mas materiales seleccionados ya no existen.'],
                ]);
            }

            $balances = $this->itemBalanceService->summaries($items->values());
            $createdDetails = collect();

            foreach ($normalizedItems as $itemId => $selection) {
                /** @var ObraCivilMaterialRequestItem $item */
                $item = $items->get($itemId);
                $request = $item->request;
                $insumo = $item->insumo;
                $requestedQuantity = (float) $selection['quantity'];

                $this->ensureCanAttachItem($item, $orden);

                $approvedQuantity = (float) ($item->approved_quantity ?? 0);
                $catalogPrice = (float) ($insumo->precio_unitario ?? 0);
                $requestedPrice = $selection['price'] ?? null;
                $price = $requestedPrice !== null ? (float) $requestedPrice : $catalogPrice;
                $balance = $balances->get($item->id, []);
                $orderedQuantity = (float) ($balance['ordered_quantity'] ?? 0);
                $draftQuantity = (float) ($balance['draft_quantity'] ?? 0);
                $availableToLoad = round($approvedQuantity - $orderedQuantity - $draftQuantity, 4);

                if ($requestedQuantity > $availableToLoad + 0.0001) {
                    throw ValidationException::withMessages([
                        'obra_civil_material_request_items' => [
                            sprintf(
                                'La cantidad para %s supera lo pendiente por cargar. Pendiente disponible: %s %s.',
                                $insumo?->codigo ?: 'material seleccionado',
                                number_format(max($availableToLoad, 0), 4),
                                $item->unit ?: $insumo?->unidad ?: ''
                            ),
                        ],
                    ]);
                }

                $detail = OrdenCompraDetalle::query()
                    ->where('orden_compra_id', $orden->id)
                    ->where('obra_civil_material_request_item_id', $item->id)
                    ->first();

                if ($detail) {
                    $detail->cantidad = $requestedQuantity;
                    $detail->precio_unitario = $price;
                    $detail->precio_tope = $catalogPrice;
                    $detail->importe = round($requestedQuantity * $price, 2);
                    $detail->obra_civil_insumo_snapshot = $this->insumoSnapshot($insumo, $item->insumo_snapshot ?? []);
                    $detail->save();
                    $createdDetails->push($detail);
                    continue;
                }

                $importe = round($requestedQuantity * $price, 2);
                $ivaPercent = (float) ($orden->iva ?? 0);

                $detail = OrdenCompraDetalle::create([
                    'orden_compra_id' => $orden->id,
                    'producto_id' => null,
                    'civil_concept_id' => null,
                    'civil_concept_snapshot' => null,
                    'obra_civil_insumo_id' => $insumo->id,
                    'obra_civil_insumo_snapshot' => $this->insumoSnapshot($insumo, $item->insumo_snapshot ?? []),
                    'obra_civil_material_request_item_id' => $item->id,
                    'legacy_prod_id' => null,
                    'descripcion' => $insumo->concepto,
                    'unidad' => $item->unit ?: $insumo->unidad,
                    'cantidad' => $requestedQuantity,
                    'precio_unitario' => $price,
                    'precio_tope' => $catalogPrice,
                    'descuento_porcentaje' => 0,
                    'descuento_importe' => 0,
                    'importe' => $importe,
                    'iva' => $ivaPercent,
                    'tipo_retencion_id' => null,
                    'retencion_porcentaje' => 0,
                    'retenciones' => 0,
                    'otros_impuestos' => 0,
                    'tipo_cambio' => (float) ($orden->tipo_cambio ?? 1),
                    'notas' => $this->detailNotes($request, $item),
                ]);

                $createdDetails->push($detail);
            }

            if ($createdDetails->isNotEmpty()) {
                $this->syncRequestLinks($createdDetails, $orden, $user);
                OrdenCompraTotalesService::recalcular($orden);
            }

            return $createdDetails;
        });
    }

    /**
     * @deprecated Use attachApprovedItemsToOrder(). Se conserva para no romper llamadas viejas durante el refactor.
     */
    public function attachApprovedRequestToOrder(
        ObraCivilMaterialRequest $request,
        OrdenCompra $orden,
        ?User $user = null
    ): Collection {
        $selectedItems = $request->approvedItems()
            ->get(['id', 'approved_quantity'])
            ->map(fn (ObraCivilMaterialRequestItem $item) => [
                'id' => $item->id,
                'quantity' => $item->approved_quantity,
            ])
            ->all();

        return $this->attachApprovedItemsToOrder($selectedItems, $orden, $user);
    }

    /**
     * @param  Collection<int, OrdenCompraDetalle>  $details
     */
    private function syncRequestLinks(Collection $details, OrdenCompra $orden, ?User $user): void
    {
        $details->each(fn (OrdenCompraDetalle $detail) => $detail->loadMissing('obraCivilMaterialRequestItem.request'));

        $details
            ->groupBy(fn (OrdenCompraDetalle $detail) => $detail->obraCivilMaterialRequestItem?->obra_civil_material_request_id)
            ->filter(fn (Collection $group, $requestId) => ! empty($requestId))
            ->each(function (Collection $group, int|string $requestId) use ($orden, $user) {
                ObraCivilMaterialRequestOrderLink::updateOrCreate(
                    [
                        'obra_civil_material_request_id' => (int) $requestId,
                        'orden_compra_id' => $orden->id,
                    ],
                    [
                        'status' => ObraCivilMaterialRequestOrderLink::STATUS_BORRADOR,
                        'created_by' => $user?->id,
                        'metadata' => [
                            'source' => 'orden_compra_create_items',
                            'loaded_detail_ids' => $group->pluck('id')->values()->all(),
                            'loaded_item_ids' => $group->pluck('obra_civil_material_request_item_id')->values()->all(),
                            'loaded_at' => now()->toIso8601String(),
                        ],
                    ]
                );
            });
    }

    /**
     * @param  array<int, array{id:int|string, quantity:int|float|string|null, price?:int|float|string|null}>  $selectedItems
     * @return Collection<int, array{quantity: float, price: float|null}>
     */
    private function normalizeSelectedItems(array $selectedItems): Collection
    {
        return collect($selectedItems)
            ->mapWithKeys(function (array $item) {
                $id = (int) ($item['id'] ?? 0);
                $quantity = (float) ($item['quantity'] ?? 0);
                $priceInput = $item['price'] ?? null;
                $price = $priceInput === null || $priceInput === ''
                    ? null
                    : round((float) $priceInput, 4);

                return $id > 0 && $quantity > 0
                    ? [$id => [
                        'quantity' => round($quantity, 4),
                        'price' => $price,
                    ]]
                    : [];
            });
    }

    private function ensureCanAttachItem(ObraCivilMaterialRequestItem $item, OrdenCompra $orden): void
    {
        $request = $item->request;
        $insumo = $item->insumo;

        if (! $request || (int) $request->obra_id !== (int) $orden->obra_id) {
            throw ValidationException::withMessages([
                'obra_civil_material_request_items' => ['Un material seleccionado no pertenece a la obra de la orden.'],
            ]);
        }

        if (! in_array($request->status, self::ORDERABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'obra_civil_material_request_items' => ['Todas las partidas deben venir de solicitudes aprobadas.'],
            ]);
        }

        if (! $insumo || ! $this->isValidMaterialForOrder($insumo, $orden)) {
            throw ValidationException::withMessages([
                'obra_civil_material_request_items' => ['Uno o mas insumos aprobados ya no pertenecen a los materiales activos de esta obra civil.'],
            ]);
        }

        if ((float) ($item->approved_quantity ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'obra_civil_material_request_items' => ['La partida seleccionada no tiene cantidad autorizada.'],
            ]);
        }
    }

    private function itemOptionPayload(ObraCivilMaterialRequestItem $item, array $balance): array
    {
        $request = $item->request;
        $insumo = $item->insumo;
        $approvedQuantity = (float) ($item->approved_quantity ?? 0);
        $orderedQuantity = (float) ($balance['ordered_quantity'] ?? 0);
        $draftQuantity = (float) ($balance['draft_quantity'] ?? 0);
        $pendingQuantity = max(round($approvedQuantity - $orderedQuantity, 4), 0);
        $availableToLoad = max(round($approvedQuantity - $orderedQuantity - $draftQuantity, 4), 0);

        return [
            'request_item_id' => $item->id,
            'request_id' => $request?->id,
            'request_folio' => $request?->folio,
            'request_status' => $request?->status,
            'submitted_at' => optional($request?->submitted_at)->toIso8601String(),
            'reviewed_at' => optional($request?->reviewed_at)->toIso8601String(),
            'solicitante' => $request?->empleado->nombre ?? $request?->user->name ?? 'Residente',
            'insumo_id' => $insumo?->id,
            'codigo' => $insumo?->codigo,
            'concepto' => $insumo?->concepto ?? ($item->insumo_snapshot['concepto'] ?? 'Material'),
            'unidad' => $item->unit ?: $insumo?->unidad,
            'requested_quantity' => round((float) $item->quantity, 4),
            'approved_quantity' => round($approvedQuantity, 4),
            'ordered_quantity' => round($orderedQuantity, 4),
            'draft_quantity' => round($draftQuantity, 4),
            'pending_order_quantity' => $pendingQuantity,
            'available_to_load_quantity' => $availableToLoad,
            'commercial_request' => $item->insumo_snapshot['commercial_request'] ?? null,
            'suggested_price' => round((float) ($insumo?->precio_unitario ?? 0), 4),
            'precio_tope' => round((float) ($insumo?->precio_unitario ?? 0), 4),
            'resident_notes' => $item->notes,
            'approval_notes' => $item->approval_notes,
            'label' => trim(sprintf(
                '%s - %s - %s %s pendientes',
                $request?->folio,
                $insumo?->codigo,
                number_format($availableToLoad, 4),
                $item->unit ?: $insumo?->unidad ?: ''
            )),
        ];
    }

    private function isValidMaterialForOrder(ObraCivilInsumo $insumo, OrdenCompra $orden): bool
    {
        return (int) $insumo->obra_id === (int) $orden->obra_id
            && $insumo->is_active
            && $insumo->tipo === 'material';
    }

    private function insumoSnapshot(ObraCivilInsumo $insumo, ?array $sourceSnapshot = null): array
    {
        $import = $insumo->import;

        $snapshot = [
            'obra_civil_insumo_import_id' => $import?->id,
            'filename' => $import?->filename,
            'sheet_name' => $import?->sheet_name,
            'source_row' => $insumo->source_row,
            'tipo' => $insumo->tipo,
            'codigo' => $insumo->codigo,
            'concepto' => $insumo->concepto,
            'unidad' => $insumo->unidad,
            'cantidad_presupuestada' => (float) $insumo->cantidad_presupuestada,
            'precio_unitario' => (float) $insumo->precio_unitario,
            'importe_importado' => (float) $insumo->importe_importado,
            'importe_calculado' => (float) $insumo->importe_calculado,
        ];

        if (is_array($sourceSnapshot) && isset($sourceSnapshot['commercial_request'])) {
            $snapshot['commercial_request'] = $sourceSnapshot['commercial_request'];
        }

        return $snapshot;
    }

    private function detailNotes(ObraCivilMaterialRequest $request, ObraCivilMaterialRequestItem $item): ?string
    {
        $parts = array_filter([
            'Solicitud: ' . $request->folio,
            $item->notes ? 'Nota residente: ' . $item->notes : null,
            $item->approval_notes ? 'Nota aprobacion: ' . $item->approval_notes : null,
        ]);

        return $parts !== [] ? implode("\n", $parts) : null;
    }

    private function isCivil(Obra $obra): bool
    {
        return in_array(strtoupper((string) $obra->tipo_obra), ['OBRA_CIVIL', 'CIVIL'], true);
    }
}

