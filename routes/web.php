<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ObraController;
use App\Http\Controllers\ObraContratoController;
use App\Http\Controllers\ObraPlanoController;
use App\Http\Controllers\ObraPresupuestoController;
use App\Http\Controllers\ObraEmpleadoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EmpleadoNotaController;
use App\Http\Controllers\EmpleadoContactoEmergenciaController;
use App\Http\Controllers\NominaGeneradorController;
use App\Http\Controllers\ComisionController;
use App\Http\Controllers\ComisionPilaController;
use App\Http\Controllers\ObraMaquinaController;
use App\Http\Controllers\CatalogoPilaController;
use App\Http\Controllers\ObraPilaController;
use App\Http\Controllers\ObraFacturaController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\OrdenCompraDetalleController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProductoController;

use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\EmpresaSecurityController;

use App\Http\Controllers\EmpresaConfigController;
use App\Http\Controllers\ObraMaquinaHorasController;
use App\Http\Controllers\EmpresaConfigMaquinaController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\MaquinasReporteDiarioController;
use App\Http\Controllers\SnapshotsController;
use App\Http\Controllers\Inventario\InventarioStockController;


use App\Http\Controllers\Inventario\InventarioDocumentoController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    // return view('welcome');
      return redirect()->route('login');
});


Route::prefix('inventario')->group(function () {
    Route::get('stock', [InventarioStockController::class, 'index'])
        ->name('inventario.stock.index.temp');
});
Route::middleware(['auth', 'verified'])
    ->prefix('usuarios')
    ->name('usuarios.')
    ->group(function () {

        Route::get('/', [UsuarioController::class, 'index'])->name('index');
        Route::get('/create', [UsuarioController::class, 'create'])->name('create');

        // 🔎 buscador empleados legacy (JSON)
        Route::get('/empleados/search', [UsuarioController::class, 'searchEmpleados'])->name('empleados.search');

        Route::post('/', [UsuarioController::class, 'store'])->name('store');
        Route::get('/{usuario}/edit', [UsuarioController::class, 'edit'])->name('edit');
        Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update');
    });

