<?php

namespace App\Http\Controllers\Api\V1\Mecanico;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use App\Services\Mobile\Equipos\EquipoMecanicoPresenter;
use App\Services\Mobile\Equipos\VehiculosConsultaService;
use App\Services\Mobile\Equipos\VehiculosHistorialConsultaService;
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

    public function detalle(Request $request, Vehiculo $vehiculo, VehiculosConsultaService $consulta, EquipoMecanicoPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->detalleVehiculoAmpliado($consulta->show($request, $vehiculo)));
    }

    public function asignaciones(Request $request, Vehiculo $vehiculo, VehiculosHistorialConsultaService $consulta): JsonResponse
    {
        $payload = $consulta->asignaciones($request, $vehiculo);
        foreach ($payload['data'] as &$asignacion) {
            if ($asignacion['empleado']) {
                unset($asignacion['empleado']['celular'], $asignacion['empleado']['puesto']);
            }
        }
        unset($asignacion);

        return response()->json($payload);
    }

    public function servicios(Request $request, Vehiculo $vehiculo, VehiculosHistorialConsultaService $consulta): JsonResponse
    {
        return response()->json($consulta->servicios($request, $vehiculo));
    }

    public function seguros(Request $request, Vehiculo $vehiculo, VehiculosHistorialConsultaService $consulta): JsonResponse
    {
        return response()->json($consulta->seguros($request, $vehiculo));
    }

    public function combustible(Request $request, Vehiculo $vehiculo, VehiculosHistorialConsultaService $consulta): JsonResponse
    {
        return response()->json($consulta->combustible($request, $vehiculo));
    }
}
