<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpresaServicioPreventivoTipo;
use App\Models\Mantenimiento;
use App\Models\Maquina;
use App\Models\Vehiculo;
use App\Services\Maquinas\MaquinaHorometroService;
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

        return view('mantenimiento.index', compact('mantenimientos'));
    }

    /**
     * Formulario de creación
     */
    public function create(Request $request, MaquinaHorometroService $horometroService)
    {
        $vehiculos = Vehiculo::orderBy('marca')->orderBy('modelo')->get();
        $maquinas = Maquina::orderBy('nombre')->get();
        $maquinas->each(function (Maquina $maquina) use ($horometroService) {
            $maquina->horometro_actual_sugerido = $horometroService->horometroSugeridoParaAsignacion($maquina);
        });

        $vehiculoIdFromUrl = $request->query('vehiculo_id');
        $maquinaIdFromUrl = $request->query('maquina_id');
        $mecanicos = Empleado::whereRaw('LOWER(puesto) = ?', ['mecanico'])->get();
        $serviciosPreventivosMaquinaria = $this->serviciosPreventivosPorAmbito(EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA);
        $serviciosPreventivosVehiculos = $this->serviciosPreventivosPorAmbito(EmpresaServicioPreventivoTipo::AMBITO_VEHICULO);

        return view('mantenimiento.create', compact(
            'vehiculos',
            'maquinas',
            'mecanicos',
            'vehiculoIdFromUrl',
            'maquinaIdFromUrl',
            'serviciosPreventivosMaquinaria',
            'serviciosPreventivosVehiculos'
        ));
    }

    /**
     * Guardar mantenimiento
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehiculo_id' => 'nullable|required_without:maquina_id|exists:vehiculos,id',
            'maquina_id' => 'nullable|required_without:vehiculo_id|exists:maquinas,id',
            'obra_id' => 'nullable|exists:obras,id',
            'tipo' => 'required|in:programado,emergencia',
            'categoria_mantenimiento' => 'nullable|string|max:100',
            'servicio_preventivo_tipo_id' => 'nullable|exists:empresa_servicio_preventivo_tipos,id',
            'descripcion' => 'nullable|string',
            'km_actuales' => 'nullable|integer',
            'km_proximo_servicio' => 'nullable|integer',
            'horometro' => 'nullable|numeric|min:0',
            'mecanico_id' => 'nullable|integer|exists:empleados,id_Empleado',
            'fecha_programada' => 'nullable|date',
            'estatus' => 'nullable|in:pendiente,en_proceso,completado,cancelado',
        ]);

        if (!empty($validated['vehiculo_id']) && !empty($validated['maquina_id'])) {
            return back()
                ->withErrors(['activo' => 'Selecciona solo un vehiculo o una maquina, no ambos.'])
                ->withInput();
        }

        if ($error = $this->validarServicioPreventivoTipo($validated)) {
            return back()->withErrors(['servicio_preventivo_tipo_id' => $error])->withInput();
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
        $mecanicos = Empleado::whereRaw('LOWER(puesto) = ?', ['mecanico'])->get();
        $serviciosPreventivosMaquinaria = $this->serviciosPreventivosPorAmbito(EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA);
        $serviciosPreventivosVehiculos = $this->serviciosPreventivosPorAmbito(EmpresaServicioPreventivoTipo::AMBITO_VEHICULO);

        return view('mantenimiento.edit', compact(
            'mantenimiento',
            'vehiculos',
            'maquinas',
            'mecanicos',
            'serviciosPreventivosMaquinaria',
            'serviciosPreventivosVehiculos'
        ));
    }

    /**
     * Actualizar mantenimiento
     */
    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        $validated = $request->validate([
            'vehiculo_id' => 'nullable|required_without:maquina_id|exists:vehiculos,id',
            'maquina_id' => 'nullable|required_without:vehiculo_id|exists:maquinas,id',
            'obra_id' => 'nullable|exists:obras,id',
            'tipo' => 'required|in:programado,emergencia',
            'categoria_mantenimiento' => 'nullable|string|max:100',
            'servicio_preventivo_tipo_id' => 'nullable|exists:empresa_servicio_preventivo_tipos,id',
            'descripcion' => 'nullable|string',
            'km_actuales' => 'nullable|integer',
            'km_proximo_servicio' => 'nullable|integer',
            'horometro' => 'nullable|numeric|min:0',
            'mecanico_id' => 'nullable|integer|exists:empleados,id_Empleado',
            'fecha_programada' => 'nullable|date',
            'estatus' => 'required|in:pendiente,en_proceso,completado,cancelado',
        ]);

        if (!empty($validated['vehiculo_id']) && !empty($validated['maquina_id'])) {
            return back()
                ->withErrors(['activo' => 'Selecciona solo un vehiculo o una maquina, no ambos.'])
                ->withInput();
        }

        if ($error = $this->validarServicioPreventivoTipo($validated)) {
            return back()->withErrors(['servicio_preventivo_tipo_id' => $error])->withInput();
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

    private function serviciosPreventivosPorAmbito(string $ambito)
    {
        return EmpresaServicioPreventivoTipo::query()
            ->where('ambito', $ambito)
            ->where('activo', true)
            ->ordenados()
            ->get();
    }

    private function validarServicioPreventivoTipo(array $data): ?string
    {
        $tipoId = $data['servicio_preventivo_tipo_id'] ?? null;

        if (!$tipoId) {
            return null;
        }

        $ambitoEsperado = !empty($data['maquina_id'])
            ? EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA
            : (!empty($data['vehiculo_id']) ? EmpresaServicioPreventivoTipo::AMBITO_VEHICULO : null);

        if (!$ambitoEsperado) {
            return 'Selecciona un vehiculo o una maquina antes de elegir el tipo de servicio.';
        }

        $existe = EmpresaServicioPreventivoTipo::query()
            ->whereKey($tipoId)
            ->where('ambito', $ambitoEsperado)
            ->where('activo', true)
            ->exists();

        return $existe
            ? null
            : 'El tipo de servicio seleccionado no corresponde al activo o no esta activo.';
    }
}