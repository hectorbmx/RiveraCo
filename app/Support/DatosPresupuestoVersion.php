<?php

namespace App\Support;

use App\Models\Presupuesto;
use App\Models\PresupuestoVolPila;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DatosPresupuestoVersion
{
    public static function restaurar(Presupuesto $presupuesto, array $datos): void
    {
        $presupuesto->forceFill(Arr::only($datos['presupuesto'], [
            'nombre_cliente', 'nombre_obra', 'descripcion', 'equipo_propuesto',
            'vol_traslape_varilla', 'duracion_apupi', 'indirecto_traslado_apupi',
            'ajustes_apupi', 'ajustes_propuesta_apupi', 'total_costo_directo', 'total_presupuesto',
        ]));
        $presupuesto->presentacion_comercial = $datos['presupuesto']['presentacion_comercial'] ?? null;
        $presupuesto->datos_previo = $datos['presupuesto']['datos_previo'] ?? null;
        $presupuesto->ajustes_gastos_semanales = $datos['presupuesto']['ajustes_gastos_semanales'] ?? null;

        foreach ([
            'detalles' => $datos['importacion']['detalles'],
            'pilas' => $datos['importacion']['pilas'],
            'resumenes' => $datos['importacion']['resumenes'],
            'pilasVol' => array_column($datos['vol']['filas'], 'pila'),
        ] as $relacion => $filas) {
            $presupuesto->{$relacion}()->delete();
            $presupuesto->{$relacion}()->createMany($filas);
        }
    }

    public static function capturar(Presupuesto $presupuesto, array $vol, array $duracion, Collection $costos, array $resumen, Collection $gastos): array
    {
        $captura = fn (Model $fila) => $fila->only(array_diff($fila->getFillable(), ['presupuesto_id']));

        return [
            'estructura' => 3,
            'presupuesto' => $presupuesto->only([
                'codigo_proyecto', 'origen', 'nombre_cliente', 'nombre_obra', 'descripcion',
                'equipo_propuesto', 'vol_traslape_varilla', 'duracion_apupi', 'indirecto_traslado_apupi',
                'ajustes_apupi', 'ajustes_propuesta_apupi', 'total_costo_directo', 'total_presupuesto',
                'presentacion_comercial', 'datos_previo', 'ajustes_gastos_semanales',
            ]) + [
                'equipo' => Presupuesto::EQUIPOS_PROPUESTOS[$presupuesto->equipo_propuesto] ?? null,
                'indirecto_traslado_efectivo' => $presupuesto->indirectoTrasladoApupi(),
            ],
            'cliente' => $presupuesto->cliente?->only(['id', 'nombre_comercial', 'razon_social', 'rfc']) ?? [],
            'importacion' => [
                'detalles' => $presupuesto->detalles->sortBy('id')->map($captura)->values()->all(),
                'pilas' => $presupuesto->pilas->sortBy('id')->map($captura)->values()->all(),
                'resumenes' => $presupuesto->resumenes->sortBy('id')->map($captura)->values()->all(),
            ],
            'vol' => [
                'filas' => $vol['filas']->map(fn (array $fila) => [
                    'pila' => $captura($fila['pila']),
                    'vol' => $fila['vol'],
                ])->all(),
                'totales' => $vol['totales'],
                'porDiametro' => $vol['porDiametro']->all(),
                'pesos_varilla' => PresupuestoVolPila::PESOS_VARILLA,
                'traslapes_metros' => PresupuestoVolPila::TRASLAPES_METROS,
            ],
            'duracion' => $duracion,
            'apupi' => [
                'filas' => $costos->map(fn (array $fila) => [
                    'partida' => $fila['detalle']->partidaApupi(),
                    'concepto' => $fila['detalle']->concepto,
                    'unidad' => $fila['detalle']->unidad,
                    'cantidad' => $fila['cantidad'],
                    'precio_unitario' => $fila['precio_unitario'],
                    'importes' => $fila['importes'],
                ])->all(),
                'resumen' => Arr::except($resumen, ['propuesta']),
            ],
            'propuesta' => $resumen['propuesta'],
            'presentacion' => PresentacionPresupuesto::preparar($presupuesto, $costos, $resumen, $duracion),
            'previo' => PrevioPresupuesto::preparar($presupuesto, $costos, $resumen['propuesta'], $duracion),
            'relacion_gastos' => RelacionGastosPresupuesto::preparar($gastos, $costos, $duracion, $presupuesto->ajustes_gastos_semanales ?? []),
            'gastos' => $gastos->map(fn ($gasto) => $gasto->only([
                'partida', 'concepto', 'unidad', 'cantidad', 'precio_unitario', 'monto_programado',
            ]))->all(),
        ];
    }
}
