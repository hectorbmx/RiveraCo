@extends('layouts.admin')

@section('title', 'Referencia del preventivo')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Inicio del control preventivo</h1>
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
        <p class="text-sm text-slate-600">Usa esta opción para comenzar a contar el próximo servicio desde la lectura actual. Se registra como ajuste del control; los mantenimientos realizados conservan su historial.</p>
        <p class="mt-4 text-lg font-semibold text-[#0B265A]">Horómetro actual: {{ $preventivo['horometro_actual'] !== null ? number_format($preventivo['horometro_actual'], 2) . ' h' : 'Sin lectura' }}</p>
        <p class="mt-1 text-xs text-slate-500">El ajuste conserva esta lectura. Solo cambia el inicio del ciclo del servicio seleccionado.</p>

        @if($preventivo['horometro_actual'] !== null)
            <form method="POST" action="{{ route('maquinas.preventivo.store', $maquina) }}" class="mt-6 space-y-4" x-data="{ guardando: false }" @submit="guardando = true">
                @csrf
                <input type="hidden" name="horometro_esperado" value="{{ number_format($preventivo['horometro_actual'], 2, '.', '') }}">
                <div>
                    <label for="servicio_preventivo_tipo_id" class="block text-sm font-semibold text-slate-700">Servicio que se pondrá al día</label>
                    <select id="servicio_preventivo_tipo_id" name="servicio_preventivo_tipo_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                        @foreach($preventivo['servicios'] as $servicio)
                            <option value="{{ $servicio['servicio_id'] ?? '' }}" @selected((string) old('servicio_preventivo_tipo_id', $preventivo['servicio_id']) === (string) $servicio['servicio_id'])>
                                {{ $servicio['servicio_nombre'] }} — Nueva meta: {{ number_format($preventivo['horometro_actual'] + $servicio['intervalo_horas'], 2) }} h ({{ number_format($servicio['intervalo_horas'], 0) }} h{{ $servicio['intervalo_meses'] > 0 ? ' o ' . $servicio['intervalo_meses'] . ' meses' : '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="motivo" class="block text-sm font-semibold text-slate-700">Motivo del ajuste</label>
                    <textarea id="motivo" name="motivo" required maxlength="1000" rows="3" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]" placeholder="Por ejemplo: inicio del control preventivo en el sistema">{{ old('motivo') }}</textarea>
                </div>
                <p class="text-xs text-slate-500">Se guardarán la lectura, la fecha de hoy, tu usuario y el motivo. Cada servicio tiene su propio ciclo.</p>
                <button type="submit" :disabled="guardando" class="rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" x-text="guardando ? 'Guardando…' : 'Establecer referencia del servicio'">Establecer referencia del servicio</button>
            </form>
        @else
            <p class="mt-4 text-sm text-amber-700">Registra una lectura de horómetro antes de establecer el inicio del preventivo.</p>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <h2 class="bg-[#0B265A] px-5 py-3 text-sm font-semibold text-white">Historial de ajustes del preventivo</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="p-3">Fecha</th><th class="p-3">Servicio</th><th class="p-3">Lectura</th><th class="p-3">Usuario y motivo</th></tr></thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($historial as $referencia)
                        <tr><td class="p-3 whitespace-nowrap">{{ $referencia->fecha_referencia->format('d/m/Y H:i') }}</td><td class="p-3">{{ $referencia->servicio?->nombre ?? 'Servicio preventivo general' }}</td><td class="p-3 whitespace-nowrap">{{ number_format($referencia->horometro, 2) }} h</td><td class="p-3"><p class="font-semibold">{{ $referencia->usuario?->name ?? 'Usuario no disponible' }}</p><p class="text-slate-600">{{ $referencia->motivo }}</p></td></tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-slate-500">Sin ajustes registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4">{{ $historial->links() }}</div>
    </div>
</div>
@endsection
