<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RelacionGastosPresupuesto
{
    // El número de fila de RELACIÓN GASTOS identifica el concepto del machote.
    private const CATALOGO = [
        'Rentas RC' => [
            8 => ['Renta de Perforadora', 'JOR', [24, 25, 26, 27, 28, 29, 30, 31, 32], 'jornales'],
            9 => ['Renta de Camioneta', 'JOR', [46], 'jornales'],
            10 => ['Renta de Tremie', 'M³', [70]],
        ],
        'Sueldos personal' => [
            12 => ['Operador Bauer', 'JOR', [37], 'sueldo'],
            13 => ['Operador sobre camión', 'JOR', [38], 'sueldo'],
            14 => ['Sueldo Residentes', 'JOR', [40], 'sueldo'],
            15 => ['Sueldo ayudantes', 'JOR', [39, 65, 86], 'sueldo'],
            16 => ['Segurista', 'JOR', [41], 'sueldo'],
            17 => ['Topógrafo', 'JOR', [42], 'sueldo'],
        ],
        'Viáticos y comidas' => [
            19 => ['Viáticos', 'DÍAS', [35, 66, 87], 'viaticos'],
            20 => ['Comidas', 'EVENTO', []],
        ],
        'Comisiones' => [
            22 => ['Comisión Perforación M.L.', 'ML', [43]],
            23 => ['Comisión Colado M3', 'M³', [69], null, 'Comisón Colado M3'],
            24 => ['Comisión Bentonita M3', 'M³', [90], null, 'Comisón Bentonita M3'],
        ],
        'Horas extras' => [
            26 => ['Horas Extras perforación', 'HRS', [45]],
            27 => ['Horas Extras col. Acero, concreto y bentonita', 'HRS', [73, 91]],
        ],
        'Hospedaje (hotel/casa/Airbnb)' => [
            29 => ['Hospedaje', 'DÍAS', [36, 67, 88], 'hospedaje'],
        ],
        'Boletos de camión, avión y taxis' => [
            31 => ['Boletos camión, avión y taxis (boletos x p/salida)', 'SALIDAS', [54, 72, 89]],
        ],
        'Bentonitas y retiro de bentonita' => [
            33 => ['Bentonita', 'TON', [81]],
            34 => ['Flete bentonita', 'TON', [82]],
            35 => ['Carga y Descarga de bentonita', 'TON', [83]],
            36 => ['Equipo de batido', 'M³', [84]],
            37 => ['Agua para lodos', 'M³', [92]],
            38 => ['Retiro de Bentonita (Proveedor)', 'M³', [98], null, 'Retiro de Bentonita (Provedor)'],
        ],
        'Grúa' => [
            40 => ['Renta de grúa', 'DÍAS', [62], 'jornales'],
        ],
        'Traslados' => [
            42 => ['Cama baja', 'LOTE', ['traslado-cama-baja-neumatica-lowboy']],
            43 => ['Plataforma', 'LOTE', ['traslado-plataforma']],
            44 => ['Torton / 3 Toneladas / 5 Toneladas', 'LOTE', ['traslado-equipo-de-15-toneladas'], null, 'Torton / 3 Tonelas / 5 Toneladas'],
            45 => ['Grúa carga y descarga', 'EVENTO', ['traslado-grua-para-carga-y-descarga']],
        ],
        'Combustibles' => [
            47 => ['Combustible camioneta', 'JOR', [47], 'jornales'],
            48 => ['Combustible perforadora', 'LTS', [33], 'jornales', 'Combustible peforadora'],
            49 => ['Combustible grúa', 'LTS', [64], 'jornales'],
            50 => ['Combustible motobombas', 'LTS', [85], 'jornales'],
        ],
        'Habilitado y armado de acero' => [
            52 => ['Mano de obra', 'KG', [106]],
            53 => ['Alambre recocido', 'KG', [109]],
            54 => ['Separadores plásticos', 'PZA', [108, 120]],
            55 => ['Conectores para varilla', 'PZA', [110]],
            56 => ['Máquina Roscadora', 'MES', [111]],
            57 => ['Generador de energía eléctrica', 'MES', [112]],
        ],
        'Suministro de acero' => [
            59 => ['Varilla desde el no. 3 hasta el no 12.', 'KG', [118]],
            60 => ['Alambre recocido', 'KG', [119]],
        ],
        'Suministro de concreto' => [
            62 => ["Concreto premezclado f'c=250kg/cm2 rev 18cm", 'M³', [126]],
        ],
        'Descabece' => [
            64 => ['Descabece de pilas', 'PZA', [131, 132, 133, 134, 135, 136]],
        ],
        'Cuadrilla topográfica' => [
            66 => ['Cuadrilla topográfica con equipo', 'SEM', [142]],
        ],
        'Retiro de material de perforación' => [
            68 => ['Renta de Retroexcavadora / Bobcat', 'JOR', [147], 'activar_jornales', 'Renta de Retroexcabadora / Bobcat'],
            69 => ['Camión de Volteo', 'M³', [148]],
        ],
        'Imprevistos y seguridad' => [
            71 => ['Imprevistos (cosas necesarias no presupuestadas)', 'LOTE', [50]],
            72 => ['Partida de seguridad', 'LOTE', [55]],
        ],
        'Gastos de Giralda' => [
            74 => ['Gastos de almacén Giralda', 'LOTE', [52]],
        ],
        'Fianzas y seguro de R.C.' => [
            76 => ['Fianza anticipo y cumplimiento', 'PZA', [154]],
            77 => ['Fianza Garantía y Vicios Ocultos', 'PZA', [155]],
            78 => ['Seguro de Responsabilidad Civil', 'PZA', [156]],
        ],
    ];

    public static function preparar(Collection $importados, Collection $costos, array $duracion, array $ajustes = []): array
    {
        $importes = $importados->reject(fn ($fila) => in_array(Str::slug($fila->concepto), [
            'monto-del-presupuesto-sin-iva', 'monto-del-presupuesto-con-iva',
            'suma-gastos', 'cobros', 'diferencia-a-favor-o-en-contra',
        ], true))->map(fn ($fila) => [
            'partida' => $fila->partida ?: 'General',
            'concepto' => $fila->concepto,
            'unidad' => $fila->unidad,
            'cantidad' => (float) $fila->cantidad,
            'precio_unitario' => (float) $fila->precio_unitario,
            'monto_programado' => (float) $fila->monto_programado,
        ])->groupBy(fn (array $fila) => self::identidad($fila['partida'], $fila['concepto']))
            ->map(fn (Collection $filas) => $filas->all())->all();

        $partidas = [];
        foreach (self::CATALOGO as $partida => $conceptos) {
            foreach ($conceptos as $id => $definicion) {
                [$concepto, $unidad, $origen] = $definicion;
                $partidaExcel = $partida === 'Bentonitas y retiro de bentonita' ? 'Bentonitas y retiro de bent' : $partida;
                $claves = array_unique([
                    self::identidad($partida, $concepto),
                    self::identidad($partidaExcel, $definicion[4] ?? $concepto),
                ]);
                $base = null;
                foreach ($claves as $clave) {
                    if (! empty($importes[$clave])) {
                        $base = array_shift($importes[$clave]);
                        break;
                    }
                }
                $fuentes = $costos->only(array_map(fn ($clave) => is_int($clave) ? 'apupi-' . $clave : $clave, $origen));
                if ($base === null || $fuentes->contains('ajustado', true)) {
                    $base = self::calcular($fuentes, $duracion['realista'], $definicion[3] ?? null);
                }
                $partidas[$partida][] = ['id' => $id, 'concepto' => $concepto, 'unidad' => $unidad] + $base;
            }
        }

        foreach ($importes as $identidad => $filas) {
            foreach ($filas as $ordinal => $fila) {
                $id = 'extra-' . hash('sha256', $identidad . '|' . $ordinal);
                $partidas[$fila['partida']][] = ['id' => $id] + $fila;
            }
        }

        $grupos = collect($partidas)->map(function (array $filas, string $partida): array {
            return ['partida' => $partida, 'filas' => collect($filas)->map(fn (array $fila) => $fila + [
                'usado' => $fila['cantidad'] != 0 || $fila['monto_programado'] != 0 || $fila['monto_programado'] === null,
            ])->all()];
        })->values();
        $filas = $grupos->pluck('filas')->collapse();

        return PlaneacionSemanalPresupuesto::preparar([
            'partidas' => $grupos->all(),
            'conceptos' => $filas->count(),
            'usados' => $filas->where('usado', true)->count(),
            'total' => round($filas->sum('monto_programado'), 2),
            'pendientes' => $filas->whereNull('monto_programado')->count(),
        ], $duracion['realista'], $ajustes);
    }

    private static function identidad(string $partida, string $concepto): string
    {
        return Str::slug($partida) . '|' . Str::slug($concepto);
    }

    private static function calcular(Collection $fuentes, array $realista, ?string $regla): array
    {
        $activas = $fuentes->filter(fn (array $fila) => $fila['cantidad'] != 0 || data_get($fila, 'importes.realista') != 0);
        $cantidad = $activas->sum('cantidad');
        $importe = $activas->contains(fn (array $fila) => data_get($fila, 'importes.realista') === null)
            ? null : $activas->sum('importes.realista');
        $precio = $activas->count() === 1 ? (float) $activas->first()['precio_unitario'] : 0;

        if ($activas->isNotEmpty() && in_array($regla, ['sueldo', 'viaticos', 'hospedaje'], true)) {
            $principal = $fuentes->first();
            $dias = $realista[$regla === 'hospedaje' ? 'dias' : 'jornales'];
            $cantidad = $principal['cantidad'] == 0 ? 0 : ($dias === null ? null : $principal['cantidad'] * $dias);
            $precio = $activas->sum('precio_unitario') / match ($regla) {
                'sueldo' => 1.65,
                'viaticos' => 1.4,
                default => 1,
            };
            $importe = $cantidad === null ? null : $cantidad * $precio;
        } elseif ($activas->isNotEmpty() && in_array($regla, ['jornales', 'activar_jornales'], true)) {
            $jornales = $realista['jornales'];
            $cantidad = $jornales === null ? null : $jornales * ($regla === 'activar_jornales' ? 1 : $cantidad);
            if ($activas->count() === 1 && $precio > 0 && $importe !== null && $importe != 0) {
                $cantidad = round($importe / $precio, 4);
            }
        }

        if ($cantidad > 0 && $importe !== null) {
            $precio = $importe / $cantidad;
        }

        return [
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'monto_programado' => $importe === null ? null : round($importe, 2),
        ];
    }
}
