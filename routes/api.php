<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AsistenciasController;
use App\Http\Controllers\Api\V1\VehiculoKmController;
use App\Http\Controllers\Api\V1\ComisionController;
use App\Http\Controllers\Api\V1\MaquinaRegistroController;
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

    });
});
