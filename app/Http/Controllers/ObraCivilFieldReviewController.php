<?php

namespace App\Http\Controllers;

use App\Models\CivilWorkReport;
use App\Models\Obra;
use App\Models\ObraCivilMaterialRequest;
use App\Services\ObraCivil\ObraCivilFieldReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ObraCivilFieldReviewController extends Controller
{
    public function __construct(private readonly ObraCivilFieldReviewService $reviewService)
    {
    }

    public function index(Obra $obra): View
    {
        $this->abortUnlessCivil($obra);
        $obra->load('cliente');

        return view('obra_civil.review.index', array_merge(
            ['obra' => $obra],
            $this->reviewService->dashboard($obra)
        ));
    }

    public function approveReport(Request $request, Obra $obra, CivilWorkReport $report): RedirectResponse
    {
        $this->abortUnlessCivil($obra);
        $data = $this->validatedReviewData($request);

        $this->reviewService->approveWorkReport($obra, $report, (int) $request->user()->id, $data['review_notes'] ?? null);

        return back()->with('success', 'Reporte de avance aprobado correctamente.');
    }

    public function rejectReport(Request $request, Obra $obra, CivilWorkReport $report): RedirectResponse
    {
        $this->abortUnlessCivil($obra);
        $data = $this->validatedReviewData($request);

        $this->reviewService->rejectWorkReport($obra, $report, (int) $request->user()->id, $data['review_notes'] ?? null);

        return back()->with('success', 'Reporte de avance rechazado correctamente.');
    }

    public function convertReportToEstimation(Request $request, Obra $obra, CivilWorkReport $report): RedirectResponse
    {
        $this->abortUnlessCivil($obra);

        $estimation = $this->reviewService->convertWorkReportToEstimation($obra, $report, (int) $request->user()->id);

        return redirect()
            ->route('obra_civil.estimations.show', [$obra, $estimation])
            ->with('success', 'Avance convertido a estimacion ' . $estimation->folio . ' correctamente.');
    }

    public function approveMaterialRequest(Request $request, Obra $obra, ObraCivilMaterialRequest $materialRequest): RedirectResponse
    {
        $this->abortUnlessCivil($obra);
        $data = $this->validatedReviewData($request);

        $this->reviewService->approveMaterialRequest($obra, $materialRequest, (int) $request->user()->id, $data['review_notes'] ?? null);

        return back()->with('success', 'Solicitud de material aprobada correctamente.');
    }

    public function rejectMaterialRequest(Request $request, Obra $obra, ObraCivilMaterialRequest $materialRequest): RedirectResponse
    {
        $this->abortUnlessCivil($obra);
        $data = $this->validatedReviewData($request);

        $this->reviewService->rejectMaterialRequest($obra, $materialRequest, (int) $request->user()->id, $data['review_notes'] ?? null);

        return back()->with('success', 'Solicitud de material rechazada correctamente.');
    }

    public function convertMaterialRequestToOrdenCompra(Request $request, Obra $obra, ObraCivilMaterialRequest $materialRequest): RedirectResponse
    {
        $this->abortUnlessCivil($obra);

        $orden = $this->reviewService->convertMaterialRequestToOrdenCompra(
            $obra,
            $materialRequest,
            (int) $request->user()->id,
            (string) $request->user()->name
        );

        return redirect()
            ->route('ordenes_compra.edit', $orden)
            ->with('success', 'Solicitud convertida a orden de compra ' . $orden->folio . '. Revisa proveedor y precios antes de autorizar.');
    }

    private function validatedReviewData(Request $request): array
    {
        return $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function abortUnlessCivil(Obra $obra): void
    {
        abort_unless(in_array(strtoupper((string) $obra->tipo_obra), ['OBRA_CIVIL', 'CIVIL'], true), 404);
    }
}


