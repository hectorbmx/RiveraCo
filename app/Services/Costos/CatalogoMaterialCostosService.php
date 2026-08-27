<?php

namespace App\Services\Costos;

use App\Models\ObraCivilCommercialMaterial;
use App\Models\ObraCivilMaterialGroup;
use App\ViewModels\Costos\CommercialMaterialRow;
use App\ViewModels\Costos\MaterialFamilyRow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CatalogoMaterialCostosService
{
    public function indexData(Request $request): array
    {
        $filters = $this->filtersFromRequest($request);
        $catalogView = $filters['vista'] === 'hijos' ? 'hijos' : 'familias';

        $families = $this->familyQuery($filters)
            ->orderBy('family')
            ->orderBy('grade')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $families->getCollection()->transform(
            fn (ObraCivilMaterialGroup $group) => MaterialFamilyRow::fromGroup($group)
        );

        $children = $this->allChildrenQuery($filters)
            ->paginate(40)
            ->withQueryString();

        $children->getCollection()->transform(
            fn (ObraCivilCommercialMaterial $material) => CommercialMaterialRow::fromMaterial($material)
        );

        return [
            'catalogView' => $catalogView,
            'families' => $families,
            'children' => $children,
            'stats' => $this->stats(),
            'filters' => $filters,
            'familiesCatalog' => $this->familiesCatalog(),
            'categoriesCatalog' => $this->categoriesCatalog(),
            'validationStatuses' => $this->validationStatuses(),
            'statusOptions' => $this->statusOptions(),
            'childStatusOptions' => $this->childStatusOptions(),
        ];
    }

    public function showData(Request $request, ObraCivilMaterialGroup $group): array
    {
        $filters = $this->childFiltersFromRequest($request);

        $group->loadCount([
            'commercialMaterials',
            'commercialMaterials as active_children_count' => fn (Builder $query) => $query->where('is_active', true),
            'commercialMaterials as inactive_children_count' => fn (Builder $query) => $query->where('is_active', false),
            'commercialMaterials as pending_validation_count' => fn (Builder $query) => $query
                ->where('is_active', true)
                ->where(fn (Builder $statusQuery) => $this->pendingValidationWhere($statusQuery)),
        ]);

        $children = ObraCivilCommercialMaterial::query()
            ->where('obra_civil_material_group_id', $group->id)
            ->when($filters['q'] !== '', fn (Builder $query) => $this->applyChildSearch($query, $filters['q']))
            ->when($filters['category'] !== '', fn (Builder $query) => $query->where('category', $filters['category']))
            ->when($filters['validation_status'] !== '', fn (Builder $query) => $query->where('validation_status', $filters['validation_status']))
            ->when($filters['state'] === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($filters['state'] === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($filters['state'] === 'pending_validation', fn (Builder $query) => $query->where('is_active', true)->where(fn (Builder $statusQuery) => $this->pendingValidationWhere($statusQuery)))
            ->orderBy('is_active', 'desc')
            ->orderBy('category')
            ->orderBy('subcategory')
            ->orderBy('medida')
            ->orderBy('calibre_espesor')
            ->orderBy('sku')
            ->paginate(25)
            ->withQueryString();

        $children->getCollection()->transform(
            fn (ObraCivilCommercialMaterial $material) => CommercialMaterialRow::fromMaterial($material)
        );

        return [
            'group' => $group,
            'familyRow' => MaterialFamilyRow::fromGroup($group),
            'children' => $children,
            'filters' => $filters,
            'rules' => $this->rulesForView($group),
            'categoriesCatalog' => $this->categoriesCatalogForGroup($group),
            'validationStatuses' => $this->validationStatusesForGroup($group),
            'childStatusOptions' => $this->childStatusOptions(),
        ];
    }

    private function familyQuery(array $filters): Builder
    {
        return ObraCivilMaterialGroup::query()
            ->withCount([
                'commercialMaterials',
                'commercialMaterials as active_children_count' => fn (Builder $query) => $query->where('is_active', true),
                'commercialMaterials as pending_validation_count' => fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->where(fn (Builder $statusQuery) => $this->pendingValidationWhere($statusQuery)),
            ])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $term = '%' . $filters['q'] . '%';

                $query->where(function (Builder $search) use ($term) {
                    $search
                        ->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('family', 'like', $term)
                        ->orWhere('grade', 'like', $term)
                        ->orWhereHas('commercialMaterials', fn (Builder $childQuery) => $this->applyChildSearch($childQuery, trim($term, '%')));
                });
            })
            ->when($filters['family'] !== '', fn (Builder $query) => $query->where('family', $filters['family']))
            ->when($filters['category'] !== '', fn (Builder $query) => $query->whereHas('commercialMaterials', fn (Builder $childQuery) => $childQuery->where('category', $filters['category'])))
            ->when($filters['validation_status'] !== '', fn (Builder $query) => $query->whereHas('commercialMaterials', fn (Builder $childQuery) => $childQuery->where('validation_status', $filters['validation_status'])))
            ->when($filters['state'] === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($filters['state'] === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($filters['state'] === 'without_active_children', fn (Builder $query) => $query->has('commercialMaterials')->whereDoesntHave('commercialMaterials', fn (Builder $childQuery) => $childQuery->where('is_active', true)))
            ->when($filters['state'] === 'pending_validation', fn (Builder $query) => $query->whereHas('commercialMaterials', fn (Builder $childQuery) => $childQuery->where('is_active', true)->where(fn (Builder $statusQuery) => $this->pendingValidationWhere($statusQuery))));
    }

    private function allChildrenQuery(array $filters): Builder
    {
        return ObraCivilCommercialMaterial::query()
            ->with('group:id,code,name,family,grade')
            ->when($filters['q'] !== '', fn (Builder $query) => $this->applyChildSearch($query, $filters['q']))
            ->when($filters['family'] !== '', fn (Builder $query) => $query->whereHas('group', fn (Builder $groupQuery) => $groupQuery->where('family', $filters['family'])))
            ->when($filters['category'] !== '', fn (Builder $query) => $query->where('category', $filters['category']))
            ->when($filters['validation_status'] !== '', fn (Builder $query) => $query->where('validation_status', $filters['validation_status']))
            ->when($filters['state'] === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($filters['state'] === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($filters['state'] === 'pending_validation', fn (Builder $query) => $query->where('is_active', true)->where(fn (Builder $statusQuery) => $this->pendingValidationWhere($statusQuery)))
            ->orderBy('is_active', 'desc')
            ->orderBy('category')
            ->orderBy('subcategory')
            ->orderBy('medida')
            ->orderBy('calibre_espesor')
            ->orderBy('sku');
    }

    private function applyChildSearch(Builder $query, string $value): void
    {
        $term = '%' . trim($value, '%') . '%';

        $query->where(function (Builder $search) use ($term) {
            $search
                ->where('sku', 'like', $term)
                ->orWhere('descripcion', 'like', $term)
                ->orWhere('category', 'like', $term)
                ->orWhere('subcategory', 'like', $term)
                ->orWhere('medida', 'like', $term)
                ->orWhere('diametro', 'like', $term)
                ->orWhere('calibre_espesor', 'like', $term)
                ->orWhereHas('group', function (Builder $groupQuery) use ($term) {
                    $groupQuery
                        ->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('family', 'like', $term)
                        ->orWhere('grade', 'like', $term);
                });
        });
    }

    private function pendingValidationWhere(Builder $query): void
    {
        $query
            ->whereNull('validation_status')
            ->orWhere('validation_status', 'like', '%validar%')
            ->orWhere('validation_status', 'like', '%Validar%')
            ->orWhere('validation_status', 'like', '%confirmar%')
            ->orWhere('validation_status', 'like', '%Confirmar%');
    }

    private function stats(): array
    {
        $pendingValidation = ObraCivilCommercialMaterial::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $this->pendingValidationWhere($query))
            ->count();

        return [
            'families' => ObraCivilMaterialGroup::count(),
            'active_families' => ObraCivilMaterialGroup::where('is_active', true)->count(),
            'children' => ObraCivilCommercialMaterial::count(),
            'active_children' => ObraCivilCommercialMaterial::where('is_active', true)->count(),
            'inactive_children' => ObraCivilCommercialMaterial::where('is_active', false)->count(),
            'pending_validation' => $pendingValidation,
            'without_active_children' => ObraCivilMaterialGroup::query()
                ->has('commercialMaterials')
                ->whereDoesntHave('commercialMaterials', fn (Builder $query) => $query->where('is_active', true))
                ->count(),
        ];
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'vista' => trim((string) $request->query('vista', 'familias')),
            'q' => trim((string) $request->query('q', '')),
            'family' => trim((string) $request->query('family', '')),
            'category' => trim((string) $request->query('category', '')),
            'validation_status' => trim((string) $request->query('validation_status', '')),
            'state' => trim((string) $request->query('state', '')),
        ];
    }

    private function childFiltersFromRequest(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'category' => trim((string) $request->query('category', '')),
            'validation_status' => trim((string) $request->query('validation_status', '')),
            'state' => trim((string) $request->query('state', '')),
        ];
    }

    private function rulesForView(ObraCivilMaterialGroup $group): array
    {
        $rules = $group->match_rules ?? [];

        return [
            'source_codes' => $group->source_codes ?? [],
            'keywords' => $group->keywords ?? [],
            'budget_units' => $group->budget_units ?? [],
            'required_terms' => $rules['required_terms'] ?? [],
            'required_any' => $rules['required_any'] ?? [],
            'grade_patterns' => $rules['grade_patterns'] ?? [],
            'reject_patterns' => $rules['reject_patterns'] ?? [],
        ];
    }

    private function categoriesCatalogForGroup(ObraCivilMaterialGroup $group): Collection
    {
        return ObraCivilCommercialMaterial::query()
            ->where('obra_civil_material_group_id', $group->id)
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    private function validationStatusesForGroup(ObraCivilMaterialGroup $group): Collection
    {
        return ObraCivilCommercialMaterial::query()
            ->where('obra_civil_material_group_id', $group->id)
            ->select('validation_status')
            ->whereNotNull('validation_status')
            ->distinct()
            ->orderBy('validation_status')
            ->pluck('validation_status');
    }

    private function familiesCatalog(): Collection
    {
        return ObraCivilMaterialGroup::query()
            ->select('family')
            ->distinct()
            ->orderBy('family')
            ->pluck('family');
    }

    private function categoriesCatalog(): Collection
    {
        return ObraCivilCommercialMaterial::query()
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    private function validationStatuses(): Collection
    {
        return ObraCivilCommercialMaterial::query()
            ->select('validation_status')
            ->whereNotNull('validation_status')
            ->distinct()
            ->orderBy('validation_status')
            ->pluck('validation_status');
    }

    private function statusOptions(): array
    {
        return [
            '' => 'Todos los estados',
            'active' => 'Activas',
            'inactive' => 'Inactivas',
            'without_active_children' => 'Sin hijos activos',
            'pending_validation' => 'Con pendientes de validacion',
        ];
    }

    private function childStatusOptions(): array
    {
        return [
            '' => 'Todos los estados',
            'active' => 'Activos',
            'inactive' => 'Inactivos',
            'pending_validation' => 'Con pendientes de validacion',
        ];
    }
}
