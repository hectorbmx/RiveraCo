<?php

namespace App\Services\ObraCivil;

use App\Models\CivilConcept;
use App\Models\CivilWorkReportItem;
use App\Models\Obra;
use App\Services\CivilConceptBalanceService;
use Illuminate\Support\Collection;

class ObraCivilConceptReportService
{
    public function __construct(
        private CivilConceptBalanceService $balanceService,
    ) {
    }

    public function detail(Obra $obra, CivilConcept $concept): array
    {
        $concept->loadMissing('partida.building.catalogImport');
        $this->assertConceptBelongsToObra($obra, $concept);

        $items = CivilWorkReportItem::query()
            ->where('civil_concept_id', $concept->id)
            ->whereHas('report', fn ($query) => $query->where('obra_id', $obra->id))
            ->with([
                'report.empleado',
                'report.user',
                'report.reviewedBy',
                'photos',
            ])
            ->latest('id')
            ->get()
            ->sortByDesc(fn (CivilWorkReportItem $item) => $item->report?->submitted_at ?? $item->created_at)
            ->values();

        $statusTotals = $items
            ->groupBy(fn (CivilWorkReportItem $item) => $item->report?->status ?? 'sin_estado')
            ->map(fn (Collection $group) => [
                'items' => $group->count(),
                'quantity' => $group->sum(fn (CivilWorkReportItem $item) => (float) $item->quantity),
            ]);

        return [
            'concept' => $concept,
            'balance' => $this->balanceService->summary($concept),
            'items' => $items,
            'reportedTotal' => $items
                ->filter(fn (CivilWorkReportItem $item) => in_array($item->report?->status, ['pendiente', 'en_revision', 'aprobado'], true))
                ->sum(fn (CivilWorkReportItem $item) => (float) $item->quantity),
            'allReportedTotal' => $items->sum(fn (CivilWorkReportItem $item) => (float) $item->quantity),
            'photosCount' => $items->sum(fn (CivilWorkReportItem $item) => $item->photos->count()),
            'statusTotals' => $statusTotals,
        ];
    }

    private function assertConceptBelongsToObra(Obra $obra, CivilConcept $concept): void
    {
        $import = $concept->partida?->building?->catalogImport;

        abort_unless($import && (int) $import->obra_id === (int) $obra->id, 404);
    }
}
