<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Services\Empleados\EmpleadoHoraExtraTarifaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpleadoHoraExtraTarifaController extends Controller
{
    public function store(Request $request, Empleado $empleado, EmpleadoHoraExtraTarifaService $service): RedirectResponse
    {
        $data = $request->validateWithBag('horaExtraTarifa', [
            'modalidad' => ['required', Rule::in(['automatica', 'cuota_fija'])],
            'costo_hora_extra' => ['exclude_unless:modalidad,cuota_fija', 'required', 'numeric', 'decimal:0,4', 'gt:0', 'max:99999999.9999'],
            'vigente_desde' => ['required', 'date_format:Y-m-d'],
            'motivo' => ['required', 'string', 'max:1000'],
        ]);

        $service->registrar($empleado, $data, (int) $request->user()->id);

        return redirect()->to(route('empleados.edit', [
            'empleado' => $empleado->getKey(), 'tab' => 'datos',
        ]).'#hora-extra-tarifa')->with('success', 'Configuración de hora extra guardada en el historial.');
    }
}
