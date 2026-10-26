<?php

namespace App\Http\Controllers\Api\V1\Mecanico;

use App\Http\Controllers\Controller;
use App\Services\Mobile\ServicioProgramadoCatalogoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogoController extends Controller
{
    public function index(Request $request, ServicioProgramadoCatalogoService $catalogos): JsonResponse
    {
        $data = $request->validate([
            'maquina_id' => ['nullable', 'integer', 'min:1', Rule::prohibitedIf($request->filled('vehiculo_id'))],
            'vehiculo_id' => ['nullable', 'integer', 'min:1', Rule::prohibitedIf($request->filled('maquina_id'))],
        ]);

        return response()->json($catalogos->obtener(
            isset($data['maquina_id']) ? (int) $data['maquina_id'] : null,
            isset($data['vehiculo_id']) ? (int) $data['vehiculo_id'] : null
        ));
    }
}