Route::middleware('auth','verified')->group(function () {

    Route::prefix('inventario')->group(function () {

    // 🔹 STOCK
    Route::get('stock', [InventarioStockController::class, 'view'])->name('inventario.stock.index');

    Route::get('stock.json', [InventarioStockController::class, 'index'])->name('inventario.stock.index.json');

    // 🔹 DOCUMENTOS
    Route::get('documentos', [InventarioDocumentoController::class, 'index'])->name('inventario.documentos.index');

    Route::post('documentos', [InventarioDocumentoController::class, 'store']);

    Route::get('documentos/{doc}', [InventarioDocumentoController::class, 'show'])->name('inventario.documentos.show');

    Route::post('documentos/{doc}/aplicar', [InventarioDocumentoController::class, 'aplicar'])->name('inventario.documentos.aplicar');

    Route::post('documentos/{doc}/cancelar', [InventarioDocumentoController::class, 'cancelar'])->name('inventario.documentos.cancelar');
});


    Route::get('/configuracion-empresa', [EmpresaConfigController::class, 'edit'])
    ->name('empresa_config.edit');

    Route::put('/configuracion-empresa', [EmpresaConfigController::class, 'update'])
        ->name('empresa_config.update');

           Route::middleware(['role:admin|super-admin'])->prefix('configuracion-empresa')->name('empresa_config.')->group(function () {

        // Roles
        Route::post('/roles', [EmpresaSecurityController::class, 'roleStore'])->name('roles.store');
        Route::put('/roles/{role}', [EmpresaSecurityController::class, 'roleUpdate'])->name('roles.update');
        Route::delete('/roles/{role}', [EmpresaSecurityController::class, 'roleDestroy'])->name('roles.destroy');

        // Asignar permisos a un rol
        Route::put('/roles/{role}/permisos', [EmpresaSecurityController::class, 'roleSyncPermissions'])->name('roles.permissions.sync');

        // Permisos
        Route::post('/permisos', [EmpresaSecurityController::class, 'permissionStore'])->name('permissions.store');
        Route::put('/permisos/{permission}', [EmpresaSecurityController::class, 'permissionUpdate'])->name('permissions.update');
        Route::delete('/permisos/{permission}', [EmpresaSecurityController::class, 'permissionDestroy'])->name('permissions.destroy');
    });

    Route::get('/configuracion-empresa/maquinas/create', [EmpresaConfigMaquinaController::class, 'create'])
        ->name('empresa_config.maquinas.create');

    Route::post('/empresa-config/maquinas', [EmpresaConfigMaquinaController::class, 'store'])
        ->name('empresa_config.maquinas.store');

    Route::put('/empresa-config/maquinas/{maquina}', [EmpresaConfigMaquinaController::class, 'update'])
        ->name('empresa_config.maquinas.update');

    Route::get('/configuracion-empresa/maquinas/{maquina}/edit', [EmpresaConfigMaquinaController::class, 'edit'])
    ->name('empresa_config.maquinas.edit');

    
    Route::get('/reportes/maquinaria/diario', [MaquinasReporteDiarioController::class, 'index'])
        ->name('reportes.maquinaria.reporte_diario');

        //pra los snapshots
    Route::get('/reportes/maquinaria/snapshots', [MaquinasReporteDiarioController::class, 'snapshotsIndex'])
        ->name('reportes.maquinaria.snapshots.index');


    Route::get('/reportes/maquinaria/historial', [MaquinasReporteDiarioController::class, 'historial'])
        ->name('reportes.maquinaria.historial');

    Route::patch('/reportes/maquinaria/historial/observaciones', [MaquinasReporteDiarioController::class, 'updateObservacionSnapshot'])
    ->name('reportes.maquinaria.historial.observaciones.update');



    // Route::get('/maquinas/reporte-diario', [MaquinasReporteDiarioController::class, 'index'])
    // ->name('maquinas.reporte_diario.index');

    // Route::post('/maquinas/reporte-diario/guardar', [MaquinasReporteDiarioController::class, 'store'])
    // ->name('maquinas.reporte_diario.store');

    Route::get('/reportes', [ReportesController::class, 'index'])
        ->name('reportes.index');
   //para guardar el reporte diario de obra y que quede configurable

  Route::post('snapshots', [MaquinasReporteDiarioController::class, 'storeSnapshot'])
    ->name('snapshots.store');

    Route::delete('/empresa-config/maquinas/{maquina}', [EmpresaConfigMaquinaController::class, 'destroy'])
        ->name('empresa_config.maquinas.destroy');

    Route::post('reportes/maquinaria/snapshots', [MaquinasReporteDiarioController::class, 'storeSnapshot'])
    ->name('reportes.maquinaria.snapshots.store');


    // Route::get('empresa-config', [EmpresaConfigController::class, 'index'])->name('empresa_config.index');
    // Route::put('empresa-config/{section}', [EmpresaConfigController::class, 'update'])->name('empresa_config.update');



    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('clientes', ClienteController::class)->except(['show']);
    Route::resource('obras', ObraController::class)->except(['show']);

    Route::resource('obras', ObraController::class)->except(['show']);

    // Contratos de obra (solo store y destroy por ahora)
    Route::post('obras/{obra}/contratos', [ObraContratoController::class, 'store'])
        ->name('obras.contratos.store');

    Route::delete('obras/{obra}/contratos/{contrato}', [ObraContratoController::class, 'destroy'])
        ->name('obras.contratos.destroy');

    Route::post('obras/{obra}/planos', [ObraPlanoController::class, 'store'])
    ->name('obras.planos.store');

    Route::delete('obras/{obra}/planos/{plano}', [ObraPlanoController::class, 'destroy'])
    ->name('obras.planos.destroy');
    Route::post('obras/{obra}/presupuestos', [ObraPresupuestoController::class, 'store'])
    ->name('obras.presupuestos.store');

    Route::delete('obras/{obra}/presupuestos/{presupuesto}', [ObraPresupuestoController::class, 'destroy'])
        ->name('obras.presupuestos.destroy');
    Route::post('obras/{obra}/empleados', [ObraEmpleadoController::class, 'store'])
    ->name('obras.empleados.store');

    Route::patch('obras/{obra}/empleados/{asignacion}/baja', [ObraEmpleadoController::class, 'baja'])
    ->name('obras.empleados.baja');
    
    Route::post('obras/{obra}/facturas', [ObraFacturaController::class, 'store'])
        ->name('obras.facturas.store');

    Route::delete('obras/{obra}/facturas/{factura}', [ObraFacturaController::class, 'destroy'])
        ->name('obras.facturas.destroy');

          // NUEVAS: cambio de estado
    Route::patch('obras/{obra}/facturas/{factura}/pagada', [ObraFacturaController::class, 'marcarPagada'])
        ->name('obras.facturas.pagada');

    Route::patch('obras/{obra}/facturas/{factura}/cancelada', [ObraFacturaController::class, 'marcarCancelada'])
        ->name('obras.facturas.cancelada');

    Route::get('obras/{obra}/maquinaria/{obraMaquina}/horas/create',[ObraMaquinaHorasController::class,'create'])->name('obras.horas_maquina.create');
    Route::post('obras/{obra}/maquinaria/{obraMaquina}/horas',[ObraMaquinaHorasController::class, 'store'])->name('obras.horas_maquina.store');
        
    Route::resource('ordenes_compra', OrdenCompraController::class)->except(['show','destroy']);

    Route::post('ordenes_compra/{id}/autorizar', [OrdenCompraController::class, 'autorizar'])
            // ->middleware('permission:ordenes_compra.autorizar')
            ->name('ordenes_compra.autorizar');

    Route::post('ordenes_compra/{id}/cancelar', [OrdenCompraController::class, 'cancelar'])
            ->name('ordenes_compra.cancelar');
                    // Detalles anidados
    Route::post('ordenes_compra/{orden}/detalles', [OrdenCompraDetalleController::class, 'store'])
            ->name('ordenes_compra.detalles.store');

    Route::put('ordenes_compra/{orden}/detalles/{detalle}', [OrdenCompraDetalleController::class, 'update'])
            ->name('ordenes_compra.detalles.update');

    Route::delete('ordenes_compra/{orden}/detalles/{detalle}', [OrdenCompraDetalleController::class, 'destroy'])
            ->name('ordenes_compra.detalles.destroy');
    Route::get('ordenes_compra/{orden_compra}/print', [OrdenCompraController::class, 'print'])
        ->name('ordenes_compra.print');
    
     

    Route::get('proveedores/buscar', [ProveedorController::class, 'buscar'])
    ->name('proveedores.buscar');

    Route::resource('proveedores', ProveedorController::class)
    ->parameters(['proveedores' => 'proveedor'])
    ->except(['destroy']);



    Route::post('proveedores/{proveedor}/toggle-activo', [ProveedorController::class, 'toggleActivo'])
    ->name('proveedores.toggleActivo');

//productos
    Route::get('productos/buscar', [ProductoController::class, 'buscar'])
    ->name('productos.buscar');

    Route::post('productos/{producto}/toggle-activo', [ProductoController::class, 'toggleActivo'])->name('productos.toggleActivo');

    Route::resource('productos', ProductoController::class)->except(['destroy', 'show']);

    Route::post('productos/{producto}/proveedores', [ProductoController::class, 'proveedoresAttach'])
    ->name('productos.proveedores.attach');

    Route::put('productos/{producto}/proveedores/{proveedor}', [ProductoController::class, 'proveedoresUpdate'])
        ->name('productos.proveedores.update');

    Route::delete('productos/{producto}/proveedores/{proveedor}', [ProductoController::class, 'proveedoresDetach'])
        ->name('productos.proveedores.detach');
//mantenimiento y vehiculos
    Route::prefix('mantenimiento')->name('mantenimiento.')->group(function () {

        // Catálogo de vehículos
        Route::resource('vehiculos', VehiculoController::class)
            ->except(['destroy']);
              // Asignar vehículo a empleado
        Route::post('vehiculos/{vehiculo}/asignar', [VehiculoController::class, 'asignar'])
        ->name('vehiculos.asignar');
        
        Route::post('vehiculos/{vehiculo}/seguro', [VehiculoController::class, 'guardarSeguro'])
        ->name('vehiculos.seguro.store');

        // Mantenimientos de vehículos
        Route::resource('mantenimientos', MantenimientoController::class)
            ->except(['destroy']);


    });
    Route::prefix('obras/{obra}')
        ->name('obras.')
        ->group(function () {
            // Lista de comisiones de una obra (TAB Comisiones)
            Route::get('comisiones', [ComisionController::class, 'index'])
                ->name('comisiones.index');

            // Formulario para crear nueva comisión
            Route::get('comisiones/create', [ComisionController::class, 'create'])
                ->name('comisiones.create');

            // Guardar nueva comisión
            Route::post('comisiones', [ComisionController::class, 'store'])
                ->name('comisiones.store');

            // Ver detalle de una comisión (y desde aquí imprimir)
            Route::get('comisiones/{comision}', [ComisionController::class, 'show'])
                ->name('comisiones.show');

            // Editar comisión
            Route::get('comisiones/{comision}/edit', [ComisionController::class, 'edit'])
                ->name('comisiones.edit');

            // Actualizar comisión
            Route::put('comisiones/{comision}', [ComisionController::class, 'update'])
                ->name('comisiones.update');

            // Eliminar comisión
            Route::delete('comisiones/{comision}', [ComisionController::class, 'destroy'])
                ->name('comisiones.destroy');

            // Vista para imprimir el formato (opcional, pero muy útil)
            Route::get('comisiones/{comision}/imprimir', [ComisionController::class, 'print'])
                ->name('comisiones.print');

                   // Asignar máquina a la obra
            Route::post('maquinaria', [ObraMaquinaController::class, 'store'])
                ->name('maquinaria.store');

            // Dar de baja una máquina de la obra
            Route::patch('maquinaria/{asignacion}/baja', [ObraMaquinaController::class, 'baja'])
                ->name('maquinaria.baja');
        });
    // Rutas para gestión de empleados

     Route::resource('empleados', EmpleadoController::class)->except(['destroy']);

    // Ruta para dar de baja / alta (cambiar estatus)
    Route::patch('empleados/{empleado}/toggle-status', [EmpleadoController::class, 'toggleStatus'])
        ->name('empleados.toggle-status');

    Route::post('empleados/{empleado}/notas', [EmpleadoNotaController::class, 'store'])
    ->name('empleados.notas.store');

    Route::delete('empleados/{empleado}/notas/{nota}', [EmpleadoNotaController::class, 'destroy'])
    ->name('empleados.notas.destroy');

    Route::post('empleados/{empleado}/contactos', [EmpleadoContactoEmergenciaController::class, 'store'])
    ->name('empleados.contactos.store');

    Route::put('empleados/{empleado}/contactos/{contacto}', [EmpleadoContactoEmergenciaController::class, 'update'])
        ->name('empleados.contactos.update');

    Route::delete('empleados/{empleado}/contactos/{contacto}', [EmpleadoContactoEmergenciaController::class, 'destroy'])
        ->name('empleados.contactos.destroy');


    Route::get('nomina/generador', [NominaGeneradorController::class, 'index'])
        ->name('nomina.generador.index');
    Route::post('nomina/generador/{empleado}/guardar',[NominaGeneradorController::class, 'storeEmpleado'])
        ->name('nomina.generador.storeEmpleado');

    Route::get('catalogo-pilas', [CatalogoPilaController::class, 'index'])
        ->name('catalogo-pilas.index');

    Route::get('catalogo-pilas/create', [CatalogoPilaController::class, 'create'])
        ->name('catalogo-pilas.create');

    Route::post('catalogo-pilas', [CatalogoPilaController::class, 'store'])
        ->name('catalogo-pilas.store');

    Route::post('obras/{obra}/pilas', [ObraPilaController::class, 'store'])
        ->name('obras.pilas.store');

    Route::patch('obras/{obra}/pilas/{pila}/baja', [ObraPilaController::class, 'baja'])
        ->name('obras.pilas.baja');

});

require __DIR__.'/auth.php';
