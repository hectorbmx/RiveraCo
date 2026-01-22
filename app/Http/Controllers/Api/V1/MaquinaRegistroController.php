<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ObraEmpleado;
use App\Models\ObraMaquina;
use App\Models\ObraMaquinaRegistro;
use App\Models\UsuarioApp;
use Illuminate\Http\Request;

class MaquinaRegistroController extends Controller
{
    /**
     * Lista registros y da “horómetro sugerido”
     * GET /api/v1/maquinas/{obraMaquina}/registros
     */
    public function index(Request $request, ObraMaquina $obraMaquina)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'No autenticado.'
            ], 401);
        }
        // 1) Verificar usuario app activo
        $usuarioApp = UsuarioApp::where('user_id', $user->id)->first();
        if (!$usuarioApp || !$usuarioApp->is_active) {
            return response()->json([
                'ok' => false,
                'message' => 'Acceso no habilitado para la app.'
            ], 403);
        }

        // 2) Verificar que esté activa
        if ($obraMaquina->estado !== 'activa') {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes registrar horas en una asignación finalizada.'
            ], 422);
        }

        // 3) Verificar que el residente esté asignado a la obra de esa máquina
        $estaAsignado = ObraEmpleado::query()
            ->where('obra_id', $obraMaquina->obra_id)
            ->where('empleado_id', $usuarioApp->empleado_id)
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->exists();

        if (!$estaAsignado) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes acceso a esta obra.'
            ], 403);
        }

        // 4) Horómetro sugerido: último fin o baseline de asignación
        $ultimo = $obraMaquina->registrosHoras()
            ->orderByDesc('fin')
            ->orderByDesc('id')
            ->first();

        $horometroSugerido = $ultimo?->horometro_fin ?? $obraMaquina->horometro_inicio ?? 0;

        // 5) Últimos registros (para mostrar historial)
        $registros = ObraMaquinaRegistro::query()
            ->where('obra_maquina_id', $obraMaquina->id)
            ->orderByDesc('fin')
            ->orderByDesc('id')
            ->limit(30)
            ->get([
                'id',
                'inicio',
                'fin',
                'horometro_inicio',
                'horometro_fin',
                'horas',
                'notas',
                'created_at',
            ]);

        return response()->json([
            'ok' => true,
            'asignacion' => [
                'obra_maquina_id'  => $obraMaquina->id,
                'obra_id'          => $obraMaquina->obra_id,
                'maquina_id'       => $obraMaquina->maquina_id,
                'estado'           => $obraMaquina->estado,
                'fecha_inicio'     => optional($obraMaquina->fecha_inicio)->toDateString(),
                'horometro_inicio' => $obraMaquina->horometro_inicio,
                'maquina'          => $obraMaquina->maquina ? [
                    'id'     => $obraMaquina->maquina->id ?? null,
                    'nombre' => $obraMaquina->maquina->nombre ?? null,
                ] : null,
            ],
            'horometro_sugerido' => (float) $horometroSugerido,
            'registros' => $registros,
        ]);
    }

    /**
     * Crea un registro de horas
     * POST /api/v1/maquinas/{obraMaquina}/registros
     */
    public function store(Request $request, ObraMaquina $obraMaquina)
    {
        $user = $request->user();

        // 1) Verificar usuario app activo
        $usuarioApp = UsuarioApp::where('user_id', $user->id)->first();
        if (!$usuarioApp || !$usuarioApp->is_active) {
            return response()->json([
                'ok' => false,
                'message' => 'Acceso no habilitado para la app.'
            ], 403);
        }

        // 2) Verificar activa
        if ($obraMaquina->estado !== 'activa') {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes registrar horas en una asignación finalizada.'
            ], 422);
        }

        // 3) Verificar acceso a obra
        $estaAsignado = ObraEmpleado::query()
            ->where('obra_id', $obraMaquina->obra_id)
            ->where('empleado_id', $usuarioApp->empleado_id)
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->exists();

        if (!$estaAsignado) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes acceso a esta obra.'
            ], 403);
        }

        $data = $request->validate([
            'horometro_fin' => 'required|numeric|min:0',
            'inicio'        => 'nullable|date',
            'fin'           => 'nullable|date|after_or_equal:inicio',
            'notas'         => 'nullable|string|max:500',
        ]);

        // Horómetro inicio sugerido: último registro o baseline
        $ultimo = $obraMaquina->registrosHoras()
            ->orderByDesc('fin')
            ->orderByDesc('id')
            ->first();

        $horometroInicio = (float) ($ultimo?->horometro_fin ?? $obraMaquina->horometro_inicio ?? 0);
        $horometroFin    = (float) $data['horometro_fin'];

        if ($horometroFin < $horometroInicio) {
            return response()->json([
                'ok' => false,
                'message' => "El horómetro final no puede ser menor al último registrado ({$horometroInicio}).",
                'horometro_inicio' => $horometroInicio
            ], 422);
        }

        $inicio = $data['inicio'] ?? now();
        $fin    = $data['fin'] ?? now();

        $registro = ObraMaquinaRegistro::create([
            'obra_maquina_id'  => $obraMaquina->id,
            'obra_id'          => $obraMaquina->obra_id,
            'maquina_id'       => $obraMaquina->maquina_id,
            'inicio'           => $inicio,
            'fin'              => $fin,
            'horometro_inicio' => $horometroInicio,
            'horometro_fin'    => $horometroFin,
            'horas'            => round(max(0, $horometroFin - $horometroInicio), 2),
            'notas'            => $data['notas'] ?? null,
            'created_by'       => $user->id,
            'updated_by'       => $user->id,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Horas registradas correctamente.',
            'registro' => $registro,
        ], 201);
    }
}
