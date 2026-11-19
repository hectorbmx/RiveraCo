<?php

namespace App\Support;

use App\Models\PagoProveedor;
use App\Models\Presupuesto;
use Illuminate\Support\Collection;

class PrevioPresupuesto
{
    public const ADMINISTRADORES = ['admin-rivera', 'super-admin', 'admin'];
    public const PORCENTAJES = ['administracion' => 15, 'imss' => 4, 'comisiones' => 2.15, 'margen_objetivo' => null];
    public const PERSONAL = [
        'periodo' => 'Periodo', 'residente' => 'Residente', 'operador' => 'Operador',
        'ayudante' => 'Ayudante', 'bentonitero' => 'Bentonitero',
    ];

    public static function preparar(Presupuesto $presupuesto, Collection $costos, array $propuesta, array $duracion): array
    {
        $datos = $presupuesto->datos_previo ?? [];
        $porcentajes = array_replace(self::PORCENTAJES, $datos['porcentajes'] ?? []);
        $filasVenta = collect($propuesta['filas']);
        $cobrables = self::cobrables($filasVenta);
        $venta = PropuestaApupi::totalVenta($filasVenta);
        $ventaAjustable = PropuestaApupi::totalVenta($cobrables);
        $financiamiento = (float) ($filasVenta['venta-218']['importe'] ?? 0);
        $tasaFinanciamiento = $cobrables->has('venta-218') && $ventaAjustable > 0 ? $financiamiento / $ventaAjustable : 0;
        $gastoReal = self::gastoReal($presupuesto);
        $partidasRenta = array_merge(array_map(fn (int $numero) => 'apupi-' . $numero, range(24, 32)), ['apupi-46', 'apupi-70', 'apupi-84']);
        $rentas = $costos->only($partidasRenta);
        $fianzas = $costos->only(array_keys(PlantillaApupi::TASAS_FIANZA))->filter(
            fn (array $fila, string $clave) => ! $fila['detalle']->exists
                && ! isset($presupuesto->ajustes_apupi[$clave]['precio_unitario'])
        );
        $tasaFianzas = $fianzas->map(fn (array $fila, string $clave) => $fila['cantidad'] * PlantillaApupi::TASAS_FIANZA[$clave])->sum();
        $tasaGastos = ($porcentajes['administracion'] + $porcentajes['imss'] + $porcentajes['comisiones']) / 100;
        $escenarios = [];

        foreach (Presupuesto::ESCENARIOS_APUPI as $escenario) {
            $campo = 'importes.' . $escenario;
            $completo = ! $costos->contains(fn (array $fila) => $fila['cantidad'] > 0 && $fila['precio_unitario'] > 0 && data_get($fila, $campo) === null);
            $renta = $rentas->sum($campo);
            $almacen = (float) data_get($costos, 'apupi-52.' . $campo);
            $imprevistos = (float) data_get($costos, 'apupi-50.' . $campo) + $financiamiento;
            $directo = $completo ? round($costos->sum($campo) + $financiamiento, 2) : null;
            $gastos = $completo ? round($directo - $renta - $almacen - $imprevistos, 2) : null;
            $pendiente = $datos['pendiente'][$escenario] ?? null;
            $cierre = $gastoReal['importe'] !== null && $pendiente !== null
                ? round($gastoReal['importe'] + $pendiente, 2) : null;
            $base = $cierre ?? $directo;
            $utilidad = $base === null || $venta <= 0 ? null : round($venta * (1 - $tasaGastos) - $base, 2);
            $ventaObjetivo = null;
            if ($base !== null && $porcentajes['margen_objetivo'] !== null) {
                $tasaVariable = $cierre === null ? $tasaFianzas + $tasaFinanciamiento : 0;
                $fijo = $cierre === null ? $base - $fianzas->sum($campo) - $venta * $tasaFinanciamiento : $base;
                $factor = 1 - $tasaGastos - $porcentajes['margen_objetivo'] / 100 - $tasaVariable;
                if ($factor > 0) {
                    $ventaObjetivo = round(max(0, $fijo) / $factor, 2);
                }
            }

            $escenarios[$escenario] = [
                'jornales' => $duracion[$escenario]['jornales'],
                'renta' => $completo ? round($renta, 2) : null,
                'almacen' => $completo ? $almacen : null,
                'gastos' => $gastos,
                'imprevistos' => $completo ? round($imprevistos, 2) : null,
                'filas' => self::filasDesglose($rentas, $escenario, $completo ? $almacen : null, $gastos, $completo ? round($imprevistos, 2) : null),
                'administracion' => round($venta * $porcentajes['administracion'] / 100, 2),
                'imss' => round($venta * $porcentajes['imss'] / 100, 2),
                'comisiones' => round($venta * $porcentajes['comisiones'] / 100, 2),
                'directo' => $directo,
                'cierre' => $cierre,
                'utilidad' => $utilidad,
                'margen' => $utilidad === null ? null : round($utilidad / $venta * 100, 2),
                'venta_objetivo' => $ventaObjetivo,
                'diferencia' => $ventaObjetivo === null ? null : round($ventaObjetivo - $venta, 2),
            ];
        }

        return [
            'escenario' => $datos['escenario'] ?? 'realista',
            'porcentajes' => $porcentajes,
            'pendiente' => $datos['pendiente'] ?? [],
            'personal' => $datos['personal'] ?? [],
            'firmas' => $datos['firmas'] ?? null,
            'venta_actual' => $venta,
            'gasto_real' => $gastoReal,
            'escenarios' => $escenarios,
        ];
    }

