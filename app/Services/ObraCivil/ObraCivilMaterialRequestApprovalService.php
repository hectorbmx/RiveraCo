<?php

namespace App\Services\ObraCivil;

use App\Models\ObraCivilMaterialRequest;
use App\Models\ObraCivilMaterialRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ObraCivilMaterialRequestApprovalService
{
    /**
     * @var array<int, string>
     */
    private const EDITABLE_STATUSES = [
        ObraCivilMaterialRequest::STATUS_ENVIADA,
        ObraCivilMaterialRequest::STATUS_EN_REVISION,
    ];

    /**
     * @var array<int, string>
     */
    private const APPROVED_STATUSES = [
        ObraCivilMaterialRequest::STATUS_APROBADA,
        ObraCivilMaterialRequest::STATUS_APROBADA_PARCIAL,
    ];

    public function approveFull(ObraCivilMaterialRequest $request, User $user, ?string $notes = null): ObraCivilMaterialRequest
    {
        $request->loadMissing('items');

        $quantities = $request->items
            ->mapWithKeys(fn (ObraCivilMaterialRequestItem $item) => [
                $item->id => [
                    'approved_quantity' => $item->quantity,
                    'approval_notes' => $notes,
                ],
            ])
            ->all();

        return $this->approveWithQuantities($request, $quantities, $user, $notes);
    }

    /**
     * @param array<int|string, mixed> $items
     */
    public function approveWithQuantities(ObraCivilMaterialRequest $request, array $items, User $user, ?string $notes = null): ObraCivilMaterialRequest
    {
        return DB::transaction(function () use ($request, $items, $user, $notes) {
            $request = ObraCivilMaterialRequest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($request->id);

            $normalizedItems = $this->normalizeItems($items);

            $this->ensureCanApprove($request, $normalizedItems);

            if ($this->isApprovedWithSameQuantities($request, $normalizedItems)) {
                return $request->load(['items.insumo', 'reviewedBy']);
            }

            $totalApproved = 0.0;
            $hasPartialChange = false;

            foreach ($request->items as $item) {
                $approvedQuantity = $normalizedItems[$item->id]['approved_quantity'];
                $approvalNotes = $normalizedItems[$item->id]['approval_notes'] ?? $notes;
                $requestedQuantity = (float) $item->quantity;

                $totalApproved += $approvedQuantity;

                if ($this->compareDecimal($approvedQuantity, $requestedQuantity) !== 0) {
                    $hasPartialChange = true;
                }

                $item->forceFill([
                    'approved_quantity' => $approvedQuantity,
                    'approval_notes' => $approvalNotes,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ])->save();
            }

            if ($this->compareDecimal($totalApproved, 0.0) <= 0) {
                throw ValidationException::withMessages([
                    'items' => ['Para aprobar la solicitud debes autorizar al menos una cantidad mayor a cero. Si no se surtira nada, usa rechazar.'],
                ]);
            }

            $request->forceFill([
                'status' => $hasPartialChange
                    ? ObraCivilMaterialRequest::STATUS_APROBADA_PARCIAL
                    : ObraCivilMaterialRequest::STATUS_APROBADA,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'metadata' => $this->mergeApprovalMetadata($request, $totalApproved, $hasPartialChange, $notes),
            ])->save();

            return $request->load(['items.insumo', 'reviewedBy']);
        });
    }

    public function reject(ObraCivilMaterialRequest $request, User $user, ?string $reason = null): ObraCivilMaterialRequest
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            $request = ObraCivilMaterialRequest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($request->status === ObraCivilMaterialRequest::STATUS_RECHAZADA) {
                return $request->load(['items.insumo', 'reviewedBy']);
            }

            $this->ensureEditable($request);

            foreach ($request->items as $item) {
                $item->forceFill([
                    'approved_quantity' => 0,
                    'approval_notes' => $reason,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ])->save();
            }

            $request->forceFill([
                'status' => ObraCivilMaterialRequest::STATUS_RECHAZADA,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'metadata' => $this->mergeRejectionMetadata($request, $reason),
            ])->save();

            return $request->load(['items.insumo', 'reviewedBy']);
        });
    }

    /**
     * @param array<int|string, mixed> $items
     * @return array<int, array{approved_quantity: float, approval_notes?: string|null}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $itemId = (int) ($value['id'] ?? $value['item_id'] ?? $key);
                $quantity = $value['approved_quantity'] ?? $value['quantity'] ?? null;
                $approvalNotes = array_key_exists('approval_notes', $value)
                    ? $this->nullableString($value['approval_notes'])
                    : null;
            } else {
                $itemId = (int) $key;
                $quantity = $value;
                $approvalNotes = null;
            }

            if ($itemId <= 0) {
                throw ValidationException::withMessages([
                    'items' => ['Uno o mas renglones de aprobacion no tienen un identificador valido.'],
                ]);
            }

            if (! is_numeric($quantity)) {
                throw ValidationException::withMessages([
                    "items.{$itemId}.approved_quantity" => ['La cantidad autorizada debe ser numerica.'],
                ]);
            }

            $normalized[$itemId] = [
                'approved_quantity' => round((float) $quantity, 4),
            ];

            if ($approvalNotes !== null) {
                $normalized[$itemId]['approval_notes'] = $approvalNotes;
            }
        }

        return $normalized;
    }

    /**
     * @param array<int, array{approved_quantity: float, approval_notes?: string|null}> $items
     */
    private function ensureCanApprove(ObraCivilMaterialRequest $request, array $items): void
    {
        if (in_array($request->status, self::APPROVED_STATUSES, true)) {
            return;
        }

        $this->ensureEditable($request);

        $requestItemIds = $request->items->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $payloadItemIds = collect(array_keys($items))->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($requestItemIds !== $payloadItemIds) {
            throw ValidationException::withMessages([
                'items' => ['La aprobacion debe incluir exactamente los renglones de la solicitud.'],
            ]);
        }

        foreach ($request->items as $item) {
            $approvedQuantity = $items[$item->id]['approved_quantity'];
            $requestedQuantity = (float) $item->quantity;

            if ($this->compareDecimal($approvedQuantity, 0.0) < 0) {
                throw ValidationException::withMessages([
                    "items.{$item->id}.approved_quantity" => ['La cantidad autorizada no puede ser negativa.'],
                ]);
            }

            if ($this->compareDecimal($approvedQuantity, $requestedQuantity) > 0) {
                throw ValidationException::withMessages([
                    "items.{$item->id}.approved_quantity" => ['La cantidad autorizada no puede ser mayor a la solicitada.'],
                ]);
            }
        }
    }

    private function ensureEditable(ObraCivilMaterialRequest $request): void
    {
        if (! in_array($request->status, self::EDITABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => ['La solicitud ya no se puede modificar en su estado actual.'],
            ]);
        }
    }

    /**
     * @param array<int, array{approved_quantity: float, approval_notes?: string|null}> $items
     */
    private function isApprovedWithSameQuantities(ObraCivilMaterialRequest $request, array $items): bool
    {
        if (! in_array($request->status, self::APPROVED_STATUSES, true)) {
            return false;
        }

        $requestItemIds = $request->items->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $payloadItemIds = collect(array_keys($items))->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($requestItemIds !== $payloadItemIds) {
            throw ValidationException::withMessages([
                'items' => ['La solicitud ya fue aprobada. Para reintentar, debes enviar exactamente los mismos renglones y cantidades.'],
            ]);
        }

        foreach ($request->items as $item) {
            if ($this->compareDecimal((float) $item->approved_quantity, $items[$item->id]['approved_quantity']) !== 0) {
                throw ValidationException::withMessages([
                    'status' => ['La solicitud ya fue aprobada con cantidades distintas. No se puede reaplicar otra aprobacion sin permiso especial.'],
                ]);
            }
        }

        return true;
    }

    private function mergeApprovalMetadata(ObraCivilMaterialRequest $request, float $totalApproved, bool $hasPartialChange, ?string $notes): array
    {
        $metadata = $request->metadata ?? [];

        return array_merge($metadata, [
            'approval' => [
                'approved_total_quantity' => round($totalApproved, 4),
                'has_partial_changes' => $hasPartialChange,
                'notes' => $this->nullableString($notes),
                'resolved_as_closed' => true,
                'resolved_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function mergeRejectionMetadata(ObraCivilMaterialRequest $request, ?string $reason): array
    {
        $metadata = $request->metadata ?? [];

        return array_merge($metadata, [
            'rejection' => [
                'reason' => $this->nullableString($reason),
                'resolved_as_closed' => true,
                'resolved_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function compareDecimal(float $left, float $right): int
    {
        return round($left - $right, 4) <=> 0.0;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}


