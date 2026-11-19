@extends('layouts.admin')
@section('title', 'Historial de versiones')

@section('content')
@php
    $revisionEnCurso = $presupuesto->version_origen_borrador_id !== null;
    $ultimaRevision = $versiones->first()?->revision;
@endphp
<div class="space-y-5">
    <a href="{{ route('presupuesto.show', $presupuesto) }}" class="text-sm font-medium text-[#0B265A] hover:underline">← Volver al presupuesto</a>
    <header class="rounded-2xl border border-slate-200 bg-white p-5">
        <h1 class="text-xl font-semibold text-slate-800">Historial de versiones</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $presupuesto->codigo_proyecto }} · {{ $presupuesto->nombreClienteVisible() }}</p>
    </header>
    @if($errors->revisionPresupuesto->any())
        <p role="alert" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->revisionPresupuesto->first() }}</p>
    @endif
    @if($revisionEnCurso)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
            <span>Borrador #{{ $presupuesto->consecutivo_cliente }}.{{ $ultimaRevision + 1 }} en curso</span>
            <a href="{{ route('presupuesto.show', [$presupuesto, 'tab' => 'pilas']) }}" class="font-semibold hover:underline">Continuar borrador</a>
        </div>
    @endif
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-[#0B265A] text-left text-white">
                <tr><th class="px-4 py-3">Versión</th><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Autor</th><th class="px-4 py-3">Motivo</th><th class="px-4 py-3 text-right">Total de venta</th><th class="px-4 py-3">Último movimiento</th><th class="px-4 py-3">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($versiones as $version)
                    <tr class="align-top">
                        <th scope="row" class="whitespace-nowrap px-4 py-3 text-left font-semibold">{{ $version->folio() }}</th>
                        <td class="whitespace-nowrap px-4 py-3">{{ $version->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $version->autor?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $version->motivo ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold">${{ number_format($version->total_venta, 2) }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('presupuesto.versiones.show', [$presupuesto, $version]) }}#seguimiento" class="font-semibold text-[#0B265A] hover:underline">
                                {{ $version->ultimoEvento?->etiqueta() ?? 'Registrar movimiento' }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('presupuesto.versiones.show', [$presupuesto, $version]) }}" class="font-semibold text-[#0B265A] hover:underline">Abrir</a>
                            <a href="{{ route('presupuesto.versiones.pdf', [$presupuesto, $version]) }}" target="_blank" rel="noopener"
                               aria-label="PDF de {{ $version->folio() }}" class="ml-2 font-semibold text-[#0B265A] hover:underline">PDF</a>
                            @unless($revisionEnCurso)
                                <details class="mt-2">
                                    <summary class="cursor-pointer font-semibold text-[#0B265A]">Crear revisión</summary>
                                    <form method="POST" action="{{ route('presupuesto.versiones.revisar', [$presupuesto, $version]) }}" class="mt-2 max-w-sm space-y-2">
                                        @csrf
                                        <input type="hidden" name="ultima_revision" value="{{ $ultimaRevision }}">
                                        <p class="text-slate-600">El borrador tomará los datos de {{ $version->folio() }}. Los cambios del borrador actual que aún no formen parte de una versión se reemplazarán.</p>
                                        <label class="block font-medium" for="motivo-{{ $version->id }}">Motivo de la revisión</label>
                                        <textarea name="motivo" id="motivo-{{ $version->id }}" rows="2" maxlength="2000" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                                        <button type="submit" class="rounded-lg bg-[#0B265A] px-3 py-2 font-semibold text-white">Crear borrador desde {{ $version->folio() }}</button>
                                    </form>
                                </details>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Todavía no hay versiones guardadas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
