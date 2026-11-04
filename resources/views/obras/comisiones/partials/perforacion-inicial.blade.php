<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="Perforación inicial">
    <div class="bg-[#0B265A] px-5 py-4 text-white">
        <h2 class="text-base font-semibold">Perforación inicial</h2>
        <p class="mt-1 text-sm text-blue-100">Captura el inicio y deja el fin vacío si el trabajo continúa en proceso.</p>
    </div>
    <div class="p-5 sm:p-6 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div>
                <label for="alta-diametro" class="block text-sm font-medium text-slate-700 mb-1">Diámetro</label>
                <input id="alta-diametro" x-ref="altaDiametro" type="number" name="perforacion[diametro]" min="0" step="0.01"
                       value="{{ old('perforacion.diametro', '') }}" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="alta-profundidad" class="block text-sm font-medium text-slate-700 mb-1">Profundidad (m)</label>
                <input id="alta-profundidad" x-ref="altaProfundidad" type="number" name="perforacion[profundidad]" min="0" max="999999.99" step="0.01"
                       value="{{ old('perforacion.profundidad', '') }}" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="alta-metros" class="block text-sm font-medium text-slate-700 mb-1">Metros de comisión <span aria-hidden="true">*</span></label>
                <input id="alta-metros" x-ref="altaMetros" type="number" name="perforacion[metros_comision]" min="0" max="99999999.99" step="0.01" required
                       value="{{ old('perforacion.metros_comision', '') }}" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                <p class="mt-1 text-xs text-slate-500">Valor inicial de la profundidad de la pila; puedes ajustarlo para este trabajo.</p>
            </div>
            <div>
                <label for="alta-cantidad" class="block text-sm font-medium text-slate-700 mb-1">Cantidad</label>
                <input id="alta-cantidad" type="number" name="perforacion[cantidad]" value="1" readonly class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-600">
                <p class="mt-1 text-xs text-slate-500">Una pila por comisión.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label for="alta-inicio" class="block text-sm font-medium text-slate-700 mb-1">Inicio de perforación <span aria-hidden="true">*</span></label>
                <input id="alta-inicio" x-ref="altaInicio" type="time" name="perforacion[hora_inicio]" required value="{{ old('perforacion.hora_inicio', '') }}"
                       class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="alta-fin" class="block text-sm font-medium text-slate-700 mb-1">Fin de perforación</label>
                <input id="alta-fin" x-ref="altaFin" type="time" name="perforacion[hora_fin]" value="{{ old('perforacion.hora_fin', '') }}"
                       class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" aria-describedby="alta-fin-ayuda">
                <p id="alta-fin-ayuda" class="mt-1 text-xs text-slate-500">Opcional mientras la perforación está en proceso.</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs font-semibold uppercase text-slate-500">Estado previsto al guardar</p>
                <p class="mt-1 text-sm font-semibold text-[#0B265A]" x-text="estadoPerforacion" role="status">Según los horarios capturados</p>
            </div>
        </div>

        <div>
            <label for="alta-observaciones-perforacion" class="block text-sm font-medium text-slate-700 mb-1">Observaciones de perforación</label>
            <textarea id="alta-observaciones-perforacion" name="perforacion[observaciones]" rows="3" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="Detalles de esta etapa…">{{ old('perforacion.observaciones') }}</textarea>
        </div>

        <fieldset>
            <legend class="text-sm font-semibold text-slate-700">Etapas opcionales de este trabajo</legend>
            <p class="mt-1 mb-3 text-xs text-slate-500">Indica si aplican. Sus cantidades y evidencias se registrarán en la etapa correspondiente.</p>
            <div class="flex flex-wrap gap-5">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="opcionales[bentonita]" value="0">
                    <input type="checkbox" name="opcionales[bentonita]" value="1" @checked(old('opcionales.bentonita', false)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Bentonita
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="opcionales[ademe]" value="0">
                    <input type="checkbox" name="opcionales[ademe]" value="1" @checked(old('opcionales.ademe', false)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Ademe
                </label>
            </div>
        </fieldset>

        <details class="rounded-xl border border-slate-200 p-4">
            <summary class="cursor-pointer text-sm font-medium text-slate-700">Otros datos de perforación</summary>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="alta-campana" class="block text-sm font-medium text-slate-700 mb-1">Campana (piezas)</label>
                    <input id="alta-campana" type="number" name="perforacion[campana_pzas]" min="0" step="1" value="{{ old('perforacion.campana_pzas', 0) }}" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="alta-adicional" class="block text-sm font-medium text-slate-700 mb-1">Adicional</label>
                    <input id="alta-adicional" type="text" name="perforacion[adicional]" maxlength="100" value="{{ old('perforacion.adicional') }}" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </details>
    </div>
</section>
