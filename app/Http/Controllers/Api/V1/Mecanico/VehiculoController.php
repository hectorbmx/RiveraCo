<?php

namespace App\Http\Controllers\Api\V1\Mecanico;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use App\Services\Mobile\Equipos\EquipoMecanicoPresenter;
use App\Services\Mobile\Equipos\VehiculosConsultaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    public function index(Request $request, VehiculosConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->listado($consulta->index($request), 'vehiculo'));
    }

    public function show(Request $request, Vehiculo $vehiculo, VehiculosConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->detalle($consulta->show($request, $vehiculo), 'vehiculo'));
    }
}
