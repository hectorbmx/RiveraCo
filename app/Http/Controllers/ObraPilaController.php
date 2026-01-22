<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraPila;
use Illuminate\Http\Request;

class ObraPilaController extends Controller
{
    /**
     * Asignar una nueva pila a la obra.
     */
public function store(Request $request, Obra $obra)
{
    $data = $request->validate([
        'tipo'                  => ['required', 'string', 'max:50'],
        'diametro_proyecto'     => ['nullable', 'numeric', 'min:0'],
        'profundidad_proyecto'  => ['nullable', 'numeric', 'min:0'],
        'ubicacion'             => ['nullable', 'string', 'max:150'],
        'cantidad_programada'   => ['nullable', 'numeric', 'min:0'], // <-- mejor numeric
        'notas'                 => ['nullable', 'string'],
    ]);

    $data['obra_id'] = $obra->id;
    $data['activo']  = true;

    // ✅ asignar consecutivo por obra
    $max = ObraPila::where('obra_id', $obra->id)->max('numero_pila');
    $data['numero_pila'] = $max ? ($max + 1) : 1;

    ObraPila::create($data);

    return redirect()
        ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'pilas'])
        ->with('success', 'Pila asignada correctamente a la obra.');
}


    /**
     * Dar de baja una pila en la obra.
     */
    public function baja(Obra $obra, ObraPila $pila)
    {
        // seguridad: la pila debe pertenecer a esta obra
        if ($pila->obra_id !== $obra->id) {
            abort(404);
        }

        $pila->update([
            'activo' => false,
        ]);

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'pilas'])
            ->with('success', 'Pila dada de baja correctamente.');
    }
}
