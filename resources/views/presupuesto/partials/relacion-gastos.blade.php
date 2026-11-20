<div x-data="{
    soloUsados: false,
    get planSemanal() {
        return this.relacionGastos.planeacion;
    },
    get semanasGasto() {
        return this.planSemanal?.semanas ?? [];
    },
    get partidasVisibles() {
        return this.relacionGastos.partidas.map(grupo => ({
            partida: grupo.partida,
            filas: this.soloUsados ? grupo.filas.filter(fila => fila.usado) : grupo.filas
        })).filter(grupo => grupo.filas.length);
    },
    cantidadGasto(valor) {
        return valor == null ? '—' : Number(valor).toLocaleString('en-MX', {minimumFractionDigits: 2, maximumFractionDigits: 4});
    },
    montoGasto(valor) {
        return valor == null ? '—' : '$' + Number(valor).toLocaleString('en-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    },
    @if($editarSemanas ?? false)
        puedeRetrocederSemanas: false,
        puedeAvanzarSemanas: false,
        medirTablaGastos() {
            const tabla = this.$refs.tablaGastos;
            this.puedeRetrocederSemanas = tabla.scrollLeft > 0;
            this.puedeAvanzarSemanas = tabla.scrollLeft + tabla.clientWidth < tabla.scrollWidth - 1;
        },
        desplazarSemanas(direccion) {
            const tabla = this.$refs.tablaGastos;
            tabla.scrollBy({left: direccion * tabla.clientWidth * 0.8});
        },
        cambioSemana(fila, semana) {
            const coincide = cambio => cambio.concepto === String(fila.id) && cambio.semana === semana;
            return this.cambiosSemanales.find(coincide) ?? this.loteSemanal?.cambios.find(coincide);
        },
        valorSemana(fila, semana) {
            const cambio = this.cambioSemana(fila, semana);
            return cambio ? cambio.monto : fila.planeacion.montos[semana] / 100;
        },
        textoSemana(fila, semana) {
            const importe = this.valorSemana(fila, semana);
            return importe === null ? '' : this.montoGasto(importe);
        },
        enfocarSemana(input, importe) {
            input.value = importe === null ? '' : Number(importe).toFixed(2);
            input.select();
        },
        editarSemana(input, fila, semana, importeAlEnfocar) {
            const valor = input.value.trim();
            const importeAnterior = this.valorSemana(fila, semana);
            if (valor && !/^\$?\s*(?:(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d{1,2})?|\.\d{1,2})$/.test(valor)) {
                input.value = this.textoSemana(fila, semana);
                this.avisar('Captura un importe válido con hasta dos decimales.', true);
                return;
            }
            const limpio = valor.replace(/[$,\s]/g, '');
            const monto = limpio === '' ? null : (limpio.startsWith('.') ? '0' + limpio : limpio);
            const comparable = importe => importe === null ? null : Number(importe);
            if (comparable(monto) !== comparable(importeAlEnfocar) && comparable(monto) !== comparable(importeAnterior)) {
                this.programarGastoSemanal({concepto: String(fila.id), semana, monto});
            }
            input.value = this.textoSemana(fila, semana);
        },
    @endif
}" @if($editarSemanas ?? false)
    x-effect="if (tab === 'gastos' && semanasGasto) $nextTick(() => medirTablaGastos())"
    @resize.window="if (tab === 'gastos') medirTablaGastos()"
@endif>
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Relación de gastos</h2>
            <p class="mt-1 text-sm text-slate-600">Base presupuestada · Escenario realista</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-sm">
            @if($editarSemanas ?? false)
                <div x-cloak x-show="puedeRetrocederSemanas || puedeAvanzarSemanas" class="flex gap-2">
                    <button type="button" :disabled="!puedeRetrocederSemanas" @click="desplazarSemanas(-1)"
                            class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-[#0B265A] disabled:opacity-50">← Semanas</button>
                    <button type="button" :disabled="!puedeAvanzarSemanas" @click="desplazarSemanas(1)"
                            class="rounded-lg border border-slate-300 px-3 py-2 font-medium text-[#0B265A] disabled:opacity-50">Semanas →</button>
                </div>
            @endif
            <label class="flex items-center gap-2 font-medium text-slate-700">
                <input type="checkbox" x-model="soloUsados" class="rounded border-slate-300 text-[#0B265A]">
                Solo conceptos usados
            </label>
            <span class="text-slate-600" aria-live="polite"
                  x-text="(soloUsados ? relacionGastos.usados : relacionGastos.conceptos) + ' de ' + relacionGastos.conceptos + ' conceptos'"></span>
        </div>
    </div>
    @if($editarSemanas ?? false)
        <div class="flex flex-wrap items-start justify-between gap-3 px-5 pb-4">
            <dl class="flex flex-wrap gap-3 text-sm">
                <div class="rounded-lg bg-slate-100 px-3 py-2"><dt>Jornales de trabajo</dt><dd class="font-semibold" x-text="planSemanal?.jornales ?? '—'"></dd></div>
                <div class="rounded-lg bg-slate-100 px-3 py-2"><dt>Días calendario</dt><dd class="font-semibold" x-text="planSemanal?.dias ?? '—'"></dd></div>
                <div class="rounded-lg bg-slate-100 px-3 py-2"><dt>Semanas</dt><dd class="font-semibold" x-text="planSemanal?.numero_semanas ?? '—'"></dd></div>
            </dl>
            <button type="button" x-show="planSemanal?.tiene_ajustes || semanasPendientes" :disabled="!!loteSemanal"
                    @click="if (confirm('¿Restablecer el reparto automático de todos los conceptos?')) reiniciarSemanas()"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-[#0B265A] disabled:opacity-50">Restablecer reparto</button>
        </div>
        <p x-show="planSemanal && !planSemanal.error" class="px-5 pb-3 text-sm text-slate-600">Las demás semanas se ajustan al guardar. Borra el importe para devolver esa semana al reparto automático.</p>
        <p role="status" x-show="(semanasPendientes || loteSemanal) && !errorSemanal" class="px-5 pb-3 text-sm text-slate-600"
           x-text="loteSemanal ? 'Guardando importes semanales…' : 'Importes pendientes de guardar'"></p>
        <div x-show="errorSemanal" class="mx-5 mb-4 flex flex-wrap items-center gap-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">
            <p role="alert" x-text="errorSemanal"></p>
            <button type="button" class="font-semibold underline" @click="enviarSemanas()">Reintentar</button>
            <button type="button" class="font-semibold underline" @click="if (confirm('¿Descartar los importes que quedaron pendientes?')) descartarSemanas()">Descartar pendientes</button>
        </div>
    @endif
    <div x-show="planSemanal?.error" class="mx-5 mb-4 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">
        <p x-text="planSemanal?.error"></p>
        @if($editarSemanas ?? false)
            <button type="button" @click="$dispatch('editar-semanas')" class="font-semibold underline">Editar semanas del escenario realista</button>
        @endif
    </div>
    <p x-show="!planSemanal?.error && planSemanal?.pendientes > 0" class="mx-5 mb-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900"
       x-text="planSemanal?.pendientes + ' conceptos o ajustes requieren revisión antes de aplicar la planeación a Obra.'"></p>
    <div class="overflow-x-auto" role="region" aria-label="Base presupuestada de gastos" tabindex="0"
         @if($editarSemanas ?? false) x-ref="tablaGastos" @scroll.passive="medirTablaGastos()" @endif>
        <table class="w-full text-sm">
            <thead class="bg-[#0B265A] text-white">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left">Concepto</th>
                    <th scope="col" class="px-4 py-3 text-center">Unidad</th>
                    <th scope="col" class="px-4 py-3 text-right">Cantidad</th>
                    <th scope="col" class="px-4 py-3 text-right">P.U.</th>
                    <th scope="col" class="px-4 py-3 text-right">Monto previsto</th>
                    <template x-for="semana in semanasGasto" :key="semana.numero">
                        <th scope="col" class="min-w-[135px] whitespace-nowrap px-4 py-3 text-right">
                            <span x-text="'Semana ' + semana.numero"></span>
                            <span class="block text-xs font-normal" x-text="semana.jornales + ' jornales'"></span>
                        </th>
                    </template>
                    <th x-show="semanasGasto.length" scope="col" class="whitespace-nowrap px-4 py-3 text-right">Por programar</th>
                </tr>
            </thead>
            <template x-for="grupo in partidasVisibles" :key="grupo.partida">
                <tbody class="divide-y divide-slate-100">
                    <tr class="bg-slate-100">
                        <th scope="rowgroup" :colspan="5 + semanasGasto.length + Number(semanasGasto.length > 0)" class="px-4 py-2 text-left font-semibold uppercase text-[#0B265A]" x-text="grupo.partida"></th>
                    </tr>
                    <template x-for="fila in grupo.filas" :key="fila.id">
                        <tr :class="fila.usado ? 'text-slate-800' : 'text-slate-500'">
                            <th scope="row" class="px-4 py-2 text-left font-medium" x-text="fila.concepto"></th>
                            <td class="px-4 py-2 text-center" x-text="fila.unidad"></td>
                            <td class="px-4 py-2 text-right tabular-nums" x-text="cantidadGasto(fila.cantidad)"></td>
                            <td class="whitespace-nowrap px-4 py-2 text-right tabular-nums" x-text="montoGasto(fila.precio_unitario)"></td>
                            <td class="whitespace-nowrap px-4 py-2 text-right font-semibold tabular-nums" x-text="montoGasto(fila.monto_programado)"></td>
                            <template x-for="semana in semanasGasto" :key="semana.numero">
                                <td class="border-l border-slate-100 px-3 py-1 text-right tabular-nums">
                                    @if($editarSemanas ?? false)
                                        <template x-if="fila.planeacion?.editable">
                                            <input type="text" inputmode="decimal" data-gasto-semanal x-data="{editando: false, importeAlEnfocar: null}" placeholder="Automático"
                                                   :aria-label="fila.concepto + ', semana ' + semana.numero"
                                                   :title="cambioSemana(fila, semana.numero) ? 'Pendiente de guardar' : (fila.planeacion.manual[semana.numero] != null ? 'Ajuste manual' : 'Reparto automático')"
                                                   :class="cambioSemana(fila, semana.numero) ? 'border-amber-300 bg-amber-50' : (fila.planeacion.manual[semana.numero] != null ? 'border-blue-300 bg-blue-50' : 'border-slate-200')"
                                                   x-effect="if (!editando) $el.value = textoSemana(fila, semana.numero)"
                                                   @focus="editando = true; importeAlEnfocar = valorSemana(fila, semana.numero); enfocarSemana($el, importeAlEnfocar)"
                                                   @blur="editarSemana($el, fila, semana.numero, importeAlEnfocar); editando = false"
                                                   class="w-full rounded-md px-2 py-1 text-right text-sm">
                                        </template>
                                        <span x-show="!fila.planeacion?.editable" x-text="fila.planeacion?.montos[semana.numero] == null ? '—' : montoGasto(fila.planeacion.montos[semana.numero] / 100)"></span>
                                    @else
                                        <span x-text="fila.planeacion?.montos[semana.numero] == null ? '—' : montoGasto(fila.planeacion.montos[semana.numero] / 100)"></span>
                                    @endif
                                </td>
                            </template>
                            <td x-show="semanasGasto.length" class="whitespace-nowrap px-4 py-2 text-right tabular-nums"
                                :class="fila.planeacion?.saldo !== 0 || fila.planeacion?.fuera ? 'text-amber-800 font-semibold' : ''"
                                x-text="fila.planeacion?.fuera ? 'Ajustes fuera del plazo' : (fila.planeacion?.saldo == null ? '—' : montoGasto(fila.planeacion.saldo / 100))"></td>
                        </tr>
                    </template>
                </tbody>
            </template>
            <tbody x-show="!partidasVisibles.length">
                <tr>
                    <td :colspan="5 + semanasGasto.length + Number(semanasGasto.length > 0)" class="px-4 py-8 text-center text-slate-600">Todavía no hay conceptos usados en este presupuesto.</td>
                </tr>
            </tbody>
            <tfoot class="bg-slate-100">
                <tr>
                    <th scope="row" colspan="4" class="px-4 py-4 text-right text-slate-700"
                        x-text="relacionGastos.pendientes ? 'Subtotal de gastos calculados' : 'Total de gastos previstos'"></th>
                    <td class="whitespace-nowrap px-4 py-4 text-right font-bold text-[#0B265A]" x-text="montoGasto(relacionGastos.total)"></td>
                    <template x-for="semana in semanasGasto" :key="semana.numero">
                        <td class="whitespace-nowrap border-l border-slate-200 px-3 py-4 text-right font-bold text-[#0B265A]"
                            x-text="planSemanal.error ? '—' : montoGasto(planSemanal.totales[semana.numero] / 100)"></td>
                    </template>
                    <td x-show="semanasGasto.length" class="whitespace-nowrap px-3 py-4 text-right font-bold text-[#0B265A]"
                        x-text="planSemanal?.error ? '—' : montoGasto((Math.round(relacionGastos.total * 100) - Object.values(planSemanal?.totales ?? {}).reduce((a, b) => a + b, 0)) / 100)"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
