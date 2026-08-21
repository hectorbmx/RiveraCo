<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraCivilMaterialRequest;
use App\Services\ObraCivil\ObraCivilMaterialRequestApprovalService;
use App\Services\ObraCivil\ObraCivilMaterialRequestIndexService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ObraCivilMaterialRequestController extends Controller
{
    public function __construct(
        private readonly ObraCivilMaterialRequestIndexService $requestIndexService,
        private readonly ObraCivilMaterialRequestApprovalService $approvalService,
    ) {
    }

    public function index(Request $request, Obra $obra): View
    {
        $this->abortUnlessCivil($obra);
        $obra->load('cliente');

        $filters = $request->only(['status', 'scope', 'q']);

        return view('obra_civil.material_requests.index', [
            'obra' => $obra,
            'filters' => $filters,
            'requests' => $this->requestIndexService->list($obra, $filters),
            'stats' => $this->requestIndexService->stats($obra),
        ]);
    }

    public function show(Obra $obra, ObraCivilMaterialRequest $materialRequest): View
    {
        $this->abortUnlessCivil($obra);
        $this->abortUnlessRequestBelongsToObra($obra, $materialRequest);

        $obra->load('cliente');
        $materialRequest->load([
            'empleado',
            'user',
            'reviewedBy',
            'ordenCompra',
            'items.insumo',
            'items.approvedBy',
            'items.ordenCompraDetalles.orden',
        ]);

        return view('obra_civil.material_requests.show', [
            'obra' => $obra,
            'materialRequest' => $materialRequest,
        ]);
    }

    public function approveFull(Request $request, Obra $obra, ObraCivilMaterialRequest $materialRequest): RedirectResponse
    {
        $this->abortUnlessCivil($obra);
        $this->abortUnlessRequestBelongsToObra($obra, $materialRequest);

        $user = $request->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->approvalService->approveFull(
            $materialRequest,
            $user,
            $validated['approval_notes'] ?? null,
        );

        return redirect()
            ->route('obra_civil.material-requests.show', [$obra, $materialRequest])
            ->with('success', 'Solicitud aprobada completa correctamente.');
    }

    public function approveQuantities(Request $request, Obra $obra, ObraCivilMaterialRequest $materialRequest): RedirectResponse
    {
        $this->abortUnlessCivil($obra);
        $this->abortUnlessRequestBelongsToObra($obra, $materialRequest);

        $user = $request->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.approved_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.approval_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->approvalService->approveWithQuantities(
            $materialRequest,
            $validated['items'],
            $user,
            $validated['approval_notes'] ?? null,
        );

        return redirect()
            ->route('obra_civil.material-requests.show', [$obra, $materialRequest])
            ->with('success', 'Solicitud aprobada con cantidades capturadas correctamente.');
    }

    public function reject(Request $request, Obra $obra, ObraCivilMaterialRequest $materialRequest): RedirectResponse
    {
        $this->abortUnlessCivil($obra);
        $this->abortUnlessRequestBelongsToObra($obra, $materialRequest);

        $user = $request->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->approvalService->reject(
            $materialRequest,
            $user,
            $validated['rejection_reason'] ?? null,
        );

        return redirect()
            ->route('obra_civil.material-requests.show', [$obra, $materialRequest])
            ->with('success', 'Solicitud rechazada correctamente.');
    }

    private function abortUnlessCivil(Obra $obra): void
    {
        abort_unless(in_array(strtoupper((string) $obra->tipo_obra), ['OBRA_CIVIL', 'CIVIL'], true), 404);
    }

    private function abortUnlessRequestBelongsToObra(Obra $obra, ObraCivilMaterialRequest $materialRequest): void
    {
        abort_unless((int) $materialRequest->obra_id === (int) $obra->id, 404);
    }
}

