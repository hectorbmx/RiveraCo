<?php

namespace App\Http\Requests\Costos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommercialMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $group = $this->route('materiale') ?? $this->route('group');

        if ($group?->id) {
            $this->merge([
                'obra_civil_material_group_id' => $group->id,
            ]);
        }
    }

    public function rules(): array
    {
        return $this->baseRules([
            'sku' => ['required', 'string', 'max:100', Rule::unique('obra_civil_commercial_materials', 'sku')],
            'obra_civil_material_group_id' => ['required', 'integer', 'exists:obra_civil_material_groups,id'],
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(fn ($validator) => $this->validateConversion($validator));
    }

    public function messages(): array
    {
        return [
            'obra_civil_material_group_id.required' => 'Selecciona la familia padre.',
            'sku.required' => 'Captura el SKU interno del material hijo.',
            'sku.unique' => 'Ya existe un material hijo con este SKU.',
            'descripcion.required' => 'Captura la descripcion comercial.',
            'unidad_compra.required' => 'Captura la unidad de compra.',
            'conversion_type.required' => 'Selecciona el tipo de conversion.',
        ];
    }

    protected function baseRules(array $overrides = []): array
    {
        return array_merge([
            'obra_civil_material_group_id' => ['required', 'integer', 'exists:obra_civil_material_groups,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:100'],
            'sku' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:500'],
            'medida' => ['nullable', 'string', 'max:100'],
            'diametro' => ['nullable', 'string', 'max:50'],
            'calibre_espesor' => ['nullable', 'string', 'max:100'],
            'longitud' => ['nullable', 'numeric', 'min:0'],
            'unidad_compra' => ['required', 'string', 'max:50'],
            'conversion_type' => ['required', 'string', 'max:50', Rule::in(['fixed_weight_per_piece'])],
            'peso_por_metro' => ['nullable', 'numeric', 'min:0'],
            'peso_por_pieza' => ['nullable', 'numeric', 'min:0'],
            'peso_por_m2' => ['nullable', 'numeric', 'min:0'],
            'peso_por_rollo' => ['nullable', 'numeric', 'min:0'],
            'factor_conversion' => ['nullable', 'numeric', 'min:0'],
            'tolerance' => ['nullable', 'string', 'max:50'],
            'validation_status' => ['nullable', 'string', 'max:100'],
            'technical_source' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ], $overrides);
    }

    protected function validateConversion($validator): void
    {
        if ($this->input('conversion_type') !== 'fixed_weight_per_piece') {
            return;
        }

        $pesoPorPieza = (float) $this->input('peso_por_pieza', 0);
        $factor = (float) $this->input('factor_conversion', 0);

        if ($pesoPorPieza <= 0 && $factor <= 0) {
            $validator->errors()->add('peso_por_pieza', 'Captura peso por pieza o factor de conversion mayor a cero.');
        }
    }
}

