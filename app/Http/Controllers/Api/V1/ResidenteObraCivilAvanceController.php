<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CivilWorkReport;
use App\Models\Obra;
use App\Services\ObraCivil\ResidenteObraCivilAvanceCatalogService;
use App\Services\ObraCivil\ResidenteObraCivilAvanceReportService;
use App\Services\ObraCivil\ResidenteObraCivilContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResidenteObraCivilAvanceController extends Controller
{
    public function __construct(
        private ResidenteObraCivilContextService $contextService,
        private ResidenteObraCivilAvanceCatalogService $catalogService,
        private ResidenteObraCivilAvanceReportService $reportService,
    ) {
    }

    public function catalogo(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $context = $this->contextService->resolve($request->user());
        $catalogo = $this->catalogService->search($context, $data);

        return response()->json([
            'ok' => true,
            'obra' => $this->mapObra($context->obra),
            'data' => $catalogo['data'],
            'meta' => $catalogo['meta'],
        ]);
    }


    public function reportes(Request $request)
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $context = $this->contextService->resolve($request->user());
        $empleadoId = $context->empleadoId();

        $reports = CivilWorkReport::query()
            ->where('obra_id', $context->obra->id)
            ->where(function ($query) use ($context, $empleadoId) {
                $query->where('user_id', $context->user->id);

                if ($empleadoId) {
                    $query->orWhere('empleado_id', $empleadoId);
                }
            })
            ->with(['items.photos', 'items.concept'])
            ->latest('submitted_at')
            ->latest('id')
            ->paginate((int) ($data['per_page'] ?? 20));

        return response()->json([
            'ok' => true,
            'obra' => $this->mapObra($context->obra),
            'data' => $reports->getCollection()
                ->map(fn (CivilWorkReport $report) => $this->mapReport($report))
                ->values(),
            'meta' => [
                'page' => $reports->currentPage(),
                'per_page' => $reports->perPage(),
                'has_more' => $reports->hasMorePages(),
                'total' => $reports->total(),
            ],
        ]);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'civil_concept_id' => ['required', 'integer', 'exists:civil_concepts,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'item_notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array', 'max:12'],
            'photos.*' => ['required', 'file', 'image', 'max:8192'],
        ]);

        $context = $this->contextService->resolve($request->user());
        $report = $this->reportService->store($context, $data);

        return response()->json([
            'ok' => true,
            'message' => 'Avance registrado correctamente.',
            'data' => $this->mapReport($report),
        ], 201);
    }

    private function mapObra(Obra $obra): array
    {
        return [
            'id' => $obra->id,
            'cliente_id' => $obra->cliente_id,
            'cliente_nombre' => $obra->cliente?->nombre_comercial,
            'nombre' => $obra->nombre,
            'clave_obra' => $obra->clave_obra,
            'tipo_obra' => $obra->tipo_obra,
            'estatus_nuevo' => $obra->estatus_nuevo,
        ];
    }

    private function mapReport(CivilWorkReport $report): array
    {
        return [
            'id' => (int) $report->id,
            'obra_id' => (int) $report->obra_id,
            'status' => $report->status,
            'notes' => $report->notes,
            'submitted_at' => optional($report->submitted_at)->toIso8601String(),
            'reviewed_at' => optional($report->reviewed_at)->toIso8601String(),
            'is_editable' => $report->status === CivilWorkReport::STATUS_PENDIENTE,
            'items' => $report->items
                ->map(fn ($item) => [
                    'id' => (int) $item->id,
                    'civil_concept_id' => (int) $item->civil_concept_id,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'notes' => $item->notes,
                    'concept_snapshot' => $item->concept_snapshot,
                    'photos' => $item->photos
                        ->map(fn ($photo) => [
                            'id' => (int) $photo->id,
                            'path' => $photo->path,
                            'url' => $photo->path ? Storage::disk('public')->url($photo->path) : null,
                            'mime_type' => $photo->mime_type,
                            'size' => $photo->size !== null ? (int) $photo->size : null,
                        ])
                        ->values(),
                ])
                ->values(),
        ];
    }
}

