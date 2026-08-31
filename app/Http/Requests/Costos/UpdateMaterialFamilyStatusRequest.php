<?php

namespace App\Http\Requests\Costos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialFamilyStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.required' => 'Indica si la familia queda activa o inactiva.',
            'is_active.boolean' => 'El estado de la familia no es valido.',
        ];
    }
}
