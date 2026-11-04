@extends('layouts.admin')

@section('title', 'Meta del preventivo')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Ajustar meta del servicio</h1>
            <p class="text-sm text-slate-600">{{ $maquina->codigo }} · {{ $maquina->nombre }}</p>
        </div>
        <a href="{{ route('maquinas.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700">Volver a máquinas</a>
    </div>

    @if(session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <p class="text-sm text-slate-600">Indica la lectura de horómetro a la que debe realizarse el próximo servicio. La meta que captures se conservará al registrar nuevas horas. Un mantenimiento completado posterior iniciará el siguiente ciclo.</p>
        <p class="mt-4 text-lg font-semibold text-[#0B265A]">Horómetro actual: {{ $preventivo['horometro_actual'] !== null ? number_format($preventivo['horometro_actual'], 2) . ' h' : 'Sin lectura' }}</p>
        <p class="mt-1 text-xs text-slate-500">El ajuste conserva esta lectura y el historial de mantenimientos. Cada tipo de servicio tiene su propia meta.</p>

        @if($preventivo['horometro_actual'] !== null)
            @php
                $servicioSeleccionado = collect($preventivo['servicios'])->first(fn ($servicio) => (string) $servicio['servicio_id'] === (string) old('servicio_preventivo_tipo_id', $preventivo['servicio_id'])) ?? $preventivo['servicios'][0];
                $metaInicial = old('meta_horometro', $servicioSeleccionado['proximo_horometro'] ?? ($preventivo['horometro_actual'] + $servicioSeleccionado['intervalo_horas']));
            @endphp
            <form method="POST" action="{{ route('maquinas.preventivo.store', $maquina) }}" class="mt-6 space-y-4" x-data="{ guardando: false, meta: @js((string) $metaInicial) }" @submit="guardando = true">
                @csrf
                <input type="hidden" name="horometro_esperado" value="{{ number_format($preventivo['horometro_actual'], 2, '.', '') }}">
                <div>
                    <label for="servicio_preventivo_tipo_id" class="block text-sm font-semibold text-slate-700">Servicio</label>
                    <select id="servicio_preventivo_tipo_id" name="servicio_preventivo_tipo_id" x-ref="servicio" @change="meta = $event.target.selectedOptions[0].dataset.meta" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                        @foreach($preventivo['servicios'] as $servicio)
                            <option value="{{ $servicio['servicio_id'] ?? '' }}" data-meta="{{ $servicio['proximo_horometro'] ?? ($preventivo['horometro_actual'] + $servicio['intervalo_horas']) }}" data-sugerida="{{ $preventivo['horometro_actual'] + $servicio['intervalo_horas'] }}" @selected((string) old('servicio_preventivo_tipo_id', $preventivo['servicio_id']) === (string) $servicio['servicio_id'])>
                                {{ $servicio['servicio_nombre'] }} — Meta actual: {{ number_format($servicio['proximo_horometro'] ?? ($preventivo['horometro_actual'] + $servicio['intervalo_horas']), 2) }} h ({{ number_format($servicio['intervalo_horas'], 0) }} h{{ $servicio['intervalo_meses'] > 0 ? ' o ' . $servicio['intervalo_meses'] . ' meses' : '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="meta_horometro" class="block text-sm font-semibold text-slate-700">Nueva meta de horómetro (h)</label>
                    <input id="meta_horometro" name="meta_horometro" type="number" min="0" max="99999999.99" step="0.01" required x-model="meta" value="{{ $metaInicial }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                    <button type="button" @click="meta = $refs.servicio.selectedOptions[0].dataset.sugerida" class="mt-2 text-xs font-semibold text-[#0B265A] hover:underline">Usar horómetro actual + intervalo del servicio</button>
                    <p class="mt-1 text-xs text-slate-500">Por ejemplo: con 6,474 h actuales puedes fijar la meta en 6,600 h. Una meta igual o menor a la lectura actual se mostrará como servicio requerido o vencido.</p>
                </div>
                <div>
                    <label for="motivo" class="block text-sm font-semibold text-slate-700">Motivo del ajuste</label>
                    <textarea id="motivo" name="motivo" required maxlength="1000" rows="3" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]" placeholder="Por ejemplo: inicio del control preventivo en el sistema">{{ old('motivo') }}</textarea>
                </div>
                <p class="text-xs text-slate-500">Se guardarán la lectura actual, la meta elegida, la fecha de hoy, tu usuario y el motivo. Se conserva el plazo por meses del servicio.</p>
                <button type="submit" :disabled="guardando" class="rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" x-text="guardando ? 'Guardando…' : 'Guardar meta del servicio'">Guardar meta del servicio</button>
            </form>
        @else
            <p class="mt-4 text-sm text-amber-700">Registra una lectura de horómetro antes de establecer el inicio del preventivo.</p>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <h2 class="bg-[#0B265A] px-5 py-3 text-sm font-semibold text-white">Historial de ajustes del preventivo</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="p-3">Fecha</th><th class="p-3">Servicio</th><th class="p-3">Lectura</th><th class="p-3">Meta elegida</th><th class="p-3">Usuario y motivo</th></tr></thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($historial as $referencia)
                        <tr><td class="p-3 whitespace-nowrap">{{ $referencia->fecha_referencia->format('d/m/Y H:i') }}</td><td class="p-3">{{ $referencia->servicio?->nombre ?? 'Servicio preventivo general' }}</td><td class="p-3 whitespace-nowrap">{{ number_format($referencia->horometro, 2) }} h</td><td class="p-3 whitespace-nowrap">{{ $referencia->meta_horometro !== null ? number_format($referencia->meta_horometro, 2) . ' h' : 'Según intervalo' }}</td><td class="p-3"><p class="font-semibold">{{ $referencia->usuario?->name ?? 'Usuario no disponible' }}</p><p class="text-slate-600">{{ $referencia->motivo }}</p></td></tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-slate-500">Sin ajustes registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4">{{ $historial->links() }}</div>
    </div>
</div>
@endsection
