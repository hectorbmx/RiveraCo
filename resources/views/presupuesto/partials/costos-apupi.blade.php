@php
    $partidasApupi = $filasApupi->groupBy(fn ($fila) => $fila['detalle']->partidaApupi(), preserveKeys: true);
    $camposApupi = ['cantidad' => 'Cantidad', 'precio_unitario' => 'Costo unitario'];
    $escenariosApupi = \App\Models\Presupuesto::ESCENARIOS_APUPI;
    $sugerenciasCantidad = \App\Support\PlantillaApupi::sugerenciasCantidad($volumetria, $duracionApupi);
@endphp
<div x-data="{
    resumen: @js($resumenApupi),
    plegadas: {},
    moneda(valor) {
        return valor == null ? '—' : '$' + Number(valor).toLocaleString('en-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    porcentaje(valor) {
        return valor == null ? '—' : Number(valor).toLocaleString('en-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %';
    },
    numero(valor) {
        return valor == null ? '—' : Number(valor).toLocaleString('en-MX', { maximumFractionDigits: 3 });
    },
    alternarPartida(clave) {
        this.plegadas[clave] = !this.plegadas[clave];
    },
    sincronizarPrecios() {
        for (const costo of this.$root.querySelectorAll('[data-precio-apupi], [data-precio-propuesta]')) {
            const precio = costo.dataset.precioPropuesta
                ? this.resumen.propuesta.filas[costo.dataset.precioPropuesta]?.precio_unitario
                : this.resumen.precios[costo.dataset.precioApupi];
            if (precio == null || costo === document.activeElement || Number(costo.value) !== Number(costo.dataset.guardado)) continue;
            costo.value = precio;
            costo.dataset.guardado = costo.value;
        }
    },
    guardar(input) {
        const valor = input.value;
        const valido = input.validity.valid;
        this.cola = this.cola.then(async () => {
            if (!valido) {
                input.setAttribute('aria-invalid', 'true');
                this.avisar('Captura un número válido para guardar.', true);
                return;
            }
            if (Number(valor) === Number(input.dataset.guardado)) {
                input.removeAttribute('aria-invalid');
                this.sincronizarPrecios();
                return;
            }
            try {
                const respuesta = await fetch(input.dataset.url, {
                    method: 'PATCH',
                    keepalive: true,
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ [input.name]: valor })
                });
                if (!respuesta.ok) {
                    const datos = await respuesta.json().catch(() => ({}));
                    throw new Error(datos.errors?.[input.name]?.[0] || 'No se pudo guardar. Vuelve a salir del campo para reintentar.');
                }
                this.resumen = await respuesta.json();
                this.presentacion = this.resumen.presentacion;
                this.previo = this.resumen.previo;
                this.relacionGastos = this.resumen.relacion_gastos;
                input.dataset.guardado = valor;
                input.removeAttribute('aria-invalid');
                this.sincronizarPrecios();
                this.avisar('Guardado');
            } catch (error) {
                input.setAttribute('aria-invalid', 'true');
                this.avisar(error.message || 'No se pudo guardar.', true);
            }
        });
    }
}">
    <p class="px-5 pb-3 text-sm text-slate-600">Los cambios se guardan al salir del campo.</p>

    <div class="overflow-x-auto" role="region" aria-label="Costos por partida de APUPI" tabindex="0">
        <table class="w-full text-sm">
            <caption class="sr-only">Costos de APUPI por partida y escenario</caption>
            <thead class="bg-[#0B265A] text-white">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left">Concepto</th>
                    <th scope="col" class="px-4 py-3 text-center">Unidad</th>
                    @foreach($camposApupi as $etiqueta)
                        <th scope="col" class="px-4 py-3 text-right">{{ $etiqueta }}</th>
                    @endforeach
                    @foreach($escenariosApupi as $escenario)
                        <th scope="col" class="px-4 py-3 text-right">{{ ucfirst($escenario) }}</th>
                    @endforeach
                </tr>
            </thead>
            @foreach($partidasApupi as $partida => $filas)
                @php
                    $nombrePartida = \Illuminate\Support\Str::of($filas->first()['detalle']->partida ?: 'Sin partida')->squish()->upper()->value();
                    $nombrePartida = match ($partida) {
                        'PERFORACION' => 'PERFORACIÓN',
                        'GRUA PARA COLOCAR ACERO DE REFUERZO Y CONCRETO' => 'GRÚA PARA COLOCAR ACERO DE REFUERZO Y CONCRETO',
                        'RETIRO DE LODOS BENTONITICOS' => 'RETIRO DE LODOS BENTONÍTICOS',
                        'TOPOGRAFIA' => 'TOPOGRAFÍA',
                        'RETIRO DE MATERIALES DE PERFORACION' => 'RETIRO DE MATERIALES DE PERFORACIÓN',
                        default => $nombrePartida,
                    };
                    $colorPartida = match ($partida) {
                        'TRASLADO DE PERFORADORA' => '#DDEBF7',
                        'PERFORACION' => '#FCE4D6',
                        default => '#E2E8F0',
                    };
                    $esTraslado = $partida === \App\Models\PresupuestoDetalle::PARTIDA_TRASLADO;
                    $tieneIndirecto = $resumenApupi['indirectos']->has($partida);
                    $plegable = 'costo-' . $partida;
                @endphp
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <th scope="rowgroup" colspan="7" class="px-4 py-2.5 text-left font-bold tracking-wide text-slate-900" style="background-color: {{ $colorPartida }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span>{{ $nombrePartida }}</span>
                                @include('presupuesto.partials.plegar-apupi', ['nombrePlegable' => $nombrePartida])
                            </div>
                        </th>
                    </tr>
                    @foreach($filas as $idFila => $fila)
                        @if($idFila === 'apupi-24')
                            <tr x-show="!plegadas[@js($plegable)]" class="bg-slate-50"><th scope="row" colspan="7" class="px-4 py-2 text-left text-xs font-semibold text-slate-700">Renta de perforadora</th></tr>
                        @endif
                        @php
                            $detalle = $fila['detalle'];
                            $url = route('presupuesto.apupi.update', [$presupuesto, $idFila]);
                        @endphp
                        <tr x-show="!plegadas[@js($plegable)]" class="hover:bg-slate-50">
                            <th scope="row" class="px-4 py-3 text-left font-medium text-slate-800">{{ $detalle->concepto }}</th>
                            <td class="whitespace-nowrap px-4 py-3 text-center text-slate-500">{{ $detalle->unidad }}</td>
                            @foreach($camposApupi as $campo => $etiqueta)
                                <td class="whitespace-nowrap px-4 py-2 text-right tabular-nums">
                                    @if($campo === 'precio_unitario') <span aria-hidden="true">$</span> @endif
                                    <input type="number" name="{{ $campo }}" required min="0" step="0.01" max="9999999999999.99"
                                           value="{{ $fila[$campo] }}" data-guardado="{{ $fila[$campo] }}" data-url="{{ $url }}"
                                           @if($campo === 'precio_unitario') data-precio-apupi="{{ $idFila }}" @endif
                                           aria-label="{{ $etiqueta }} · {{ $detalle->concepto }}" @blur="guardar($el)" @keydown.enter.prevent="$el.blur()"
                                           class="w-28 rounded-md border-slate-300 px-2 py-1 text-right text-sm">
                                    @if($campo === 'cantidad' && ($sugerenciasCantidad[$idFila] ?? 0) > 0)
                                        <span class="mt-1 block text-xs text-slate-500">Base: {{ number_format($sugerenciasCantidad[$idFila], 2) }}</span>
                                    @endif
                                </td>
                            @endforeach
                            @foreach($escenariosApupi as $escenario)
                                @php
                                    $importe = $fila['importes'][$escenario];
                                @endphp
                                <td x-text="moneda(resumen.filas[@js((string) $idFila)]?.['{{ $escenario }}'])"
                                    class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $escenario === 'optimista' ? 'text-emerald-700' : ($escenario === 'pesimista' ? 'text-red-700' : 'text-slate-800') }}">
                                    {{ $importe === null ? '—' : '$' . number_format($importe, 2) }}
                                </td>
                            @endforeach
                        </tr>
                        @if($idFila === 'apupi-43' && $volumetria['filas']->isNotEmpty())
                            <tr x-show="!plegadas[@js($plegable)]" class="bg-slate-50 text-xs text-slate-600">
                                <th scope="row" class="px-4 py-2 text-left font-medium">Volumen de perforación (referencia VOL)</th>
                                <td class="px-4 py-2 text-center">m³</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ number_format($volumetria['totales']['perforacion'], 2) }}</td>
                                <td colspan="4"></td>
                            </tr>
                        @endif
                    @endforeach
                    <tr @if($tieneIndirecto) x-show="!plegadas[@js($plegable)]" @endif class="bg-slate-50">
                        <th scope="row" colspan="4" class="px-4 py-2.5 text-right font-semibold text-slate-700">{{ $tieneIndirecto ? 'Subtotal de la partida' : 'Total de la partida' }}</th>
                        @foreach($escenariosApupi as $escenario)
                            @php
                                $subtotal = $resumenApupi['subtotales'][$partida][$escenario];
                            @endphp
                            <td x-text="moneda(resumen.subtotales[@js($partida)]?.['{{ $escenario }}'])"
                                class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums">{{ $subtotal === null ? '—' : '$' . number_format($subtotal, 2) }}</td>
                        @endforeach
                    </tr>
                    @if($tieneIndirecto)
                        @php
                            $porcentaje = $resumenApupi['porcentajes_indirectos'][$partida];
                            $claveIndirecto = \Illuminate\Support\Str::slug($partida);
                            $idIndirecto = 'indirecto-apupi-' . $claveIndirecto;
                        @endphp
                        <tr x-show="!plegadas[@js($plegable)]" class="bg-slate-50">
                            <th scope="row" colspan="3" class="px-4 py-2.5 text-right font-semibold text-slate-700">
                                <label for="{{ $idIndirecto }}">Indirecto y utilidad</label>
                            </th>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                <input id="{{ $idIndirecto }}" name="porcentaje"
                                       type="number" required min="0" max="999.99" step="0.01"
                                       value="{{ $porcentaje }}" data-guardado="{{ $porcentaje }}"
                                       data-url="{{ route('presupuesto.apupi.indirecto.update', [$presupuesto, $claveIndirecto]) }}"
                                       aria-label="Indirecto y utilidad · {{ $nombrePartida }}" @blur="guardar($el)" @keydown.enter.prevent="$el.blur()"
                                       class="w-24 rounded-md border-slate-300 px-2 py-1 text-right text-sm"> %
                            </td>
                            @foreach($escenariosApupi as $escenario)
                                @php
                                    $importe = $resumenApupi['indirectos'][$partida][$escenario];
                                @endphp
                                <td x-text="moneda(resumen.indirectos[@js($partida)]?.['{{ $escenario }}'])"
                                    class="whitespace-nowrap px-4 py-2.5 text-right font-semibold tabular-nums">{{ $importe === null ? '—' : '$' . number_format($importe, 2) }}</td>
                            @endforeach
                        </tr>
                        <tr style="background-color: {{ $colorPartida }}">
                            <th scope="row" colspan="4" class="px-4 py-2.5 text-right font-bold text-[#0B265A]">{{ $esTraslado ? 'Total de traslado' : 'Total de la partida' }}</th>
                            @foreach($escenariosApupi as $escenario)
                                @php
                                    $importe = $resumenApupi['totales_partida'][$partida][$escenario];
                                @endphp
                                <td x-text="moneda(resumen.totales_partida[@js($partida)]?.['{{ $escenario }}'])"
                                    class="whitespace-nowrap px-4 py-2.5 text-right font-bold tabular-nums text-[#0B265A]">{{ $importe === null ? '—' : '$' . number_format($importe, 2) }}</td>
                            @endforeach
                        </tr>
                    @endif
                </tbody>
            @endforeach
            <tfoot class="bg-slate-100">
                <tr>
                    <th scope="row" colspan="4" class="px-4 py-3 text-right font-bold text-[#0B265A]">Total de costos directos APUPI</th>
                    @foreach($escenariosApupi as $escenario)
                        @php
                            $total = $resumenApupi['total'][$escenario];
                        @endphp
                        <td x-text="moneda(resumen.total['{{ $escenario }}'])"
                            class="whitespace-nowrap px-4 py-3 text-right font-bold tabular-nums text-[#0B265A]">{{ $total === null ? '—' : '$' . number_format($total, 2) }}</td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
    @include('presupuesto.partials.resumen-realista-apupi')
    @include('presupuesto.partials.propuesta-apupi')
</div>

