<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use App\Models\Empleado;
use Illuminate\Http\Request;

class MantenimientoController extends Controller
{
    /**
     * Listado
     */
    public function index()
    {
        $mantenimientos = Mantenimiento::with(['vehiculo', 'mecanico'])
            ->orderBy('id', 'desc')
            ->paginate(20);

        // return view('mantenimientos.index', compact('mantenimientos'));
        return view('mantenimiento.index', compact('mantenimientos'));
    }

    /**
     * Formulario de creación
     */
    public function create(Request $request)
    {
        // $vehiculos = Vehiculo::orderBy('marca')->get();
        $vehiculos = Vehiculo::orderBy('marca')->orderBy('modelo')->get();
        $vehiculoIdFromUrl = $request->query('vehiculo_id');

        // $mecanicos = Empleado::where('Puesto','=','MECANICO')->get(); // Ajusta al nombre real de la columna
        $mecanicos = Empleado::whereRaw('LOWER(puesto) = ?', ['mecanico'])->get();
        return view('mantenimiento.create', compact('vehiculos', 'mecanicos','vehiculoIdFromUrl'));
    }

    /**
     * Guardar mantenimiento
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehiculo_id'             => 'required|exists:vehiculos,id',
            'obra_id'                 => 'nullable|exists:obras,id',
            'tipo'                    => 'required|in:programado,emergencia',
            'categoria_mantenimiento' => 'nullable|string|max:100',
            'descripcion'             => 'nullable|string',
            'km_actuales'             => 'nullable|integer',
            'km_proximo_servicio'     => 'nullable|integer',
            'mecanico_id'             => 'nullable|integer|exists:empleados,id_Empleado',
            'fecha_programada'        => 'nullable|date',
            'estatus'                 => 'nullable|string',
            'estatus' => 'nullable|in:pendiente,en_proceso,completado,cancelado',

        ]);

        Mantenimiento::create($validated);

        // return redirect()->route('mantenimiento.index')
        return redirect()->route('mantenimiento.mantenimientos.index')
            ->with('success', 'Mantenimiento registrado correctamente.');
    }

    /**
     * Mostrar detalle
     */
    public function show(Mantenimiento $mantenimiento)
    {
        $mantenimiento->load(['vehiculo', 'mecanico', 'detalles', 'fotos']);
        return view('mantenimiento.show', compact('mantenimiento'));
    }

    /**
     * Formulario edición
     */
    public function edit(Mantenimiento $mantenimiento)
    {
        $vehiculos = Vehiculo::orderBy('marca')->get();
        // $mecanicos = Empleado::orderBy('nombre')->get();
        $mecanicos = Empleado::whereRaw('LOWER(puesto) = ?', ['mecanico'])->get();


        return view('mantenimiento.edit', compact(
            'mantenimiento',
            'vehiculos',
            'mecanicos'
        ));
    }

    /**
     * Actualizar mantenimiento
     */
    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        $validated = $request->validate([
            'vehiculo_id'           => 'required|exists:vehiculos,id',
            'obra_id'               => 'nullable|exists:obras,id',
            'tipo'                  => 'required|in:programado,emergencia',
            'categoria_mantenimiento' => 'nullable|string|max:100',
            'descripcion'           => 'nullable|string',
            'km_actuales'             => 'nullable|integer',
            'km_proximo_servicio'     => 'nullable|integer',
            'mecanico_id'           => 'nullable|integer|exists:empleados,id_Empleado',
            'fecha_programada'      => 'nullable|date',
            'estatus' => 'required|in:pendiente,en_proceso,completado,cancelado',

        ]);

        $mantenimiento->update($validated);

        return redirect()->route('mantenimiento.mantenimientos.index')
            ->with('success', 'Mantenimiento actualizado correctamente.');
    }
}
