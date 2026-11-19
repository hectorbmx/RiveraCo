@php
    $nombresEscenario = ['realista' => 'Realista', 'optimista' => 'Optimista', 'pesimista' => 'Pesimista'];
    $etiquetasPrevio = ['administracion' => 'Administración', 'imss' => 'IMSS', 'comisiones' => 'Comisiones', 'margen_objetivo' => 'Margen objetivo'];
@endphp
<div class="space-y-5 p-5">
    @if($puedeEditarPrevio)
        <form method="POST" action="{{ route('presupuesto.previo.update', $presupuesto) }}" x-ref="formularioPrevio" data-previo-config
              x-init="$el.dataset.configuracionGuardada = new URLSearchParams(new FormData($el)).toString()"
              @change="guardarPrevio($el)" @submit.prevent="guardarPrevio($el)" class="space-y-4">
            @csrf
            @method('PATCH')
    @endif
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h2 class="text-lg font-semibold text-slate-800">Previo de obra</h2>
        <label class="block text-sm font-medium text-slate-700">Escenario APUPI
            <select name="escenario" x-model="escenarioPrevio" class="ml-2 rounded-lg border-slate-300 text-sm">
                @foreach($nombresEscenario as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected($previo['escenario'] === $clave)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </label>
    </div>
    @if($puedeEditarPrevio)
            @if($errors->previoPresupuesto->any())
                <p role="alert" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">{{ $errors->previoPresupuesto->first() }}</p>
            @endif
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($etiquetasPrevio as $clave => $etiqueta)
                    <label class="block text-sm font-medium text-slate-700">{{ $etiqueta }} (%)
                        <input name="porcentajes[{{ $clave }}]" type="number" min="0" max="99.99" step="0.01"
                               value="{{ old('porcentajes.' . $clave, $previo['porcentajes'][$clave]) }}"
                               @required($clave !== 'margen_objetivo') class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    </label>
                @endforeach
            </div>
            <fieldset x-show="previo.gasto_real.obras > 0" class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-3">
                <legend class="px-2 text-sm font-semibold">Por gastar al cierre</legend>
                @foreach($nombresEscenario as $clave => $etiqueta)
                    <label class="block text-sm font-medium text-slate-700">{{ $etiqueta }}
                        <input name="pendiente[{{ $clave }}]" type="number" min="0" max="9999999999999.99" step="0.01"
                               value="{{ old('pendiente.' . $clave, $previo['pendiente'][$clave] ?? null) }}"
                               class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    </label>
                @endforeach
                <p class="text-xs text-slate-500 sm:col-span-3">Captura el costo pendiente de cada escenario para usar la proyección al cierre en la simulación.</p>
            </fieldset>
            <details class="rounded-xl border border-slate-200 p-3">
                <summary class="cursor-pointer text-sm font-semibold">Periodo, personal y firmas</summary>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach(\App\Support\PrevioPresupuesto::PERSONAL as $clave => $etiqueta)
                        <label class="block text-sm font-medium text-slate-700">{{ $etiqueta }}
                            <input name="personal[{{ $clave }}]" maxlength="180" value="{{ old('personal.' . $clave, $previo['personal'][$clave] ?? null) }}"
                                   class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        </label>
                    @endforeach
                    <label class="block text-sm font-medium text-slate-700 sm:col-span-2">Nombres para firma
                        <textarea name="firmas" rows="2" maxlength="1000" class="mt-1 w-full rounded-lg border-slate-300 text-sm">{{ old('firmas', $previo['firmas']) }}</textarea>
                    </label>
                </div>
            </details>
            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-[#0B265A] hover:bg-slate-50">Guardar parámetros</button>
        </form>
    @else
        <dl class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
            @foreach($etiquetasPrevio as $clave => $etiqueta)
                <div><dt class="text-slate-600">{{ $etiqueta }}</dt><dd class="font-semibold">{{ $previo['porcentajes'][$clave] === null ? '—' : (float) $previo['porcentajes'][$clave] . ' %' }}</dd></div>
            @endforeach
        </dl>
        @if(collect($previo['personal'])->filter()->isNotEmpty() || $previo['firmas'])
            <dl class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                @foreach(\App\Support\PrevioPresupuesto::PERSONAL as $clave => $etiqueta)
                    @if($previo['personal'][$clave] ?? null)
                        <div><dt class="text-slate-600">{{ $etiqueta }}</dt><dd>{{ $previo['personal'][$clave] }}</dd></div>
                    @endif
                @endforeach
                @if($previo['firmas'])
                    <div><dt class="text-slate-600">Nombres para firma</dt><dd class="whitespace-pre-line">{{ $previo['firmas'] }}</dd></div>
                @endif
            </dl>
        @endif
    @endif

    @include('presupuesto.partials.previo-desglose')

    <dl class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl bg-slate-100 p-3">
            <dt class="text-sm text-slate-600">Gasto real registrado</dt>
            <dd class="text-lg font-semibold text-slate-900" x-text="previo.gasto_real.importe == null ? 'Obra sin vincular' : formatoImporte(previo.gasto_real.importe)"></dd>
            <dd class="text-xs text-slate-600" x-show="previo.gasto_real.obras > 0">Pagos a proveedores registrados, sin IVA.</dd>
            <dd class="text-xs text-amber-800" x-show="previo.gasto_real.sin_base > 0" x-text="previo.gasto_real.sin_base + ' pagos sin datos suficientes para el cálculo.'"></dd>
        </div>
        <div class="rounded-xl bg-sky-50 p-3">
            <dt class="text-sm text-sky-900">Venta objetivo sin IVA</dt>
            <dd class="text-lg font-semibold text-[#0B265A]" x-text="previo.escenarios[escenarioPrevio].venta_objetivo == null ? '—' : formatoImporte(previo.escenarios[escenarioPrevio].venta_objetivo)"></dd>
            <dd class="text-xs text-sky-800" x-show="previo.escenarios[escenarioPrevio].diferencia != null"
                x-text="'Diferencia respecto de Propuesta 1 ' + formatoImporte(previo.escenarios[escenarioPrevio].diferencia)"></dd>
        </div>
    </dl>

    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full whitespace-nowrap text-sm">
            <caption class="sr-only">Comparativo de escenarios APUPI</caption>
            <thead class="bg-[#0B265A] text-white">
                <tr><th class="p-3 text-left">Escenario</th><th class="p-3 text-right">Jornales</th><th class="p-3 text-right">Costo directo</th><th class="p-3 text-right">Costo al cierre</th><th class="p-3 text-right">Utilidad estimada</th><th class="p-3 text-right">Margen</th><th class="p-3 text-right">Venta objetivo</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($nombresEscenario as $clave => $etiqueta)
                    <tr :class="escenarioPrevio === @js($clave) ? 'bg-sky-50 font-semibold' : ''">
                        <th scope="row" class="p-3 text-left">{{ $etiqueta }}</th>
                        <td class="p-3 text-right" x-text="previo.escenarios[@js($clave)].jornales ?? '—'"></td>
                        @foreach(['directo', 'cierre', 'utilidad', 'margen', 'venta_objetivo'] as $columna)
                            <td class="p-3 text-right tabular-nums"
                                x-text="previo.escenarios[@js($clave)][@js($columna)] == null ? '—' : (@js($columna) === 'margen' ? previo.escenarios[@js($clave)].margen + ' %' : formatoImporte(previo.escenarios[@js($clave)][@js($columna)]))"></td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-slate-600">La utilidad usa el costo directo de APUPI o el costo al cierre cuando se captura lo que falta por gastar. El margen se mide sobre la venta sin IVA.</p>

    @if($puedeEditarPrevio)
        <form method="POST" action="{{ route('presupuesto.previo.aplicar', $presupuesto) }}" @submit.prevent="aplicarPrevio($el)" class="flex flex-wrap items-center gap-3">
            @csrf
            <input type="hidden" name="escenario">
            <input type="hidden" name="objetivo">
            <button type="submit" :disabled="!(previo.escenarios[escenarioPrevio].venta_objetivo > 0 && previo.venta_actual > 0)"
                    class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Aplicar a Propuesta 1</button>
            <p class="text-xs text-slate-600">Ajusta los precios unitarios en proporción. El total puede variar por el redondeo a centavos.</p>
        </form>
    @endif
</div>
