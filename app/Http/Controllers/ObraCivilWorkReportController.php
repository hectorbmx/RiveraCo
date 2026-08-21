<?php

namespace App\Http\Controllers;

use App\Models\CivilWorkReport;
use App\Models\Obra;
use App\Services\ObraCivil\ObraCivilWorkReportIndexService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ObraCivilWorkReportController extends Controller
{
    public function __construct(private readonly ObraCivilWorkReportIndexService $reportIndexService)
    {
    }

    public function index(Request $request, Obra $obra): View
    {
        $this->abortUnlessCivil($obra);
        $obra->load('cliente');

        $filters = $request->only(['status', 'scope', 'q', 'date_from', 'date_to']);

        return view('obra_civil.work_reports.index', [
            'obra' => $obra,
            'filters' => $filters,
            'reports' => $this->reportIndexService->list($obra, $filters),
            'stats' => $this->reportIndexService->stats($obra),
        ]);
    }

    public function show(Obra $obra, CivilWorkReport $report): View
    {
        $this->abortUnlessCivil($obra);
        abort_unless((int) $report->obra_id === (int) $obra->id, 404);

        $obra->load('cliente');
        $report->load([
            'empleado',
            'user',
            'reviewedBy',
            'items.concept.partida.building',
            'items.photos',
        ]);

        return view('obra_civil.work_reports.show', [
            'obra' => $obra,
            'report' => $report,
        ]);
    }

    private function abortUnlessCivil(Obra $obra): void
    {
        abort_unless(in_array(strtoupper((string) $obra->tipo_obra), ['OBRA_CIVIL', 'CIVIL'], true), 404);
    }
}

