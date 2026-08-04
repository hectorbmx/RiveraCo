<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpleadoEppEntrega;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class EmpleadoEppEntregaController extends Controller
{
    public function store(Request $request, Empleado $empleado)
    {
        $this->authorizeAny(['empleados.epp.access', 'empleados.access', 'giralda.access']);

        $data = $request->validate([
            'articulo' => ['required', 'string', 'max:120'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'talla' => ['nullable', 'string', 'max:50'],
            'fecha_entrega' => ['required', 'date'],
            'condicion' => ['required', 'string', 'max:80'],
            'obra_area' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string'],
            'confirmado_por_empleado' => ['nullable', 'boolean'],
        ]);

        $data['empleado_id'] = $empleado->id_Empleado;
        $data['entregado_por'] = auth()->id();
        $data['confirmado_por_empleado'] = $request->boolean('confirmado_por_empleado');
        $data['fecha_confirmacion'] = $data['confirmado_por_empleado'] ? now() : null;

        EmpleadoEppEntrega::create($data);

        if ($request->input('redirect_to') === 'giralda.empleados') {
            return redirect()
                ->route('giralda.empleados', ['tab' => 'epp', 'empleado_id' => $empleado->id_Empleado])
                ->with('success', 'Entrega de EPP registrada.');
        }

        return redirect()
            ->route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'epp'])
            ->with('success', 'Entrega de EPP registrada.');
    }

    private function authorizeAny(array $permissions): void
    {
        $user = auth()->user();

        if (!$user || !$user->canAny($permissions)) {
            throw new AuthorizationException('No tienes permiso para registrar entregas de EPP.');
        }
    }
}
