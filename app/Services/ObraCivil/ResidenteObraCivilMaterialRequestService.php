<?php

namespace App\Services\ObraCivil;

use App\Models\ObraCivilInsumo;
use App\Models\ObraCivilMaterialRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResidenteObraCivilMaterialRequestService
{
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

                $request->items()->create([
                    'obra_civil_insumo_id' => $insumo->id,
                    'quantity' => $item['quantity'],
                    'unit' => $insumo->unidad,
                    'insumo_snapshot' => $this->insumoSnapshot($insumo),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $request->load(['items.insumo']);
        });
    }

    private function insumoSnapshot(ObraCivilInsumo $insumo): array
    {
        return [
            'id' => $insumo->id,
            'codigo' => $insumo->codigo,
            'concepto' => $insumo->concepto,
            'unidad' => $insumo->unidad,
            'cantidad' => (float) $insumo->cantidad_presupuestada,
            'tipo' => $insumo->tipo,
        ];
    }
}

