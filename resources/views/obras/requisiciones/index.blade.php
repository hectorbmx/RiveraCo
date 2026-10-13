@extends('layouts.admin')

@section('title', 'Requisiciones de obra')

@section('content')
@php
    use App\Models\ObraRequisicion;

    $estadoLabel = function ($estado) {
        return match ((string) $estado) {
            ObraRequisicion::ESTADO_PENDIENTE_REVISION => 'Pendiente de revision',
            ObraRequisicion::ESTADO_AUTORIZADA => 'Autorizada',
            ObraRequisicion::ESTADO_RECHAZADA => 'Rechazada',
            ObraRequisicion::ESTADO_EN_SURTIDO => 'En surtido',
            ObraRequisicion::ESTADO_SURTIDA_PARCIAL => 'Surtida parcial',
            ObraRequisicion::ESTADO_SURTIDA => 'Surtida',
            ObraRequisicion::ESTADO_RECIBIDA => 'Recibida',
            ObraRequisicion::ESTADO_CANCELADA => 'Cancelada',
            default => str_replace('_', ' ', ucfirst((string) $estado)),
        };
    };
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Obra / Requisiciones</div>
            <h1 class="mt-1 text-2xl font-bold text-[#0B265A]">{{ $obra->nombre }}</h1>
            <p class="text-sm text-slate-500">Clave: <span class="font-mono">{{ $obra->clave_obra }}</span></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('obras.requisiciones.create', $obra) }}"
               class="inline-flex items-center justify-center rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900">
                Nueva requisicion
            </a>
            <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'requisiciones']) }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[#0B265A] shadow-sm transition hover:bg-slate-50">
                Volver a la obra
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $stats['total'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pendientes</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $stats['pendientes'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Autorizadas</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $stats['autorizadas'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">En surtido</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $stats['en_surtido'] ?? 0 }}</p>
        </div>
    </div>

    @if($requisiciones->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
            <p class="text-sm font-semibold text-slate-700">No hay requisiciones registradas.</p>
            <p class="mt-1 text-sm text-slate-500">Crea la primera cabecera para empezar a capturar partidas.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#0B265A] text-left text-white">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Folio</th>
                            <th class="px-4 py-3 font-semibold">Tipo</th>
                            <th class="px-4 py-3 font-semibold">Fecha solicitud</th>
                            <th class="px-4 py-3 font-semibold">Fecha requerida</th>
                            <th class="px-4 py-3 font-semibold">Partidas</th>
                            <th class="px-4 py-3 font-semibold">Solicita</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requisiciones as $requisicion)
                            <tr class="border-t border-slate-200 hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('obras.requisiciones.show', [$obra, $requisicion]) }}" class="text-[#0B265A] underline-offset-2 hover:underline">{{ $requisicion->folio }}</a></td>
                                <td class="px-4 py-3 text-slate-700">{{ $requisicion->tipo?->nombre ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($requisicion->fecha_solicitud)->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($requisicion->fecha_requerida)->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $requisicion->detalles_count ?? $requisicion->detalles()->count() }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $requisicion->solicitadoPor?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $estadoLabel($requisicion->estado) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection





