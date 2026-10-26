<?php

namespace App\Http\Controllers\Api\V1\Mecanico;

use App\Http\Controllers\Controller;
use App\Services\Mantenimiento\MantenimientoCatalogoService;
use App\Services\Mobile\InventarioConsultaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    public function productos(Request $request, MantenimientoCatalogoService $catalogos, InventarioConsultaService $consulta): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'almacen_id' => ['nullable', 'integer', 'min:1'],
            'con_existencia' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $almacen = $catalogos->almacenGiralda();
        if (! $almacen) {
            return response()->json(['ok' => false, 'message' => 'No hay un almacén Giralda activo configurado.'], 503);
        }
        if (isset($data['almacen_id']) && (int) $data['almacen_id'] !== (int) $almacen->id) {
            throw ValidationException::withMessages(['almacen_id' => 'Este acceso solo permite consultar el inventario de Giralda.']);
        }

        return response()->json($consulta->productos($request, $almacen));
    }
}
