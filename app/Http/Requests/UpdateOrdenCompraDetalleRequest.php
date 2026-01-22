<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdenCompraDetalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id'     => ['nullable','integer','exists:productos,id'],
            'legacy_prod_id'  => ['nullable','string','max:50'],

            'descripcion'     => ['required','string','max:255'],
            'unidad'          => ['nullable','string','max:50'],

            'cantidad'        => ['required','numeric','min:0.0001'],
            'precio_unitario' => ['required','numeric','min:0'],

            'importe'         => ['nullable','numeric','min:0'],
            'iva'             => ['nullable','numeric','min:0'],
            'retenciones'     => ['nullable','numeric','min:0'],
            'otros_impuestos' => ['nullable','numeric','min:0'],

            'notas'           => ['nullable','string','max:255'],
        ];
    }
}
