@extends('layouts.admin')

@section('title', 'Detalle extension')

@section('content')
<div class="max-w-7xl mx-auto space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('telephony.extensions.index', ['tab' => 'extensions']) }}" class="text-sm font-medium text-blue-700 hover:text-blue-900 hover:underline">Volver a extensiones</a>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">Extension {{ $phoneExtension->extension }}</h1>
            <p class="text-sm text-slate-500">{{ $phoneExtension->fullname ?: 'Sin nombre UCM' }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
            <div class="font-semibold text-slate-900">Usuario SIRICO</div>
            <div>{{ $phoneExtension->user?->name ?: 'Sin asignar' }}</div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Llamadas relacionadas</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $stats['total'] }}</div>
        </div>
        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">Realizadas</div>
            <div class="mt-1 text-2xl font-semibold text-blue-900">{{ $stats['made'] }}</div>
        </div>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Contestadas</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-900">{{ $stats['answered'] }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Duracion total</div>
            @php
                $totalDuration = (int) $stats['duration'];
                $totalDurationLabel = sprintf('%02d:%02d:%02d', floor($totalDuration / 3600), floor(($totalDuration % 3600) / 60), $totalDuration % 60);
            @endphp
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $totalDurationLabel }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-slate-900">Historial de llamadas</h2>
            <p class="text-sm text-slate-500">Llamadas donde la extension participa como origen, destino o extension asociada.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Direccion</th>
                        <th class="px-4 py-3">Origen</th>
                        <th class="px-4 py-3">Destino</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Duracion</th>
                        <th class="px-4 py-3">CDR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($calls as $call)
                        @php
                            $duration = (int) $call->duration_seconds;
                            $durationLabel = sprintf('%02d:%02d:%02d', floor($duration / 3600), floor(($duration % 3600) / 60), $duration % 60);
                            $statusClass = [
                                'answered' => 'bg-emerald-50 text-emerald-700',
                                'no_answer' => 'bg-amber-50 text-amber-700',
                                'busy' => 'bg-orange-50 text-orange-700',
                                'failed' => 'bg-red-50 text-red-700',
                            ][$call->status] ?? 'bg-slate-100 text-slate-700';
                        @endphp
                        <tr class="align-top hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                                <div>{{ optional($call->started_at)->format('Y-m-d') ?: '-' }}</div>
                                <div class="text-xs text-slate-400">{{ optional($call->started_at)->format('H:i:s') }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $call->direction ? ucfirst($call->direction) : 'N/D' }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                <div class="font-medium text-slate-900">{{ $call->source_number ?: $call->source_extension ?: '-' }}</div>
                                @if ($call->source_extension)
                                    <div class="text-xs text-slate-400">Ext. {{ $call->source_extension }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <div class="font-medium text-slate-900">{{ $call->destination_number ?: $call->destination_extension ?: '-' }}</div>
                                @if ($call->destination_extension)
                                    <div class="text-xs text-slate-400">Ext. {{ $call->destination_extension }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ $call->status ? str_replace('_', ' ', ucfirst($call->status)) : ($call->disposition ?: 'N/D') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                                <div>{{ $durationLabel }}</div>
                                <div class="text-xs text-slate-400">Billsec: {{ (int) $call->billsec }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                <div class="font-mono text-slate-700">{{ $call->ucm_cdr_id }}</div>
                                @if ($call->session)
                                    <div class="mt-1 max-w-xs truncate" title="{{ $call->session }}">{{ $call->session }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
                                No hay llamadas relacionadas con esta extension.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-4 py-3">
            {{ $calls->links() }}
        </div>
    </div>
</div>
@endsection