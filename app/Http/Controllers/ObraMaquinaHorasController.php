<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraMaquina;
use App\Models\ObraMaquinaRegistro;
use App\Services\Maquinas\MaquinaHorometroService;
use Illuminate\Support\Facades\DB;
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
            'fecha' => 'required|date',
            'notas' => 'nullable|string|max:500',
        ]);

        $data['inicio'] = $data['fecha'] . ' 00:00:00';
        $data['fin'] = $data['fecha'] . ' 23:59:59';

        try {
            $horometroService->crearRegistro($obraMaquina, $data, $request->user(), 'web_obras');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'horas-maquina'])
            ->with('success', 'Horas registradas correctamente.');
    }

    public function update(Request $request, Obra $obra, ObraMaquina $obraMaquina, ObraMaquinaRegistro $registro)
    {
        abort_unless($request->user()?->can('editar_horas_maquina.access'), 403);

        if ((int) $obraMaquina->obra_id !== (int) $obra->id
            || (int) $registro->obra_maquina_id !== (int) $obraMaquina->id) {
            abort(404);
        }

        $data = $request->validate([
            'horometro_fin' => ['required', 'numeric', 'min:0'],
            'fecha' => ['required', 'date'],
            'notas' => ['nullable', 'string', 'max:500'],
        ]);

        $ultimoRegistro = $obraMaquina->registrosHoras()
            ->orderByDesc('inicio')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (! $ultimoRegistro || (int) $ultimoRegistro->id !== (int) $registro->id) {
            return back()
                ->withErrors(['registro' => 'Solo se puede editar el último registro de la asignación.'])
                ->withInput();
        }

        $registroAnterior = $obraMaquina->registrosHoras()
            ->where('id', '!=', $registro->id)
            ->orderByDesc('horometro_fin')
            ->orderByDesc('id')
            ->first();

        $horometroAnterior = max([
            (float) ($registroAnterior?->horometro_fin ?? 0),
            (float) ($obraMaquina->horometro_inicio ?? 0),
            (float) ($obraMaquina->maquina?->horometro_base ?? 0),
        ]);
        $horometroNuevo = (float) $data['horometro_fin'];

        if ($horometroNuevo <= $horometroAnterior) {
            return back()
                ->withErrors([
                    'horometro_fin' => "El horómetro debe ser mayor al registro anterior ({$horometroAnterior}).",
                ])
                ->withInput();
        }

        $inicio = $data['fecha'] . ' 00:00:00';
        $fin = $data['fecha'] . ' 23:59:59';

        try {
            DB::transaction(function () use ($registro, $data, $inicio, $fin, $horometroAnterior, $horometroNuevo) {
                $registroBloqueado = ObraMaquinaRegistro::query()
                    ->lockForUpdate()
                    ->findOrFail($registro->id);

                $registroBloqueado->update([
                    'inicio' => $inicio,
                    'fin' => $fin,
                    'horometro_inicio' => $horometroAnterior,
                    'horometro_fin' => $horometroNuevo,
                    'horas' => round($horometroNuevo - $horometroAnterior, 2),
                    'notas' => $data['notas'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['registro' => $e->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'horas-maquina'])
            ->with('success', 'Registro de horas actualizado correctamente.');
    }

    public function destroy(Request $request, Obra $obra, ObraMaquina $obraMaquina, ObraMaquinaRegistro $registro)
    {
        abort_unless($request->user()?->can('borrar_horas_maquina.access'), 403);

        if ((int) $obraMaquina->obra_id !== (int) $obra->id
            || (int) $registro->obra_maquina_id !== (int) $obraMaquina->id) {
            abort(404);
        }

        $ultimoRegistro = $obraMaquina->registrosHoras()
            ->orderByDesc('inicio')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (! $ultimoRegistro || (int) $ultimoRegistro->id !== (int) $registro->id) {
            return back()->with('error', 'Solo se puede eliminar el último registro de la asignación.');
        }

        try {
            DB::transaction(function () use ($registro) {
                $registroBloqueado = ObraMaquinaRegistro::query()
                    ->lockForUpdate()
                    ->findOrFail($registro->id);

                $registroBloqueado->delete();
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo eliminar el registro de horas.');
        }

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'horas-maquina'])
            ->with('success', 'Registro de horas eliminado correctamente.');
    }
}
