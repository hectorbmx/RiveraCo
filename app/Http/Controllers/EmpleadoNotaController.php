<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpleadoNota;
use Illuminate\Http\Request;

class EmpleadoNotaController extends Controller
{
    public function store(Request $request, Empleado $empleado)
    {
        $data = $request->validate([
            'tipo'        => ['nullable', 'string', 'max:50'],
            'titulo'      => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'monto'       => ['nullable', 'numeric'],
            'fecha_evento'=> ['nullable', 'date'],
        ]);

        $data['empleado_id'] = $empleado->id_Empleado;
        $data['user_id']     = auth()->id();
        $data['fecha_evento'] = $data['fecha_evento'] ?? now()->toDateString();

        EmpleadoNota::create($data);

        return redirect()
            ->route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'notas'])
            ->with('success', 'Nota creada correctamente.');
    }

    public function destroy(Empleado $empleado, EmpleadoNota $nota)
    {
        // Seguridad básica: validar que la nota pertenece al empleado
        if ($nota->empleado_id !== $empleado->id_Empleado) {
            abort(404);
        }

        $nota->delete();

        return redirect()
            ->route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'notas'])
            ->with('success', 'Nota eliminada.');
    }
}
