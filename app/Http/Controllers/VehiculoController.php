<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\VehiculoEmpleado;
use App\Models\Empleado;
use App\Models\SeguroVehiculo;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;

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
        $polizaVigente   = null;
        $historialSeguros = collect();
        $mantenimientosVehiculo = collect();
        $statsMantenimientos = [
            'total'       => 0,
            'pendiente'   => 0,
            'en_proceso'  => 0,
            'completado'  => 0,
            'cancelado'   => 0,
        ];

        if ($tab === 'asignacion') {
            // Asignación actual (sin fecha_fin)
            $asignacionActual = $vehiculo->asignaciones()
                ->with('empleado')
                ->whereNull('fecha_fin')
                ->orderByDesc('fecha_asignacion')
                ->first();

            // Historial completo
            $historialAsignaciones = $vehiculo->asignaciones()
                ->with('empleado')
                ->orderByDesc('fecha_asignacion')
                ->get();

            // Empleados para asignar (por ahora todos; luego podemos filtrar)
            $empleadosAsignables = Empleado::where('Estatus','=','1')
                ->orderBy('Apellidos')
                ->get();
        }
        if ($tab === 'seguro') {
            // Póliza vigente: por estatus activa o por fecha_fin >= hoy
            $hoy = now()->toDateString();

            $polizaVigente = $vehiculo->seguros()
                ->where(function ($q) use ($hoy) {
                    $q->where('estatus', 'activa')
                    ->orWhere('fecha_fin', '>=', $hoy);
                })
                ->orderByDesc('fecha_fin')
                ->first();

            // Historial completo
            $historialSeguros = $vehiculo->seguros()
                ->orderByDesc('fecha_inicio')
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
            'statsMantenimientos'
        ));
    }

    //asignar seguros a un vehiculo
    public function guardarSeguro(Request $request, Vehiculo $vehiculo)
    {
        $validated = $request->validate([
            'aseguradora'     => 'required|string|max:100',
            'numero_poliza'   => 'required|string|max:100',
            'tipo_cobertura'  => 'nullable|string|max:100',
            'fecha_inicio'    => 'required|date',
            'fecha_fin'       => 'required|date|after_or_equal:fecha_inicio',
            'costo_anual'     => 'nullable|numeric|min:0',
            'estatus'         => 'required|in:activa,vencida,cancelada',
            'archivo_poliza'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'notas'           => 'nullable|string',
        ]);

        // Si se marca como activa, puedes marcar otras pólizas como vencidas
        if ($validated['estatus'] === 'activa') {
            $vehiculo->seguros()
                ->where('estatus', 'activa')
                ->update(['estatus' => 'vencida']);
        }

        // Manejo de archivo
        $rutaArchivo = null;
        if ($request->hasFile('archivo_poliza')) {
            $rutaArchivo = $request->file('archivo_poliza')
                ->store('polizas_vehiculos', 'public');
        }

        SeguroVehiculo::create([
            'vehiculo_id'    => $vehiculo->id,
            'aseguradora'    => $validated['aseguradora'],
            'numero_poliza'  => $validated['numero_poliza'],
            'tipo_cobertura' => $validated['tipo_cobertura'] ?? null,
            'fecha_inicio'   => $validated['fecha_inicio'],
            'fecha_fin'      => $validated['fecha_fin'],
            'costo_anual'    => $validated['costo_anual'] ?? 0,
            'estatus'        => $validated['estatus'],
            'archivo_poliza' => $rutaArchivo,
            'notas'          => $validated['notas'] ?? null,
        ]);

        return redirect()
            ->route('mantenimiento.vehiculos.edit', ['vehiculo' => $vehiculo->id, 'tab' => 'seguro'])
            ->with('success', 'Póliza de seguro registrada correctamente.');
    }


    public function asignar(Request $request, Vehiculo $vehiculo)
    {
        $validated = $request->validate([
            'empleado_id'      => 'required|integer|exists:empleados,id_Empleado',
            'fecha_asignacion' => 'nullable|date',
            'notas'            => 'nullable|string',
        ]);

        // Cerrar cualquier asignación activa previa de este vehículo
        $vehiculo->asignaciones()
            ->whereNull('fecha_fin')
            ->update([
                'fecha_fin' => now()->toDateString(),
            ]);

        // Crear nueva asignación
        VehiculoEmpleado::create([
            'vehiculo_id'      => $vehiculo->id,
            'empleado_id'      => $validated['empleado_id'],
            'fecha_asignacion' => $validated['fecha_asignacion'] ?? now()->toDateString(),
            'notas'            => $validated['notas'] ?? null,
        ]);

        return redirect()
            ->route('mantenimiento.vehiculos.edit', ['vehiculo' => $vehiculo->id, 'tab' => 'asignacion'])
            ->with('success', 'Vehículo asignado correctamente al empleado.');
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
