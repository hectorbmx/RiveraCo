<?php

namespace App\Http\Controllers\Api\V1\Mecanico;

use App\Http\Controllers\Controller;
use App\Models\Maquina;
use App\Services\Mobile\Equipos\EquipoMecanicoPresenter;
use App\Services\Mobile\Equipos\MaquinasConsultaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaquinaController extends Controller
{
    public function index(Request $request, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->listado($consulta->index($request), 'maquina'));
    }

    public function show(Request $request, Maquina $maquina, MaquinasConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->detalle($consulta->show($request, $maquina), 'maquina'));
    }
}
