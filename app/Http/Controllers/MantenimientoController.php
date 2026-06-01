<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use App\Models\Maquina;
use App\Models\Empleado;
use Illuminate\Http\Request;

class MantenimientoController extends Controller
{
    /**
     * Listado
     */
    public function index()
    {
        $mantenimientos = Mantenimiento::with(['vehiculo', 'maquina', 'mecanico'])
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
        $maquinas = Maquina::orderBy('nombre')->get();
        $vehiculoIdFromUrl = $request->query('vehiculo_id');
        $maquinaIdFromUrl = $request->query('maquina_id');

        // $mecanicos = Empleado::where('Puesto','=','MECANICO')->get(); // Ajusta al nombre real de la columna
        $mecanicos = Empleado::whereRaw('LOWER(puesto) = ?', ['mecanico'])->get();
        return view('mantenimiento.create', compact('vehiculos', 'maquinas', 'mecanicos','vehiculoIdFromUrl', 'maquinaIdFromUrl'));
    }

    /**
     * Guardar mantenimiento
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehiculo_id'             => 'nullable|required_without:maquina_id|exists:vehiculos,id',
            'maquina_id'              => 'nullable|required_without:vehiculo_id|exists:maquinas,id',
            'obra_id'                 => 'nullable|exists:obras,id',
            'tipo'                    => 'required|in:programado,emergencia',
            'categoria_mantenimiento' => 'nullable|string|max:100',
            'descripcion'             => 'nullable|string',
            'km_actuales'             => 'nullable|integer',
            'km_proximo_servicio'     => 'nullable|integer',
            'horometro'               => 'nullable|numeric|min:0',
            'mecanico_id'             => 'nullable|integer|exists:empleados,id_Empleado',
            'fecha_programada'        => 'nullable|date',
            'estatus' => 'nullable|in:pendiente,en_proceso,completado,cancelado',

        ]);

        if (!empty($validated['vehiculo_id']) && !empty($validated['maquina_id'])) {
            return back()
                ->withErrors(['activo' => 'Selecciona solo un vehiculo o una maquina, no ambos.'])
                ->withInput();
        }

        $mantenimiento = Mantenimiento::create($validated);

        if ($mantenimiento->maquina_id) {
            return redirect()
                ->route('maquinas.show', ['maquina' => $mantenimiento->maquina_id, 'tab' => 'servicios'])
                ->with('success', 'Mantenimiento programado correctamente.');
        }

        if ($mantenimiento->vehiculo_id) {
            return redirect()
                ->route('mantenimiento.vehiculos.edit', ['vehiculo' => $mantenimiento->vehiculo_id, 'tab' => 'mantenimientos'])
                ->with('success', 'Mantenimiento programado correctamente.');
        }

        // return redirect()->route('mantenimiento.index')
        return redirect()->route('mantenimiento.mantenimientos.index')
            ->with('success', 'Mantenimiento registrado correctamente.');
    }

    /**
     * Mostrar detalle
     */
    public function show(Mantenimiento $mantenimiento)
    {
        $mantenimiento->load(['vehiculo', 'maquina', 'mecanico', 'detalles', 'fotos']);
        return view('mantenimiento.show', compact('mantenimiento'));
    }

    /**
     * Formulario edición
     */
    public function edit(Mantenimiento $mantenimiento)
    {
        $vehiculos = Vehiculo::orderBy('marca')->get();
        $maquinas = Maquina::orderBy('nombre')->get();
        // $mecanicos = Empleado::orderBy('nombre')->get();
        $mecanicos = Empleado::whereRaw('LOWER(puesto) = ?', ['mecanico'])->get();


        return view('mantenimiento.edit', compact(
            'mantenimiento',
            'vehiculos',
            'maquinas',
            'mecanicos'
        ));
    }

    /**
     * Actualizar mantenimiento
     */
    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        $validated = $request->validate([
            'vehiculo_id'           => 'nullable|required_without:maquina_id|exists:vehiculos,id',
            'maquina_id'            => 'nullable|required_without:vehiculo_id|exists:maquinas,id',
            'obra_id'               => 'nullable|exists:obras,id',
            'tipo'                  => 'required|in:programado,emergencia',
            'categoria_mantenimiento' => 'nullable|string|max:100',
            'descripcion'           => 'nullable|string',
            'km_actuales'             => 'nullable|integer',
            'km_proximo_servicio'     => 'nullable|integer',
            'horometro'               => 'nullable|numeric|min:0',
            'mecanico_id'           => 'nullable|integer|exists:empleados,id_Empleado',
            'fecha_programada'      => 'nullable|date',
            'estatus' => 'required|in:pendiente,en_proceso,completado,cancelado',

        ]);

        if (!empty($validated['vehiculo_id']) && !empty($validated['maquina_id'])) {
            return back()
                ->withErrors(['activo' => 'Selecciona solo un vehiculo o una maquina, no ambos.'])
                ->withInput();
        }

        $mantenimiento->update($validated);

        if ($mantenimiento->maquina_id) {
            return redirect()
                ->route('maquinas.show', ['maquina' => $mantenimiento->maquina_id, 'tab' => 'servicios'])
                ->with('success', 'Mantenimiento actualizado correctamente.');
        }

        if ($mantenimiento->vehiculo_id) {
            return redirect()
                ->route('mantenimiento.vehiculos.edit', ['vehiculo' => $mantenimiento->vehiculo_id, 'tab' => 'mantenimientos'])
                ->with('success', 'Mantenimiento actualizado correctamente.');
        }

        return redirect()->route('mantenimiento.mantenimientos.index')
            ->with('success', 'Mantenimiento actualizado correctamente.');
    }
}
