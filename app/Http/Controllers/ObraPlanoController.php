<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraPlano;
use App\Models\PlanoCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ObraPlanoController extends Controller
{
    public function store(Request $request, Obra $obra)
    {
        $data = $request->validate([
            'plano_categoria_id' => ['required', 'exists:plano_categorias,id'],
            'nombre'             => ['required', 'string', 'max:255'],
            'version'            => ['nullable', 'string', 'max:100'],
            'archivo'            => ['required', 'file', 'max:15360'], // 15MB
            'notas'              => ['nullable', 'string'],
        ]);

        // Guardar archivo
        $path = $request->file('archivo')->store('planos', 'public');

        $data['archivo_path'] = $path;
        $data['obra_id'] = $obra->id;

        ObraPlano::create($data);

        return back()->with('success', 'Plano subido correctamente.');
    }

    public function destroy(Obra $obra, ObraPlano $plano)
    {
        if ($plano->obra_id !== $obra->id) abort(404);

        if (Storage::disk('public')->exists($plano->archivo_path)) {
            Storage::disk('public')->delete($plano->archivo_path);
        }

        $plano->delete();

        return back()->with('success', 'Plano eliminado correctamente.');
    }
}
