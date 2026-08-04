<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Empleado;
use App\Models\EmpleadoEppEntrega;
use App\Models\Obra;
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
            'obra_id' => ['nullable', 'integer', 'exists:obras,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'obra_area' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $obra = !empty($data['obra_id']) ? Obra::find($data['obra_id']) : null;
        $area = !empty($data['area_id']) ? Area::find($data['area_id']) : null;

        $snapshot = collect([
            $obra ? 'Obra: ' . trim(($obra->clave_obra ? $obra->clave_obra . ' - ' : '') . $obra->nombre) : null,
            $area ? 'Area: ' . $area->nombre : null,
        ])->filter()->implode(' / ');

        $data['obra_area'] = $snapshot ?: ($data['obra_area'] ?? null);
        $data['empleado_id'] = $empleado->id_Empleado;
        $data['entregado_por'] = auth()->id();
        $data['confirmado_por_empleado'] = false;
        $data['fecha_confirmacion'] = null;

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
