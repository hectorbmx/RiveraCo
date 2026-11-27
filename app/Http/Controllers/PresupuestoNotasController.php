<?php

namespace App\Http\Controllers;

use App\Support\PlantillasNotasPresupuesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PresupuestoNotasController extends Controller
{
    public function edit(string $grupo = 'PILAS')
    {
        abort_unless(isset(PlantillasNotasPresupuesto::GRUPOS[$grupo]), 404);

        return view('presupuesto.notas-base', [
            'grupo' => $grupo,
            'grupos' => PlantillasNotasPresupuesto::GRUPOS,
            'filas' => PlantillasNotasPresupuesto::vigente($grupo),
            'reglas' => $grupo === 'PILAS' ? PlantillasNotasPresupuesto::REGLAS_PILAS : [],
            'patente' => $grupo === 'POZOS' ? PlantillasNotasPresupuesto::PATENTE_POZOS : null,
        ]);
    }

    public function update(Request $request, string $grupo)
    {
        abort_unless(isset(PlantillasNotasPresupuesto::GRUPOS[$grupo]), 404);
        $datos = $request->validate(['notas' => ['required', 'string', 'max:150000', 'json']]);
        $filas = json_decode($datos['notas'], true);
        if (! is_array($filas) || ! array_is_list($filas)) {
            throw ValidationException::withMessages(['notas' => 'Las notas deben enviarse en orden.']);
        }

        $reglas = $grupo === 'PILAS' ? array_keys(PlantillasNotasPresupuesto::REGLAS_PILAS) : [];
        validator(['filas' => $filas], [
            'filas' => ['present', 'array', 'max:100'],
            'filas.*' => ['required', 'array:texto,regla'],
            'filas.*.texto' => ['required_without:filas.*.regla', 'prohibits:filas.*.regla', 'string', 'max:10000'],
            'filas.*.regla' => ['sometimes', 'distinct:strict', Rule::in($reglas)],
        ])->validate();

        $filas = array_map(fn (array $fila) => isset($fila['texto'])
            ? ['texto' => Str::squish($fila['texto'])]
            : $fila, $filas);
        if (mb_strlen(implode("\n", array_column($filas, 'texto'))) > 10000) {
            throw ValidationException::withMessages(['notas' => 'La plantilla admite hasta 10,000 caracteres de texto.']);
        }

        DB::table('presupuesto_notas_plantillas')->upsert([[
            'grupo' => $grupo,
            'notas' => json_encode($filas, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]], ['grupo'], ['notas', 'updated_at']);

        return redirect()->route('presupuesto.notas.edit', $grupo)->with('estadoPlantillaNotas', 'Plantilla guardada.');
    }
}
