<dialog x-ref="dialogoImportacion" aria-labelledby="titulo-importacion-partidas"
        @cancel.prevent="if (!importando) $el.close()"
        class="m-auto max-h-[85vh] w-11/12 max-w-5xl rounded-xl p-0 text-slate-800 shadow-xl backdrop:bg-slate-900/55">
    <div class="flex max-h-[85vh] flex-col">
        <header class="border-b border-slate-200 px-5 py-4">
            <h3 id="titulo-importacion-partidas" class="text-lg font-semibold text-[#0B265A]">Importar Excel</h3>
            <p class="mt-1 text-sm text-slate-600">Selecciona un archivo .xlsx con el catálogo Estándar (E) en la primera hoja.</p>
        </header>
        <div class="space-y-4 overflow-y-auto overscroll-contain px-5 py-4">
            <form class="flex flex-wrap items-end gap-3"
                  @submit.prevent="previsualizarExcel(@js(route('presupuesto.partidas.importacion', $presupuesto)))">
                <label class="block text-sm font-semibold">Archivo Excel
                    <input type="file" x-ref="archivoImportacion" accept=".xlsx" required :disabled="importando"
                           @change="importacion = null; errorImportacion = ''" class="mt-1 block max-w-full text-sm">
                </label>
                <button type="submit" :disabled="importando" x-text="importando ? 'Leyendo Excel…' : 'Ver vista previa'"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-[#0B265A] disabled:opacity-60">Ver vista previa</button>
            </form>
            <p x-show="errorImportacion" x-text="errorImportacion" role="alert" class="text-sm text-red-700"></p>
            <template x-if="importacion">
                <div class="space-y-3">
                    <div class="space-y-1 rounded-lg bg-slate-50 p-3 text-sm">
                        <p class="font-semibold text-[#0B265A]" x-text="importacion.partidas.length + ' partidas · ' + importacion.cantidad_conceptos + ' conceptos'"></p>
                        <p x-show="importacion.contexto.length" x-text="importacion.contexto.join(' · ')"></p>
                        <p x-text="'Subtotal calculado: ' + moneda(centesimas(importacion.subtotal), 100n)"></p>
                        <p x-show="importacion.subtotal_excel !== null"
                           x-text="'Subtotal del Excel: ' + moneda(centesimas(importacion.subtotal_excel), 100n)"></p>
                        <p x-show="importacion.subtotal_excel !== null && importacion.subtotal_excel !== importacion.subtotal"
                           class="text-amber-900">El subtotal del archivo difiere del cálculo con las cantidades y precios importados.</p>
                        <p x-show="importacion.partidas.some(partida => partida.subpartidas.some(fila => !unidadDisponible(fila.unidad)))"
                           class="text-amber-900">Selecciona una unidad en la tabla para cada concepto cuya unidad no esté disponible.</p>
                    </div>
                    <template x-for="(partida, indice) in importacion.partidas" :key="indice">
                        <details :open="indice === 0" class="rounded-lg border border-slate-200">
                            <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-[#0B265A]"
                                     x-text="partida.nombre + ' · ' + partida.subpartidas.length + ' conceptos'"></summary>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50 text-slate-700">
                                        <tr>
                                            <th scope="col" class="px-3 py-2">Código / Fila</th>
                                            <th scope="col" class="px-3 py-2">Concepto</th>
                                            <th scope="col" class="px-3 py-2">Unidad</th>
                                            <th scope="col" class="px-3 py-2 text-right">Cantidad</th>
                                            <th scope="col" class="px-3 py-2 text-right">P. U.</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="fila in partida.subpartidas" :key="fila.fila">
                                            <tr class="align-top">
                                                <td class="whitespace-nowrap px-3 py-2">
                                                    <span x-text="fila.codigo"></span>
                                                    <span class="block text-xs text-slate-500" x-text="'Fila ' + fila.fila"></span>
                                                </td>
                                                <td class="px-3 py-2" x-text="fila.concepto"></td>
                                                <td class="px-3 py-2">
                                                    <span x-text="fila.unidad"></span>
                                                    <span x-show="!unidadDisponible(fila.unidad)" class="block text-xs text-amber-900">Por asignar</span>
                                                </td>
                                                <td class="px-3 py-2 text-right tabular-nums" x-text="fila.cantidad"></td>
                                                <td class="px-3 py-2 text-right tabular-nums" x-text="fila.precio_unitario"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    </template>
                </div>
            </template>
        </div>
        <footer class="border-t border-slate-200 px-5 py-4 text-sm">
            <p x-show="importacion" class="mb-2 text-slate-600">La carga modifica la tabla en pantalla. Para conservarla, pulsa Guardar partidas.</p>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" :disabled="importando" @click="$refs.dialogoImportacion.close()"
                        class="rounded-lg border border-slate-300 px-4 py-2 font-semibold">Cancelar</button>
                <button type="button" x-show="importacion && partidas.length" @click="aplicarImportacion(true)"
                        class="rounded-lg border border-slate-300 px-4 py-2 font-semibold text-[#0B265A]">Reemplazar partidas</button>
                <button type="button" x-show="importacion" @click="aplicarImportacion(false)"
                        class="rounded-lg bg-[#0B265A] px-4 py-2 font-semibold text-white"
                        x-text="partidas.length ? 'Agregar partidas' : 'Cargar partidas'">Cargar partidas</button>
            </div>
        </footer>
    </div>
</dialog>
