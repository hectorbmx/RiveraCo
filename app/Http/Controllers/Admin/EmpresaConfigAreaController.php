<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpresaConfigAreaController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['required','string','max:50', 'unique:areas,codigo'],
            'nombre' => ['required','string','max:150'],
            'descripcion' => ['nullable','string','max:500'],
            'activo' => ['nullable','boolean'],
        ]);

        $data['activo'] = (bool) ($data['activo'] ?? true);

        Area::create($data);

        return back()->with('success', 'Área creada correctamente.');
    }

    public function update(Request $request, Area $area)
    {
        $data = $request->validate([
            'codigo' => ['required','string','max:50', Rule::unique('areas','codigo')->ignore($area->id)],
            'nombre' => ['required','string','max:150'],
            'descripcion' => ['nullable','string','max:500'],
            'activo' => ['nullable','boolean'],
        ]);

        $data['activo'] = (bool) ($data['activo'] ?? false);

        $area->update($data);

        return back()->with('success', 'Área actualizada correctamente.');
    }

    public function toggle(Area $area)
    {
        $area->update(['activo' => ! (bool)$area->activo]);

        return back()->with('success', 'Estatus de área actualizado.');
    }

    public function destroy(Area $area)
    {
        $area->delete();

        return back()->with('success', 'Área eliminada.');
    }
}
