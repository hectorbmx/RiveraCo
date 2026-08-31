<?php

namespace Tests\Unit;

use App\Models\ObraCivilInsumo;
use App\Models\ObraCivilMaterialRequest;
use App\Models\ObraCivilMaterialRequestItem;
use App\Services\ObraCivil\ObraCivilFieldReviewService;
use App\Services\ObraCivil\ObraCivilMaterialRequestItemBalanceService;
use App\Services\ObraCivil\ObraCivilMaterialRequestOrderService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ObraCivilCommercialRequestSnapshotTest extends TestCase
{
    public function test_order_service_copies_commercial_request_to_oc_detail_snapshot(): void
    {
        $service = new ObraCivilMaterialRequestOrderService(
            new ObraCivilMaterialRequestItemBalanceService()
        );

        $snapshot = $this->invokePrivate($service, 'insumoSnapshot', [
            $this->insumo(),
            ['commercial_request' => $this->commercialRequest()],
        ]);

        $this->assertSame('TON', $snapshot['unidad']);
        $this->assertSame(0.167, $snapshot['commercial_request']['converted_quantity']);
        $this->assertSame(25.0, $snapshot['commercial_request']['total_commercial_quantity']);
        $this->assertSame('VAR-3-8-12M', $snapshot['commercial_request']['items'][0]['sku']);
    }

    public function test_field_review_snapshot_keeps_commercial_request_and_request_item_link(): void
    {
        $service = new ObraCivilFieldReviewService();
        $item = new ObraCivilMaterialRequestItem();
        $item->forceFill([
            'id' => 77,
            'insumo_snapshot' => ['commercial_request' => $this->commercialRequest()],
        ]);

        $snapshot = $this->invokePrivate($service, 'materialRequestItemInsumoSnapshot', [
            $this->insumo(),
            $item,
            15500.1234,
        ]);

        $this->assertSame(77, $snapshot['source_material_request_item_id']);
        $this->assertSame('TON', $snapshot['unidad']);
        $this->assertSame(0.167, $snapshot['commercial_request']['converted_quantity']);
        $this->assertEqualsWithDelta(167.0, $snapshot['commercial_request']['total_kg'], 0.0001);
    }

    public function test_order_create_payload_exposes_commercial_request(): void
    {
        $service = new ObraCivilMaterialRequestOrderService(
            new ObraCivilMaterialRequestItemBalanceService()
        );

        $request = new ObraCivilMaterialRequest();
        $request->forceFill([
            'id' => 5,
            'folio' => 'SCM-000005',
            'status' => ObraCivilMaterialRequest::STATUS_APROBADA,
        ]);
        $request->setRelation('empleado', null);
        $request->setRelation('user', null);

        $item = new ObraCivilMaterialRequestItem();
        $item->forceFill([
            'id' => 77,
            'quantity' => 0.167,
            'approved_quantity' => 0.167,
            'unit' => 'TON',
            'insumo_snapshot' => ['commercial_request' => $this->commercialRequest()],
        ]);
        $item->setRelation('request', $request);
        $item->setRelation('insumo', $this->insumo());

        $payload = $this->invokePrivate($service, 'itemOptionPayload', [
            $item,
            ['ordered_quantity' => 0.0, 'draft_quantity' => 0.0],
        ]);

        $this->assertSame(77, $payload['request_item_id']);
        $this->assertSame('SCM-000005', $payload['request_folio']);
        $this->assertSame('TON', $payload['unidad']);
        $this->assertSame(0.167, $payload['available_to_load_quantity']);
        $this->assertEqualsWithDelta(25.0, $payload['commercial_request']['total_commercial_quantity'], 0.0001);
        $this->assertSame('VAR-3-8-12M', $payload['commercial_request']['items'][0]['sku']);
    }
    public function test_material_request_show_view_authorizes_commercial_items_by_pieces(): void
    {
        $view = file_get_contents(__DIR__ . '/../../resources/views/obra_civil/material_requests/show.blade.php');

        $this->assertStringContainsString('Piezas', $view);
        $this->assertStringContainsString('Autorizar piezas', $view);
        $this->assertStringContainsString('approved_commercial_quantity', $view);
        $this->assertStringContainsString('data-commercial-approved-input', $view);
        $this->assertStringContainsString('data-approved-input', $view);
        $this->assertStringContainsString('syncCommercialAuthorization', $view);
    }
    /**
     * @param array<int, mixed> $arguments
     */

    public function test_purchase_order_create_view_uses_piece_inputs_for_commercial_requests(): void
    {
        $view = file_get_contents(__DIR__ . '/../../resources/views/ordencompra/create.blade.php');

        $this->assertStringContainsString('commercialMetrics', $view);
        $this->assertStringContainsString('js-material-request-commercial-quantity', $view);
        $this->assertStringContainsString('js-material-request-quantity', $view);
        $this->assertStringContainsString('syncCommercialQuantity', $view);
        $this->assertStringNotContainsString('Impacto:', $view);
    }

    public function test_purchase_order_edit_view_displays_commercial_details_as_pieces(): void
    {
        $view = file_get_contents(__DIR__ . '/../../resources/views/ordencompra/edit.blade.php');

        $this->assertStringContainsString('$detailCommercialRequest', $view);
        $this->assertStringContainsString('$detailDisplayQuantity', $view);
        $this->assertStringContainsString('$detailDisplayUnit', $view);
        $this->assertStringContainsString('$detailDisplayPrice', $view);
    }

    public function test_purchase_order_print_uses_commercial_piece_display_values(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/OrdenCompraController.php');

        $this->assertStringContainsString('$displayCant', $controller);
        $this->assertStringContainsString('$displayUni', $controller);
        $this->assertStringContainsString('$displayPu', $controller);
        $this->assertStringContainsString("obra_civil_insumo_snapshot['commercial_request']", $controller);
        $this->assertStringContainsString('number_format($displayCant, 1)', $controller);
        $this->assertStringContainsString('$utf8($displayUni ?: \'-\')', $controller);
        $this->assertStringContainsString('$money($displayPu)', $controller);
    }
    private function invokePrivate(object $service, string $methodName, array $arguments): mixed
    {
        $method = new ReflectionMethod($service, $methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($service, $arguments);
    }

    private function insumo(): ObraCivilInsumo
    {
        $insumo = new ObraCivilInsumo();
        $insumo->forceFill([
            'id' => 10,
            'codigo' => 'AC-001',
            'concepto' => 'Acero de refuerzo',
            'unidad' => 'TON',
            'tipo' => 'material',
            'cantidad_presupuestada' => 12.5,
            'precio_unitario' => 15500.1234,
            'importe_importado' => 193751.54,
            'importe_calculado' => 193751.54,
            'source_row' => 42,
        ]);

        $insumo->setRelation('import', null);

        return $insumo;
    }

    /**
     * @return array<string, mixed>
     */
    private function commercialRequest(): array
    {
        return [
            'items' => [
                [
                    'commercial_material_id' => 15,
                    'sku' => 'VAR-3-8-12M',
                    'descripcion' => 'Varilla 3/8 x 12 m',
                    'unidad_compra' => 'PZA',
                    'commercial_quantity' => 25.0,
                    'peso_por_pieza' => 6.68,
                    'factor_conversion' => 6.68,
                    'kg' => 167.0,
                    'material_group_id' => 3,
                ],
            ],
            'total_commercial_quantity' => 25.0,
            'total_kg' => 167.0,
            'converted_quantity' => 0.167,
            'converted_unit' => 'TON',
        ];
    }
}

