<?php

namespace App\Services\ObraCivil;

use App\Models\ObraCivilInsumo;
use App\Services\ObraCivilInsumoBalanceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ResidenteObraCivilMaterialCatalogService
{
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 30;

    public function __construct(
        private ObraCivilInsumoBalanceService $balanceService,
    ) {
    }

    public function search(ResidenteObraCivilContext $context, array $filters = []): array
    {
        $perPage = $this->perPage($filters['per_page'] ?? null);
        $q = trim((string) ($filters['q'] ?? ''));

        $paginator = ObraCivilInsumo::query()
            ->where('obra_id', $context->obra->id)
            ->where('is_active', true)
            ->where('tipo', 'material')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->where('codigo', 'like', '%' . $q . '%')
                        ->orWhere('concepto', 'like', '%' . $q . '%')
                        ->orWhere('unidad', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('sort_order')
            ->orderBy('codigo')
            ->paginate($perPage);

        return $this->mapPaginator($paginator);
    }

    private function mapPaginator(LengthAwarePaginator $paginator): array
    {
        $insumos = collect($paginator->items());
        $balances = $this->balanceService->summaries($insumos->pluck('id'));

        return [
            'data' => $insumos
                ->map(function (ObraCivilInsumo $insumo) use ($balances) {
                    $balance = $balances->get((int) $insumo->id, []);

                    return [
                        'id' => $insumo->id,
                        'codigo' => $insumo->codigo,
                        'concepto' => $insumo->concepto,
                        'unidad' => $insumo->unidad,
                        'cantidad' => (float) $insumo->cantidad_presupuestada,
                        'usado' => (float) ($balance['used_quantity'] ?? 0),
                        'disponible' => (float) ($balance['available_quantity'] ?? $insumo->cantidad_presupuestada),
                    ];
                })
                ->values(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'has_more' => $paginator->hasMorePages(),
                'total' => $paginator->total(),
            ],
        ];
    }

    private function perPage(mixed $value): int
    {
        $perPage = (int) ($value ?: self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
