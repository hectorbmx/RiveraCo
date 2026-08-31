<?php

namespace App\ViewModels\Costos;

use App\Models\ObraCivilCommercialMaterial;

class CommercialMaterialRow
{
    public function __construct(
        public readonly int $id,
        public readonly string $sku,
        public readonly ?int $parentId,
        public readonly ?string $parentCode,
        public readonly ?string $parentName,
        public readonly string $descripcion,
        public readonly ?string $category,
        public readonly ?string $subcategory,
        public readonly ?string $grade,
        public readonly ?string $medida,
        public readonly ?string $diametro,
        public readonly ?string $calibreEspesor,
        public readonly ?string $longitud,
        public readonly string $unidadCompra,
        public readonly string $conversionType,
        public readonly ?string $pesoPorMetro,
        public readonly ?string $pesoPorPieza,
        public readonly ?string $pesoPorM2,
        public readonly ?string $pesoPorRollo,
        public readonly ?string $factorConversion,
        public readonly ?string $tolerance,
        public readonly ?string $validationStatus,
        public readonly ?string $technicalSource,
        public readonly bool $isActive,
        public readonly string $statusLabel,
        public readonly string $statusClass,
        public readonly string $validationClass,
    ) {
    }

    public static function fromMaterial(ObraCivilCommercialMaterial $material): self
    {
        return new self(
            id: (int) $material->id,
            sku: (string) $material->sku,
            parentId: $material->obra_civil_material_group_id ? (int) $material->obra_civil_material_group_id : null,
            parentCode: $material->group?->code,
            parentName: $material->group?->name,
            descripcion: (string) $material->descripcion,
            category: $material->category,
            subcategory: $material->subcategory,
            grade: $material->grade,
            medida: $material->medida,
            diametro: $material->diametro,
            calibreEspesor: $material->calibre_espesor,
            longitud: $material->longitud !== null ? number_format((float) $material->longitud, 4, '.', '') : null,
            unidadCompra: (string) $material->unidad_compra,
            conversionType: (string) $material->conversion_type,
            pesoPorMetro: $material->peso_por_metro !== null ? number_format((float) $material->peso_por_metro, 6, '.', '') : null,
            pesoPorPieza: $material->peso_por_pieza !== null ? number_format((float) $material->peso_por_pieza, 6, '.', '') : null,
            pesoPorM2: $material->peso_por_m2 !== null ? number_format((float) $material->peso_por_m2, 6, '.', '') : null,
            pesoPorRollo: $material->peso_por_rollo !== null ? number_format((float) $material->peso_por_rollo, 6, '.', '') : null,
            factorConversion: $material->factor_conversion !== null ? number_format((float) $material->factor_conversion, 6, '.', '') : null,
            tolerance: $material->tolerance,
            validationStatus: $material->validation_status,
            technicalSource: $material->technical_source,
            isActive: (bool) $material->is_active,
            statusLabel: $material->is_active ? 'Activo' : 'Inactivo',
            statusClass: $material->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
            validationClass: self::validationClass($material->validation_status),
        );
    }

    public function categoryText(): string
    {
        return trim(implode(' / ', array_filter([$this->category, $this->subcategory]))) ?: '-';
    }

    public function specsText(): string
    {
        $parts = array_filter([
            $this->medida,
            $this->diametro ? 'diam. ' . $this->diametro : null,
            $this->calibreEspesor,
            $this->longitud ? $this->longitud . ' m' : null,
        ]);

        return empty($parts) ? '-' : implode(' · ', $parts);
    }

    public function weightText(): string
    {
        if ($this->pesoPorPieza !== null) {
            return number_format((float) $this->pesoPorPieza, 3) . ' kg/pza';
        }

        if ($this->factorConversion !== null) {
            return number_format((float) $this->factorConversion, 3) . ' kg/' . strtolower($this->unidadCompra);
        }

        return '-';
    }

    private static function validationClass(?string $status): string
    {
        $normalized = mb_strtolower((string) $status);

        if ($normalized === '') {
            return 'bg-amber-100 text-amber-800';
        }

        if (str_contains($normalized, 'validar') || str_contains($normalized, 'confirmar')) {
            return 'bg-orange-100 text-orange-800';
        }

        return 'bg-blue-100 text-blue-800';
    }
}
