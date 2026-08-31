<?php

namespace App\Http\Requests\Costos;

use Illuminate\Validation\Rule;

class UpdateMaterialFamilyRequest extends StoreMaterialFamilyRequest
{
    public function rules(): array
    {
        $groupId = $this->route('materiale')?->id ?? $this->route('group')?->id;

        return [
            'code' => ['required', 'string', 'max:100', Rule::unique('obra_civil_material_groups', 'code')->ignore($groupId)],
            'name' => ['required', 'string', 'max:255'],
            'family' => ['required', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:50'],
            'source_codes' => ['nullable', 'array'],
            'source_codes.*' => ['nullable', 'string', 'max:100'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['nullable', 'string', 'max:100'],
            'match_rules' => ['nullable', 'array'],
            'budget_units' => ['nullable', 'array'],
            'budget_units.*' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
