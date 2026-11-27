<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use App\Services\Presupuestos\CalculoPresupuestoPorPartidas;
use App\Services\Presupuestos\ImportadorEstandarPresupuesto;
use App\Support\PresentacionPresupuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PresupuestoPartidasController extends Controller
{
    public function previsualizarImportacion(
        Request $request,
        Presupuesto $presupuesto,
        ImportadorEstandarPresupuesto $importador
    ): JsonResponse {
        abort_unless($presupuesto->usaEditorPorPartidas(), 404);
        abort_if($presupuesto->estaAprobado(), 409, 'El presupuesto aprobado está bloqueado.');
        $datos = $request->validate(['archivo' => ['required', 'file', 'mimes:xlsx', 'max:5120']]);

        return response()->json(['data' => $importador->leer($datos['archivo']->getPathname())]);
    }

    public function update(Request $request, Presupuesto $presupuesto, CalculoPresupuestoPorPartidas $calculo): JsonResponse
    {
        abort_unless($presupuesto->usaEditorPorPartidas(), 404);
        $datos = $request->validate([
            'revision' => ['required', 'string', 'size:64'],
            'tasa_iva' => ['required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'nota_ejecucion' => ['sometimes', 'required', 'string', 'max:200000', 'json'],
            'notas' => ['sometimes', 'bail', 'array', 'max:100', function ($atributo, $notas, $error): void {
                if (! array_is_list($notas)) {
                    $error('Las notas importantes deben enviarse en orden.');
                }
            }],
            'notas.*' => ['required', 'string', 'max:10000'],
            'partidas' => ['present', 'array'],
            'partidas.*' => ['required', 'array:id,nombre,subpartidas'],
            'partidas.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'partidas.*.nombre' => ['required', 'string', 'max:255'],
            'partidas.*.subpartidas' => ['present', 'array'],
            'partidas.*.subpartidas.*' => ['required', 'array:id,concepto,unidad,cantidad,precio_unitario'],
            'partidas.*.subpartidas.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'partidas.*.subpartidas.*.concepto' => ['required', 'string', 'max:16000'],
            'partidas.*.subpartidas.*.unidad' => ['required', 'string', 'max:32'],
            'partidas.*.subpartidas.*.cantidad' => ['required', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
            'partidas.*.subpartidas.*.precio_unitario' => ['required', 'numeric', 'decimal:0,2', 'between:0,9999999999999.99'],
        ], [], [
            'partidas.*.nombre' => 'nombre de partida',
            'partidas.*.subpartidas.*.concepto' => 'concepto',
            'partidas.*.subpartidas.*.unidad' => 'unidad',
            'partidas.*.subpartidas.*.cantidad' => 'cantidad',
            'partidas.*.subpartidas.*.precio_unitario' => 'precio unitario',
            'tasa_iva' => 'tasa de IVA',
            'nota_ejecucion' => 'notas adicionales',
            'notas' => 'notas importantes',
            'notas.*' => 'nota importante',
        ]);
        if (isset($datos['nota_ejecucion'])) {
            $datos['nota_ejecucion'] = PresentacionPresupuesto::validarNotaEjecucion($datos['nota_ejecucion'], 'notas adicionales');
        }

        $resultado = DB::transaction(function () use ($presupuesto, $datos, $calculo): array {
            $actual = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);
            abort_unless($actual->usaEditorPorPartidas(), 404);
            abort_if($actual->estaAprobado(), 409, 'El presupuesto aprobado está bloqueado.');
            abort_if($calculo->estadoEditor($actual)['revision'] !== $datos['revision'], 409,
                'Las partidas o las notas cambiaron en otra sesión. Recarga la página antes de guardar.');

            $existentes = $actual->partidas->keyBy('id');
            $partidasGuardadas = [];
            foreach (array_values($datos['partidas']) as $orden => $datosPartida) {
                $partida = empty($datosPartida['id'])
                    ? $actual->partidas()->make()
                    : $existentes->get($datosPartida['id']);
                if (! $partida) {
                    throw ValidationException::withMessages(["partidas.$orden.id" => 'La partida no pertenece al presupuesto.']);
                }
                $partida->fill(['nombre' => $datosPartida['nombre'], 'orden' => $orden])->save();
                $partidasGuardadas[] = $partida->id;

                $subpartidas = $partida->subpartidas->keyBy('id');
                $subpartidasGuardadas = [];
                foreach (array_values($datosPartida['subpartidas']) as $posicion => $renglon) {
                    $subpartida = empty($renglon['id'])
                        ? $partida->subpartidas()->make()
                        : $subpartidas->get($renglon['id']);
                    if (! $subpartida) {
                        throw ValidationException::withMessages([
                            "partidas.$orden.subpartidas.$posicion.id" => 'La subpartida no pertenece a esta partida.',
                        ]);
                    }
                    unset($renglon['id']);
                    $subpartida->fill($renglon + ['orden' => $posicion])->save();
                    $subpartidasGuardadas[] = $subpartida->id;
                }
                $partida->subpartidas()->whereNotIn('id', $subpartidasGuardadas)->delete();
            }
            $actual->partidas()->whereNotIn('id', $partidasGuardadas)->delete();

            $presentacion = $actual->presentacion_comercial;
            $presentacion['iva'] = $datos['tasa_iva'];
            foreach (['nota_ejecucion', 'notas'] as $campo) {
                if (array_key_exists($campo, $datos)) {
                    $presentacion[$campo] = $datos[$campo];
                }
            }
            $actual->forceFill(['presentacion_comercial' => $presentacion])->save();

            return $calculo->estadoEditor($actual->unsetRelation('partidas'));
        }, 3);

        return response()->json(['ok' => true, 'data' => $resultado]);
    }
}
