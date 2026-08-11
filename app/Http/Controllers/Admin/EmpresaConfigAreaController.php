<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class EmpresaConfigAreaController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validatedData($request, null);
        $areaData = Arr::only($data, ['codigo', 'nombre', 'descripcion', 'activo']);
        $areaData['activo'] = (bool) ($areaData['activo'] ?? true);

        $area = Area::create($areaData);
        $this->syncHorarioBase($area, $data, $request);

        return back()->with('success', 'Área creada correctamente.');
    }

    public function update(Request $request, Area $area)
    {
        $data = $this->validatedData($request, $area);
        $areaData = Arr::only($data, ['codigo', 'nombre', 'descripcion', 'activo']);
        $areaData['activo'] = (bool) ($areaData['activo'] ?? false);

        $area->update($areaData);
        $this->syncHorarioBase($area, $data, $request);

        return back()->with('success', 'Área actualizada correctamente.');
    }

    public function toggle(Area $area)
    {
        $area->update(['activo' => ! (bool) $area->activo]);

        return back()->with('success', 'Estatus de área actualizado.');
    }

    public function destroy(Area $area)
    {
        $area->delete();

        return back()->with('success', 'Área eliminada.');
    }

    private function validatedData(Request $request, ?Area $area): array
    {
        $this->normalizeHorarioDias($request);

        $codigoRule = Rule::unique('areas', 'codigo');

        if ($area) {
            $codigoRule->ignore($area->id);
        }

        return $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                $codigoRule,
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['nullable', 'boolean'],
            'horario_nombre' => ['nullable', 'string', 'max:150'],
            'horario_hora_entrada' => ['nullable', 'date_format:H:i'],
            'horario_hora_salida' => ['nullable', 'date_format:H:i'],
            'horario_dias_laborables' => ['nullable', 'array'],
            'horario_dias_laborables.*' => [
                'string',
                'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            ],
            'horario_minutos_comida' => ['nullable', 'integer', 'min:0', 'max:600'],
            'horario_minutos_tolerancia' => ['nullable', 'integer', 'min:0', 'max:240'],
        ]);
    }

    private function normalizeHorarioDias(Request $request): void
    {
        $dias = collect($request->input('horario_dias_laborables', []))
            ->map(function ($dia) {
                if (is_array($dia)) {
                    return $dia['value'] ?? null;
                }

                return is_string($dia) ? trim($dia) : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $request->merge(['horario_dias_laborables' => $dias]);
    }

    private function syncHorarioBase(Area $area, array $data, Request $request): void
    {
        $horarioKeys = [
            'horario_nombre',
            'horario_hora_entrada',
            'horario_hora_salida',
            'horario_dias_laborables',
            'horario_minutos_comida',
            'horario_minutos_tolerancia',
        ];

        $recibioHorario = collect($horarioKeys)->contains(fn ($key) => $request->has($key));

        if (! $recibioHorario) {
            return;
        }

        $tieneDatosHorario = filled($data['horario_nombre'] ?? null)
            || filled($data['horario_hora_entrada'] ?? null)
            || filled($data['horario_hora_salida'] ?? null)
            || count($data['horario_dias_laborables'] ?? []) > 0
            || (int) ($data['horario_minutos_comida'] ?? 0) > 0
            || (int) ($data['horario_minutos_tolerancia'] ?? 0) > 0;

        if (! $tieneDatosHorario) {
            return;
        }

        $horarioData = [
            'nombre' => $data['horario_nombre'] ?? 'Horario base',
            'hora_entrada' => $data['horario_hora_entrada'] ?? null,
            'hora_salida' => $data['horario_hora_salida'] ?? null,
            'dias_laborables' => $data['horario_dias_laborables'] ?? [],
            'minutos_comida' => (int) ($data['horario_minutos_comida'] ?? 0),
            'minutos_tolerancia' => (int) ($data['horario_minutos_tolerancia'] ?? 0),
            'activo' => true,
        ];

        $horarioActivo = $area->horarioActivo()->first();

        if ($horarioActivo) {
            $horarioActivo->update($horarioData);
            return;
        }

        $area->horarios()->create($horarioData);
    }
}
