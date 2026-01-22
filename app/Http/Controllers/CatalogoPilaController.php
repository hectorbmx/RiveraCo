<?php

namespace App\Http\Controllers;

use App\Models\CatalogoPila;
use Illuminate\Http\Request;

class CatalogoPilaController extends Controller
{
    /**
     * Listado de pilas del catálogo.
     */
    public function index()
    {
        $pilas = CatalogoPila::orderBy('diametro_cm')
            ->orderBy('codigo')
            ->get();

        return view('catalogo_pilas.index', [
            'pilas' => $pilas,
        ]);
    }

    /**
     * Formulario para crear una nueva pila en el catálogo.
     */
    public function create()
    {
        return view('catalogo_pilas.create');
    }

    /**
     * Guardar una nueva pila en el catálogo.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo'       => ['required', 'string', 'max:50', 'unique:catalogo_pilas,codigo'],
            'descripcion'  => ['nullable', 'string', 'max:255'],
            'diametro_cm'  => ['nullable', 'integer', 'min:0'],
            'activa'       => ['nullable', 'boolean'],
        ]);

        // checkbox "activa"
        $data['activa'] = $request->has('activa');

        CatalogoPila::create($data);

        return redirect()
            ->route('catalogo-pilas.index')
            ->with('success', 'Pila agregada al catálogo correctamente.');
    }

    // Más adelante podemos llenar edit/update/destroy si hace falta.
}
