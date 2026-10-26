@php
    $tarifasHoraExtra = $empleado->horaExtraTarifas()->with('registradoPor:id,name')->get();
    $hoyTarifa = today();
    $tarifaVigente = $tarifasHoraExtra->first(fn ($tarifa) =>
        $tarifa->vigente_desde->lte($hoyTarifa) &&
        (! $tarifa->vigente_hasta || $tarifa->vigente_hasta->gte($hoyTarifa))
    );
    $ultimaTarifa = $tarifasHoraExtra->first();
    $fechaMinimaTarifa = $ultimaTarifa?->vigente_desde->copy()->addDay()->toDateString();
    $fechaSugeridaTarifa = max($hoyTarifa->toDateString(), $fechaMinimaTarifa ?? $hoyTarifa->toDateString());
    $erroresTarifa = $errors->getBag('horaExtraTarifa');
    $modalidadCaptura = old('modalidad', $ultimaTarifa?->modalidad ?? 'automatica');
@endphp

<div id="hora-extra-tarifa" class="mt-6 border-t border-slate-200 pt-5"
     x-data="{ modalidad: @js($modalidadCaptura) }">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h4 class="text-sm font-bold text-[#0B265A]">Costo por hora extra</h4>
        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-[#0B265A]">
            {{ $tarifaVigente?->modalidad === 'cuota_fija' ? 'Cuota fija vigente' : 'Automático por sueldo' }}
        </span>
    </div>

    <p class="mt-2 text-sm text-slate-600">
        @if($tarifaVigente?->modalidad === 'cuota_fija')
            Costo vigente: <strong class="text-slate-900">${{ number_format((float) $tarifaVigente->costo_hora_extra, 4) }} por hora extra</strong>,
            desde el {{ $tarifaVigente->vigente_desde->format('d/m/Y') }}.
        @else
            Sin cuota fija vigente. La hora extra se calcula a partir del sueldo.
        @endif
    </p>

    @if($ultimaTarifa && $ultimaTarifa->vigente_desde->gt($hoyTarifa))
        <p class="mt-2 text-xs font-medium text-blue-700">
            Hay un cambio programado para el {{ $ultimaTarifa->vigente_desde->format('d/m/Y') }}. Consulta el historial.
        </p>
    @endif

    <details class="mt-4 rounded-xl border border-slate-200 bg-slate-50/60 p-4" open>
        <summary class="cursor-pointer text-sm font-semibold text-slate-800">Registrar cambio de tarifa</summary>
        <p class="mt-2 text-xs text-slate-500">Cada cambio conserva la configuración anterior y su periodo de vigencia.</p>

        @if($erroresTarifa->any())
            <div role="alert" class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                No se guardó la tarifa. Revisa los campos señalados.
            </div>
        @endif

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label for="he-modalidad" class="block text-xs font-medium text-slate-700">Modalidad</label>
                <select id="he-modalidad" name="modalidad" form="hora-extra-tarifa-form" x-model="modalidad" required
                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                    <option value="automatica" @selected($modalidadCaptura === 'automatica')>Automático por sueldo</option>
                    <option value="cuota_fija" @selected($modalidadCaptura === 'cuota_fija')>Cuota fija</option>
                </select>
                @error('modalidad', 'horaExtraTarifa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="he-costo" class="block text-xs font-medium text-slate-700">Costo por hora extra ($)</label>
                <input id="he-costo" name="costo_hora_extra" form="hora-extra-tarifa-form" type="number"
                       step="0.0001" min="0.0001" max="99999999.9999"
                       value="{{ old('costo_hora_extra', $ultimaTarifa?->costo_hora_extra) }}"
                       :disabled="modalidad !== 'cuota_fija'" :required="modalidad === 'cuota_fija'"
                       class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-[#FFC107] focus:ring-[#FFC107] disabled:bg-slate-100 disabled:text-slate-400">
                <p class="mt-1 text-xs text-slate-500" x-show="modalidad === 'cuota_fija'">Importe final por hora extra; no se multiplica nuevamente por dos.</p>
                <p class="mt-1 text-xs text-slate-500" x-show="modalidad === 'automatica'">Se determina a partir del sueldo; no requiere cuota fija.</p>
                @error('costo_hora_extra', 'horaExtraTarifa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="he-desde" class="block text-xs font-medium text-slate-700">Vigente desde</label>
                <input id="he-desde" name="vigente_desde" form="hora-extra-tarifa-form" type="date" required
                       @if($fechaMinimaTarifa) min="{{ $fechaMinimaTarifa }}" @endif
                       value="{{ old('vigente_desde', $fechaSugeridaTarifa) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                @if($ultimaTarifa)
                    <p class="mt-1 text-xs text-slate-500">Debe ser posterior al {{ $ultimaTarifa->vigente_desde->format('d/m/Y') }}.</p>
                @endif
                @error('vigente_desde', 'horaExtraTarifa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-3">
                <label for="he-motivo" class="block text-xs font-medium text-slate-700">Motivo del cambio</label>
                <textarea id="he-motivo" name="motivo" form="hora-extra-tarifa-form" rows="2" maxlength="1000" required
                          class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-[#FFC107] focus:ring-[#FFC107]"
                          placeholder="Indica por qué se establece o cambia esta tarifa.">{{ old('motivo') }}</textarea>
                @error('motivo', 'horaExtraTarifa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-slate-500">Este botón guarda únicamente la configuración de hora extra.</p>
            <button type="submit" form="hora-extra-tarifa-form"
                    class="rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                Guardar tarifa
            </button>
        </div>
    </details>

    <h5 class="mb-2 mt-5 text-sm font-semibold text-slate-800">Historial de tarifas</h5>
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-left text-xs">
            <thead class="bg-[#0B265A] text-white">
                <tr>
                    <th class="px-3 py-3">Modalidad / costo</th>
                    <th class="px-3 py-3">Desde</th>
                    <th class="px-3 py-3">Hasta</th>
                    <th class="px-3 py-3">Estado</th>
                    <th class="px-3 py-3">Motivo</th>
                    <th class="px-3 py-3">Registró</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($tarifasHoraExtra as $tarifa)
                    <tr>
                        <td class="px-3 py-3 font-medium">
                            {{ $tarifa->modalidad === 'cuota_fija' ? 'Cuota fija: $'.number_format((float) $tarifa->costo_hora_extra, 4).' / h' : 'Automático por sueldo' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-3">{{ $tarifa->vigente_desde->format('d/m/Y') }}</td>
                        <td class="whitespace-nowrap px-3 py-3">{{ $tarifa->vigente_hasta?->format('d/m/Y') ?? 'Hasta nuevo cambio' }}</td>
                        <td class="px-3 py-3">
                            {{ $tarifa->vigente_desde->gt($hoyTarifa) ? 'Programada' : ($tarifa->vigente_hasta && $tarifa->vigente_hasta->lt($hoyTarifa) ? 'Finalizada' : 'Vigente') }}
                        </td>
                        <td class="min-w-[180px] whitespace-pre-line break-words px-3 py-3">{{ $tarifa->motivo ?? '—' }}</td>
                        <td class="px-3 py-3">
                            <div>{{ $tarifa->registradoPor?->name ?? 'Usuario no disponible' }}</div>
                            <div class="mt-1 text-slate-400">{{ $tarifa->created_at?->format('d/m/Y H:i') }}</div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-5 text-center text-slate-500">Sin cambios registrados. Se mantiene el cálculo automático por sueldo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
