@php
    $realista = $resumenApupi['realista'];
    $nombresPartidas = [
        'PERFORACION' => 'Perforación',
        'GRUA PARA COLOCAR ACERO DE REFUERZO Y CONCRETO' => 'Colocación de acero y concreto',
        'BENTONITA' => 'Lodos bentoníticos',
        'RETIRO DE LODOS BENTONITICOS' => 'Retiro de lodos bentoníticos',
        'HABILITADO DE ACERO DE REFUERZO' => 'Habilitado de acero',
        'SUMINISTRO DE ACERO' => 'Suministro de acero',
        'SUMINISTRO DE CONCRETO' => 'Suministro de concreto',
        'DESCABECE' => 'Descabece',
        'TOPOGRAFIA' => 'Topografía',
        'RETIRO DE MATERIALES DE PERFORACION' => 'Retiro de material',
        'TRASLADO DE PERFORADORA' => 'Traslados',
        \App\Support\PlantillaApupi::FIANZAS => 'Fianzas y seguro de responsabilidad civil',
    ] + array_combine($realista['minimos']->keys()->all(), $realista['minimos']->keys()->all());
    $columnas = [array_slice($nombresPartidas, 0, 6, true), array_slice($nombresPartidas, 6, null, true)];
    $camposVolumen = [
        'perforacion' => ['Perforación', 'm³', 2],
        'comision_ml' => ['Comisión', 'ML', 2],
        'bentonita' => ['Bentonita', 'm³', 2],
        'concreto' => ['Concreto', 'm³', 2],
        'piezas' => ['N.º de pilas', 'pzas', 0],
        'retiro' => ['Retiro de material', 'm³', 2],
        'acero' => ['Acero', 'kg', 2],
    ];
    $indicadores = [
        'total_directo' => 'Total costo directo',
        'total_minimo' => 'Total mínimo a cobrar',
    ];
    $hayVolumetria = $volumetria['filas']->isNotEmpty();
@endphp

<style>
    .resumen-realista-apupi .resumen-titulo { background: #A9D18E; border: 1px solid #64748b; padding: .45rem .65rem; }
    .resumen-realista-apupi .resumen-grid { display: grid; grid-template-columns: 1.2fr 1.1fr 1fr; gap: 1rem; align-items: start; }
    .resumen-realista-apupi table { width: 100%; border-collapse: collapse; background: white; }
    .resumen-realista-apupi .resumen-celdas th,
    .resumen-realista-apupi .resumen-celdas td { border: 1px solid #64748b; padding: .3rem .5rem; }
    .resumen-realista-apupi .resumen-partida { background: #f8fafc; text-align: center; font-weight: 600; }
    .resumen-realista-apupi .resumen-total { margin-top: .75rem; border: 2px solid #334155; color: #B91C1C; text-align: center; }
    .resumen-realista-apupi .resumen-total dt { border-bottom: 1px solid #64748b; padding: .28rem .5rem; }
    .resumen-realista-apupi .resumen-total dd { padding: .3rem .65rem; }
    @media (max-width: 1023px) {
        .resumen-realista-apupi .resumen-grid { grid-template-columns: 1fr; gap: .75rem; }
    }
</style>

<section class="resumen-realista-apupi border-t border-slate-200 p-5" aria-labelledby="resumen-realista-apupi">
    <h2 id="resumen-realista-apupi" class="resumen-titulo text-sm font-bold uppercase text-slate-900">
        Resumen de partidas y volúmenes <span class="whitespace-nowrap">(Escenario realista)</span>
    </h2>

    <p x-show="resumen.realista.pendiente" x-cloak class="mt-2 text-sm text-amber-800" role="status">
        Hay conceptos activos sin importe realista. Completa su duración para calcular el mínimo de esas partidas y el total.
    </p>

    <div class="resumen-grid mt-3 text-sm">
        @foreach($columnas as $partidas)
            <table class="resumen-celdas">
                <caption class="sr-only">Partidas y mínimos a cobrar</caption>
                @foreach($partidas as $partida => $nombre)
                    @php
                        $minimo = $realista['minimos'][$partida];
                    @endphp
                    <tbody>
                        <tr>
                            <th scope="rowgroup" colspan="2" class="resumen-partida text-slate-900">{{ $nombre }}</th>
                        </tr>
                        <tr>
                            <th scope="row" class="whitespace-nowrap text-left text-xs font-medium text-slate-700">Mínimo a cobrar</th>
                            <td x-text="moneda(resumen.realista.minimos[@js($partida)] || null)" class="whitespace-nowrap text-right tabular-nums">
                                {{ $minimo ? '$' . number_format($minimo, 2) : '—' }}
                            </td>
                        </tr>
                        @if($partida === 'PERFORACION')
                            <tr>
                                <th scope="row" class="whitespace-nowrap text-left text-xs font-medium text-slate-700">
                                    <abbr title="Mínimo de perforación por m³" class="no-underline">$ por m³</abbr>
                                </th>
                                <td x-text="moneda(resumen.realista.perforacion_por_m3)" class="whitespace-nowrap text-right tabular-nums">
                                    {{ $realista['perforacion_por_m3'] === null ? '—' : '$' . number_format($realista['perforacion_por_m3'], 2) }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @endforeach
            </table>
        @endforeach

        <div>
            <table class="resumen-celdas">
                <caption class="sr-only">Volúmenes de Pilas / VOL</caption>
                <tbody>
                    @foreach($camposVolumen as $campo => [$nombre, $unidad, $decimales])
                        <tr>
                            <th scope="row" class="text-right text-xs font-medium uppercase text-slate-700">{{ $nombre }}</th>
                            <td class="whitespace-nowrap text-right font-semibold tabular-nums">{{ $hayVolumetria ? number_format($volumetria['totales'][$campo], $decimales) . ' ' . $unidad : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if(! $hayVolumetria)
                <p class="mt-2 text-sm text-slate-600">Captura los tipos de pila en Pilas / VOL para obtener la volumetría y el mínimo por m³.</p>
            @endif

            <dl>
                @foreach($indicadores as $campo => $etiqueta)
                    <div class="resumen-total bg-white font-bold">
                        <dt>{{ $etiqueta }}</dt>
                        <dd x-text="moneda(resumen.realista['{{ $campo }}'])" class="text-lg tabular-nums">
                            {{ $realista[$campo] === null ? '—' : '$' . number_format($realista[$campo], 2) }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</section>

