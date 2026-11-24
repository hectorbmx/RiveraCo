@php
    $partidasApupi = $filasApupi->groupBy(fn ($fila) => $fila['detalle']->partidaApupi(), preserveKeys: true);
    $camposApupi = ['cantidad' => 'Cantidad', 'precio_unitario' => 'Costo unitario'];
    $escenariosApupi = \App\Models\Presupuesto::ESCENARIOS_APUPI;
    $sugerenciasCantidad = \App\Support\PlantillaApupi::sugerenciasCantidad($volumetria, $duracionApupi);
    $claveRespaldoApupi = implode(':', ['apupi-pendiente', auth()->id(), $presupuesto->id, $versionGuardada?->id, $presupuesto->version_origen_borrador_id]);
@endphp
<div x-data="{
    resumen: @js($resumenApupi),
    plegadas: {},
    claveRespaldo: @js($claveRespaldoApupi),
    pendientesApupi: {},
    respaldoDisponible: true,
    errorApupi: '',
    init() {
        this.pendientesApupi = this.cargarRespaldo();
        for (const input of this.camposApupi()) {
            const clave = this.claveCampo(input);
            if (!Object.hasOwn(this.pendientesApupi, clave)) continue;
            const valor = this.pendientesApupi[clave];
            if (this.mismoValor(valor, input.dataset.guardado)) {
                this.confirmarCampo(input, valor);
            } else {
                input.value = valor;
            }
        }
    },
    cargarRespaldo() {
        try {
            return JSON.parse(localStorage.getItem(this.claveRespaldo) || '{}') || {};
        } catch {
            this.respaldoDisponible = false;
            return {};
        }
    },
    camposApupi() {
        return [...this.$root.querySelectorAll('input[data-url][data-guardado]')];
    },
    claveCampo(input) {
        return new URL(input.dataset.url, location.href).pathname + ':' + input.name;
    },
    actualizarRespaldo(clave, valor, confirmado = false) {
        if (confirmado && this.pendientesApupi[clave] !== valor) return;
        this.pendientesApupi = { ...this.pendientesApupi };
        if (confirmado) delete this.pendientesApupi[clave];
        else this.pendientesApupi[clave] = valor;
        if (!this.respaldoDisponible) return;
        try {
            const respaldo = this.cargarRespaldo();
            if (!this.respaldoDisponible) return;
            if (!confirmado) respaldo[clave] = valor;
            else if (respaldo[clave] === valor) delete respaldo[clave];
            if (Object.keys(respaldo).length) localStorage.setItem(this.claveRespaldo, JSON.stringify(respaldo));
            else localStorage.removeItem(this.claveRespaldo);
        } catch {
            this.respaldoDisponible = false;
        }
    },
    respaldarCampo(input) {
        this.actualizarRespaldo(this.claveCampo(input), input.value);
    },
    confirmarCampo(input, valor) {
        this.actualizarRespaldo(this.claveCampo(input), valor, true);
        if (!Object.keys(this.pendientesApupi).length) this.errorApupi = '';
    },
    irAlPendiente() {
        const input = this.camposApupi().find(campo => Object.hasOwn(this.pendientesApupi, this.claveCampo(campo)));
        if (!input) return;
        this.plegadas = {};
        this.$nextTick(() => input.focus());
    },
    reenviarApupi() {
        for (const input of this.camposApupi()) {
            if (Object.hasOwn(this.pendientesApupi, this.claveCampo(input))) this.guardar(input);
        }
    },
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
    mismoValor(valor, guardado) {
        return valor === guardado || (valor !== '' && guardado !== '' && Number(valor) === Number(guardado));
    },
    sincronizarCampos() {
        for (const input of this.$root.querySelectorAll('[data-precio-apupi], [data-renglon-propuesta]')) {
            const valor = input.dataset.renglonPropuesta
                ? this.resumen.propuesta.filas[input.dataset.renglonPropuesta]?.[input.name]
                : this.resumen.precios[input.dataset.precioApupi];
            if (valor === undefined || input === document.activeElement || Object.hasOwn(this.pendientesApupi, this.claveCampo(input))
                || !this.mismoValor(input.value, input.dataset.guardado)) continue;
            input.value = valor ?? '';
            input.dataset.guardado = input.value;
        }
    },
    guardar(input) {
        const valor = input.value;
        const valido = input.validity.valid;
        this.respaldarCampo(input);
        this.cola = this.cola.then(async () => {
            if (!valido) {
                input.setAttribute('aria-invalid', 'true');
                this.errorApupi = 'Captura un número válido para guardar.';
                this.avisar(this.errorApupi, true);
                return;
            }
            if (this.mismoValor(valor, input.dataset.guardado)) {
                this.confirmarCampo(input, valor);
                input.removeAttribute('aria-invalid');
                this.sincronizarCampos();
                return;
            }
            try {
                const respuesta = await fetch(new URL(input.dataset.url, location.href).pathname, {
                    method: 'PATCH',
                    keepalive: true,
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ [input.name]: valor === '' ? null : valor })
                });
                const datos = await respuesta.json().catch(() => null);
                if (!respuesta.ok || !datos?.propuesta) {
                    const detalle = respuesta.status === 419 || respuesta.status === 401 || respuesta.redirected
                        ? 'La sesión expiró. Vuelve a iniciar sesión y reintenta el guardado.'
                        : 'El servidor no confirmó el guardado (HTTP ' + respuesta.status + ').';
                    throw new Error(datos?.errors?.[input.name]?.[0] || detalle);
                }
                this.resumen = datos;
                this.presentacion = this.resumen.presentacion;
                this.previo = this.resumen.previo;
                this.relacionGastos = this.resumen.relacion_gastos;
                input.dataset.guardado = valor;
                this.confirmarCampo(input, valor);
                input.removeAttribute('aria-invalid');
                this.sincronizarCampos();
                this.avisar('Guardado');
            } catch (error) {
                input.setAttribute('aria-invalid', 'true');
                this.errorApupi = error instanceof TypeError
                    ? 'No se pudo conectar con el servidor.'
                    : error.message;
                this.avisar(this.errorApupi, true);
            }
        });
    }
}" @input="if ($event.target.matches('input[data-url][data-guardado]')) respaldarCampo($event.target)"
   @beforeunload.window="if (!respaldoDisponible && Object.keys(pendientesApupi).length) { $event.preventDefault(); $event.returnValue = ''; }">
    <p class="px-5 pb-3 text-sm text-slate-600">Los cambios se guardan al salir del campo.</p>
    <div x-show="Object.keys(pendientesApupi).length" x-cloak
         class="sticky top-0 z-10 mx-5 mb-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950">
        <div role="status">
            <p x-text="respaldoDisponible ? 'Cambios pendientes respaldados en este navegador.' : 'Cambios pendientes. El respaldo del navegador no está disponible.'"></p>
            <p x-show="errorApupi" x-text="errorApupi"></p>
        </div>
        <div class="flex gap-3 font-semibold">
            <button type="button" class="underline" @click="irAlPendiente()">Ir al primer campo</button>
            <button type="button" class="underline" @click="reenviarApupi()">Reintentar guardado</button>
        </div>
    </div>

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

