@extends('layouts.admin')

@section('title', 'Solicitudes de material obra civil')

@section('content')
@php
    $statusClass = function (?string $status): string {
        return match ($status) {
            'aprobada', 'aprobada_parcial' => 'bg-emerald-100 text-emerald-800',
            'rechazada', 'cancelada' => 'bg-red-100 text-red-800',
            'convertida_a_oc' => 'bg-blue-100 text-blue-800',
            'en_revision', 'enviada' => 'bg-amber-100 text-amber-800',
            default => 'bg-slate-100 text-slate-700',
        };
    };

    $statusLabel = fn (?string $status): string => str_replace('_', ' ', strtoupper($status ?: 'sin estado'));
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? '-' }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Solicitudes de material</h1>
            <p class="text-sm text-slate-500">Folios enviados desde Ionic para esta obra civil.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('obra_civil.material-requests.index', [$obra, 'scope' => 'no_atendidas']) }}"
               class="rounded-lg border border-amber-700 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50">
                No atendidas
            </a>
            <a href="{{ route('obra_civil.insumos.index', $obra) }}"
               class="rounded-lg border border-[#0B265A] px-4 py-2 text-sm font-semibold text-[#0B265A] hover:bg-blue-50">
                Ver insumos
            </a>
            <a href="{{ route('obra_civil.details', $obra) }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver al detalle
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Total</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">No atendidas</div>
            <div class="mt-2 text-2xl font-bold text-amber-700">{{ number_format($stats['unattended'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Enviadas</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['enviada'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Aprobadas</div>
            <div class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($stats['aprobada'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Convertidas OC</div>
            <div class="mt-2 text-2xl font-bold text-blue-700">{{ number_format($stats['convertida_a_oc'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Rechazadas</div>
            <div class="mt-2 text-2xl font-bold text-red-700">{{ number_format($stats['rechazada'] ?? 0) }}</div>
        </div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('obra_civil.material-requests.index', $obra) }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="text-xs font-semibold uppercase text-slate-500">Buscar</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Folio, codigo o concepto"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs font-semibold uppercase text-slate-500">Estado</label>
                <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach(['enviada' => 'Enviada', 'en_revision' => 'En revision', 'aprobada' => 'Aprobada', 'aprobada_parcial' => 'Aprobada parcial', 'rechazada' => 'Rechazada', 'convertida_a_oc' => 'Convertida a OC', 'cancelada' => 'Cancelada'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="w-full rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]">
                    Filtrar
                </button>
            </div>
            <div class="md:col-span-4 flex flex-wrap gap-2 pt-1">
                <a href="{{ route('obra_civil.material-requests.index', $obra) }}"
                   class="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Limpiar filtros
                </a>
                <a href="{{ route('obra_civil.material-requests.index', [$obra, 'scope' => 'no_atendidas']) }}"
                   class="rounded-full border border-amber-700 px-3 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-50">
                    Ver no atendidas
                </a>
            </div>
        </form>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Folios</h2>
                <p class="text-sm text-slate-500">Presiona un folio para revisar su contenido.</p>
            </div>
            <div class="text-sm font-semibold text-slate-500">{{ number_format($requests->total()) }} resultado(s)</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Folio</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Estado</th>
                        <th class="px-4 py-3 text-left">Solicitante</th>
                        <th class="px-4 py-3 text-right">Partidas</th>
                        <th class="px-4 py-3 text-left">Notas</th>
                        <th class="px-4 py-3 text-left">Orden compra</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requests as $request)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-semibold text-blue-700">
                                <a href="{{ route('obra_civil.material-requests.show', [$obra, $request]) }}" class="hover:underline">
                                    {{ $request->folio }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ optional($request->submitted_at ?? $request->created_at)->format('d/m/Y') }}
                                <div class="text-xs text-slate-400">{{ optional($request->submitted_at ?? $request->created_at)->format('H:i') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-bold {{ $statusClass($request->status) }}">{{ $statusLabel($request->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $request->empleado->nombre ?? $request->user->name ?? 'Residente' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($request->items->count()) }}</td>
                            <td class="max-w-md px-4 py-3 text-slate-600">{{ $request->notes ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                @php
                                    $ordenesSolicitud = $request->items
                                        ->flatMap(fn ($item) => $item->ordenCompraDetalles)
                                        ->map(fn ($detalle) => $detalle->orden)
                                        ->filter()
                                        ->unique('id')
                                        ->values();
                                @endphp

                                @if($ordenesSolicitud->isNotEmpty())
                                    <div class="space-y-1">
                                        @foreach($ordenesSolicitud as $ordenSolicitud)
                                            @php
                                                $estadoOc = strtoupper((string) ($ordenSolicitud->estado ?? 'BORRADOR'));
                                                $estadoOcClass = in_array($estadoOc, ['AUTORIZADA', 'VERIFICADA'], true)
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : ($estadoOc === 'CANCELADA' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800');
                                            @endphp
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('ordenes_compra.edit', $ordenSolicitud) }}" class="font-semibold text-blue-700 hover:underline">
                                                    {{ $ordenSolicitud->folio ?? 'Ver OC' }}
                                                </a>
                                                <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $estadoOcClass }}">{{ $estadoOc }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($request->ordenCompra)
                                    <a href="{{ route('ordenes_compra.edit', $request->ordenCompra) }}" class="font-semibold text-blue-700 hover:underline">
                                        {{ $request->ordenCompra->folio ?? 'Ver OC' }}
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">Sin OC</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('obra_civil.material-requests.show', [$obra, $request]) }}"
                                   class="inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-[#0B265A] px-3 py-2 text-xs font-semibold text-[#0B265A] hover:bg-blue-50">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">
                                No hay solicitudes con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $requests->links() }}</div>
        @endif
    </section>
</div>
@endsection



