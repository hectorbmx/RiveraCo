<?php

namespace App\Http\Controllers;

use App\Models\CivilConcept;
use App\Models\Obra;
use App\Services\ObraCivil\ObraCivilConceptReportService;
use Illuminate\View\View;

class ObraCivilConceptReportController extends Controller
{
    public function __construct(private readonly ObraCivilConceptReportService $reportService)
    {
    }

    public function show(Obra $obra, CivilConcept $concept): View
    {
        $this->abortUnlessCivil($obra);
        $obra->load('cliente');

        return view('obra_civil.concept_reports.show', array_merge(
            ['obra' => $obra],
            $this->reportService->detail($obra, $concept)
        ));
    }

    private function abortUnlessCivil(Obra $obra): void
    {
        abort_unless(in_array(strtoupper((string) $obra->tipo_obra), ['OBRA_CIVIL', 'CIVIL'], true), 404);
    }
}
