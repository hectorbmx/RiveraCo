<?php

namespace App\Services\ObraCivil;

use App\Models\Area;
use App\Models\CivilEstimation;
use App\Models\CivilWorkReport;
use App\Models\Obra;
use App\Models\ObraCivilMaterialRequest;
use App\Models\OrdenCompra;
use App\Services\CivilConceptBalanceService;
use App\Services\ObraCivilInsumoBalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ObraCivilFieldReviewService
{
    public function dashboard(Obra $obra): array
    {
        $workReports = CivilWorkReport::query()
            ->where('obra_id', $obra->id)
            ->with([
                'empleado',
                'user',
                'reviewedBy',
                'items.concept.partida.building',
                'items.photos',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->limit(50)
            ->get();

        $materialRequests = ObraCivilMaterialRequest::query()
            ->where('obra_id', $obra->id)
            ->with([
                'empleado',
                'user',
                'reviewedBy',
                'ordenCompra',
                'items.insumo',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->limit(50)
            ->get();

        return [
            'workReports' => $workReports,
            'materialRequests' => $materialRequests,
            'stats' => [
                'work_pending' => $workReports->whereIn('status', [
                    CivilWorkReport::STATUS_PENDIENTE,
                    CivilWorkReport::STATUS_EN_REVISION,
                ])->count(),
                'work_approved' => $workReports->where('status', CivilWorkReport::STATUS_APROBADO)->count(),
                'material_pending' => $materialRequests->whereIn('status', [
                    ObraCivilMaterialRequest::STATUS_ENVIADA,
                    ObraCivilMaterialRequest::STATUS_EN_REVISION,
                ])->count(),
                'material_approved' => $materialRequests->where('status', ObraCivilMaterialRequest::STATUS_APROBADA)->count(),
            ],
        ];
    }

    public function approveWorkReport(Obra $obra, CivilWorkReport $report, int $reviewerId, ?string $notes = null): CivilWorkReport
    {
        $this->assertWorkReportBelongsToObra($obra, $report);
        $this->assertWorkReportReviewable($report);

        return $this->updateWorkReportStatus($report, CivilWorkReport::STATUS_APROBADO, $reviewerId, $notes);
    }

    public function rejectWorkReport(Obra $obra, CivilWorkReport $report, int $reviewerId, ?string $notes = null): CivilWorkReport
    {
        $this->assertWorkReportBelongsToObra($obra, $report);
        $this->assertWorkReportReviewable($report);

        return $this->updateWorkReportStatus($report, CivilWorkReport::STATUS_RECHAZADO, $reviewerId, $notes);
    }

    public function convertWorkReportToEstimation(Obra $obra, CivilWorkReport $report, int $creatorId): CivilEstimation
    {
        $this->assertWorkReportBelongsToObra($obra, $report);

        if ($report->status !== CivilWorkReport::STATUS_APROBADO) {
            throw ValidationException::withMessages([
                'status' => 'Solo los reportes aprobados pueden convertirse a estimacion.',
            ]);
        }

        if (! empty($report->metadata['estimation_id'] ?? null)) {
            throw ValidationException::withMessages([
                'status' => 'Este reporte ya fue convertido a una estimacion.',
            ]);
        }

        $report->loadMissing('items.concept.partida.building');

        if ($report->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'El reporte no tiene conceptos para estimar.',
            ]);
        }

        $conceptIds = $report->items->pluck('civil_concept_id')->map(fn ($id) => (int) $id)->values();
        $balances = app(CivilConceptBalanceService::class)->summaries($conceptIds);
        $catalogImportId = null;
        $lines = [];
        $totalQuantity = 0.0;
        $subtotal = 0.0;

        foreach ($report->items as $item) {
            $concept = $item->concept;

            if (! $concept) {
                throw ValidationException::withMessages([
                    'items' => 'Uno de los conceptos reportados ya no existe.',
                ]);
            }

            $partida = $concept->partida;
            $building = $partida?->building;
            $currentCatalogImportId = $building?->civil_catalog_import_id;

            if (! $currentCatalogImportId) {
                throw ValidationException::withMessages([
                    'items' => 'No se pudo identificar el catalogo civil del concepto ' . ($concept->excel_code ?: $concept->id) . '.',
                ]);
            }

            $catalogImportId ??= $currentCatalogImportId;

            if ((int) $catalogImportId !== (int) $currentCatalogImportId) {
                throw ValidationException::withMessages([
                    'items' => 'El reporte contiene conceptos de mas de un catalogo civil.',
                ]);
            }

            $quantity = round((float) $item->quantity, 4);
            $availableQuantity = (float) ($balances->get($concept->id)['available_quantity'] ?? 0);

            if ($quantity <= 0 || $quantity > $availableQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'La cantidad reportada de ' . ($concept->excel_code ?: 'un concepto') . ' excede la cantidad disponible por estimar.',
                ]);
            }

            $unitPrice = round((float) $concept->unit_price, 4);
            $amount = round($quantity * $unitPrice, 2);
            $totalQuantity += $quantity;
            $subtotal += $amount;

            $lines[] = [
                'civil_concept_id' => $concept->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'concept_snapshot' => [
                    'building' => $building?->name,
                    'partida_code' => $partida?->code,
                    'partida_name' => $partida?->name,
                    'excel_code' => $concept->excel_code,
                    'description' => $concept->description,
                    'unit' => $concept->unit,
                    'budget_quantity' => (float) $concept->budget_quantity,
                    'unit_price' => $unitPrice,
                    'budget_amount' => (float) $concept->budget_amount,
                    'source_work_report_item_id' => $item->id,
                ],
            ];
        }

        return DB::transaction(function () use ($obra, $report, $catalogImportId, $lines, $totalQuantity, $subtotal, $creatorId) {
            $nextNumber = CivilEstimation::query()
                ->where('obra_id', $obra->id)
                ->lockForUpdate()
                ->count() + 1;
            $obraKey = Str::upper(Str::slug($obra->clave_obra ?: (string) $obra->id));

            $estimation = CivilEstimation::create([
                'obra_id' => $obra->id,
                'civil_catalog_import_id' => $catalogImportId,
                'folio' => sprintf('EST-%s-%03d', $obraKey, $nextNumber),
                'status' => 'confirmed',
                'total_items' => count($lines),
                'total_quantity' => round($totalQuantity, 4),
                'subtotal' => round($subtotal, 2),
                'created_by' => $creatorId,
                'confirmed_at' => now(),
                'metadata' => [
                    'source' => 'civil_work_report',
                    'civil_work_report_id' => $report->id,
                ],
            ]);

            $estimation->items()->createMany($lines);

            $report->update([
                'status' => CivilWorkReport::STATUS_CONVERTIDO_A_ESTIMACION,
                'metadata' => array_merge($report->metadata ?? [], [
                    'estimation_id' => $estimation->id,
                    'estimation_folio' => $estimation->folio,
                    'converted_to_estimation_at' => now()->toISOString(),
                ]),
            ]);

            return $estimation;
        });
    }

    public function convertMaterialRequestToOrdenCompra(Obra $obra, ObraCivilMaterialRequest $request, int $creatorId, string $creatorName): OrdenCompra
    {
        $this->assertMaterialRequestBelongsToObra($obra, $request);

        if ($request->status !== ObraCivilMaterialRequest::STATUS_APROBADA) {
            throw ValidationException::withMessages([
                'status' => 'Solo las solicitudes aprobadas pueden convertirse a orden de compra.',
            ]);
        }

        if (! empty($request->orden_compra_id)) {
            throw ValidationException::withMessages([
                'status' => 'Esta solicitud ya fue convertida a una orden de compra.',
            ]);
        }

        $area = Area::find($obra->area_id);

        if (! $area) {
            throw ValidationException::withMessages([
                'area_id' => 'La obra no tiene un area configurada para generar el folio de la orden de compra.',
            ]);
        }

        $request->loadMissing('items.insumo');

        if ($request->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'La solicitud no tiene insumos para convertir.',
            ]);
        }

        $insumoIds = $request->items->pluck('obra_civil_insumo_id')->map(fn ($id) => (int) $id)->values();
        $balances = app(ObraCivilInsumoBalanceService::class)->summaries($insumoIds);
        $lines = [];
        $subtotal = 0.0;
        $ivaTotal = 0.0;

        foreach ($request->items as $item) {
            $insumo = $item->insumo;

            if (! $insumo || (int) $insumo->obra_id !== (int) $obra->id) {
                throw ValidationException::withMessages([
                    'items' => 'Uno de los insumos solicitados no pertenece a esta obra.',
                ]);
            }

            if ($insumo->tipo !== 'material') {
                throw ValidationException::withMessages([
                    'items' => 'Solo los insumos de tipo material pueden convertirse a orden de compra.',
                ]);
            }

            $quantity = round((float) $item->quantity, 4);
            $availableQuantity = (float) ($balances->get($insumo->id)['available_quantity'] ?? 0);

            if ($quantity <= 0 || $quantity > $availableQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'La cantidad solicitada de ' . ($insumo->codigo ?: 'un insumo') . ' excede la cantidad disponible.',
                ]);
            }

            $unitPrice = round((float) $insumo->precio_unitario, 4);
            $amount = round($quantity * $unitPrice, 2);
            $iva = round($amount * 0.16, 2);

            $subtotal += $amount;
            $ivaTotal += $iva;

            $lines[] = [
                'producto_id' => null,
                'civil_concept_id' => null,
                'obra_civil_insumo_id' => $insumo->id,
                'obra_civil_insumo_snapshot' => [
                    'codigo' => $insumo->codigo,
                    'concepto' => $insumo->concepto,
                    'unidad' => $insumo->unidad,
                    'tipo' => $insumo->tipo,
                    'cantidad_presupuestada' => (float) $insumo->cantidad_presupuestada,
                    'precio_unitario' => $unitPrice,
                    'source_material_request_item_id' => $item->id,
                ],
                'legacy_prod_id' => null,
                'descripcion' => $insumo->concepto,
                'unidad' => $item->unit ?: $insumo->unidad,
                'cantidad' => $quantity,
                'precio_unitario' => $unitPrice,
                'descuento_porcentaje' => 0,
                'descuento_importe' => 0,
                'importe' => $amount,
                'iva' => $iva,
                'retenciones' => 0,
                'otros_impuestos' => 0,
                'tipo_cambio' => 1,
                'notas' => $item->notes,
            ];
        }

        return DB::transaction(function () use ($obra, $request, $area, $lines, $subtotal, $ivaTotal, $creatorId, $creatorName) {
            $orden = OrdenCompra::create([
                'folio' => $this->generateFolioForArea($area),
                'proveedor_id' => null,
                'obra_id' => $obra->id,
                'centro_costo_id' => null,
                'area_id' => $area->id,
                'area' => $area->nombre,
                'cotizacion' => null,
                'atencion' => null,
                'tipo_pago' => null,
                'forma_pago' => null,
                'subtotal' => round($subtotal, 2),
                'iva' => round($ivaTotal, 2),
                'otros_impuestos' => 0,
                'total' => round($subtotal + $ivaTotal, 2),
                'tipo_cambio' => 1,
                'moneda' => 'MXN',
                'fecha' => now()->toDateString(),
                'estado' => 'BORRADOR',
                'usuario_registro' => $creatorName,
                'registrado_por' => $creatorId,
                'comentarios' => trim('Generada desde solicitud de material ' . $request->folio . '. ' . (string) $request->notes),
            ]);

            $orden->detalles()->createMany($lines);

            $request->update([
                'status' => ObraCivilMaterialRequest::STATUS_CONVERTIDA_A_OC,
                'orden_compra_id' => $orden->id,
                'metadata' => array_merge($request->metadata ?? [], [
                    'orden_compra_id' => $orden->id,
                    'orden_compra_folio' => $orden->folio,
                    'converted_to_oc_at' => now()->toISOString(),
                ]),
            ]);

            return $orden;
        });
    }
    public function approveMaterialRequest(Obra $obra, ObraCivilMaterialRequest $request, int $reviewerId, ?string $notes = null): ObraCivilMaterialRequest
    {
        $this->assertMaterialRequestBelongsToObra($obra, $request);
        $this->assertMaterialRequestReviewable($request);

        return $this->updateMaterialRequestStatus($request, ObraCivilMaterialRequest::STATUS_APROBADA, $reviewerId, $notes);
    }

    public function rejectMaterialRequest(Obra $obra, ObraCivilMaterialRequest $request, int $reviewerId, ?string $notes = null): ObraCivilMaterialRequest
    {
        $this->assertMaterialRequestBelongsToObra($obra, $request);
        $this->assertMaterialRequestReviewable($request);

        return $this->updateMaterialRequestStatus($request, ObraCivilMaterialRequest::STATUS_RECHAZADA, $reviewerId, $notes);
    }

    private function updateWorkReportStatus(CivilWorkReport $report, string $status, int $reviewerId, ?string $notes): CivilWorkReport
    {
        return DB::transaction(function () use ($report, $status, $reviewerId, $notes) {
            $report->update([
                'status' => $status,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'metadata' => array_merge($report->metadata ?? [], [
                    'review_notes' => $notes,
                    'review_action' => $status,
                ]),
            ]);

            return $report->refresh();
        });
    }

    private function updateMaterialRequestStatus(ObraCivilMaterialRequest $request, string $status, int $reviewerId, ?string $notes): ObraCivilMaterialRequest
    {
        return DB::transaction(function () use ($request, $status, $reviewerId, $notes) {
            $request->update([
                'status' => $status,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'metadata' => array_merge($request->metadata ?? [], [
                    'review_notes' => $notes,
                    'review_action' => $status,
                ]),
            ]);

            return $request->refresh();
        });
    }

    private function assertWorkReportBelongsToObra(Obra $obra, CivilWorkReport $report): void
    {
        abort_unless((int) $report->obra_id === (int) $obra->id, 404);
    }

    private function assertMaterialRequestBelongsToObra(Obra $obra, ObraCivilMaterialRequest $request): void
    {
        abort_unless((int) $request->obra_id === (int) $obra->id, 404);
    }

    private function assertWorkReportReviewable(CivilWorkReport $report): void
    {
        if (! in_array($report->status, [CivilWorkReport::STATUS_PENDIENTE, CivilWorkReport::STATUS_EN_REVISION], true)) {
            throw ValidationException::withMessages([
                'status' => 'Este reporte de avance ya no esta pendiente de revision.',
            ]);
        }
    }

    private function assertMaterialRequestReviewable(ObraCivilMaterialRequest $request): void
    {
        if (! in_array($request->status, [ObraCivilMaterialRequest::STATUS_ENVIADA, ObraCivilMaterialRequest::STATUS_EN_REVISION], true)) {
            throw ValidationException::withMessages([
                'status' => 'Esta solicitud de material ya no esta pendiente de revision.',
            ]);
        }
    }
}

