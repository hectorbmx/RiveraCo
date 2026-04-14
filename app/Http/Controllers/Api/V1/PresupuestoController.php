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
    // public function store(Request $request)
    // {
    //     // 1. Log para debugear (puedes ver esto en storage/logs/laravel.log)
    //     Log::info('Carga desde Excel:', $request->all());

    //     try {
    //         // 2. Validación básica
    //         $request->validate([
    //             'codigo_proyecto' => 'required',
    //             'nombre_cliente' => 'required'
    //         ]);

    //         // 3. Inserción o Actualización (Upsert)
    //         // Esto evita duplicados si presionas el botón varias veces
    //         DB::table('presupuestos')->updateOrInsert(
    //             ['codigo_proyecto' => $request->codigo_proyecto], // Condición
    //             [
    //                 'nombre_cliente' => $request->nombre_cliente,
    //                 'total_costo_directo' => $request->total_costo_directo ?? 0,
    //                 'total_presupuesto' => $request->total_presupuesto ?? 0,
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ]
    //         );

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Presupuesto ' . $request->codigo_proyecto . ' guardado en MySQL',
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage()
    //         ], 400);
    //     }
    // }
//     public function store(Request $request)
// {
//     // Log para ver qué está llegando realmente (revisa storage/logs/laravel.log)
//     Log::info('Datos crudos recibidos:', $request->all());

//     return response()->json([
//         'status' => 'success',
//         'message' => '¡Conexión exitosa! El servidor recibió algo.',
//         'data_recibida' => $request->all() 
//     ], 200);
// }
//ESTE SI FUNCINA PARA LA CABECERA, PERO LO VAMOS A DEJAR COMENTADO PARA PROBAR EL INSERT DE LOS DETALLES
    // public function store(Request $request)
    // {
    //     Log::info('Datos recibidos desde Excel:', $request->all());

    //     try {
    //         // updateOrCreate busca por el código de proyecto. 
    //         // Si existe, lo actualiza; si no, lo crea.
    //         $presupuesto = Presupuesto::updateOrCreate(
    //             ['codigo_proyecto' => $request->codigo_proyecto], // Criterio de búsqueda
    //             [
    //                 'nombre_cliente'       => $request->nombre_cliente,
    //                 'total_costo_directo'  => $request->total_costo_directo,
    //                 'total_presupuesto'    => $request->total_presupuesto,
    //                 'estatus'              => 'Sincronizado'
    //             ]
    //         );

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Presupuesto ' . $presupuesto->codigo_proyecto . ' guardado/actualizado.',
    //             'data' => $presupuesto
    //         ], 200);

    //     } catch (\Exception $e) {
    //         Log::error('Error al guardar presupuesto: ' . $e->getMessage());
            
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error interno en el servidor.'
    //         ], 500);
    //     }
    // }
//con esta funcion, se exportan los detalles antes de las cantidades de pila, al que sigue vamos a exportar las cantidades de pilas
//    public function store(Request $request)
// {
//     return DB::transaction(function () use ($request) {
//         // 1. Cabecera
//         $presupuesto = Presupuesto::updateOrCreate(
//             ['codigo_proyecto' => $request->codigo_proyecto],
//             [
//                 'nombre_cliente'      => $request->nombre_cliente,
//                 'total_costo_directo' => $request->total_costo_directo,
//                 'total_presupuesto'   => $request->total_presupuesto,
//                 'estatus'             => 'Sincronizado'
//             ]
//         );

//         $presupuesto->detalles()->delete();

//         // 2. Detalles con los nuevos campos
//         foreach ($request->detalles as $item) {
//             $presupuesto->detalles()->create([
//                 'partida'           => $item['partida'],
//                 'concepto'          => $item['concepto'],
//                 'unidad'            => $item['unidad'],
//                 'cantidad'          => $item['cantidad'],
//                 'precio_unitario'   => $item['precio_unitario'],
//                 'importe'           => $item['importe'],
//                 'importe_optimista' => $item['importe_optimista'], // Nuevo
//                 'importe_pesimista' => $item['importe_pesimista'], // Nuevo
//             ]);
//         }

//         return response()->json(['status' => 'success', 'message' => 'Sincronización completa']);
//     });
// }

//este funciona para detalles mas pilas individualmente correcto el que sigue es el final ya con los resumenes
// public function store(Request $request)
// {
//     return DB::transaction(function () use ($request) {
//         // 1. Cabecera (Sincronización de datos generales)
//         $presupuesto = Presupuesto::updateOrCreate(
//             ['codigo_proyecto' => $request->codigo_proyecto],
//             [
//                 'nombre_cliente'      => $request->nombre_cliente,
//                 'total_costo_directo' => $request->total_costo_directo,
//                 'total_presupuesto'   => $request->total_presupuesto,
//                 'estatus'             => 'Sincronizado'
//             ]
//         );

//         // 2. Detalles Generales (Limpieza y guardado)
//         $presupuesto->detalles()->delete();
//         foreach ($request->detalles ?? [] as $item) {
//             $presupuesto->detalles()->create([
//                 'partida'           => $item['partida'],
//                 'concepto'          => $item['concepto'],
//                 'unidad'            => $item['unidad'],
//                 'cantidad'          => $item['cantidad'],
//                 'precio_unitario'   => $item['precio_unitario'],
//                 'importe'           => $item['importe'],
//                 'importe_optimista' => $item['importe_optimista'],
//                 'importe_pesimista' => $item['importe_pesimista'],
//             ]);
//         }

//         // 3. NUEVO: Detalles de Pilas (Rango 174-195 del Excel)
//         // Limpiamos registros previos de pilas para este presupuesto específico
//         $presupuesto->pilas()->delete();

//         // Recorremos el nuevo array 'pilas' que vendrá en el JSON
//         foreach ($request->pilas ?? [] as $pila) {
//             $presupuesto->pilas()->create([
//                 'concepto'  => $pila['concepto'],
//                 'unidad'    => $pila['unidad'],
//                 'cantidad'  => $pila['cantidad'],
//                 'costo'     => $pila['costo'],
//                 'total'     => $pila['total'],
//                 'optimista' => $pila['optimista'], // Columna G
//                 'pesimista' => $pila['pesimista'], // Columna H
//             ]);
//         }

//         return response()->json([
//             'status' => 'success', 
//             'message' => 'Sincronización completa (Cabecera, Detalles y Pilas)'
//         ]);
//     });
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