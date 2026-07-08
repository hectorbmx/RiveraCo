<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NominaListaRaya;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpresaConfigListaRayaController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['activo'] = $request->boolean('activo', true);
        $data['es_automatica'] = false;

        NominaListaRaya::create($data);

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'listas_raya'])
            ->with('success', 'Lista de raya creada correctamente.');
    }

    public function update(Request $request, NominaListaRaya $listaRaya)
    {
        if ($listaRaya->es_automatica) {
            return redirect()
                ->route('empresa_config.edit', ['tab' => 'listas_raya'])
                ->with('error', 'Las listas automaticas de obra no se editan manualmente.');
        }

        $data = $this->validatedData($request, $listaRaya);
        $data['activo'] = $request->boolean('activo');
        $data['es_automatica'] = false;

        $listaRaya->update($data);

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'listas_raya'])
            ->with('success', 'Lista de raya actualizada correctamente.');
    }

    public function toggle(NominaListaRaya $listaRaya)
    {
        if ($listaRaya->es_automatica) {
            return redirect()
                ->route('empresa_config.edit', ['tab' => 'listas_raya'])
                ->with('error', 'Las listas automaticas dependen del estado de la obra.');
        }

        $listaRaya->update(['activo' => ! (bool) $listaRaya->activo]);

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'listas_raya'])
            ->with('success', 'Estatus de lista de raya actualizado.');
    }

    public function destroy(NominaListaRaya $listaRaya)
    {
        if ($listaRaya->es_automatica) {
            return redirect()
                ->route('empresa_config.edit', ['tab' => 'listas_raya'])
                ->with('error', 'No puedes eliminar una lista automatica de obra.');
        }

        if ($listaRaya->empleadosPrincipales()->exists()) {
            return redirect()
                ->route('empresa_config.edit', ['tab' => 'listas_raya'])
                ->with('error', 'No puedes eliminar una lista asignada a empleados.');
        }

        $listaRaya->delete();

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'listas_raya'])
            ->with('success', 'Lista de raya eliminada.');
    }

    private function validatedData(Request $request, ?NominaListaRaya $listaRaya = null): array
    {
        $tipos = array_keys(NominaListaRaya::TIPOS);

        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('nomina_listas_raya', 'nombre')->ignore($listaRaya?->id),
            ],
            'tipo' => ['required', Rule::in($tipos)],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'almacen_id' => ['nullable', 'integer', 'exists:almacenes,id'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
