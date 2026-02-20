<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AsistenciasController;
use App\Http\Controllers\Api\V1\VehiculoKmController;
use App\Http\Controllers\Api\V1\ComisionController;
use App\Http\Controllers\Api\V1\MaquinaRegistroController;
use App\Http\Controllers\Api\V1\Gerencial\ObrasGerencialController;
use App\Http\Controllers\Api\V1\Gerencial\MaquinasGerencialController;
use App\Http\Controllers\Api\V1\Gerencial\PersonalGerencialController;
use App\Http\Controllers\Api\V1\Gerencial\InventarioGerencialController;
use App\Http\Controllers\Api\V1\Gerencial\DashboardGerencialController;
use App\Http\Controllers\Api\V1\Gerencial\InventarioKardexGerencialController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::prefix('v1')->group(function () {

    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('me', [AuthController::class, 'me']);

        Route::get('maquinas/{obraMaquina}/registros', [MaquinaRegistroController::class, 'index']);
        Route::post('maquinas/{obraMaquina}/registros', [MaquinaRegistroController::class, 'store']);

        //checadas asistencias en obra
        Route::post('obras/{obra}/asistencias',[AsistenciasController::class, 'store']);

        Route::get('obras/{obra}/asistencias', [AsistenciasController::class, 'show']); //trae las asistencias de la obra
        Route::get('obras/{obra}/empleados/{empleado}/asistencias', [AsistenciasController::class, 'showEmpleado']);

        //ruta para eliminar una asistencia
        Route::delete('obras/{obra}/asistencias/{asistencia}', [AsistenciasController::class, 'destroy']);

        
        Route::get('vehiculos/km-log', [VehiculoKmController::class, 'index']);
        Route::post('vehiculos/km-log', [VehiculoKmController::class, 'store']);
    
        Route::post('obras/{obra}/comisiones', [ComisionController::class, 'store']);

        //acceso gerencial
          Route::prefix('gerencial')->middleware('permission:app.gerencial.access')->group(function () {

                Route::get('dashboard', [DashboardGerencialController::class, 'index']);

                Route::get('obras', [ObrasGerencialController::class, 'index']); // cabecera paginada
                Route::get('obras/{obra}', [ObrasGerencialController::class, 'show']);
                //catalogo de maquinas
                Route::get('maquinas', [MaquinasGerencialController::class, 'index']);
                Route::get('maquinas/{maquina}', [MaquinasGerencialController::class, 'show']);
                Route::get('maquinas/{maquina}/registros', [MaquinasGerencialController::class, 'registros']);
                Route::get('maquinas/{maquina}/registros/resumen', [MaquinasGerencialController::class, 'registrosResumen']);
                
                //catalogo de empleados
                Route::get('empleados', [PersonalGerencialController::class, 'index']);
                Route::get('empleados/{empleado}', [PersonalGerencialController::class, 'show']); 
                //catalogo de productos
                Route::get('inventario/stock', [InventarioGerencialController::class, 'stock']);
                Route::get('inventario/stock/resumen', [InventarioGerencialController::class, 'resumen']);
                
                Route::get('inventario/productos/{producto}/kardex', [InventarioKardexGerencialController::class, 'producto'])->name('api.gerencial.inventario.kardex.producto');
                Route::get('inventario/productos/{producto}/kardex/resumen', [InventarioKardexGerencialController::class, 'resumenProducto']);
            });

    });
});
