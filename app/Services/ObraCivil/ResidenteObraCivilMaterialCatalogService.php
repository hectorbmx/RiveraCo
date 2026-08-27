<?php

namespace App\Services\ObraCivil;

use App\Models\ObraCivilCommercialMaterial;
use App\Models\ObraCivilInsumo;
use App\Models\ObraCivilMaterialGroup;
use App\Services\ObraCivilInsumoBalanceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ResidenteObraCivilMaterialCatalogService
{
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 30;

    public function __construct(
        private ObraCivilInsumoBalanceService $balanceService,
        private ObraCivilMaterialGroupResolver $groupResolver,
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
                    $resolution = $this->groupResolver->resolve($insumo);
                    $commercialMaterials = $resolution['commercial_materials'];

                    return array_merge([
                        'id' => $insumo->id,
                        'codigo' => $insumo->codigo,
                        'concepto' => $insumo->concepto,
                        'unidad' => $insumo->unidad,
                        'cantidad' => (float) $insumo->cantidad_presupuestada,
                        'usado' => (float) ($balance['used_quantity'] ?? 0),
                        'disponible' => (float) ($balance['available_quantity'] ?? $insumo->cantidad_presupuestada),
                    ], $this->commercialResolutionPayload($resolution, $commercialMaterials));
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

    private function commercialResolutionPayload(array $resolution, Collection $commercialMaterials): array
    {
        $matched = (bool) $resolution['matched'];
        $hasActiveProducts = $matched && $commercialMaterials->isNotEmpty();

        return [
            'has_commercial_products' => $hasActiveProducts,
            'commercial_resolution_status' => $this->resolutionStatus($resolution, $hasActiveProducts),
            'commercial_resolution_reason' => $resolution['reason'],
            'commercial_resolution_confidence' => $resolution['confidence'],
            'material_group' => $resolution['group'] instanceof ObraCivilMaterialGroup
                ? $this->materialGroupPayload($resolution['group'])
                : null,
            'commercial_products_count' => $commercialMaterials->count(),
            'commercial_products' => $commercialMaterials
                ->map(fn (ObraCivilCommercialMaterial $material) => $this->commercialMaterialPayload($material))
                ->values(),
        ];
    }

    private function resolutionStatus(array $resolution, bool $hasActiveProducts): string
    {
        if (! $resolution['matched']) {
            return $resolution['reason'] === 'ambiguous_match' ? 'ambiguous' : 'not_resolved';
        }

        return $hasActiveProducts ? 'ready' : 'group_found_no_active_products';
    }

    private function materialGroupPayload(ObraCivilMaterialGroup $group): array
    {
        return [
            'id' => (int) $group->id,
            'code' => $group->code,
            'name' => $group->name,
            'family' => $group->family,
            'grade' => $group->grade,
        ];
    }

    private function commercialMaterialPayload(ObraCivilCommercialMaterial $material): array
    {
        return [
            'id' => (int) $material->id,
            'sku' => $material->sku,
            'descripcion' => $material->descripcion,
            'category' => $material->category,
            'subcategory' => $material->subcategory,
            'grade' => $material->grade,
            'medida' => $material->medida,
            'diametro' => $material->diametro,
            'calibre_espesor' => $material->calibre_espesor,
            'longitud' => $material->longitud !== null ? (float) $material->longitud : null,
            'unidad_compra' => $material->unidad_compra,
            'conversion_type' => $material->conversion_type,
            'peso_por_metro' => $material->peso_por_metro !== null ? (float) $material->peso_por_metro : null,
            'peso_por_pieza' => $material->peso_por_pieza !== null ? (float) $material->peso_por_pieza : null,
            'peso_por_m2' => $material->peso_por_m2 !== null ? (float) $material->peso_por_m2 : null,
            'peso_por_rollo' => $material->peso_por_rollo !== null ? (float) $material->peso_por_rollo : null,
            'factor_conversion' => $material->factor_conversion !== null ? (float) $material->factor_conversion : null,
            'tolerance' => $material->tolerance,
            'validation_status' => $material->validation_status,
        ];
    }

    private function perPage(mixed $value): int
    {
        $perPage = (int) ($value ?: self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
