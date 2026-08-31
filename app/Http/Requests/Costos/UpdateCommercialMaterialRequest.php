<?php

namespace App\Http\Requests\Costos;

use Illuminate\Validation\Rule;

class UpdateCommercialMaterialRequest extends StoreCommercialMaterialRequest
{
    public function rules(): array
    {
        $materialId = $this->route('hijo')?->id ?? $this->route('commercialMaterial')?->id;

        return $this->baseRules([
            'sku' => ['required', 'string', 'max:100', Rule::unique('obra_civil_commercial_materials', 'sku')->ignore($materialId)],
            'obra_civil_material_group_id' => ['required', 'integer', 'exists:obra_civil_material_groups,id'],
        ]);
    }
}
