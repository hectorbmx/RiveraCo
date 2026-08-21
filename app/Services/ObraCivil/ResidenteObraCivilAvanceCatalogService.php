<?php

namespace App\Services\ObraCivil;

use App\Models\CivilConcept;
use App\Services\CivilConceptBalanceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ResidenteObraCivilAvanceCatalogService
{
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 30;

    public function __construct(
        private CivilConceptBalanceService $balanceService,
    ) {
    }

    public function search(ResidenteObraCivilContext $context, array $filters = []): array
    {
        $perPage = $this->perPage($filters['per_page'] ?? null);
        $q = trim((string) ($filters['q'] ?? ''));

        $paginator = CivilConcept::query()
            ->select('civil_concepts.*')
            ->join('civil_partidas', 'civil_partidas.id', '=', 'civil_concepts.civil_partida_id')
            ->join('civil_buildings', 'civil_buildings.id', '=', 'civil_partidas.civil_building_id')
            ->join('civil_catalog_imports', 'civil_catalog_imports.id', '=', 'civil_buildings.civil_catalog_import_id')
            ->with([
                'partida:id,civil_building_id,code,name,sort_order',
                'partida.building:id,civil_catalog_import_id,name,sort_order',
            ])
            ->where('civil_concepts.is_active', true)
            ->where('civil_catalog_imports.obra_id', $context->obra->id)
            ->whereIn('civil_catalog_imports.status', ['imported', 'validated'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->where('civil_concepts.excel_code', 'like', '%' . $q . '%')
                        ->orWhere('civil_concepts.description', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('civil_buildings.sort_order')
            ->orderBy('civil_partidas.sort_order')
            ->orderBy('civil_concepts.sort_order')
            ->paginate($perPage);

        return $this->mapPaginator($paginator);
    }

    private function mapPaginator(LengthAwarePaginator $paginator): array
    {
        $concepts = collect($paginator->items());
        $conceptIds = $concepts->pluck('id');
        $balances = $this->balanceService->summaries($conceptIds);
        $reportedQuantities = $this->reportedQuantities($conceptIds);

        return [
            'data' => $concepts
                ->map(function (CivilConcept $concept) use ($balances, $reportedQuantities) {
                    $balance = $balances->get((int) $concept->id, []);
                    $estimado = (float) ($balance['used_quantity'] ?? 0);
                    $reportado = (float) ($reportedQuantities->get((int) $concept->id, 0) ?? 0);
                    $disponible = (float) ($balance['available_quantity'] ?? $concept->budget_quantity);

                    return [
                        'id' => $concept->id,
                        'clave' => $concept->excel_code,
                        'descripcion' => $concept->description,
                        'unidad' => $concept->unit,
                        'cantidad' => (float) $concept->budget_quantity,
                        'estimado' => $estimado,
                        'reportado' => $reportado,
                        'disponible' => $disponible,
                        'edificio' => $concept->partida?->building?->name,
                        'partida' => trim(implode(' ', array_filter([
                            $concept->partida?->code,
                            $concept->partida?->name,
                        ]))),
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

    private function reportedQuantities($conceptIds)
    {
        $ids = collect($conceptIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::table('civil_work_report_items as i')
            ->join('civil_work_reports as r', 'r.id', '=', 'i.civil_work_report_id')
            ->whereIn('r.status', ['pendiente', 'en_revision', 'aprobado'])
            ->whereIn('i.civil_concept_id', $ids)
            ->selectRaw('i.civil_concept_id, COALESCE(SUM(i.quantity), 0) as reported_quantity')
            ->groupBy('i.civil_concept_id')
            ->pluck('reported_quantity', 'civil_concept_id');
    }
    private function perPage(mixed $value): int
    {
        $perPage = (int) ($value ?: self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}




