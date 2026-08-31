<?php

namespace App\Services\Costos;

use App\Models\ObraCivilCommercialMaterial;
use App\Models\ObraCivilMaterialGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CatalogoMaterialCostosWriteService
{
    public function createFamily(array $data): ObraCivilMaterialGroup
    {
        return DB::transaction(function () use ($data) {
            return ObraCivilMaterialGroup::create($this->familyAttributes($data));
        });
    }

    public function updateFamily(ObraCivilMaterialGroup $group, array $data): ObraCivilMaterialGroup
    {
        return DB::transaction(function () use ($group, $data) {
            $group->update($this->familyAttributes($data));

            return $group->refresh();
        });
    }

    public function setFamilyActive(ObraCivilMaterialGroup $group, bool $isActive): ObraCivilMaterialGroup
    {
        return DB::transaction(function () use ($group, $isActive) {
            $group->update(['is_active' => $isActive]);

            return $group->refresh();
        });
    }

    public function createCommercialMaterial(array $data): ObraCivilCommercialMaterial
    {
        return DB::transaction(function () use ($data) {
            return ObraCivilCommercialMaterial::create($this->commercialMaterialAttributes($data));
        });
    }

    public function updateCommercialMaterial(ObraCivilCommercialMaterial $material, array $data): ObraCivilCommercialMaterial
    {
        return DB::transaction(function () use ($material, $data) {
            $material->update($this->commercialMaterialAttributes($data));

            return $material->refresh();
        });
    }

    public function setCommercialMaterialActive(ObraCivilCommercialMaterial $material, bool $isActive): ObraCivilCommercialMaterial
    {
        return DB::transaction(function () use ($material, $isActive) {
            $material->update(['is_active' => $isActive]);

            return $material->refresh();
        });
    }

    private function familyAttributes(array $data): array
    {
        return [
            'code' => trim((string) $data['code']),
            'name' => trim((string) $data['name']),
            'family' => trim((string) $data['family']),
            'grade' => $this->nullableString($data['grade'] ?? null),
            'source_codes' => $this->cleanStringArray($data['source_codes'] ?? []),
            'keywords' => $this->cleanStringArray($data['keywords'] ?? []),
            'match_rules' => $this->cleanArray($data['match_rules'] ?? []),
            'budget_units' => $this->cleanStringArray($data['budget_units'] ?? []),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'metadata' => $this->cleanArray($data['metadata'] ?? []),
        ];
    }

    private function commercialMaterialAttributes(array $data): array
    {
        $pesoPorPieza = $this->nullableFloat($data['peso_por_pieza'] ?? null);
        $factorConversion = $this->nullableFloat($data['factor_conversion'] ?? null);

        if (($data['conversion_type'] ?? null) === 'fixed_weight_per_piece' && $factorConversion === null) {
            $factorConversion = $pesoPorPieza;
        }

        return [
            'obra_civil_material_group_id' => (int) $data['obra_civil_material_group_id'],
            'category' => $this->nullableString($data['category'] ?? null),
            'subcategory' => $this->nullableString($data['subcategory'] ?? null),
            'grade' => $this->nullableString($data['grade'] ?? null),
            'sku' => trim((string) $data['sku']),
            'descripcion' => trim((string) $data['descripcion']),
            'medida' => $this->nullableString($data['medida'] ?? null),
            'diametro' => $this->nullableString($data['diametro'] ?? null),
            'calibre_espesor' => $this->nullableString($data['calibre_espesor'] ?? null),
            'longitud' => $this->nullableFloat($data['longitud'] ?? null),
            'unidad_compra' => trim((string) $data['unidad_compra']),
            'conversion_type' => trim((string) $data['conversion_type']),
            'peso_por_metro' => $this->nullableFloat($data['peso_por_metro'] ?? null),
            'peso_por_pieza' => $pesoPorPieza,
            'peso_por_m2' => $this->nullableFloat($data['peso_por_m2'] ?? null),
            'peso_por_rollo' => $this->nullableFloat($data['peso_por_rollo'] ?? null),
            'factor_conversion' => $factorConversion,
            'tolerance' => $this->nullableString($data['tolerance'] ?? null),
            'validation_status' => $this->nullableString($data['validation_status'] ?? null),
            'technical_source' => $this->nullableString($data['technical_source'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'metadata' => $this->cleanArray($data['metadata'] ?? []),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function cleanStringArray(mixed $values): array
    {
        if (is_string($values)) {
            $values = preg_split('/\r\n|\r|\n|,/', $values) ?: [];
        }

        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function cleanArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return Arr::where($value, fn ($item) => $item !== null && $item !== '');
    }
}
