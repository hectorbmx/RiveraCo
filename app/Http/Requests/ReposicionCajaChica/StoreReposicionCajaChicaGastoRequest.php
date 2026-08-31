<?php

namespace App\Http\Requests\ReposicionCajaChica;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReposicionCajaChicaGastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', Rule::exists('reposicion_caja_chica_categorias', 'id')->where('activo', true)],
            'subcategoria_id' => ['required', 'integer', Rule::exists('reposicion_caja_chica_subcategorias', 'id')->where('activo', true)],
            'destino' => ['required', Rule::in(['obra', 'almacen'])],
            'obra_id' => ['nullable', 'required_if:destino,obra', 'integer', 'exists:obras,id'],
            'almacen_id' => ['nullable', 'required_if:destino,almacen', 'integer', 'exists:almacenes,id'],
            'fecha_gasto' => ['required', 'date', 'before_or_equal:today'],
            'proveedor_nombre' => ['required', 'string', 'max:255'],
            'proveedor_rfc' => ['nullable', 'string', 'max:20'],
            'concepto' => ['required', 'string', 'max:1000'],
            'forma_pago' => ['nullable', 'string', 'max:50'],
            'importe_registrado' => ['required', 'numeric', 'min:0.01'],
            'motivo_sin_factura' => ['nullable', 'string', 'max:2000'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'evidencias.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}

