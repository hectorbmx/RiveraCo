<div class="space-y-4 p-5">
    <h2 class="text-lg font-semibold text-slate-800">Presupuesto comercial</h2>
    <details x-ref="datosImpresion" class="rounded-lg border border-slate-200 p-4" @if($errors->presentacionPresupuesto->any()) open @endif>
        <summary class="cursor-pointer font-semibold text-[#0B265A]">Datos para impresión</summary>
        @if($errors->presentacionPresupuesto->any())
            <p role="alert" class="mt-2 text-sm text-red-700">{{ $errors->presentacionPresupuesto->first() }}</p>
        @endif
        <form data-formato-comercial method="POST" action="{{ route('presupuesto.presentacion.update', $presupuesto) }}" class="mt-4 space-y-4">
            @csrf
            @method('PATCH')
            <input type="hidden" name="tab" value="presupuesto">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach(['atencion' => 'Atención', 'telefono' => 'Teléfono', 'correo' => 'Correo', 'ubicacion' => 'Ubicación de la obra'] as $campo => $etiqueta)
                    <label class="text-sm font-medium">{{ $etiqueta }}
                        <input name="{{ $campo }}" type="{{ $campo === 'correo' ? 'email' : 'text' }}" value="{{ old($campo, $presentacion[$campo]) }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </label>
                @endforeach
                @foreach(['anticipo' => ['Anticipo (%)',100], 'plazo_semanas' => ['Plazo (semanas)',99999], 'iva' => ['IVA (%)',100]] as $campo => [$etiqueta, $maximo])
                    <label class="text-sm font-medium">{{ $etiqueta }}
                        <input name="{{ $campo }}" type="number" min="0" max="{{ $maximo }}" step="0.01" value="{{ old($campo, $campo === 'iva' ? $presentacion['tasa_iva'] : $presentacion[$campo]) }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </label>
                @endforeach
                @foreach(['domicilio' => 'Domicilio del cliente', 'condiciones_pago' => 'Condiciones de pago'] as $campo => $etiqueta)
                    <label class="text-sm font-medium">{{ $etiqueta }}
                        <textarea name="{{ $campo }}" rows="3" class="mt-1 block w-full rounded-md border-slate-300 text-sm">{{ old($campo, $presentacion[$campo]) }}</textarea>
                    </label>
                @endforeach
            </div>
            <label class="block text-sm font-medium">Notas importantes
                <textarea name="notas" rows="4" maxlength="10000" placeholder="Una nota por renglón. Deja vacío para usar las notas del formato." class="mt-1 block w-full rounded-md border-slate-300 text-sm">{{ old('notas', implode("\n", $presupuesto->presentacion_comercial['notas'] ?? [])) }}</textarea>
            </label>
            <details class="text-sm">
                <summary class="cursor-pointer font-semibold">Unidades impresas</summary>
                <template x-for="([clave, fila]) in Object.entries(presentacion.filas)" :key="clave">
                    <fieldset class="mt-3">
                        <legend class="text-slate-600" x-text="fila.concepto"></legend>
                        <label>Unidad impresa
                            <input type="text" maxlength="32" :name="'unidades[' + clave + ']'" :value="unidadesCapturadas[clave] ?? ''"
                                   :data-inicial="unidadesCapturadas[clave] ?? ''" :placeholder="fila.unidad" class="mt-1 block w-40 rounded-md border-slate-300 text-sm">
                        </label>
                    </fieldset>
                </template>
            </details>
            <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white">Guardar datos para impresión</button>
        </form>
    </details>
    <p class="text-sm text-slate-600">Los cambios en Concepto se guardan al salir del campo.</p>
