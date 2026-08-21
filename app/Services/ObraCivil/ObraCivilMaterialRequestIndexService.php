<?php

namespace App\Services\ObraCivil;

use App\Models\Obra;
use App\Models\ObraCivilMaterialRequest;
use App\Models\ObraCivilMaterialRequestItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ObraCivilMaterialRequestIndexService
{
    public const UNATTENDED_STATUSES = [
        ObraCivilMaterialRequest::STATUS_ENVIADA,
        ObraCivilMaterialRequest::STATUS_EN_REVISION,
    ];

    /**
     * @param array<string, mixed> $filters
     */
    public function list(Obra $obra, array $filters): LengthAwarePaginator
    {
        return ObraCivilMaterialRequest::query()
            ->where('obra_id', $obra->id)
            ->with([
                'empleado',
                'user',
                'reviewedBy',
                'ordenCompra',
                'items.insumo',
                'items.ordenCompraDetalles.orden',
            ])
            ->when($this->unattendedOnly($filters), fn (Builder $query) => $query->whereIn('status', self::UNATTENDED_STATUSES))
            ->when($this->statusFilter($filters) !== null, fn (Builder $query) => $query->where('status', $this->statusFilter($filters)))
            ->when($this->searchFilter($filters) !== null, function (Builder $query) use ($filters) {
                $search = $this->searchFilter($filters);

                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('folio', 'like', "%{$search}%")
                        ->orWhereHas('items.insumo', function (Builder $insumoQuery) use ($search) {
                            $insumoQuery->where('codigo', 'like', "%{$search}%")
                                ->orWhere('concepto', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function stats(Obra $obra): array
    {
        $statusCounts = ObraCivilMaterialRequest::query()
            ->where('obra_id', $obra->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $statusCounts->sum(),
            'unattended' => (int) ObraCivilMaterialRequest::query()
                ->where('obra_id', $obra->id)
                ->whereIn('status', self::UNATTENDED_STATUSES)
                ->count(),
            'enviada' => (int) ($statusCounts[ObraCivilMaterialRequest::STATUS_ENVIADA] ?? 0),
            'en_revision' => (int) ($statusCounts[ObraCivilMaterialRequest::STATUS_EN_REVISION] ?? 0),
            'aprobada' => (int) (($statusCounts[ObraCivilMaterialRequest::STATUS_APROBADA] ?? 0)
                + ($statusCounts[ObraCivilMaterialRequest::STATUS_APROBADA_PARCIAL] ?? 0)),
            'aprobada_parcial' => (int) ($statusCounts[ObraCivilMaterialRequest::STATUS_APROBADA_PARCIAL] ?? 0),
            'rechazada' => (int) ($statusCounts[ObraCivilMaterialRequest::STATUS_RECHAZADA] ?? 0),
            'convertida_a_oc' => (int) ($statusCounts[ObraCivilMaterialRequest::STATUS_CONVERTIDA_A_OC] ?? 0),
        ];
    }

    /**
     * @param iterable<int> $insumoIds
     * @return Collection<int, float>
     */
    public function pendingQuantitiesByInsumo(Obra $obra, iterable $insumoIds): Collection
    {
        $ids = collect($insumoIds)->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return ObraCivilMaterialRequestItem::query()
            ->join('obra_civil_material_requests as requests', 'requests.id', '=', 'obra_civil_material_request_items.obra_civil_material_request_id')
            ->where('requests.obra_id', $obra->id)
            ->whereIn('requests.status', self::UNATTENDED_STATUSES)
            ->whereIn('obra_civil_material_request_items.obra_civil_insumo_id', $ids)
            ->selectRaw('obra_civil_material_request_items.obra_civil_insumo_id, SUM(obra_civil_material_request_items.quantity) as pending_quantity')
            ->groupBy('obra_civil_material_request_items.obra_civil_insumo_id')
            ->pluck('pending_quantity', 'obra_civil_material_request_items.obra_civil_insumo_id')
            ->map(fn ($quantity) => (float) $quantity);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function statusFilter(array $filters): ?string
    {
        $status = trim((string) ($filters['status'] ?? ''));

        return in_array($status, [
            ObraCivilMaterialRequest::STATUS_ENVIADA,
            ObraCivilMaterialRequest::STATUS_EN_REVISION,
            ObraCivilMaterialRequest::STATUS_APROBADA,
            ObraCivilMaterialRequest::STATUS_APROBADA_PARCIAL,
            ObraCivilMaterialRequest::STATUS_RECHAZADA,
            ObraCivilMaterialRequest::STATUS_CONVERTIDA_A_OC,
            ObraCivilMaterialRequest::STATUS_CANCELADA,
        ], true) ? $status : null;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function searchFilter(array $filters): ?string
    {
        $search = trim((string) ($filters['q'] ?? ''));

        return $search !== '' ? str_replace(['%', '_'], ['\\%', '\\_'], $search) : null;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function unattendedOnly(array $filters): bool
    {
        return (string) ($filters['scope'] ?? '') === 'no_atendidas';
    }
}






