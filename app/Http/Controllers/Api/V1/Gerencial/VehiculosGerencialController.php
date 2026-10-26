<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use App\Services\Mobile\Equipos\VehiculosConsultaService;
use Illuminate\Http\Request;

class VehiculosGerencialController extends Controller
{
    public function index(Request $request, VehiculosConsultaService $consulta)
    {
        return response()->json($consulta->index($request));
    }

    public function show(Request $request, Vehiculo $vehiculo, VehiculosConsultaService $consulta)
    {
        return response()->json($consulta->show($request, $vehiculo));
    }
}
