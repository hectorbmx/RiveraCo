<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Obra;
use App\Models\Presupuesto;
use App\Models\ObraPlaneacionGasto;
use Illuminate\Support\Facades\DB;

class PlaneacionGastosController extends Controller
{
    public function sync(Request $request)
{
    // 1. Buscar el presupuesto que sync1 ya insertó
    $presupuesto = Presupuesto::where('codigo_proyecto', $request->codigo_proyecto)->first();
    
    if (!$presupuesto) {
        return response()->json(['error' => 'No existe presupuesto para este código. Sincroniza primero el presupuesto.'], 422);
    }

    try {
        return DB::transaction(function () use ($request, $presupuesto) {
            
            // 2. Limpiar planeación base previa (semana 0)
            ObraPlaneacionGasto::where('presupuesto_id', $presupuesto->id)
                ->where('numero_semana', 0)
                ->delete();

            // 3. Insertar conceptos de RELACIÓN GASTOS
            foreach ($request->costos as $item) {
                $cantidad = is_numeric($item['cantidad']) ? $item['cantidad'] : 0;
                $precio   = is_numeric($item['precio_unitario']) ? $item['precio_unitario'] : 0;
                $total    = is_numeric($item['total']) ? $item['total'] : ($cantidad * $precio);

                ObraPlaneacionGasto::create([
                    'obra_id'          => null  ,        // Se asigna después en Laravel
                    'presupuesto_id'   => $presupuesto->id,
                    'partida'          => $item['partida'] ?? 'GENERAL',
                    'concepto'         => $item['concepto'],
                    'unidad'           => $item['unidad'] ?? 'PZA',
                    'cantidad'         => $cantidad,
                    'precio_unitario'  => $precio,
                    'monto_programado' => $total,
                    'numero_semana'    => 0,
                ]);
            }

            return response()->json([
                'status'  => 'OK',
                'message' => 'Planeación base sincronizada bajo presupuesto #' . $presupuesto->id
            ], 201);
        });

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}