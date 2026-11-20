@php
    $propuesta = $resumenApupi['propuesta'];
    $equipo = \App\Models\Presupuesto::EQUIPOS_PROPUESTOS[$presupuesto->equipo_propuesto] ?? 'Equipo por definir';
@endphp

<section class="space-y-4 border-t border-slate-200 p-5" aria-labelledby="propuesta-apupi">
    <h2 id="propuesta-apupi" tabindex="-1" class="scroll-mt-20 border border-slate-400 px-3 py-2 text-sm font-bold uppercase text-slate-900" style="background-color: #DDEBF7">
        Propuesta 1: {{ $equipo }}
    </h2>
    <div class="overflow-x-auto" role="region" aria-label="Renglones de venta de la propuesta" tabindex="0">
        <table class="w-full border-collapse text-sm">
            <caption class="sr-only">Catálogo y precios de venta de Propuesta 1</caption>
            <thead class="bg-[#0B265A] text-white">
                <tr>
                    <th scope="col" class="border border-slate-400 px-3 py-2 text-left">Concepto</th>
                    <th scope="col" class="border border-slate-400 px-3 py-2 text-center">Unidad</th>
                    <th scope="col" class="border border-slate-400 px-3 py-2 text-right">Cantidad</th>
                    <th scope="col" class="border border-slate-400 px-3 py-2 text-right">P.U. de venta</th>
                    <th scope="col" class="border border-slate-400 px-3 py-2 text-right">Importe</th>
                    <th scope="col" class="border border-slate-400 px-3 py-2 text-right">P.U. sugerido</th>
                    <th scope="col" class="border border-slate-400 px-3 py-2 text-right">Dif. $</th>
                    <th scope="col" class="border border-slate-400 px-3 py-2 text-right">Dif. %</th>
                </tr>
            </thead>
            @foreach($propuesta['partidas'] as $clavePartida => $partida)
                @php
                    $plegable = 'propuesta-' . $clavePartida;
                @endphp
                <tbody>
                    <tr>
                        <th scope="rowgroup" colspan="8" class="border border-slate-400 bg-slate-100 px-3 py-2 text-left font-semibold text-slate-900">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span>{{ $partida['titulo'] }}</span>
                                @include('presupuesto.partials.plegar-apupi', ['nombrePlegable' => $partida['titulo']])
                            </div>
                        </th>
                    </tr>
                    @foreach($partida['claves'] as $clave)
                        @php
                            $fila = $propuesta['filas'][$clave];
                            $url = route('presupuesto.apupi.propuesta.update', [$presupuesto, $clave]);
                        @endphp
                        <tr x-show="!plegadas[@js($plegable)]">
                            <th scope="row" class="border border-slate-300 px-3 py-2 text-left font-medium">{{ $fila['concepto'] }}</th>
                            <td class="whitespace-nowrap border border-slate-300 px-3 py-2 text-center text-slate-600">{{ $fila['unidad'] }}</td>
                            @foreach(['cantidad' => 'Cantidad', 'precio_unitario' => 'P.U. de venta'] as $campo => $etiqueta)
                                <td class="whitespace-nowrap border border-slate-300 px-3 py-1.5 text-right tabular-nums">
                                    <input type="number" min="0" step="0.01" max="9999999999999.99" required name="{{ $campo }}"
                                           @if($campo === 'precio_unitario' && $clave === 'venta-215') data-precio-propuesta="{{ $clave }}" @endif
                                           value="{{ $fila[$campo] }}" data-guardado="{{ $fila[$campo] }}" data-url="{{ $url }}"
                                           aria-label="{{ $etiqueta }} · {{ $fila['concepto'] }}"
                                           @blur="guardar($el)" @keydown.enter.prevent="$el.blur()"
                                           class="w-28 rounded-md border-slate-300 px-2 py-1 text-right text-sm">
                                    @if($campo === 'cantidad')
                                        <span x-show="(resumen.propuesta.filas[@js($clave)]?.referencia ?? 0) > 0"
                                              x-text="'Base: ' + numero(resumen.propuesta.filas[@js($clave)]?.referencia)"
                                              class="mt-1 block text-xs text-slate-500" x-cloak></span>
                                    @endif
                                </td>
                            @endforeach
                            <td x-text="moneda(resumen.propuesta.filas[@js($clave)]?.importe)" class="whitespace-nowrap border border-slate-300 px-3 py-2 text-right font-semibold tabular-nums">
                                ${{ number_format($fila['importe'], 2) }}
                            </td>
                            <td x-text="moneda(resumen.propuesta.filas[@js($clave)]?.sugerido)" class="whitespace-nowrap border border-slate-300 px-3 py-2 text-right tabular-nums text-slate-600">
                                {{ $fila['sugerido'] === null ? '—' : '$' . number_format($fila['sugerido'], 2) }}
                            </td>
                            <td colspan="2" class="border border-slate-300"></td>
                        </tr>
                    @endforeach
                    <tr style="background-color: #E2EFDA">
                        <th scope="row" colspan="4" class="border border-slate-400 px-3 py-2 text-right font-semibold">Total de {{ mb_strtolower($partida['titulo']) }}</th>
                        <td x-text="moneda(resumen.propuesta.partidas[@js($clavePartida)]?.importe)" class="whitespace-nowrap border border-slate-400 px-3 py-2 text-right font-bold tabular-nums">
                            ${{ number_format($partida['importe'], 2) }}
                        </td>
                        <td class="whitespace-nowrap border border-slate-400 px-3 py-2 text-right text-xs">
                            <span class="text-slate-600">Mínimo: </span><span x-text="moneda(resumen.propuesta.partidas[@js($clavePartida)]?.minimo)">{{ $partida['minimo'] === null ? '—' : '$' . number_format($partida['minimo'], 2) }}</span>
                        </td>
                        <td x-text="moneda(resumen.propuesta.partidas[@js($clavePartida)]?.diferencia)" class="whitespace-nowrap border border-slate-400 px-3 py-2 text-right tabular-nums">
                            {{ $partida['diferencia'] === null ? '—' : '$' . number_format($partida['diferencia'], 2) }}
                        </td>
                        <td x-text="porcentaje(resumen.propuesta.partidas[@js($clavePartida)]?.margen)" class="whitespace-nowrap border border-slate-400 px-3 py-2 text-right tabular-nums">
                            {{ $partida['margen'] === null ? '—' : number_format($partida['margen'], 2) . ' %' }}
                        </td>
                    </tr>
                </tbody>
            @endforeach
            <tfoot class="bg-[#0B265A] text-white">
                <tr>
                    <th scope="row" colspan="4" class="border border-slate-400 px-3 py-3 text-right font-bold">Monto por presupuestar</th>
                    <td x-text="moneda(resumen.propuesta.total)" class="whitespace-nowrap border border-slate-400 px-3 py-3 text-right font-bold tabular-nums">
                        ${{ number_format($propuesta['total'], 2) }}
                    </td>
                    <td colspan="3" class="border border-slate-400"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
