<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\Empleado;
use App\Models\ObraEmpleado;
use Illuminate\Http\Request;

class ObraEmpleadoController extends Controller
{
    // Asignar empleado a una obra
    public function store(Request $request, Obra $obra)
    {
        $data = $request->validate([
            'empleado_id'    => ['required', 'exists:empleados,id_Empleado'],
            'rol_id'         => ['required', 'exists:catalogo_roles,id'],
            'puesto_en_obra' => ['nullable', 'string', 'max:100'],
            'sueldo_en_obra' => ['nullable', 'numeric'],
            'notas'          => ['nullable', 'string'],
            'fecha_alta'     => ['nullable', 'date'],
        ]);

        $nombreRol = \DB::table('catalogo_roles')->where('id', $data['rol_id'])->value('nombre');
        $empleado = Empleado::findOrFail($data['empleado_id']);
        $puestoEmpleado = mb_strtoupper(trim((string) $empleado->Puesto));
        $puestoBaseEmpleado = mb_strtoupper(trim((string) $empleado->puesto_base));
        $esResidente = str_contains($puestoEmpleado, 'RESIDENTE')
            || str_contains($puestoBaseEmpleado, 'RESIDENTE');

        $yaAsignadoEnEstaObra = ObraEmpleado::where('empleado_id', $data['empleado_id'])
            ->where('obra_id', $obra->id)
            ->where('activo', true)
            ->whereNull('fecha_baja')
            ->exists();

        if ($yaAsignadoEnEstaObra) {
            return back()
                ->withErrors(['empleado_id' => 'Este empleado ya tiene una asignación activa en esta obra.'])
                ->withInput();
        }

        // Regla: solo empleados con Puesto o puesto_base RESIDENTE pueden estar activos en mas de una obra viva.
        $yaAsignadoEnOtraObra = ObraEmpleado::where('empleado_id', $data['empleado_id'])
            ->where('obra_id', '!=', $obra->id)
            ->where('activo', true)
            ->whereNull('fecha_baja')
            ->whereHas('obra', function ($query) {
                $query->whereNotIn('estatus_nuevo', [
                    Obra::ESTATUS_TERMINADA,
                    Obra::ESTATUS_CANCELADA,
                ]);
            })
            ->exists();

        if ($yaAsignadoEnOtraObra && !$esResidente) {
            return back()
                ->withErrors(['empleado_id' => 'Este empleado ya tiene una asignación activa en otra obra.'])
                ->withInput();
        }
// Si no quieres depender del texto, puedes autollenar puesto_en_obra con el nombre del rol
        if (empty($data['puesto_en_obra'])) {
            $data['puesto_en_obra'] = $nombreRol;
        }
        $data['obra_id'] = $obra->id;
        $data['activo']  = true;
        $data['fecha_alta'] = $data['fecha_alta'] ?? now()->toDateString();

        ObraEmpleado::create($data);

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'empleados'])
            ->with('success', 'Empleado asignado correctamente a la obra.');
    }

    // Dar de baja al empleado en esta obra (no borramos historial)
    public function baja(Request $request, Obra $obra, ObraEmpleado $asignacion)
    {
        if ($asignacion->obra_id !== $obra->id) {
            abort(404);
        }

        $data = $request->validate([
            'fecha_baja' => ['nullable', 'date'],
        ]);

        $asignacion->fecha_baja = $data['fecha_baja'] ?? now()->toDateString();
        $asignacion->activo     = false;
        $asignacion->save();

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'empleados'])
            ->with('success', 'Empleado dado de baja en esta obra.');
    }
}

