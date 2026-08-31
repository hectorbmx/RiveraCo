<?php

namespace App\Http\Requests\Costos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source_codes' => $this->stringListFromInput('source_codes_text', 'source_codes'),
            'keywords' => $this->stringListFromInput('keywords_text', 'keywords'),
            'budget_units' => $this->stringListFromInput('budget_units_text', 'budget_units'),
            'match_rules' => $this->arrayFromJsonInput('match_rules_json', 'match_rules'),
            'metadata' => $this->arrayFromJsonInput('metadata_json', 'metadata'),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', Rule::unique('obra_civil_material_groups', 'code')],
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

    public function messages(): array
    {
        return [
            'code.required' => 'Captura la clave interna de la familia.',
            'code.unique' => 'Ya existe una familia con esta clave interna.',
            'name.required' => 'Captura el nombre de la familia.',
            'family.required' => 'Captura el grupo tecnico de la familia.',
            'match_rules.array' => 'Las reglas deben tener formato JSON valido.',
            'metadata.array' => 'La metadata debe tener formato JSON valido.',
        ];
    }

    private function stringListFromInput(string $textKey, string $arrayKey): array
    {
        $value = $this->input($textKey, $this->input($arrayKey, []));

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n|,/', $value) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function arrayFromJsonInput(string $jsonKey, string $arrayKey): array|string
    {
        $value = $this->input($jsonKey, $this->input($arrayKey, []));

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : '__invalid_json__';
    }
}
