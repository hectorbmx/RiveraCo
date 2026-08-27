<?php

namespace App\Services\ObraCivil;

use App\Models\ObraCivilCommercialMaterial;
use App\Models\ObraCivilInsumo;
use App\Models\ObraCivilMaterialRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResidenteObraCivilMaterialRequestService
{
    public function __construct(
        private ObraCivilMaterialGroupResolver $groupResolver,
    ) {
    }

    public function store(ResidenteObraCivilContext $context, array $data): ObraCivilMaterialRequest
    {
        $items = collect($data['items'] ?? []);
        $insumoIds = $items
            ->pluck('obra_civil_insumo_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $insumos = ObraCivilInsumo::query()
            ->where('obra_id', $context->obra->id)
            ->where('is_active', true)
            ->where('tipo', 'material')
            ->whereIn('id', $insumoIds)
            ->get()
            ->keyBy('id');

        if ($insumos->count() !== $insumoIds->count()) {
            throw ValidationException::withMessages([
                'items' => ['Uno o mas insumos no pertenecen a los materiales activos de tu obra civil.'],
            ]);
        }

        return DB::transaction(function () use ($context, $data, $items, $insumos) {
            $request = ObraCivilMaterialRequest::create([
                'obra_id' => $context->obra->id,
                'user_id' => $context->user->id,
                'empleado_id' => $context->empleadoId(),
                'status' => ObraCivilMaterialRequest::STATUS_ENVIADA,
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
                'metadata' => [
                    'source' => 'mobile',
                    'obra_snapshot' => [
                        'id' => $context->obra->id,
                        'nombre' => $context->obra->nombre,
                        'clave_obra' => $context->obra->clave_obra,
                        'tipo_obra' => $context->obra->tipo_obra,
                    ],
                ],
            ]);

            $request->forceFill([
                'folio' => 'SCM-' . str_pad((string) $request->id, 6, '0', STR_PAD_LEFT),
            ])->save();

            foreach ($items as $item) {
                $insumo = $insumos->get((int) $item['obra_civil_insumo_id']);
                $commercialLines = $this->resolveCommercialLines($insumo, $item);
                $quantity = $commercialLines->isNotEmpty()
                    ? $this->kgToBudgetUnit($insumo, (float) $commercialLines->sum('kg'))
                    : (float) $item['quantity'];

                $request->items()->create([
                    'obra_civil_insumo_id' => $insumo->id,
                    'quantity' => $quantity,
                    'unit' => $insumo->unidad,
                    'insumo_snapshot' => $this->insumoSnapshot($insumo, $commercialLines, $quantity),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $request->load(['items.insumo']);
        });
    }

    private function resolveCommercialLines(ObraCivilInsumo $insumo, array $item): Collection
    {
        $commercialItems = $this->commercialItemsFromPayload($item);

        if ($commercialItems->isEmpty()) {
            return collect();
        }

        $resolution = $this->groupResolver->resolve($insumo);
        if (! $resolution['matched']) {
            throw ValidationException::withMessages([
                'items' => ['La partida seleccionada no tiene un grupo comercial resuelto.'],
            ]);
        }

        $materials = $resolution['commercial_materials']->keyBy('id');

        return $commercialItems
            ->map(function (array $commercialItem) use ($materials) {
                $materialId = (int) $commercialItem['commercial_material_id'];
                $commercialQuantity = (float) $commercialItem['commercial_quantity'];

                if ($commercialQuantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => ['Captura cantidades de piezas mayores a cero.'],
                    ]);
                }

                /** @var ObraCivilCommercialMaterial|null $material */
                $material = $materials->get($materialId);
                if (! $material) {
                    throw ValidationException::withMessages([
                        'items' => ['El material comercial no pertenece al grupo resuelto de la partida seleccionada.'],
                    ]);
                }

                $kg = $this->commercialQuantityToKg($material, $commercialQuantity);

                return [
                    'commercial_material_id' => (int) $material->id,
                    'sku' => $material->sku,
                    'descripcion' => $material->descripcion,
                    'unidad_compra' => $material->unidad_compra,
                    'commercial_quantity' => $commercialQuantity,
                    'peso_por_pieza' => $material->peso_por_pieza !== null ? (float) $material->peso_por_pieza : null,
                    'factor_conversion' => $material->factor_conversion !== null ? (float) $material->factor_conversion : null,
                    'kg' => round($kg, 4),
                    'material_group_id' => (int) $material->obra_civil_material_group_id,
                ];
            })
            ->values();
    }

    private function commercialItemsFromPayload(array $item): Collection
    {
        if (! empty($item['commercial_items']) && is_array($item['commercial_items'])) {
            return collect($item['commercial_items'])
                ->filter(fn ($commercialItem) => is_array($commercialItem))
                ->values();
        }

        $commercialMaterialId = (int) ($item['commercial_material_id'] ?? 0);
        if ($commercialMaterialId <= 0) {
            return collect();
        }

        return collect([[
            'commercial_material_id' => $commercialMaterialId,
            'commercial_quantity' => $item['commercial_quantity'] ?? null,
        ]]);
    }

    private function kgToBudgetUnit(ObraCivilInsumo $insumo, float $kg): float
    {
        $unit = strtoupper(trim((string) $insumo->unidad));

        return match ($unit) {
            'TON', 'T', 'TONS', 'TONELADA', 'TONELADAS' => round($kg / 1000, 4),
            'KG', 'KGS', 'KILO', 'KILOS', 'KILOGRAMO', 'KILOGRAMOS' => round($kg, 4),
            default => throw ValidationException::withMessages([
                'items' => ["No se puede convertir piezas comerciales a la unidad {$insumo->unidad} de la partida."],
            ]),
        };
    }

    private function commercialQuantityToKg(ObraCivilCommercialMaterial $material, float $commercialQuantity): float
    {
        if ($material->peso_por_pieza !== null && (float) $material->peso_por_pieza > 0) {
            return $commercialQuantity * (float) $material->peso_por_pieza;
        }

        if ($material->factor_conversion !== null && (float) $material->factor_conversion > 0) {
            return $commercialQuantity * (float) $material->factor_conversion;
        }

        throw ValidationException::withMessages([
            'items' => ['El material comercial seleccionado no tiene factor de conversion a kg.'],
        ]);
    }

    private function insumoSnapshot(ObraCivilInsumo $insumo, Collection $commercialLines, float $quantity): array
    {
        $snapshot = [
            'id' => $insumo->id,
            'codigo' => $insumo->codigo,
            'concepto' => $insumo->concepto,
            'unidad' => $insumo->unidad,
            'cantidad' => (float) $insumo->cantidad_presupuestada,
            'tipo' => $insumo->tipo,
        ];

        if ($commercialLines->isNotEmpty()) {
            $snapshot['commercial_request'] = [
                'items' => $commercialLines->values()->all(),
                'total_commercial_quantity' => round((float) $commercialLines->sum('commercial_quantity'), 4),
                'total_kg' => round((float) $commercialLines->sum('kg'), 4),
                'converted_quantity' => $quantity,
                'converted_unit' => $insumo->unidad,
            ];
        }

        return $snapshot;
    }
}
