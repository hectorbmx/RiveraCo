<?php

namespace App\Http\Controllers;

use App\Models\EmpresaServicioPreventivoTipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpresaServicioPreventivoTipoController extends Controller
{
    public function validarCodigo(Request $request)
    {
        $data = $request->validate([
            'ambito' => ['required', Rule::in([
                EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA,
                EmpresaServicioPreventivoTipo::AMBITO_VEHICULO,
            ])],
            'codigo' => ['required', 'string', 'max:80'],
            'ignore_id' => ['nullable', 'integer'],
        ]);

        $codigo = str($data['codigo'])->lower()->slug('_')->toString();

        $exists = EmpresaServicioPreventivoTipo::query()
            ->where('ambito', $data['ambito'])
            ->where('codigo', $codigo)
            ->when($data['ignore_id'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
            ->exists();

        return response()->json([
            'exists' => $exists,
            'codigo' => $codigo,
        ]);
    }
    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        EmpresaServicioPreventivoTipo::create($data);

        return back()->with('success', 'Tipo de servicio preventivo creado correctamente.');
    }

    public function update(Request $request, EmpresaServicioPreventivoTipo $tipo)
    {
        $data = $this->validatedData($request, $tipo);

        $tipo->update($data);

        return back()->with('success', 'Tipo de servicio preventivo actualizado correctamente.');
    }

    public function toggleActivo(EmpresaServicioPreventivoTipo $tipo)
    {
        $tipo->update([
            'activo' => ! $tipo->activo,
        ]);

        $estado = $tipo->activo ? 'activado' : 'desactivado';

        return back()->with('success', "Tipo de servicio preventivo {$estado} correctamente.");
    }

    private function validatedData(Request $request, ?EmpresaServicioPreventivoTipo $tipo = null): array
    {
        $ambitos = [
            EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA,
            EmpresaServicioPreventivoTipo::AMBITO_VEHICULO,
        ];

        $request->merge([
            'codigo' => str((string) $request->input('codigo'))->lower()->slug('_')->toString(),
        ]);

        $data = $request->validate([
            'ambito' => ['required', Rule::in($ambitos)],
            'nombre' => ['required', 'string', 'max:100'],
            'codigo' => [
                'required',
                'string',
                'max:80',
                Rule::unique('empresa_servicio_preventivo_tipos', 'codigo')
                    ->where(fn ($query) => $query->where('ambito', $request->input('ambito')))
                    ->ignore($tipo?->id),
            ],
            'intervalo_valor' => ['required', 'integer', 'min:1'],
            'intervalo_meses' => ['nullable', 'integer', 'min:1', 'max:120'],
            'alerta_valor' => ['nullable', 'integer', 'min:0'],
            'alerta_dias' => ['nullable', 'integer', 'min:0'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['unidad'] = $data['ambito'] === EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA
            ? EmpresaServicioPreventivoTipo::UNIDAD_HORAS
            : EmpresaServicioPreventivoTipo::UNIDAD_KM;
        $data['activo'] = $request->has('activo');

        if ($data['ambito'] === EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA) {
            $data['alerta_dias'] = null;
        }

        return $data;
    }
}