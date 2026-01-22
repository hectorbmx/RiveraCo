<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\Maquina;
use App\Models\ObraMaquina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ObraMaquinaController extends Controller
{
    /**
     * Asignar una máquina a la obra.
     */
    public function store(Request $request, Obra $obra)
    {
        $data = $request->validate([
            'maquina_id'       => ['required', 'exists:maquinas,id'],
            'fecha_inicio'     => ['required', 'date'],
            'horometro_inicio' => ['required', 'numeric','gt:0'],
            'notas'            => ['nullable', 'string', 'max:1000'],
        ]);

        // Si no mandan fecha, usamos hoy
        $data['fecha_inicio'] = $data['fecha_inicio'] ?? now()->toDateString();

        // Verificar que la máquina NO esté ya activa en otra obra
        $yaAsignada = ObraMaquina::where('maquina_id', $data['maquina_id'])
            ->where('estado', 'activa')
            ->whereNull('fecha_fin')
            ->exists();

        if ($yaAsignada) {
            return redirect()
                ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'maquinaria'])
                ->withErrors([
                    'maquina_id' => 'Esta máquina ya está asignada activamente a otra obra.',
                ]);
        }

        ObraMaquina::create([
            'obra_id'      => $obra->id,
            'maquina_id'   => $data['maquina_id'],
            'fecha_inicio' => $data['fecha_inicio'],
            'horometro_inicio' => $data['horometro_inicio'],
            'fecha_fin'    => null,
            'estado'       => 'activa',
            'notas'        => $data['notas'] ?? null,
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'maquinaria'])
            ->with('success', 'Máquina asignada correctamente a la obra.');
    }

    /**
     * Dar de baja una máquina de la obra (finalizar asignación).
     */
    public function baja(Request $request, Obra $obra, ObraMaquina $asignacion)
    {
        // Seguridad: que la asignación pertenezca a esta obra
        if ($asignacion->obra_id !== $obra->id) {
            abort(404);
        }

        if ($asignacion->estado === 'finalizada') {
            return redirect()
                ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'maquinaria'])
                ->with('success', 'Esta asignación ya estaba finalizada.');
        }

        $asignacion->update([
            'estado'     => 'finalizada',
            'fecha_fin'  => now()->toDateString(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'maquinaria'])
            ->with('success', 'La máquina se dio de baja de la obra correctamente.');
    }
}
