<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpleadoContactoEmergencia;
use Illuminate\Http\Request;

class EmpleadoContactoEmergenciaController extends Controller
{
    public function store(Request $request, Empleado $empleado)
    {
        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:100'],
            'parentesco'  => ['nullable', 'string', 'max:50'],
            'telefono'    => ['nullable', 'string', 'max:50'],
            'celular'     => ['nullable', 'string', 'max:50'],
            'es_principal'=> ['nullable', 'boolean'],
            'notas'       => ['nullable', 'string'],
        ]);

        $data['empleado_id'] = $empleado->id_Empleado;
        $data['es_principal'] = $request->boolean('es_principal');

        // Si se marca como principal, desmarcamos los otros
        if ($data['es_principal']) {
            EmpleadoContactoEmergencia::where('empleado_id', $empleado->id_Empleado)
                ->update(['es_principal' => false]);
        }

        EmpleadoContactoEmergencia::create($data);

        return redirect()
            ->route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'emergencia'])
            ->with('success', 'Contacto de emergencia agregado correctamente.');
    }

    public function update(Request $request, Empleado $empleado, EmpleadoContactoEmergencia $contacto)
    {
        if ($contacto->empleado_id !== $empleado->id_Empleado) {
            abort(404);
        }

        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:100'],
            'parentesco'  => ['nullable', 'string', 'max:50'],
            'telefono'    => ['nullable', 'string', 'max:50'],
            'celular'     => ['nullable', 'string', 'max:50'],
            'es_principal'=> ['nullable', 'boolean'],
            'notas'       => ['nullable', 'string'],
        ]);

        $data['es_principal'] = $request->boolean('es_principal');

        if ($data['es_principal']) {
            EmpleadoContactoEmergencia::where('empleado_id', $empleado->id_Empleado)
                ->where('id', '!=', $contacto->id)
                ->update(['es_principal' => false]);
        }

        $contacto->update($data);

        return redirect()
            ->route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'emergencia'])
            ->with('success', 'Contacto de emergencia actualizado.');
    }

    public function destroy(Empleado $empleado, EmpleadoContactoEmergencia $contacto)
    {
        if ($contacto->empleado_id !== $empleado->id_Empleado) {
            abort(404);
        }

        $contacto->delete();

        return redirect()
            ->route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'emergencia'])
            ->with('success', 'Contacto de emergencia eliminado.');
    }
}
