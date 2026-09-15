<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\Empleado;
use App\Models\ObraEmpleado;
use App\Models\CatalogoRol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function updateFechaAlta(Request $request, Obra $obra, ObraEmpleado $asignacion)
    {
        abort_unless(
            $request->user()?->can('obras.empleados.fecha_alta.edit.access'),
            403
        );

        if ($asignacion->obra_id !== $obra->id) {
            abort(404);
        }

        $data = $request->validate([
            'fecha_alta' => ['required', 'date'],
        ]);

        if ($asignacion->fecha_baja && $data['fecha_alta'] > $asignacion->fecha_baja->toDateString()) {
            return back()->withErrors([
                'fecha_alta' => 'La fecha de alta no puede ser posterior a la fecha de baja.',
            ]);
        }

        $fechaAnterior = $asignacion->fecha_alta?->toDateString();
        $fechaNueva = $data['fecha_alta'];

        if ($fechaAnterior === $fechaNueva) {
            return redirect()
                ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'empleados'])
                ->with('success', 'La fecha de asignación no tuvo cambios.');
        }

        $asignacion->fecha_alta = $fechaNueva;
        $asignacion->save();

        Log::warning('Cambio de fecha de asignacion de empleado en obra; revisar afectaciones relacionadas.', [
            'obra_id' => $obra->id,
            'obra_empleado_id' => $asignacion->id,
            'empleado_id' => $asignacion->empleado_id,
            'fecha_anterior' => $fechaAnterior,
            'fecha_nueva' => $fechaNueva,
            'usuario_id' => $request->user()?->id,
            'pendiente_revisar' => [
                'asistencia_semanal',
                'viaticos_reposiciones',
                'lista_raya_obra_viva',
                'registros_historicos_relacionados',
            ],
        ]);

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'empleados'])
            ->with('success', 'Fecha de asignación actualizada. Queda pendiente revisar afectaciones en asistencia, viáticos y lista de raya.');
    }

    public function updateRol(Request $request, Obra $obra, ObraEmpleado $asignacion)
    {
        abort_unless(
            $request->user()?->can('obras.empleados.rol.edit.access'),
            403
        );

        if ($asignacion->obra_id !== $obra->id) {
            abort(404);
        }

        $data = $request->validate([
            'rol_id' => ['required', 'integer', 'exists:catalogo_roles,id'],
        ]);

        $rolNuevo = CatalogoRol::query()
            ->where('id', $data['rol_id'])
            ->where('activo', true)
            ->first();

        if (! $rolNuevo) {
            return back()->withErrors([
                'rol_id' => 'El rol seleccionado no está activo.',
            ]);
        }

        $rolAnterior = $asignacion->rol()->first();
        $rolAnteriorId = $asignacion->rol_id;

        if ((int) $rolAnteriorId === (int) $rolNuevo->id) {
            return redirect()
                ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'empleados'])
                ->with('success', 'El puesto en obra no tuvo cambios.');
        }

        $asignacion->rol_id = $rolNuevo->id;
        $asignacion->puesto_en_obra = $rolNuevo->nombre;
        $asignacion->save();

        Log::warning('Cambio de puesto/rol de empleado en obra; revisar afectaciones relacionadas.', [
            'obra_id' => $obra->id,
            'obra_empleado_id' => $asignacion->id,
            'empleado_id' => $asignacion->empleado_id,
            'rol_anterior_id' => $rolAnteriorId,
            'rol_anterior_key' => $rolAnterior?->rol_key,
            'rol_anterior_nombre' => $rolAnterior?->nombre,
            'rol_nuevo_id' => $rolNuevo->id,
            'rol_nuevo_key' => $rolNuevo->rol_key,
            'rol_nuevo_nombre' => $rolNuevo->nombre,
            'usuario_id' => $request->user()?->id,
            'pendiente_revisar' => [
                'comisiones_existentes',
                'comision_personal.rol_id_snapshot',
                'lista_raya_reportes',
            ],
        ]);

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'empleados'])
            ->with('success', 'Puesto en obra actualizado. Queda pendiente revisar comisiones existentes si ya fueron generadas.');
    }
}

