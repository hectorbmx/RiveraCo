<?php

namespace App\Support;

class PlaneacionSemanalPresupuesto
{
    public const JORNALES_SEMANA = 5.5;
    public const LIMITE_SEMANAS = 520;

    public static function preparar(array $relacion, array $duracion, array $ajustes): array
    {
        $jornales = $duracion['jornales'];
        $numero = (int) $duracion['semanas'];
        $minimo = (int) ceil((float) $jornales / self::JORNALES_SEMANA);
        $semanas = [];
        $error = match (true) {
            $numero > self::LIMITE_SEMANAS => 'La cuadrícula admite hasta ' . self::LIMITE_SEMANAS . ' semanas.',
            $jornales === null || $jornales <= 0 || $numero < 1 => 'Captura los jornales y las semanas del escenario realista para programar los gastos.',
            $numero < $minimo => 'Para cubrir los jornales hacen falta al menos ' . $minimo . ' semanas con un máximo de ' . self::JORNALES_SEMANA . ' jornales por semana.',
            default => null,
        };
        $restantes = (int) round((float) $jornales * 100);
        if ($numero <= self::LIMITE_SEMANAS) {
            for ($semana = 1; $semana <= $numero; $semana++) {
                $semanas[$semana] = min((int) (self::JORNALES_SEMANA * 100), $restantes);
                $restantes -= $semanas[$semana];
            }
        }

        $totales = array_fill_keys(array_keys($semanas), 0);
        $filasConAjustes = [];
        $pendientes = 0;
        foreach ($relacion['partidas'] as &$grupo) {
            foreach ($grupo['filas'] as &$fila) {
                $manual = $ajustes[$fila['id']] ?? [];
                $filasConAjustes[$fila['id']] = true;
                $tope = $fila['monto_programado'];
                $centavos = $tope === null ? null : self::centavos($tope);
                $fuera = array_diff_key($manual, $semanas);
                $reparto = $error === null && $centavos !== null
                    ? self::repartir($centavos, $semanas, $manual)
                    : ['montos' => [], 'saldo' => $centavos, 'exceso' => false];
                $editable = $error === null && ($tope > 0 || $manual !== []);
                $fila['planeacion'] = $reparto + [
                    'manual' => $manual,
                    'fuera' => count($fuera),
                    'editable' => $editable,
                ];
                if ($tope === null || $reparto['saldo'] !== 0 || $fuera !== []) {
                    $pendientes++;
                }
                foreach ($reparto['montos'] as $semana => $monto) {
                    $totales[$semana] += $monto;
                }
            }
            unset($fila);
        }
        unset($grupo);
        $huerfanos = count(array_diff_key($ajustes, $filasConAjustes));
        $relacion['planeacion'] = [
            'jornales' => $jornales,
            'dias' => $duracion['dias'],
            'numero_semanas' => $duracion['semanas'],
            'semanas' => collect($semanas)->map(fn ($peso, $semana) => ['numero' => $semana, 'jornales' => $peso / 100])->values()->all(),
            'totales' => $totales,
            'error' => $error,
            'pendientes' => $pendientes + $huerfanos,
            'huerfanos' => $huerfanos,
            'tiene_ajustes' => $ajustes !== [],
            'publicable' => $error === null && $pendientes === 0 && $huerfanos === 0 && array_sum($totales) > 0,
        ];

        return $relacion;
    }

    // Los importes se guardan en centavos para que el reparto cuadre con el presupuesto.
    public static function centavos(int|float|string $monto): int
    {
        return (int) round($monto * 100);
    }

    public static function ajustarSemana(int $total, array $semanas, array $manual, int $semana, ?int $monto): array
    {
        $fuera = array_diff_key($manual, $semanas);
        $dentro = array_intersect_key($manual, $semanas);
        unset($dentro[$semana]);
        if ($monto !== null && count($semanas) > 1) {
            $dentro[$semana] = $monto;
        }

        if (self::repartir($total, $semanas, $dentro)['saldo'] !== 0) {
            // El último ajuste tiene prioridad cuando los anteriores dejan un saldo sin repartir.
            $dentro = $monto !== null && count($semanas) > 1 ? [$semana => $monto] : [];
        } elseif (count($dentro) === count($semanas)) {
            // Conserva una semana automática para absorber cambios posteriores al presupuesto.
            unset($dentro[array_key_first($dentro)]);
        }

        return $dentro + $fuera;
    }

    private static function repartir(int $total, array $semanas, array $manual): array
    {
        $montos = array_fill_keys(array_keys($semanas), 0);
        $dentro = array_intersect_key($manual, $semanas);
        $montos = array_replace($montos, $dentro);
        $residuo = $total - array_sum($montos);
        $disponibles = array_diff_key($semanas, $dentro);
        $automaticas = array_filter($disponibles) ?: array_fill_keys(array_keys($disponibles), 1);
        $peso = array_sum($automaticas);
        if ($peso > 0 && ($residuo > 0 || ($residuo < 0 && $total < 0 && $manual === []))) {
            // El reparto acumulado asigna el ajuste de redondeo sin perder centavos.
            $acumulado = 0;
            $distribuido = 0;
            foreach ($automaticas as $semana => $jornales) {
                $acumulado += $jornales;
                $objetivo = (int) round($residuo * ($acumulado / $peso));
                $montos[$semana] = $objetivo - $distribuido;
                $distribuido = $objetivo;
            }
        }

        return ['montos' => $montos, 'saldo' => $total - array_sum($montos), 'exceso' => $total >= 0 && $residuo < 0];
    }
}
