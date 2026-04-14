<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Presupuesto;
use App\Models\PresupuestoDetalle;
use App\Models\PresupuestoPila;
use App\Models\PresupuestoResumen;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresupuestoController extends Controller
{

// // public function store(Request $request)
// // {
// //     return DB::transaction(function () use ($request) {
// //         // 1. CABECERA: Sincronización principal
// //         $presupuesto = Presupuesto::updateOrCreate(
// //             ['codigo_proyecto' => $request->codigo_proyecto],
// //             [
// //                 'nombre_cliente'      => $request->nombre_cliente,
// //                 'total_costo_directo' => $request->total_costo_directo,
// //                 'total_presupuesto'   => $request->total_presupuesto,
// //                 'estatus'             => 'Sincronizado'
// //             ]
// //         );

// //         // 2. DETALLES GENERALES (Primer bloque)
// //         $presupuesto->detalles()->delete();
// //         foreach ($request->detalles ?? [] as $item) {
// //             $presupuesto->detalles()->create([
// //                 'partida'           => $item['partida'],
// //                 'concepto'          => $item['concepto'],
// //                 'unidad'            => $item['unidad'],
// //                 'cantidad'          => $item['cantidad'],
// //                 'precio_unitario'   => $item['precio_unitario'],
// //                 'importe'           => $item['importe'],
// //                 'importe_optimista' => $item['importe_optimista'],
// //                 'importe_pesimista' => $item['importe_pesimista'],
// //             ]);
// //         }

// //         // 3. DETALLES DE PILAS (Segundo bloque)
// //         $presupuesto->pilas()->delete();
// //         foreach ($request->pilas ?? [] as $pila) {
// //             $presupuesto->pilas()->create([
// //                 'concepto'  => $pila['concepto'],
// //                 'unidad'    => $pila['unidad'],
// //                 'cantidad'  => $pila['cantidad'],
// //                 'costo'     => $pila['costo'],
// //                 'total'     => $pila['total'],
// //                 'optimista' => $pila['optimista'],
// //                 'pesimista' => $pila['pesimista'],
// //             ]);
// //         }

// //         // 4. NUEVO: RESUMEN DE VENTAS (Tercer bloque - Filas 196-219)
// //         $presupuesto->resumenes()->delete();
// //         foreach ($request->resumen ?? [] as $res) {
// //             $presupuesto->resumenes()->create([
// //                 'partida'         => $res['partida'],
// //                 'concepto'        => $res['concepto'],
// //                 'unidad'          => $res['unidad'],
// //                 'cantidad'        => $res['cantidad'],
// //                 'precio_unitario' => $res['precio_unitario'],
// //                 'importe'         => $res['importe'],
// //             ]);
// //         }

// //         return response()->json([
// //             'status' => 'success', 
// //             'message' => 'Sincronización total exitosa (Detalles, Pilas y Resumen)'
// //         ]);
// //     });
// }
public function store(Request $request)
{
    return DB::transaction(function () use ($request) {
        $presupuesto = Presupuesto::firstOrCreate(
            ['codigo_proyecto' => $request->codigo_proyecto],
            [
                'nombre_cliente'      => filled($request->nombre_cliente) ? trim($request->nombre_cliente) : 'SIN CLIENTE',
                'total_costo_directo' => $request->total_costo_directo ?? 0,
                'total_presupuesto'   => $request->total_presupuesto ?? 0,
                'estatus'             => 'Sincronizado',
            ]
        );

        $updateData = [
            'total_costo_directo' => $request->total_costo_directo ?? 0,
            'total_presupuesto'   => $request->total_presupuesto ?? 0,
            'estatus'             => 'Sincronizado',
        ];

        if (filled($request->nombre_cliente)) {
            $updateData['nombre_cliente'] = trim($request->nombre_cliente);
        }

        $presupuesto->update($updateData);

        $presupuesto->detalles()->delete();
        foreach ($request->detalles ?? [] as $item) {
            $presupuesto->detalles()->create([
                'partida'           => $item['partida'] ?? null,
                'concepto'          => $item['concepto'] ?? null,
                'unidad'            => $item['unidad'] ?? null,
                'cantidad'          => $item['cantidad'] ?? 0,
                'precio_unitario'   => $item['precio_unitario'] ?? 0,
                'importe'           => $item['importe'] ?? 0,
                'importe_optimista' => $item['importe_optimista'] ?? 0,
                'importe_pesimista' => $item['importe_pesimista'] ?? 0,
            ]);
        }

        $presupuesto->pilas()->delete();
        foreach ($request->pilas ?? [] as $pila) {
            $presupuesto->pilas()->create([
                'concepto'  => $pila['concepto'] ?? null,
                'unidad'    => $pila['unidad'] ?? null,
                'cantidad'  => $pila['cantidad'] ?? 0,
                'costo'     => $pila['costo'] ?? 0,
                'total'     => $pila['total'] ?? 0,
                'optimista' => $pila['optimista'] ?? 0,
                'pesimista' => $pila['pesimista'] ?? 0,
            ]);
        }

        $presupuesto->resumenes()->delete();
        foreach ($request->resumen ?? [] as $res) {
            $presupuesto->resumenes()->create([
                'partida'         => $res['partida'] ?? null,
                'concepto'        => $res['concepto'] ?? null,
                'unidad'          => $res['unidad'] ?? null,
                'cantidad'        => $res['cantidad'] ?? 0,
                'precio_unitario' => $res['precio_unitario'] ?? 0,
                'importe'         => $res['importe'] ?? 0,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Sincronización total exitosa (Detalles, Pilas y Resumen)',
        ]);
    });
}
}