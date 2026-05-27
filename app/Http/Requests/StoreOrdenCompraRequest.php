<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrdenCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // luego lo amarramos a permisos/roles
    }

    public function rules(): array
    {
       return [
        'proveedor_id'         => ['required', 'integer', 'exists:proveedores,id'],
        'obra_id'              => ['nullable', 'integer', 'exists:obras,id'],
        'centro_costo_id'      => ['nullable', 'integer', 'exists:centros_costo,id'],
        'planeacion_gasto_id'  => ['nullable', 'integer', 'exists:obra_planeacion_gastos,id'],  // NUEVO
 
        'area_id'              => ['required', 'integer', 'exists:areas,id'],
 
        'moneda'               => ['required', Rule::in(['MXN', 'USD', 'EUR'])],
        'tipo_cambio'          => ['nullable', 'numeric', 'min:0'],
 
        'fecha'                => ['required', 'date'],
 
        'cotizacion'           => ['nullable', 'string', 'max:50'],
        'atencion'             => ['nullable', 'string', 'max:100'],
        'tipo_pago'            => ['nullable', 'string', 'max:50'],
        'forma_pago'           => ['nullable', 'string', 'max:50'],
        'comentarios'          => ['nullable', 'string'],
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

            if ($this->filled('obra_id') && $this->filled('centro_costo_id')) {
                $v->errors()->add('centro_costo_id', 'Selecciona obra o centro de costo, no ambos.');
            }

            if ($this->filled('centro_costo_id') && $this->filled('planeacion_gasto_id')) {
                $v->errors()->add('planeacion_gasto_id', 'La partida presupuestal solo aplica cuando la orden pertenece a una obra.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'Selecciona un proveedor.',
            'area_id.required'      => 'Selecciona un área.',
            'fecha.required'        => 'La fecha es obligatoria.',
        ];
    }

    
}
