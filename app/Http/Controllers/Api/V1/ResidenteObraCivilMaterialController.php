<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\ObraCivilMaterialRequest;
use App\Models\OrdenCompraDetalle;
use App\Services\ObraCivil\ResidenteObraCivilContextService;
use App\Services\ObraCivil\ResidenteObraCivilMaterialCatalogService;
use App\Services\ObraCivil\ResidenteObraCivilMaterialRequestService;
use Illuminate\Http\Request;

class ResidenteObraCivilMaterialController extends Controller
{
    public function __construct(
        private ResidenteObraCivilContextService $contextService,
        private ResidenteObraCivilMaterialCatalogService $catalogService,
        private ResidenteObraCivilMaterialRequestService $requestService,
    ) {
    }

    public function index(Request $request)
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

    public function requests(Request $request)
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $context = $this->contextService->resolve($request->user());
        $empleadoId = $context->empleadoId();

        $requests = ObraCivilMaterialRequest::query()
            ->where('obra_id', $context->obra->id)
            ->where(function ($query) use ($context, $empleadoId) {
                $query->where('user_id', $context->user->id);

                if ($empleadoId) {
                    $query->orWhere('empleado_id', $empleadoId);
                }
            })
            ->with([
                'items.insumo',
                'items.ordenCompraDetalles.orden',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->paginate((int) ($data['per_page'] ?? 20));

        return response()->json([
            'ok' => true,
            'obra' => $this->mapObra($context->obra),
            'data' => $requests->getCollection()
                ->map(fn (ObraCivilMaterialRequest $materialRequest) => $this->mapMaterialRequest($materialRequest))
                ->values(),
            'meta' => [
                'page' => $requests->currentPage(),
                'per_page' => $requests->perPage(),
                'has_more' => $requests->hasMorePages(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.obra_civil_insumo_id' => ['required', 'integer', 'exists:obra_civil_insumos,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.commercial_material_id' => ['nullable', 'integer', 'exists:obra_civil_commercial_materials,id'],
            'items.*.commercial_quantity' => ['nullable', 'numeric', 'gt:0'],
            'items.*.commercial_items' => ['nullable', 'array', 'min:1', 'max:50'],
            'items.*.commercial_items.*.commercial_material_id' => ['required_with:items.*.commercial_items', 'integer', 'exists:obra_civil_commercial_materials,id'],
            'items.*.commercial_items.*.commercial_quantity' => ['required_with:items.*.commercial_items', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $context = $this->contextService->resolve($request->user());
        $materialRequest = $this->requestService->store($context, $data);

        return response()->json([
            'ok' => true,
            'message' => 'Solicitud de material enviada correctamente.',
            'data' => $this->mapMaterialRequest($materialRequest),
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

    private function mapMaterialRequest(ObraCivilMaterialRequest $request): array
    {
        $items = $request->items
            ->map(function ($item) {
                $ordenes = $item->ordenCompraDetalles
                    ->map(fn (OrdenCompraDetalle $detalle) => $detalle->orden)
                    ->filter()
                    ->unique('id')
                    ->values();

                $hasFinalOrder = $ordenes->contains(fn ($orden) => in_array(strtoupper((string) $orden->estado), ['AUTORIZADA', 'VERIFICADA'], true));
                $hasDraftOrder = $ordenes->contains(fn ($orden) => in_array(strtoupper((string) $orden->estado), ['BORRADOR', 'PROGRAMADA'], true));

                return [
                    'id' => (int) $item->id,
                    'obra_civil_insumo_id' => (int) $item->obra_civil_insumo_id,
                    'quantity' => (float) $item->quantity,
                    'approved_quantity' => $item->approved_quantity !== null ? (float) $item->approved_quantity : null,
                    'unit' => $item->unit,
                    'notes' => $item->notes,
                    'approval_notes' => $item->approval_notes,
                    'insumo_snapshot' => $item->insumo_snapshot,
                    'insumo' => $item->insumo ? [
                        'id' => (int) $item->insumo->id,
                        'codigo' => $item->insumo->codigo,
                        'concepto' => $item->insumo->concepto,
                        'unidad' => $item->insumo->unidad,
                    ] : null,
                    'ordenes_compra' => $ordenes
                        ->map(fn ($orden) => [
                            'id' => (int) $orden->id,
                            'folio' => $orden->folio,
                            'estado' => $orden->estado,
                        ])
                        ->values(),
                    'has_final_order' => $hasFinalOrder,
                    'has_draft_order' => $hasDraftOrder,
                ];
            })
            ->values();

        $ordenes = $items
            ->flatMap(fn ($item) => $item['ordenes_compra'])
            ->unique('id')
            ->values();

        return [
            'id' => (int) $request->id,
            'folio' => $request->folio,
            'obra_id' => (int) $request->obra_id,
            'status' => $request->status,
            'notes' => $request->notes,
            'submitted_at' => optional($request->submitted_at)->toIso8601String(),
            'reviewed_at' => optional($request->reviewed_at)->toIso8601String(),
            'items_count' => $items->count(),
            'approved_items_count' => $items->filter(fn ($item) => (float) ($item['approved_quantity'] ?? 0) > 0)->count(),
            'has_purchase_order' => $ordenes->isNotEmpty(),
            'has_final_purchase_order' => $items->contains(fn ($item) => $item['has_final_order']),
            'has_draft_purchase_order' => $items->contains(fn ($item) => $item['has_draft_order']),
            'ordenes_compra' => $ordenes,
            'items' => $items,
        ];
    }
}


