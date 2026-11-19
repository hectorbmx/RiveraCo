<?php

namespace App\Support;

use App\Models\Presupuesto;
use App\Models\PresupuestoDetalle;
use App\Models\PresupuestoPila;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PropuestaApupi
{
    public const PERFORACION_M3 = 'perforacion-120-150-m3';

    private const ACERO_COLOCACION = 'venta-199-acero';
    private const CONCRETO_COLOCACION = 'venta-200-concreto';
    private const COLOCACION_APUPI = 'GRUA PARA COLOCAR ACERO DE REFUERZO Y CONCRETO';
    private const CONCEPTOS_ANTERIORES = [
        'venta-199' => ['Colocación de tubería', 'PZA', ['colocacion-de-tuberia']],
        'venta-200' => ['Pruebas de aforo', 'HR', ['pruebas-de-aforo', 'pruebasaforo', 'pruebas-aforo']],
    ];

    private const PARTIDAS = [
        'perforacion' => ['Perforación', ['PERFORACION'], [
            self::PERFORACION_M3 => ['Perforación de 120 a 150 cm', 'M3'],
            'venta-194' => ['Campanas', 'PZA'],
            'venta-195' => ['Ademe', 'ML', ['brocal-de-ademe-metalico']],
        ]],
        'topografia' => ['Topografía', ['TOPOGRAFIA'], [
            'venta-197' => ['Topógrafo', 'SEM'],
        ]],
        'colocacion' => ['Colocación de acero y concreto', [self::COLOCACION_APUPI], [
            self::ACERO_COLOCACION => ['Colocación de Acero', 'KG'],
            self::CONCRETO_COLOCACION => ['Colocación de Concreto', 'M3.'],
            'venta-201' => ['Colocación de acero y concreto', 'PZA'],
        ]],
        'habilitado' => ['Habilitado de acero y conectores', ['HABILITADO DE ACERO DE REFUERZO'], [
            'venta-203' => ['Habilitado de acero', 'KG'],
            'venta-204' => ['Suministro y colocación de conectores', 'PZA'],
        ]],
        'bentonita' => ['Suministro y retiro de bentonita', ['BENTONITA', 'RETIRO DE LODOS BENTONITICOS'], [
            'venta-206' => ['Suministro, preparación y colocación de bentonita', 'M3', ['suministro-prep-y-col-de-bentonita']],
            'venta-207' => ['Retiro de bentonita', 'M3', ['retiro-de-lodos-bentoniticos']],
        ]],
        'materiales' => ['Suministro de materiales', ['SUMINISTRO DE CONCRETO', 'SUMINISTRO DE ACERO'], [
            'venta-209' => ['Suministro de concreto', 'M3'],
            'venta-210' => ['Suministro de acero', 'KG'],
        ]],
        'retiro' => ['Retiro de materiales', ['RETIRO DE MATERIALES DE PERFORACION', 'DESCABECE'], [
            'venta-212' => ['Retiro de material de perforación', 'M3'],
            'venta-213' => ['Descabece', 'PZA'],
        ]],
        'traslados' => ['Traslados', [PresupuestoDetalle::PARTIDA_TRASLADO], [
            'venta-215' => ['Traslado', 'LOTE'],
        ]],
        'fianzas' => ['Fianzas y financiamiento', [PlantillaApupi::FIANZAS], [
            'venta-217' => ['Fianza', 'LOTE'],
            'venta-218' => ['Financiamiento', 'LOTE'],
        ]],
    ];

    public static function filas(Presupuesto $presupuesto, Collection $porDiametro, ?float $minimoTraslado = null): Collection
    {
        $catalogo = [];
        $indices = [];
        foreach (self::PARTIDAS as $partida => [, , $conceptos]) {
            foreach ($conceptos as $clave => [$concepto, $unidad]) {
                $catalogo[$clave] = ['partida' => $partida, 'concepto' => $concepto, 'unidad' => $unidad];
                foreach ([Str::slug($concepto), ...($conceptos[$clave][2] ?? [])] as $alias) {
                    $indices[$alias] = $clave;
                }
            }
        }

        $ajustes = $presupuesto->ajustes_propuesta_apupi ?? [];
        $nombresAnteriores = [];
        foreach (self::CONCEPTOS_ANTERIORES as $clave => [$nombre, $unidad, $aliases]) {
            foreach ($aliases as $alias) {
                $nombresAnteriores[$alias] = $clave;
            }
            if (isset($ajustes[$clave])) {
                $catalogo[$clave] = ['partida' => 'colocacion', 'concepto' => $nombre, 'unidad' => $unidad];
            }
        }

        $importados = [];
        $apariciones = [];
        $clavesAnteriores = [];
        $diametros = $porDiametro->keys()->map(fn ($diametro) => (int) round((float) $diametro * 1000))->all();
        foreach (array_keys($ajustes) as $clave) {
            if (preg_match('/^diametro-([0-9]+)$/D', $clave, $coincidencia)) {
                $diametros[] = (int) $coincidencia[1];
            }
        }
        foreach ($presupuesto->pilas->sortBy('id')->concat($presupuesto->resumenes->sortBy('id')) as $fila) {
            $nombre = Str::slug($fila->concepto);
            $unidad = Str::slug(str_replace('³', '3', $fila->unidad));
            $diametro = null;
            if (preg_match('/perforacion (?:en |de )?(\d+(?:[.,]\d+)?)\s*cm\b/i', Str::ascii($fila->concepto), $coincidencia)
                && $unidad === 'ml') {
                $diametro = (int) round((float) str_replace(',', '.', $coincidencia[1]) * 10);
                $diametros[] = $diametro;
            }

            $clave = $diametro === null ? ($indices[$nombre] ?? null) : 'diametro-' . $diametro;
            if ($unidad === 'm3' && preg_match('/^perforacion-(?:en-|desde-|de-)?120-?cm(?:$|-)/', $nombre)) {
                $clave = self::PERFORACION_M3;
            } elseif ($clave === self::PERFORACION_M3 && $unidad !== 'm3') {
                $clave = null;
            }
            $partidaAnterior = $fila instanceof PresupuestoPila ? 'perforacion' : 'otros';
            if ($clave === self::PERFORACION_M3 && ! isset($importados[$clave])) {
                $clavesAnteriores[$clave] = self::claveImportada($partidaAnterior, $nombre, Str::slug($fila->unidad), $apariciones);
            }
            if (($clave === self::ACERO_COLOCACION && $unidad !== 'kg') || ($clave === self::CONCRETO_COLOCACION && $unidad !== 'm3')) {
                $clave = null;
            }
            if ($clave === null && isset($nombresAnteriores[$nombre]) && ! isset($importados[$nombresAnteriores[$nombre]])) {
                $clave = $nombresAnteriores[$nombre];
                $catalogo[$clave] = ['partida' => 'colocacion', 'concepto' => $fila->concepto, 'unidad' => $fila->unidad];
            }
            if (in_array($clave, [self::ACERO_COLOCACION, self::CONCRETO_COLOCACION], true) && ! isset($importados[$clave])) {
                // Un renglón de colocación con la unidad correcta puede tener ajustes guardados como importado adicional.
                $clavesAnteriores[$clave] = self::claveImportada('colocacion', $nombre, $unidad, $apariciones);
            }
            if ($clave === null || isset($importados[$clave])) {
                // La exportación puede dar a una fila el encabezado del bloque anterior.
                $partida = $catalogo[$clave]['partida'] ?? ($diametro !== null ? 'perforacion' : $partidaAnterior);
                if (isset($nombresAnteriores[$nombre]) || in_array($nombre, ['colocacion-de-acero', 'colocacion-de-concreto'], true)) {
                    $partida = 'colocacion';
                }
                $clave = self::claveImportada($partida, $nombre, $unidad, $apariciones);
                $catalogo[$clave] = ['partida' => $partida, 'concepto' => $fila->concepto, 'unidad' => $fila->unidad];
            }

            $importados[$clave] = [
                'cantidad' => (float) $fila->cantidad,
                'precio_unitario' => (float) ($fila->precio_unitario ?? $fila->costo),
                'importe' => (float) ($fila->importe ?? $fila->total),
            ];
        }

        $diametros = array_unique($diametros);
        sort($diametros, SORT_NUMERIC);
        $perforacion = [];
        foreach ($diametros as $diametro) {
            $perforacion['diametro-' . $diametro] = [
                'partida' => 'perforacion',
                'concepto' => 'Perforación en ' . ($diametro / 10) . ' cm',
                'unidad' => 'ML',
                'diametro' => $diametro / 1000,
            ];
        }

        return collect($perforacion + $catalogo)->map(function (array $concepto, string $clave) use ($importados, $ajustes, $clavesAnteriores, $minimoTraslado) {
            $base = $importados[$clave] ?? [
                'cantidad' => 0,
                'precio_unitario' => $clave === 'venta-215' ? round($minimoTraslado ?? 0, 2) : 0,
                'importe' => 0,
            ];
            $claveAnterior = $clavesAnteriores[$clave] ?? null;
            $ajuste = $ajustes[$clave] ?? $ajustes[$claveAnterior ?? ''] ?? [];
            $cantidad = (float) ($ajuste['cantidad'] ?? $base['cantidad']);
            $precio = (float) ($ajuste['precio_unitario'] ?? $base['precio_unitario']);
            $ajustada = $cantidad !== (float) $base['cantidad'] || $precio !== (float) $base['precio_unitario'];

            return $concepto + [
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'importe' => $cantidad == 0 ? 0 : ($ajustada ? round($cantidad * $precio, 2) : $base['importe']),
                'base' => $base,
                'clave_anterior' => $claveAnterior,
            ];
        });
    }

    private static function claveImportada(string $partida, string $nombre, string $unidad, array &$apariciones): string
    {
        $identidad = hash('sha256', $partida . '|' . $nombre . '|' . $unidad);
        $apariciones[$identidad] = ($apariciones[$identidad] ?? 0) + 1;

        return 'importado-' . $identidad . '-' . $apariciones[$identidad];
    }

    public static function totalVenta(Collection $filas): float
    {
        return round($filas->sum('importe'), 2);
    }

    public static function calcular(Collection $filas, array $vol, array $apupi, Collection $costos, array $duracion): array
    {
        $minimos = $apupi['realista']['minimos'];
        $totalesVol = $vol['totales'];
        $metraje = $vol['porDiametro'];
        $venta = self::totalVenta($filas);
        $referencias = self::referencias($totalesVol, $costos, $duracion['realista']['semanas']);
        $porM3 = self::dividir($minimos['PERFORACION'], $totalesVol['perforacion']);
        $sugeridos = self::preciosSugeridos($minimos, $totalesVol, $costos, $duracion['realista']['semanas'], $venta);
        $referencias[self::PERFORACION_M3] = $totalesVol['perforacion'];
        $sugeridos[self::PERFORACION_M3] = $porM3;
        $preciosDiametro = [];
        foreach ($filas as $clave => $fila) {
            if (isset($fila['diametro'])) {
                $metros = $metraje[number_format($fila['diametro'], 3, '.', '')]['metros_lineales'] ?? 0;
                $referencias[$clave] = $metros;
                if ($metros > 0 && $porM3 !== null) {
                    $preciosDiametro[$clave] = pi() * $fila['diametro'] ** 2 / 4 * $porM3;
                }
            }
        }
        $sugeridos += $preciosDiametro;
        if ($filas['venta-194']['cantidad'] > 0 && $preciosDiametro !== []) {
            $sugeridos['venta-194'] = array_sum($preciosDiametro) / count($preciosDiametro);
        }
        if ($filas['venta-195']['cantidad'] > 0) {
            $sugeridos['venta-195'] = $costos['apupi-53']['precio_unitario'] * 1.8;
        }

        $filas = $filas->map(fn ($fila, $clave) => $fila + [
            'referencia' => $referencias[$clave] ?? null,
            'sugerido' => isset($sugeridos[$clave]) ? round($sugeridos[$clave], 2) : null,
        ]);
        $grupos = $filas->groupBy('partida', preserveKeys: true);
        $partidas = [];
        foreach (self::PARTIDAS + ['otros' => ['Otros conceptos importados', []]] as $clave => [$titulo, $partidasApupi]) {
            if (! $grupos->has($clave)) {
                continue;
            }
            $importe = round($grupos[$clave]->sum('importe'), 2);
            $minimosPartida = $minimos->only($partidasApupi);
            $minimo = $minimosPartida->isEmpty() || $minimosPartida->containsStrict(null) ? null : round($minimosPartida->sum(), 2);
            $diferencia = $minimo === null ? null : round($importe - $minimo, 2);
            $partidas[$clave] = [
                'titulo' => $titulo,
                'claves' => $grupos[$clave]->keys(),
                'importe' => $importe,
                'minimo' => $minimo,
                'diferencia' => $diferencia,
                'margen' => $importe > 0 && $diferencia !== null ? round($diferencia / $importe * 100, 2) : null,
            ];
        }

        return ['filas' => $filas, 'partidas' => $partidas, 'total' => $venta];
    }

    private static function referencias(array $vol, Collection $costos, ?float $semanas): array
    {
        $pilas = $vol['piezas'];
        $colocacionActiva = self::partidaActiva($costos, self::COLOCACION_APUPI);
        $aceroColocacion = $colocacionActiva ? $vol['acero'] : null;
        return [
            'venta-197' => $semanas,
            self::ACERO_COLOCACION => $aceroColocacion,
            self::CONCRETO_COLOCACION => $colocacionActiva ? $vol['concreto'] : null,
            'venta-201' => $colocacionActiva ? $pilas : null,
            'venta-203' => $vol['acero'],
            'venta-204' => $costos['apupi-110']['cantidad'],
            'venta-206' => self::partidaActiva($costos, 'BENTONITA') ? $vol['bentonita'] : null,
            'venta-207' => $vol['bentonita'],
            'venta-209' => $vol['concreto'],
            'venta-210' => $costos['apupi-118']['cantidad'] > 0 ? $aceroColocacion : null,
            'venta-212' => $vol['perforacion'],
            'venta-213' => $costos->only(['apupi-131', 'apupi-132', 'apupi-133', 'apupi-134', 'apupi-135', 'apupi-136'])->sum('cantidad'),
        ];
    }

    private static function preciosSugeridos(Collection $minimos, array $vol, Collection $costos, ?float $semanas, float $venta): array
    {
        $colocacion = $minimos[self::COLOCACION_APUPI];
        $mitadColocacion = $colocacion === null ? null : $colocacion / 2;

        return [
            'venta-197' => self::dividir($minimos['TOPOGRAFIA'], $semanas),
            self::ACERO_COLOCACION => self::dividir($mitadColocacion, $vol['acero']),
            self::CONCRETO_COLOCACION => self::dividir($mitadColocacion, $vol['concreto']),
            'venta-201' => self::dividir($colocacion, $vol['piezas']),
            'venta-203' => self::dividir($costos->only(['apupi-106', 'apupi-107'])->sum('importes.realista') * 1.33, $vol['acero']),
            'venta-204' => self::dividir($costos->only(['apupi-110', 'apupi-111', 'apupi-112'])->sum('importes.realista') * 1.35, $vol['piezas']),
            'venta-206' => self::dividir($minimos['BENTONITA'], $vol['bentonita']),
            'venta-207' => self::dividir($minimos['RETIRO DE LODOS BENTONITICOS'], $vol['bentonita']),
            'venta-209' => self::dividir($minimos['SUMINISTRO DE CONCRETO'], $vol['concreto']),
            'venta-210' => self::dividir($minimos['SUMINISTRO DE ACERO'], $vol['acero']),
            'venta-212' => self::dividir($minimos['RETIRO DE MATERIALES DE PERFORACION'], $vol['perforacion']),
            'venta-213' => self::dividir($minimos['DESCABECE'], $vol['piezas']),
            'venta-215' => $minimos[PresupuestoDetalle::PARTIDA_TRASLADO],
            'venta-217' => $minimos[PlantillaApupi::FIANZAS],
            'venta-218' => $venta * 0.056,
        ];
    }

    private static function partidaActiva(Collection $costos, string $partida): bool
    {
        return $costos->contains(fn ($fila) => $fila['detalle']->partidaApupi() === $partida
            && $fila['cantidad'] > 0 && ($fila['ajustado'] || $fila['importes']['realista'] > 0));
    }

    private static function dividir(?float $importe, ?float $cantidad): ?float
    {
        return $importe !== null && $cantidad > 0 ? $importe / $cantidad : null;
    }
}
