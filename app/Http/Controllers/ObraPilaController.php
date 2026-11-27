<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraPila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    DB::transaction(function () use ($obra, $data): void {
        $actual = Obra::lockForUpdate()->findOrFail($obra->id);
        $actual->pilas()->create($data + [
            'activo' => true,
            'numero_pila' => (int) $actual->pilas()->max('numero_pila') + 1,
        ]);
    }, 3);

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
