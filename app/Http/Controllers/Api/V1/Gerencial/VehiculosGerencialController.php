<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use App\Services\Mobile\Equipos\VehiculosConsultaService;
use App\Services\Mobile\Equipos\VehiculosHistorialConsultaService;
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

    public function asignaciones(Request $request, Vehiculo $vehiculo, VehiculosHistorialConsultaService $consulta)
    {
        return response()->json($consulta->asignaciones($request, $vehiculo));
    }

    public function servicios(Request $request, Vehiculo $vehiculo, VehiculosHistorialConsultaService $consulta)
    {
        return response()->json($consulta->servicios($request, $vehiculo));
    }

    public function seguros(Request $request, Vehiculo $vehiculo, VehiculosHistorialConsultaService $consulta)
    {
        return response()->json($consulta->seguros($request, $vehiculo));
    }

    public function combustible(Request $request, Vehiculo $vehiculo, VehiculosHistorialConsultaService $consulta)
    {
        return response()->json($consulta->combustible($request, $vehiculo));
    }
}
