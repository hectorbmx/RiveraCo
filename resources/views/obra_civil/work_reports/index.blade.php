@extends('layouts.admin')

@section('title', 'Avances reportados obra civil')

@section('content')
@php
    $statusClass = function (?string $status): string {
        return match ($status) {
            'aprobado' => 'bg-emerald-100 text-emerald-800',
            'rechazado' => 'bg-red-100 text-red-800',
            'convertido_a_estimacion' => 'bg-blue-100 text-blue-800',
            'en_revision' => 'bg-amber-100 text-amber-800',
            'pendiente' => 'bg-slate-100 text-slate-800',
            default => 'bg-slate-100 text-slate-600',
        };
    };

    $statusLabel = function (?string $status): string {
        return str_replace('_', ' ', strtoupper($status ?: 'sin estado'));
    };
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? '-' }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Avances reportados</h1>
            <p class="text-sm text-slate-500">Reportes de campo capturados desde Ionic para seleccionar despues los aprobados y armar una estimacion.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('obra_civil.field-review.index', $obra) }}"
               class="rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                Revision campo
            </a>
            <a href="{{ route('obra_civil.details', $obra) }}"
               class="rounded-lg border border-[#0B265A] px-4 py-2 text-sm font-semibold text-[#0B265A] hover:bg-blue-50">
                Ver catalogo
            </a>
            <a href="{{ route('obra_civil.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Total reportes</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Pendientes</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['pendiente'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">En revision</div>
            <div class="mt-2 text-2xl font-bold text-amber-700">{{ number_format($stats['en_revision'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Aprobados</div>
            <div class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($stats['aprobado'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Pendientes de estimar</div>
            <div class="mt-2 text-2xl font-bold text-blue-700">{{ number_format($stats['aprobados_pendientes_estimacion'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Convertidos</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['convertido_a_estimacion'] ?? 0) }}</div>
        </div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('obra_civil.work-reports.index', $obra) }}" class="grid grid-cols-1 gap-3 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label class="text-xs font-semibold uppercase text-slate-500">Buscar</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Clave o descripcion"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs font-semibold uppercase text-slate-500">Estado</label>
                <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach(['pendiente' => 'Pendiente', 'en_revision' => 'En revision', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado', 'convertido_a_estimacion' => 'Convertido'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold uppercase text-slate-500">Desde</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs font-semibold uppercase text-slate-500">Hasta</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button class="w-full rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]">
                    Filtrar
                </button>
            </div>
            <div class="lg:col-span-6 flex flex-wrap gap-2 pt-1">
                <a href="{{ route('obra_civil.work-reports.index', $obra) }}"
                   class="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Limpiar filtros
                </a>
                <a href="{{ route('obra_civil.work-reports.index', [$obra, 'scope' => 'aprobados_pendientes_estimacion']) }}"
                   class="rounded-full border border-blue-700 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                    Ver aprobados pendientes de estimar
                </a>
            </div>
        </form>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Reportes</h2>
                <p class="text-sm text-slate-500">Cada fila representa un registro capturado por el residente para una partida.</p>
            </div>
            <div class="text-sm font-semibold text-slate-500">
                {{ number_format($reports->total()) }} resultado(s)
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Estado</th>
                        <th class="px-4 py-3 text-left">Clave</th>
                        <th class="px-4 py-3 text-left">Concepto</th>
                        <th class="px-4 py-3 text-left">Partida</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                        <th class="px-4 py-3 text-left">Residente</th>
                        <th class="px-4 py-3 text-center">Fotos</th>
                        <th class="px-4 py-3 text-left">Estimacion</th>
                        <th class="px-4 py-3 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reports as $report)
                        @php
                            $item = $report->items->first();
                            $concept = $item?->concept;
                            $snapshot = $item?->concept_snapshot ?? [];
                            $partida = $concept?->partida;
                            $building = $partida?->building;
                            $photosCount = $item?->photos?->count() ?? 0;
                            $estimationId = $report->metadata['estimation_id'] ?? null;
                        @endphp
                        <tr class="align-top hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-600">
                                {{ optional($report->submitted_at ?? $report->created_at)->format('d/m/Y') }}
                                <div class="text-xs text-slate-400">{{ optional($report->submitted_at ?? $report->created_at)->format('H:i') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-bold {{ $statusClass($report->status) }}">
                                    {{ $statusLabel($report->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-700">
                                {{ $concept->excel_code ?? $snapshot['excel_code'] ?? '-' }}
                            </td>
                            <td class="max-w-xl px-4 py-3 text-slate-800">
                                <div class="font-semibold">{{ $concept->description ?? $snapshot['description'] ?? '-' }}</div>
                                @if($item?->notes)
                                    <div class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $item->notes }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $building?->name ?: '-' }}
                                <div class="text-xs text-slate-400">{{ $partida?->code }} {{ $partida?->name }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                {{ number_format((float) ($item?->quantity ?? 0), 4) }}
                                <div class="text-xs text-slate-400">{{ $item?->unit ?: $concept?->unit }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $report->empleado->nombre ?? $report->user->name ?? 'Residente' }}
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-slate-700">
                                {{ number_format($photosCount) }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                @if($report->status === 'convertido_a_estimacion' && $estimationId)
                                    <a href="{{ route('obra_civil.estimations.show', [$obra, $estimationId]) }}" class="font-semibold text-blue-700 hover:underline">
                                        {{ $report->metadata['estimation_folio'] ?? 'Ver estimacion' }}
                                    </a>
                                @elseif($report->status === 'aprobado')
                                    <span class="text-xs font-semibold text-blue-700">Pendiente de estimar</span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('obra_civil.work-reports.show', [$obra, $report]) }}"
                                   class="inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-[#0B265A] px-3 py-2 text-xs font-semibold leading-none text-[#0B265A] hover:bg-blue-50">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-sm text-slate-500">
                                No hay reportes con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $reports->links() }}
            </div>
        @endif
    </section>
</div>
@endsection


