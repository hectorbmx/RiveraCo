<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraMaquina;
use Illuminate\Http\Request;
use App\Models\ObraMaquinaRegistro;

class ObraMaquinaHorasController extends Controller
{
    public function create(Request $request, Obra $obra, ObraMaquina $obraMaquina)
    {
        // Seguridad: validar que la asignación pertenece a la obra
        if ((int) $obraMaquina->obra_id !== (int) $obra->id) {
            abort(404);
        }

        // Bloqueo: solo permitir capturar si está activa
        if ($obraMaquina->estado !== 'activa') {
            return redirect()
                ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'horas-maquina'])
                ->with('error', 'No puedes registrar horas en una asignación finalizada.');
        }

        // Horómetro sugerido de inicio: último horómetro_fin registrado o el horómetro_inicio de la asignación
        $ultimo = $obraMaquina->registrosHoras()
            ->orderByDesc('fin')
            ->orderByDesc('id')
            ->first();

        $horometroSugerido = $ultimo?->horometro_fin ?? $obraMaquina->horometro_inicio;

        return view('obras.horas-maquina.create', [
            'obra'             => $obra,
            'obraMaquina'      => $obraMaquina,
            'maquina'          => $obraMaquina->maquina,
            'horometroSugerido'=> $horometroSugerido,
        ]);
    }
    public function store(Request $request, Obra $obra, ObraMaquina $obraMaquina)
{
    if ((int) $obraMaquina->obra_id !== (int) $obra->id) {
        abort(404);
    }

    if ($obraMaquina->estado !== 'activa') {
        return back()->with('error', 'No puedes registrar horas en una asignación finalizada.');
    }

    $data = $request->validate([
        // un input: horómetro final
        'horometro_fin' => 'required|numeric|min:0',
        'inicio'        => 'nullable|date',
        'fin'           => 'nullable|date|after_or_equal:inicio',
        'notas'         => 'nullable|string|max:500',
    ]);

    // Horómetro inicio sugerido (último registro o baseline de asignación)
    $ultimo = $obraMaquina->registrosHoras()
        ->orderByDesc('fin')
        ->orderByDesc('id')
        ->first();

    $horometroInicio = (float) ($ultimo?->horometro_fin ?? $obraMaquina->horometro_inicio ?? 0);

    $horometroFin = (float) $data['horometro_fin'];

    if ($horometroFin < $horometroInicio) {
        return back()->with('error', "El horómetro final no puede ser menor al último registrado ({$horometroInicio}).");
    }

    // Fechas: si no mandas nada, usamos ahora
    $inicio = $data['inicio'] ?? now();
    $fin    = $data['fin'] ?? now();

    ObraMaquinaRegistro::create([
        'obra_maquina_id'  => $obraMaquina->id,
        'obra_id'          => $obra->id,
        'maquina_id'       => $obraMaquina->maquina_id,
        'inicio'           => $inicio,
        'fin'              => $fin,
        'horometro_inicio' => $horometroInicio,
        'horometro_fin'    => $horometroFin,
        'horas'            => round(max(0, $horometroFin - $horometroInicio), 2),
        'notas'            => $data['notas'] ?? null,
        'created_by'       => auth()->id(),
        'updated_by'       => auth()->id(),
    ]);

    return redirect()
        ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'horas-maquina'])
        ->with('success', 'Horas registradas correctamente.');
}
}