    private static function filasDesglose(Collection $rentas, string $escenario, ?float $almacen, ?float $gastos, ?float $imprevistos): array
    {
        $fila = fn (string $concepto, ?float $cantidad, string $unidad, ?float $precio, ?float $importe) => [
            'concepto' => $concepto, 'cantidad' => $cantidad, 'unidad' => $unidad,
            'precio' => $precio, 'importe' => $importe,
        ];
        $detalleRentas = $rentas->filter(fn (array $renta) => $renta['cantidad'] != 0 || $renta['importes'][$escenario] != 0)
            ->map(function (array $renta, string $clave) use ($escenario, $fila): array {
                $importe = $renta['importes'][$escenario];
                $precio = (float) $renta['precio_unitario'];
                $porJornal = ! in_array($clave, ['apupi-70', 'apupi-84'], true);
                $cantidad = $porJornal
                    ? ($importe === null || $precio == 0 ? null : round($importe / $precio, 4))
                    : (float) $renta['cantidad'];

                return $fila($renta['detalle']->concepto, $cantidad, $porJornal ? 'Jornales' : $renta['detalle']->unidad, $precio, $importe);
            })->values()->all();
        $consumibles = $almacen === null ? null : round($almacen * 0.4, 2);
        $noConsumibles = $almacen === null ? null : round($almacen * 0.35, 2);
        $reparaciones = $almacen === null ? null : round($almacen - $consumibles - $noConsumibles, 2);

        return [
            'renta' => $detalleRentas,
            'almacen' => [
                $fila('Consumibles', 1, 'Lote', $consumibles, $consumibles),
                $fila('No consumibles', 1, 'Lote', $noConsumibles, $noConsumibles),
                $fila('Reparaciones', 1, 'Lote', $reparaciones, $reparaciones),
            ],
            'gastos' => [$fila('Relación de gastos', 1, 'Lote', $gastos, $gastos)],
            'imprevistos' => [$fila('Imprevistos', 1, 'Lote', $imprevistos, $imprevistos)],
        ];
    }

    private static function gastoReal(Presupuesto $presupuesto): array
    {
        $obras = $presupuesto->obras()->pluck('obras.id');
        $pagos = collect();
        if ($obras->isNotEmpty()) {
            $pagos = PagoProveedor::query()
                ->join('ordenes_compra', 'ordenes_compra.id', '=', 'pagos_proveedores.orden_compra_id')
                ->whereIn('ordenes_compra.obra_id', $obras)
                ->where('pagos_proveedores.estatus', 'pagado')
                ->get(['pagos_proveedores.monto', 'pagos_proveedores.moneda', 'ordenes_compra.subtotal', 'ordenes_compra.total', 'ordenes_compra.tipo_cambio']);
        }
        $importes = $pagos->map(function (PagoProveedor $pago): ?float {
            $cambio = strtoupper($pago->moneda ?? 'MXN') === 'MXN' ? 1 : (float) $pago->tipo_cambio;

            return $pago->total > 0 && $pago->subtotal !== null && $cambio > 0
                ? (float) $pago->monto * ((float) $pago->subtotal / (float) $pago->total) * $cambio : null;
        })->whereNotNull();

        return [
            'obras' => $obras->count(),
            'pagos' => $importes->count(),
            'sin_base' => $pagos->count() - $importes->count(),
            'importe' => $obras->isEmpty() ? null : round($importes->sum(), 2),
        ];
    }

    public static function ajustarPrecios(Collection $filas, float $objetivo): array
    {
        $cobrables = self::cobrables($filas);
        $ventaAjustable = PropuestaApupi::totalVenta($cobrables);
        $ventaFija = PropuestaApupi::totalVenta($filas) - $ventaAjustable;
        $objetivoAjustable = $objetivo - $ventaFija;
        if ($ventaAjustable <= 0 || $objetivoAjustable <= 0) {
            return [];
        }

        $factor = $objetivoAjustable / $ventaAjustable;
        $precios = $cobrables->map(fn (array $fila) => max(0.01, round($fila['precio_unitario'] * $factor, 2)));
        $subtotal = $cobrables->map(fn (array $fila, string $clave) => round($fila['cantidad'] * $precios[$clave], 2))->sum();
        $pendiente = round($objetivoAjustable - $subtotal, 2);
        // Corrige el residuo de redondeo primero en los renglones con menor cantidad.
        foreach ($cobrables->sortBy('cantidad') as $clave => $fila) {
            if ($pendiente == 0) {
                break;
            }
            $cantidad = $fila['cantidad'];
            $importe = round($cantidad * $precios[$clave], 2);
            $candidato = max(0.01, round(($importe + $pendiente) / $cantidad, 2));
            $diferencia = round($pendiente + $importe - round($cantidad * $candidato, 2), 2);
            if (abs($diferencia) < abs($pendiente)) {
                $precios[$clave] = $candidato;
                $pendiente = $diferencia;
            }
        }

        return $precios->all();
    }

    private static function cobrables(Collection $filas): Collection
    {
        return $filas->filter(fn (array $fila) => $fila['cantidad'] > 0 && $fila['precio_unitario'] > 0);
    }
}
