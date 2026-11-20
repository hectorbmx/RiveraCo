@php
    $partidasPrevio = [
        'renta' => ['Renta de equipo', 'Total renta', '#1e345b'],
        'almacen' => ['Almacén Giralda', 'Total almacén', '#a9470c'],
        'gastos' => ['Gastos de la relación anexa', 'Total gastos', '#606970'],
        'imprevistos' => ['Imprevistos', 'Total imprevistos', '#794599'],
    ];
    $deduccionesPrevio = [
        'administracion' => ['Gasto administrativo', '#f6ba13'],
        'imss' => ['IMSS', '#0ab0d8'],
        'comisiones' => ['Comisiones', '#cbd5e1'],
    ];
@endphp
<div class="space-y-4" x-data="{
    get hoja() {
        return this.previo.escenarios[this.escenarioPrevio];
    },
    get diferenciaDirecta() {
        return this.hoja.directo == null ? null : this.previo.venta_actual - this.hoja.directo;
    },
    get utilidadDirecta() {
        if (this.diferenciaDirecta == null || this.previo.venta_actual <= 0) return null;
        const tasas = this.previo.porcentajes;
        return Math.round((this.diferenciaDirecta - this.previo.venta_actual * (Number(tasas.administracion) + Number(tasas.imss) + Number(tasas.comisiones)) / 100) * 100) / 100;
    },
    importeDesglose(valor) {
        return valor == null ? '—' : this.formatoImporte(valor);
    },
    porcentajeVenta(importe) {
        if (importe == null || this.previo.venta_actual <= 0) return '—';
        return (importe / this.previo.venta_actual).toLocaleString('es-MX', { style: 'percent', minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    get segmentosPrevio() {
        return [
            @foreach ($partidasPrevio as $clave => [$etiqueta, $total, $color])
                { etiqueta: @js($etiqueta), color: @js($color), importe: this.hoja[@js($clave)] },
            @endforeach
            @foreach ($deduccionesPrevio as $clave => [$etiqueta, $color])
                { etiqueta: @js($etiqueta), color: @js($color), importe: this.hoja[@js($clave)] },
            @endforeach
            { etiqueta: this.utilidadDirecta < 0 ? 'Pérdida esperada' : 'Utilidad esperada', color: this.utilidadDirecta < 0 ? '#c52f41' : '#84c84a', importe: this.utilidadDirecta },
        ];
    },
    get gradientePrevio() {
        if (this.previo.venta_actual <= 0 || this.hoja.directo == null) return '';
        const segmentos = this.segmentosPrevio.filter(({ importe }) => importe > 0);
        const total = segmentos.reduce((suma, { importe }) => suma + importe, 0);
        if (total <= 0) return '';
        let inicio = 0;
        const sectores = segmentos.map(({ importe, color }) => {
            const fin = inicio + importe / total * 100;
            const sector = color + ' ' + inicio + '% ' + fin + '%';
            inicio = fin;
            return sector;
        });
        return 'conic-gradient(' + sectores.join(', ') + ')';
    }
}">
    <div class="overflow-x-auto rounded-lg border border-slate-300">
        <table class="w-full border-collapse text-sm">
            <caption class="sr-only">Desglose de costo directo del escenario seleccionado</caption>
            @foreach ($partidasPrevio as $clave => [$etiqueta, $total, $color])
                <tbody class="divide-y divide-slate-200">
                    <tr class="text-white" style="background-color: {{ $color }}">
                        <th scope="rowgroup" class="px-3 py-2 text-left uppercase">{{ $etiqueta }}</th>
                        <th class="px-3 py-2 text-right">Cantidad</th>
                        <th class="px-3 py-2 text-left">Unidad</th>
                        <th class="px-3 py-2 text-right">Costo</th>
                        <th class="px-3 py-2 text-right">Importe</th>
                        <th class="whitespace-nowrap px-3 py-2 text-right">{{ $total }}</th>
                    </tr>
                    <template x-for="(fila, indice) in (hoja.filas?.[@js($clave)] ?? [])" :key="indice">
                        <tr>
                            <th scope="row" class="min-w-40 px-3 py-1.5 text-left font-medium" x-text="fila.concepto"></th>
                            <td class="px-3 py-1.5 text-right tabular-nums" x-text="fila.cantidad?.toLocaleString('es-MX', { maximumFractionDigits: 4 }) ?? '—'"></td>
                            <td class="px-3 py-1.5" x-text="fila.unidad"></td>
                            <td class="px-3 py-1.5 text-right tabular-nums" x-text="importeDesglose(fila.precio)"></td>
                            <td class="px-3 py-1.5 text-right tabular-nums" x-text="importeDesglose(fila.importe)"></td>
                            <td class="border-l border-slate-200 px-3 py-1.5 text-right font-bold tabular-nums"
                                x-show="indice === 0" :rowspan="hoja.filas[@js($clave)].length" x-text="importeDesglose(hoja[@js($clave)])"></td>
                        </tr>
                    </template>
                    <tr x-show="!hoja.filas?.[@js($clave)]?.length">
                        <td colspan="5" class="px-3 py-2 text-slate-600" x-text="hoja.filas ? 'Sin conceptos activos' : 'Subtotal guardado'"></td>
                        <td class="border-l border-slate-200 px-3 py-2 text-right font-bold tabular-nums" x-text="importeDesglose(hoja[@js($clave)])"></td>
                    </tr>
                </tbody>
            @endforeach
        </table>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <figure class="rounded-lg border border-slate-300 p-4">
            <figcaption class="mb-3 font-semibold text-slate-800"
                        x-text="segmentosPrevio.some(segmento => segmento.importe < 0) ? 'Distribución de costos y deducciones' : 'Distribución de la venta sin IVA'">Distribución de la venta sin IVA</figcaption>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <div aria-hidden="true" x-show="gradientePrevio"
                     :style="{ backgroundImage: gradientePrevio }"
                     class="relative h-40 w-40 shrink-0 rounded-full">
                    <div class="absolute inset-1/4 rounded-full bg-white"></div>
                </div>
                <ul class="min-w-52 flex-1 space-y-1 text-sm">
                    <template x-for="segmento in segmentosPrevio" :key="segmento.etiqueta">
                        <li class="flex items-center gap-2">
                            <span aria-hidden="true" class="h-2.5 w-2.5 shrink-0 rounded-sm" :style="{ backgroundColor: segmento.color }"></span>
                            <span class="flex-1" x-text="segmento.etiqueta"></span>
                            <span class="font-medium tabular-nums" x-text="porcentajeVenta(segmento.importe)"></span>
                        </li>
                    </template>
                </ul>
            </div>
            <div x-show="!gradientePrevio" class="mt-3 text-sm text-slate-600">
                <p x-text="previo.venta_actual <= 0 ? 'La gráfica y el margen se calculan cuando hay un importe de venta en Propuesta 1.' : 'Completa los costos de APUPI para ver la gráfica.'"></p>
                @if($puedeAbrirPropuesta ?? false)
                    <a x-show="previo.venta_actual <= 0" href="{{ route('presupuesto.show', [$presupuesto, 'tab' => 'apupi']) }}#propuesta-apupi"
                       @click.prevent="abrirPropuesta()" class="mt-2 inline-block font-semibold text-[#0B265A] underline">Ir a Propuesta 1</a>
                @endif
            </div>
            <p x-show="gradientePrevio && segmentosPrevio.some(segmento => segmento.importe < 0)" class="mt-3 text-xs text-slate-600">La dona reparte los importes positivos. Los porcentajes de la leyenda se calculan sobre la venta.</p>
        </figure>

        <dl class="overflow-hidden rounded-lg border border-slate-300 text-sm tabular-nums">
            <div class="flex flex-wrap justify-between gap-2 border-b border-slate-200 px-4 py-3 font-semibold">
                <dt>Subtotal costo directo</dt>
                <dd x-text="importeDesglose(hoja.directo)"></dd>
            </div>
            <div class="flex flex-wrap justify-between gap-2 border-b border-slate-200 px-4 py-3 font-semibold">
                <dt>Monto total de los trabajos sin IVA</dt>
                <dd x-text="formatoImporte(previo.venta_actual)"></dd>
            </div>
            <div class="flex flex-wrap justify-between gap-2 bg-slate-100 px-4 py-3 font-bold">
                <dt>Diferencia</dt>
                <dd class="flex gap-3"><span x-text="importeDesglose(diferenciaDirecta)"></span><span x-text="porcentajeVenta(diferenciaDirecta)"></span></dd>
            </div>
            @foreach ($deduccionesPrevio as $clave => [$etiqueta, $color])
                <div class="flex flex-wrap justify-between gap-2 px-4 py-1.5 font-medium text-slate-950" style="background-color: {{ $color }}">
                    <dt>{{ $etiqueta }} <span x-text="Number(previo.porcentajes[@js($clave)]) + ' %'"></span></dt>
                    <dd x-text="importeDesglose(hoja[@js($clave)])"></dd>
                </div>
            @endforeach
            <div class="flex flex-wrap justify-between gap-2 px-4 py-2.5 font-bold"
                 :class="utilidadDirecta < 0 ? 'bg-red-100 text-red-900' : 'bg-lime-200 text-slate-950'">
                <dt x-text="utilidadDirecta < 0 ? 'Pérdida esperada sobre costo directo' : 'Utilidad esperada sobre costo directo'"></dt>
                <dd class="flex gap-3"><span x-text="importeDesglose(utilidadDirecta)"></span><span x-text="porcentajeVenta(utilidadDirecta)"></span></dd>
            </div>
            <div x-show="hoja.cierre != null" class="flex flex-wrap justify-between gap-2 border-t border-slate-200 px-4 py-2.5 font-semibold">
                <dt>Utilidad proyectada al cierre</dt>
                <dd class="flex gap-3"><span x-text="importeDesglose(hoja.utilidad)"></span><span x-text="porcentajeVenta(hoja.utilidad)"></span></dd>
            </div>
        </dl>
    </div>
</div>