</div>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-[#0B265A] text-white">
            <tr><th class="px-3 py-3 text-left">Partida</th><th class="w-full min-w-64 px-4 py-3 text-left">Concepto</th><th class="px-3 py-3 text-center">Unidad</th><th class="px-3 py-3 text-right">Cantidad</th><th class="px-3 py-3 text-right">P.U.</th><th class="px-4 py-3 text-right">Importe</th></tr>
        </thead>
        <template x-for="([clave, fila]) in Object.entries(presentacion.filas)" :key="clave">
            <tbody class="border-b border-slate-100">
                <template x-if="fila.numero_encabezado">
                    <tr class="bg-slate-50">
                        <th scope="row" class="px-3 py-3 text-left align-top" x-text="fila.numero_encabezado"></th>
                        <td colspan="5" class="px-4 py-2">
                            <textarea rows="2" maxlength="1000" required aria-label="Descripción general de perforación"
                                      x-init="$el.value = presentacion.descripcion_perforacion; $el.dataset.conceptoGuardado = $el.value"
                                      @blur="guardarConcepto($el, encabezadoPerforacion)" @keydown.ctrl.enter.prevent="$el.blur()"
                                      class="block w-full rounded-md border-slate-300 text-sm aria-invalid:border-red-600"></textarea>
                        </td>
                    </tr>
                </template>
                <tr>
                    <th scope="row" class="px-3 py-3 text-left align-top" x-text="fila.partida === 'perforacion' ? fila.numero + ')' : fila.numero"></th>
                    <td class="px-4 py-2">
                        <textarea rows="2" maxlength="1000" required :aria-label="'Concepto de partida ' + fila.numero"
                                  x-init="$el.value = fila.concepto; $el.dataset.conceptoGuardado = $el.value"
                                  @blur="guardarConcepto($el, clave)" @keydown.ctrl.enter.prevent="$el.blur()"
                                  class="block w-full rounded-md border-slate-300 text-sm aria-invalid:border-red-600"></textarea>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-center align-top" x-text="fila.unidad"></td>
                    <td class="whitespace-nowrap px-3 py-3 text-right align-top" x-text="Number(fila.cantidad).toLocaleString('en-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></td>
                    <td class="whitespace-nowrap px-3 py-3 text-right align-top" x-text="formatoImporte(fila.precio_unitario)"></td>
                    <td class="whitespace-nowrap px-4 py-3 text-right align-top font-semibold" x-text="formatoImporte(fila.importe)"></td>
                </tr>
            </tbody>
        </template>
        <tbody x-show="Object.keys(presentacion.filas).length === 0">
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-600">
                <p>Captura una cantidad y un precio unitario de venta en Propuesta 1 para generar el presupuesto comercial.</p>
                <a href="{{ route('presupuesto.show', [$presupuesto, 'tab' => 'apupi']) }}#propuesta-apupi"
                   @click.prevent="abrirPropuesta()" class="mt-2 inline-block font-semibold text-[#0B265A] underline">Ir a Propuesta 1</a>
            </td></tr>
        </tbody>
        <tfoot class="bg-slate-100">
            <tr>
                <td colspan="4" rowspan="3" class="px-4 py-3 align-top">
                    <div class="max-w-4xl space-y-2">
                        <h3 id="titulo-nota-ejecucion" class="font-semibold text-slate-800">Notas de ejecución</h3>
                        <div class="bg-white [&_.ql-editor]:min-h-24"
                             x-init="$iniciarNotaEjecucion($el, JSON.parse(notaEjecucion), programarNotaEjecucion.bind($data)).catch(() => errorEditorNota = true)"></div>
                        <p x-show="errorEditorNota" x-cloak role="alert" class="text-red-700">No se pudo cargar el editor de notas.</p>
                        <p x-show="notaPendiente || notaEnviada !== null" x-cloak role="status" class="text-slate-700">
                            <span x-text="errorNota || (notaEnviada !== null ? 'Guardando notas…' : 'Cambios de notas pendientes')"></span>
                            <button type="button" x-show="errorNota" @click="enviarNotaEjecucion()" class="ml-2 font-semibold underline">Reintentar</button>
                        </p>
                    </div>
                </td>
                <th class="px-4 py-2 text-right align-top">Subtotal</th><td class="px-4 py-2 text-right align-top" x-text="formatoImporte(presentacion.subtotal)"></td>
            </tr>
            <tr><th class="px-4 py-2 text-right align-top" x-text="'IVA ' + presentacion.tasa_iva + '%'"></th><td class="px-4 py-2 text-right align-top" x-text="formatoImporte(presentacion.iva)"></td></tr>
            <tr><th class="px-4 py-2 text-right align-top">Total</th><td class="px-4 py-2 text-right align-top font-bold text-[#0B265A]" x-text="formatoImporte(presentacion.total)"></td></tr>
        </tfoot>
    </table>
</div>
