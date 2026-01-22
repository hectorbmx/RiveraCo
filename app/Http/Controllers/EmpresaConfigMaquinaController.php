<?php

namespace App\Http\Controllers;

use App\Models\Maquina;
use Illuminate\Http\Request;

class EmpresaConfigMaquinaController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'general'); // o como lo manejes

        $maquinas = Maquina::orderBy('nombre')->get();

        return view('empresa_config.index', compact('tab', 'maquinas'));
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo'         => ['nullable', 'string', 'max:50'],
            'nombre'         => ['required', 'string', 'max:120'],
            'marca'          => ['nullable', 'string', 'max:80'],
            'modelo'         => ['nullable', 'string', 'max:80'],
            'numero_serie'   => ['nullable', 'string', 'max:120'],
            'placas'         => ['nullable', 'string', 'max:30'],
            'color'          => ['nullable', 'string', 'max:50'],
            'horometro_base' => ['nullable', 'numeric', 'min:0'],
            'activo'         => ['nullable', 'boolean'], // si manejas activo
        ]);

        // Si no mandan activo desde checkbox
        $data['activo'] = (bool) ($request->input('activo', false));

        Maquina::create($data);

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'maquinaria'])
            ->with('success', 'Máquina creada.');
    }

    public function update(Request $request, Maquina $maquina)
    {
        $data = $request->validate([
            'codigo'         => ['nullable', 'string', 'max:50'],
            'nombre'         => ['required', 'string', 'max:120'],
            'tipo'           => ['required', 'string', 'max:120'],
            'marca'          => ['required', 'string', 'max:120'],
            'marca'          => ['nullable', 'string', 'max:80'],
            'modelo'         => ['nullable', 'string', 'max:80'],
            'numero_serie'   => ['nullable', 'string', 'max:120'],
            'placas'         => ['nullable', 'string', 'max:30'],
            'color'          => ['nullable', 'string', 'max:50'],
            'horometro_base' => ['nullable', 'numeric', 'min:0'],
            'activo'         => ['nullable', 'boolean'],
        ]);

        $data['activo'] = (bool) ($request->input('activo', false));

        $maquina->update($data);

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'maquinaria'])
            ->with('success', 'Máquina actualizada.');
    }

    public function destroy(Maquina $maquina)
    {
        // Recomendación: si usas soft deletes, aplica delete() normal.
        // Si no, y no quieres perder data, cambia a "activo = 0".
        $maquina->delete();

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'maquinaria'])
            ->with('success', 'Máquina eliminada.');
    }

    public function edit(Maquina $maquina)
    {
        return view('empresa_config.maquinas.edit', compact('maquina'));
    }

    public function create()
{
    return view('empresa_config.maquinas.create');
}

}
