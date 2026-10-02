<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraMaquina;
use App\Services\Maquinas\MaquinaHorometroService;
use Illuminate\Http\Request;

class ObraMaquinaHorasController extends Controller
{
    public function create(Request $request, Obra $obra, ObraMaquina $obraMaquina, MaquinaHorometroService $horometroService)
    {
        if ((int) $obraMaquina->obra_id !== (int) $obra->id) {
            abort(404);
        }

        if ($obraMaquina->estado !== 'activa') {
            return redirect()
                ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'horas-maquina'])
                ->with('error', 'No puedes registrar horas en una asignación finalizada.');
        }

        $horometroSugerido = $horometroService->horometroInicioParaRegistro($obraMaquina);

        return view('obras.horas-maquina.create', [
            'obra' => $obra,
            'obraMaquina' => $obraMaquina,
            'maquina' => $obraMaquina->maquina,
            'horometroSugerido' => $horometroSugerido,
        ]);
    }

    public function store(Request $request, Obra $obra, ObraMaquina $obraMaquina, MaquinaHorometroService $horometroService)
    {
        if ((int) $obraMaquina->obra_id !== (int) $obra->id) {
            abort(404);
        }

        $data = $request->validate([
            'horometro_fin' => 'required|numeric|min:0',
            'inicio' => 'nullable|date',
            'fin' => 'nullable|date|after_or_equal:inicio',
            'notas' => 'nullable|string|max:500',
        ]);

        try {
            $horometroService->crearRegistro($obraMaquina, $data, $request->user(), 'web_obras');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'horas-maquina'])
            ->with('success', 'Horas registradas correctamente.');
    }
}
