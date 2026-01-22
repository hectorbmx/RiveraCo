<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrdenCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['required','integer','exists:proveedores,id'],
            'obra_id'      => ['nullable','integer','exists:obras,id'],

            'area_id'      => ['required','integer','exists:areas,id'],

            'moneda'       => ['required', Rule::in(['MXN','USD','EUR'])],
            'tipo_cambio'  => ['nullable','numeric','min:0'],

            'fecha'        => ['required','date'],

            'cotizacion'   => ['nullable','string','max:50'],
            'atencion'     => ['nullable','string','max:100'],
            'tipo_pago'    => ['nullable','string','max:50'],
            'forma_pago'   => ['nullable','string','max:50'],
            'comentarios'  => ['nullable','string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $moneda = $this->input('moneda', 'MXN');
            $tc     = $this->input('tipo_cambio');

            if ($moneda !== 'MXN' && (is_null($tc) || (float)$tc <= 0)) {
                $v->errors()->add('tipo_cambio', 'Tipo de cambio es obligatorio y debe ser mayor a 0 cuando la moneda no es MXN.');
            }
        });
    }
}
