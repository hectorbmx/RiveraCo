<?php

namespace App\Http\Requests\Costos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommercialMaterialStatusRequest extends FormRequest
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
            'is_active.required' => 'Indica si el material hijo queda activo o inactivo.',
            'is_active.boolean' => 'El estado del material hijo no es valido.',
        ];
    }
}
