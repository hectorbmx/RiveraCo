<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use App\Services\Mobile\InventarioConsultaService;
use Illuminate\Http\Request;

class InventarioGerencialController extends Controller
{
    public function stock(Request $request, InventarioConsultaService $consulta)
    {
        return response()->json($consulta->stock($request));
    }

    public function resumen(Request $request, InventarioConsultaService $consulta)
    {
        return response()->json($consulta->resumen($request));
    }
}
