<?php
namespace App\Http\Controllers;
use App\Models\CatalogoRol;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CatalogoRolController extends Controller
{
    public function create()
    {
        return view('empresa_config.catalogo.create');
    }

    public function edit(CatalogoRol $rol)
    {
        return view('empresa_config.catalogo.edit', compact('rol'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'rol_key'      => ['required','string','max:64','unique:catalogo_roles,rol_key'],
            'nombre'       => ['required','string','max:120'],
            'comisionable' => ['nullable','boolean'],
            
        ]);

        $data['rol_key'] = Str::upper(trim($data['rol_key']));
        $data['comisionable'] = (bool)($data['comisionable'] ?? false);
        

        CatalogoRol::create($data);

        return back()->with('success', 'Puesto creado.');
    }

    public function update(Request $request, CatalogoRol $rol)
    {
        $data = $request->validate([
            'rol_key'      => ['required','string','max:64','unique:catalogo_roles,rol_key,' . $rol->id],
            'nombre'       => ['required','string','max:120'],
            'comisionable' => ['nullable','boolean'],
            'activo'       => ['nullable','boolean'],
        ]);

        $data['rol_key'] = Str::upper(trim($data['rol_key']));
        $data['comisionable'] = (bool)($data['comisionable'] ?? false);
        $data['activo'] = (bool)($data['activo'] ?? false);

        $rol->update($data);

        return back()->with('success', 'Puesto actualizado.');
    }

    public function destroy(CatalogoRol $rol)
    {
        // recomendado: desactivar en vez de borrar (si ya está en uso)
        $rol->update(['activo' => false]);

        return back()->with('success', 'Puesto desactivado.');
    }
}