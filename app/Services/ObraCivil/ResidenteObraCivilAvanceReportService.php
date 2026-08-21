<?php

namespace App\Services\ObraCivil;

use App\Models\CivilConcept;
use App\Models\CivilWorkReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

class ResidenteObraCivilAvanceReportService
{
    public function store(ResidenteObraCivilContext $context, array $data): CivilWorkReport
    {
        $concept = $this->conceptForContext($context, (int) $data['civil_concept_id']);
        $photos = $data['photos'] ?? [];

        return DB::transaction(function () use ($context, $data, $concept, $photos) {
            $report = CivilWorkReport::create([
                'obra_id' => $context->obra->id,
                'user_id' => $context->user->id,
                'empleado_id' => $context->empleadoId(),
                'status' => CivilWorkReport::STATUS_PENDIENTE,
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

            $item = $report->items()->create([
                'civil_concept_id' => $concept->id,
                'quantity' => $data['quantity'],
                'unit' => $concept->unit,
                'concept_snapshot' => $this->conceptSnapshot($concept),
                'notes' => $data['item_notes'] ?? null,
            ]);

            foreach ($photos as $photo) {
                if ($photo instanceof UploadedFile) {
                    $this->storePhoto($item, $photo);
                }
            }

            return $report->load(['items.photos', 'items.concept']);
        });
    }

    private function conceptForContext(ResidenteObraCivilContext $context, int $conceptId): CivilConcept
    {
        $concept = CivilConcept::query()
            ->with([
                'partida:id,civil_building_id,code,name',
                'partida.building:id,civil_catalog_import_id,name',
                'partida.building.catalogImport:id,obra_id,status',
            ])
            ->where('civil_concepts.id', $conceptId)
            ->where('civil_concepts.is_active', true)
            ->whereHas('partida.building.catalogImport', function ($query) use ($context) {
                $query->where('obra_id', $context->obra->id)
                    ->whereIn('status', ['imported', 'validated']);
            })
            ->first();

        if (! $concept) {
            throw ValidationException::withMessages([
                'civil_concept_id' => ['El concepto no pertenece al catalogo activo de tu obra civil.'],
            ]);
        }

        return $concept;
    }

    private function conceptSnapshot(CivilConcept $concept): array
    {
        return [
            'id' => $concept->id,
            'clave' => $concept->excel_code,
            'descripcion' => $concept->description,
            'unidad' => $concept->unit,
            'cantidad' => (float) $concept->budget_quantity,
            'edificio' => $concept->partida?->building?->name,
            'partida' => [
                'id' => $concept->partida?->id,
                'code' => $concept->partida?->code,
                'name' => $concept->partida?->name,
            ],
        ];
    }

    private function storePhoto($item, UploadedFile $photo): void
    {
        $imageManager = ImageManager::gd();
        $image = $imageManager->read($photo->getRealPath())
            ->scaleDown(width: 1600)
            ->toWebp(quality: 72);

        $path = 'obra-civil/avance/'
            . $item->civil_work_report_id
            . '/'
            . $item->id
            . '/'
            . Str::uuid()
            . '.webp';

        Storage::disk('public')->put($path, $image);

        $item->photos()->create([
            'path' => $path,
            'original_name' => $photo->getClientOriginalName(),
            'mime_type' => 'image/webp',
            'size' => strlen((string) $image),
            'metadata' => [
                'source_mime_type' => $photo->getClientMimeType(),
                'source_size' => $photo->getSize(),
            ],
        ]);
    }
}


