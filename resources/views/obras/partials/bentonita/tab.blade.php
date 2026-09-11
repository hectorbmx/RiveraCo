@php
    $metaBentonita = (float) ($obra->bentonita_total ?? 0);
    $usadaBentonita = (float) ($avanceObra['bentonita'] ?? 0);
    $restanteBentonita = max($metaBentonita - $usadaBentonita, 0);
    $pctBentonita = $metaBentonita > 0 ? min(100, round(($usadaBentonita / $metaBentonita) * 100)) : 0;
    $registrosBentonita = collect($bentonitaRegistros ?? []);
@endphp

<div class="space-y-5">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Bentonita</h2>
            <p class="text-sm text-slate-500">Avance registrado desde las comisiones de residente.</p>
        </div>
        <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'general']) }}"
           class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
            Configurar obra
        </a>
    </div>

    <form method="POST" action="{{ route('obras.bentonita.update', $obra) }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        @csrf
        @method('PATCH')
        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 md:items-end">
            <div>
                <label for="bentonita_total_tab" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Base de trabajo</label>
                <div class="mt-2 flex rounded-xl shadow-sm">
                    <input type="number" step="0.01" min="0" id="bentonita_total_tab" name="bentonita_total"
                           class="block w-full rounded-l-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                           value="{{ old('bentonita_total', $obra->bentonita_total) }}">
                    <span class="inline-flex items-center rounded-r-xl border border-l-0 border-slate-200 bg-white px-3 text-sm font-semibold text-slate-500">m3</span>
                </div>
                @error('bentonita_total')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-[#FFC107] px-4 py-2 text-sm font-semibold text-[#0B265A] shadow hover:bg-[#e0ac05] transition">
                Guardar base
            </button>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Usado</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ number_format($usadaBentonita, 2) }}</p>
            <p class="text-xs text-slate-500">m3</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Restante</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ number_format($restanteBentonita, 2) }}</p>
            <p class="text-xs text-slate-500">m3</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Avance</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $pctBentonita }}%</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-[#0B265A]" style="width: {{ $pctBentonita }}%"></div>
            </div>
        </div>
    </div>

    @if($registrosBentonita->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <p class="text-sm text-slate-500">Aun no hay capturas de bentonita para esta obra.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Fecha</th>
                            <th class="px-4 py-3 font-semibold">Pila</th>
                            <th class="px-4 py-3 font-semibold">Residente</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 text-right font-semibold">Bentonita</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registrosBentonita as $registro)
                            <tr class="border-t border-slate-200 hover:bg-slate-50">
                                <td class="px-4 py-3 align-top text-slate-700">
                                    {{ optional($registro->comision?->fecha)->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    {{ $registro->comision?->pila?->nombre ?? $registro->comision?->pila?->tipo ?? '-' }}
                                </td>
                                <td class="px-4 py-3 align-top text-slate-700">
                                    {{ trim(($registro->comision?->residente?->Nombre ?? '') . ' ' . ($registro->comision?->residente?->Apellidos ?? '')) ?: '-' }}
                                </td>
                                <td class="px-4 py-3 align-top text-slate-600">
                                    {{ ucfirst((string) ($registro->comision?->estado ?? '-')) }}
                                </td>
                                <td class="px-4 py-3 align-top text-right font-bold text-[#0B265A]">
                                    {{ number_format((float) $registro->vol_bentonita, 2) }} m3
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>