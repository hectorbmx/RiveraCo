<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\VehiculoEmpleado;
use App\Models\VehiculoAsignacionFoto;
use App\Models\Empleado;
use App\Models\SeguroVehiculo;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class VehiculoController extends Controller
{
    /**
     * Mostrar listado de vehículos
     */
    // public function index()
    // {
    //     $vehiculos = Vehiculo::orderBy('id', 'desc')->paginate(20);

    //     $vehiculos = Vehiculo::with([
    //         'asignacionActual.empleado'
    //     ])->orderBy('id', 'desc')->get();

    //     // return view('vehiculos.index', compact('vehiculos'));
    //     return view ('vehiculos.index', compact('vehiculos'));
    // }
public function index()
{
    $vehiculos = Vehiculo::with([
        'asignacionActual.empleado'
    ])->orderBy('id', 'desc')->paginate(20);

    return view('vehiculos.index', compact('vehiculos'));
}

    /**
     * Formulario de creación
     */
    public function create()
    {
        return view('vehiculos.create');
    }

    /**
     * Guardar vehículo
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'marca'           => 'nullable|string|max:100',
            'modelo'          => 'nullable|string|max:100',
            'anio'            => 'nullable|integer',
            'color'           => 'nullable|string|max:50',
            'placas'          => 'required|string|max:20|unique:vehiculos',
            'serie'           => 'nullable|string|max:50|unique:vehiculos',
            'tipo'            => 'nullable|string|max:50',
            'estatus'         => 'required|string|in:activo,baja,en_taller',
        ]);

        Vehiculo::create($validated);

        return redirect()->route('mantenimiento.vehiculos.index')
            ->with('success', 'Vehículo registrado correctamente.');
    }

    /**
     * Mostrar un vehículo
     */
    public function show(Vehiculo $vehiculo)
    {
        return view('vehiculos.show', compact('vehiculo'));
    }

    /**
     * Formulario edición
     */
    public function edit(Request $request, Vehiculo $vehiculo)
{
    $tab = $request->query('tab', 'general');

    // Valores por defecto para no romper la vista
    $asignacionActual = null;
    $historialAsignaciones = collect();
    $empleadosAsignables = collect();
    $polizaVigente = null;
    $historialSeguros = collect();
    $mantenimientosVehiculo = collect();
    $kmSugeridoAsignacion = 0;

    $statsMantenimientos = [
        'total'       => 0,
        'pendiente'   => 0,
        'en_proceso'  => 0,
        'completado'  => 0,
        'cancelado'   => 0,
    ];

    if ($tab === 'asignacion') {
        // Asignación actual
        $asignacionActual = $vehiculo->asignaciones()
            ->with('empleado')
            ->whereNull('fecha_fin')
            ->orderByDesc('fecha_asignacion')
            ->first();

          

        // Historial completo
        $historialAsignaciones = $vehiculo->asignaciones()
            ->with('empleado','fotos')
            ->orderByDesc('fecha_asignacion')
            ->get();

        // Empleados asignables
        $empleadosAsignables = Empleado::where('Estatus', '=', '1')
            ->orderBy('Apellidos')
            ->get();

        // Km sugerido
        if ($asignacionActual) {
            $ultimoLog = DB::table('vehiculo_empleado_km_logs')
                ->where('vehiculo_empleado_id', $asignacionActual->id)
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->value('km');

            $kmSugeridoAsignacion = $ultimoLog
                ?? $asignacionActual->km_final
                ?? $asignacionActual->km_inicial
                ?? 0;
        } else {
            $ultimaAsignacion = $vehiculo->asignaciones()
                ->orderByDesc('fecha_asignacion')
                ->orderByDesc('id')
                ->first();

            $kmSugeridoAsignacion = $ultimaAsignacion->km_final
                ?? $ultimaAsignacion->km_inicial
                ?? 0;
        }
    }

    if ($tab === 'seguro') {
        $hoy = now()->toDateString();

        $polizaVigente = $vehiculo->seguros()
            ->where('estatus', '!=', 'cancelada')
            ->whereDate('vigencia_desde', '<=', $hoy)
            ->whereDate('vigencia_hasta', '>=', $hoy)
            ->orderByDesc('vigencia_hasta')
            ->first();

        $historialSeguros = $vehiculo->seguros()
            ->orderByDesc('vigencia_hasta')
            ->get();
    }

    if ($tab === 'mantenimientos') {
        $mantenimientosVehiculo = $vehiculo->mantenimientos()
            ->with(['mecanico', 'obra'])
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->get();

        $statsMantenimientos['total']      = $mantenimientosVehiculo->count();
        $statsMantenimientos['pendiente']  = $mantenimientosVehiculo->where('estatus', 'pendiente')->count();
        $statsMantenimientos['en_proceso'] = $mantenimientosVehiculo->where('estatus', 'en_proceso')->count();
        $statsMantenimientos['completado'] = $mantenimientosVehiculo->where('estatus', 'completado')->count();
        $statsMantenimientos['cancelado']  = $mantenimientosVehiculo->where('estatus', 'cancelado')->count();
    }

    return view('vehiculos.edit', compact(
        'vehiculo',
        'tab',
        'asignacionActual',
        'historialAsignaciones',
        'empleadosAsignables',
        'polizaVigente',
        'historialSeguros',
        'mantenimientosVehiculo',
        'statsMantenimientos',
        'kmSugeridoAsignacion'
    ));
}
    

// public function asignar(Request $request, Vehiculo $vehiculo)
// {
//     $validated = $request->validate([
//         'empleado_id'      => 'required|integer|exists:empleados,id_Empleado',
//         'fecha_asignacion' => 'nullable|date',
//         'km_inicial'       => 'nullable|integer|min:0',
//         'notas'            => 'nullable|string',
//     ]);

//     DB::transaction(function () use ($vehiculo, $validated) {
//         $fechaAsignacion = $validated['fecha_asignacion'] ?? now()->toDateString();

//         // Asignación activa previa
//         $asignacionActiva = $vehiculo->asignaciones()
//             ->whereNull('fecha_fin')
//             ->latest('fecha_asignacion')
//             ->first();

//         // Resolver km inicial
//         $kmInicial = $validated['km_inicial'] ?? null;

//         if ($kmInicial === null) {
//             $kmInicial = $this->resolverKmInicialAsignacion($vehiculo, $asignacionActiva);
//         }

//         // Fallback final duro
//         if ($kmInicial === null) {
//             $kmInicial = 0;
//         }

//         // Cerrar asignación previa si existe
//         if ($asignacionActiva) {
//             // Si no tiene km_final, intentar cerrarla con el km resuelto
//             if ($asignacionActiva->km_final === null) {
//                 $asignacionActiva->km_final = $kmInicial;
//             }

//             $asignacionActiva->fecha_fin = $fechaAsignacion;
//             $asignacionActiva->save();
//         }

//         // Crear nueva asignación
//         VehiculoEmpleado::create([
//             'vehiculo_id'      => $vehiculo->id,
//             'empleado_id'      => $validated['empleado_id'],
//             'fecha_asignacion' => $fechaAsignacion,
//             'km_inicial'       => $kmInicial,
//             'notas'            => $validated['notas'] ?? null,
//         ]);
//     });

//     return redirect()
//         ->route('mantenimiento.vehiculos.edit', [
//             'vehiculo' => $vehiculo->id,
//             'tab' => 'asignacion',
//         ])
//         ->with('success', 'Vehículo asignado correctamente al empleado.');
// }
//nuevo metodo asignar que incluye la logica para las fotos de asignacion
public function asignar(Request $request, Vehiculo $vehiculo)
{
    // dd($request->hasFile('fotos'), $request->files->all());

    $validated = $request->validate([
        'empleado_id'      => 'required|integer|exists:empleados,id_Empleado',
        'fecha_asignacion' => 'nullable|date',
        'km_inicial'       => 'nullable|integer|min:0',
        'notas'            => 'nullable|string',
        'fotos'            => 'nullable|array|max:6',
        'fotos.*'          => 'image|max:10240', // 10 MB por foto antes de comprimir
    ]);

    DB::transaction(function () use ($vehiculo, $validated, $request) {
        $fechaAsignacion = $validated['fecha_asignacion'] ?? now()->toDateString();

        $asignacionActiva = $vehiculo->asignaciones()
            ->whereNull('fecha_fin')
            ->latest('fecha_asignacion')
            ->first();

        $kmInicial = $validated['km_inicial'] ?? null;

        if ($kmInicial === null) {
            $kmInicial = $this->resolverKmInicialAsignacion($vehiculo, $asignacionActiva);
        }

        if ($kmInicial === null) {
            $kmInicial = 0;
        }

        if ($asignacionActiva) {
            if ($asignacionActiva->km_final === null) {
                $asignacionActiva->km_final = $kmInicial;
            }
            $asignacionActiva->fecha_fin = $fechaAsignacion;
            $asignacionActiva->save();
        }

        $nuevaAsignacion = VehiculoEmpleado::create([
            'vehiculo_id'      => $vehiculo->id,
            'empleado_id'      => $validated['empleado_id'],
            'fecha_asignacion' => $fechaAsignacion,
            'km_inicial'       => $kmInicial,
            'notas'            => $validated['notas'] ?? null,
        ]);

        // Procesar y guardar fotos
        if ($request->hasFile('fotos')) {
            $imageManager = ImageManager::gd();

            foreach ($request->file('fotos') as $orden => $archivo) {
                // Redimensionar a máx 1200px ancho, convertir a WebP calidad 75
                $imagen = $imageManager->read($archivo->getRealPath())
                    ->scaleDown(width: 1200)
                    ->toWebp(quality: 75);

                $nombreArchivo = 'asignaciones/'
                    . $nuevaAsignacion->id
                    . '_' . ($orden + 1)
                    . '_' . Str::random(6)
                    . '.webp';

                Storage::disk('public')->put($nombreArchivo, $imagen);

                VehiculoAsignacionFoto::create([
                    'vehiculo_empleado_id' => $nuevaAsignacion->id,
                    'url'                  => $nombreArchivo,
                    'orden'                => $orden,
                ]);
            }
        }
    });

    return redirect()
        ->route('mantenimiento.vehiculos.edit', [
            'vehiculo' => $vehiculo->id,
            'tab'      => 'asignacion',
        ])
        ->with('success', 'Vehículo asignado correctamente al empleado.');
}


/**
 * Obtiene el mejor km disponible para iniciar una nueva asignación.
 */
protected function resolverKmInicialAsignacion(Vehiculo $vehiculo, ?VehiculoEmpleado $asignacionActiva = null): ?int
{
    $ultimoLog = DB::table('vehiculo_empleado_km_logs as logs')
        ->join('vehiculo_empleado as ve', 've.id', '=', 'logs.vehiculo_empleado_id')
        ->where('ve.vehiculo_id', $vehiculo->id)
        ->orderByDesc('logs.fecha')
        ->orderByDesc('logs.id')
        ->value('logs.km');

    if ($ultimoLog !== null) {
        return (int) $ultimoLog;
    }

    if ($asignacionActiva && $asignacionActiva->km_final !== null) {
        return (int) $asignacionActiva->km_final;
    }

    if ($asignacionActiva && $asignacionActiva->km_inicial !== null) {
        return (int) $asignacionActiva->km_inicial;
    }

    $ultimaAsignacionConKmFinal = $vehiculo->asignaciones()
        ->whereNotNull('km_final')
        ->orderByDesc('fecha_fin')
        ->orderByDesc('id')
        ->first();

    if ($ultimaAsignacionConKmFinal) {
        return (int) $ultimaAsignacionConKmFinal->km_final;
    }

    $ultimaAsignacionConKmInicial = $vehiculo->asignaciones()
        ->whereNotNull('km_inicial')
        ->orderByDesc('fecha_asignacion')
        ->orderByDesc('id')
        ->first();

    if ($ultimaAsignacionConKmInicial) {
        return (int) $ultimaAsignacionConKmInicial->km_inicial;
    }

    return null;
}


    /**
     * Actualizar vehículo
     */
  public function update(Request $request, Vehiculo $vehiculo)
{
    $validated = $request->validate([
        'marca'   => 'nullable|string|max:100',
        'modelo'  => 'nullable|string|max:100',
        'anio'    => 'nullable|integer',
        'color'   => 'nullable|string|max:50',
        'placas'  => 'required|string|max:20|unique:vehiculos,placas,' . $vehiculo->id,
        'serie'   => 'nullable|string|max:50|unique:vehiculos,serie,' . $vehiculo->id,
        'tipo'    => 'nullable|string|max:50',
        'estatus' => 'required|string|in:activo,baja,en_taller',
    ]);

    $vehiculo->update($validated);

    // Tomar tab (viene del querystring o del hidden input del form)
    $tab = $request->input('tab', $request->query('tab', 'general'));

    return redirect()
        ->route('mantenimiento.vehiculos.edit', ['vehiculo' => $vehiculo->id, 'tab' => $tab])
        ->with('success', 'Vehículo actualizado correctamente.');
}

}
