@extends('layouts.admin')

@section('title', 'Revision de campo obra civil')

@section('content')
@php
    $statusClass = function (string $status): string {
        return match ($status) {
            'aprobado', 'aprobada' => 'bg-emerald-100 text-emerald-800',
            'rechazado', 'rechazada' => 'bg-red-100 text-red-800',
            'convertido_a_estimacion', 'convertida_a_oc' => 'bg-blue-100 text-blue-800',
            'en_revision' => 'bg-amber-100 text-amber-800',
            default => 'bg-slate-100 text-slate-700',
        };
    };

    $reviewableWorkStatuses = ['pendiente', 'en_revision'];
    $reviewableMaterialStatuses = ['enviada', 'en_revision'];
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? '-' }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Revision de campo</h1>
            <p class="text-sm text-slate-500">Avances y solicitudes de material capturadas desde la app movil.</p>
        </div>
        <div class="flex items-center gap-2">
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

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Avances pendientes</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['work_pending']) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Avances aprobados</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['work_approved']) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Material pendiente</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['material_pending']) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Material aprobado</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['material_approved']) }}</div>
        </div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Reportes de avance</h2>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($workReports as $report)
                <article class="space-y-4 px-5 py-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2 py-1 text-xs font-bold {{ $statusClass($report->status) }}">{{ str_replace('_', ' ', strtoupper($report->status)) }}</span>
                                <span class="text-sm font-semibold text-slate-900">{{ $report->empleado->nombre ?? $report->user->name ?? 'Residente' }}</span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Enviado: {{ optional($report->submitted_at ?? $report->created_at)->format('d/m/Y H:i') }}
                                @if($report->reviewed_at)
                                    / Revisado por {{ $report->reviewedBy->name ?? '-' }} el {{ $report->reviewed_at->format('d/m/Y H:i') }}
                                @endif
                            </div>
                            @if($report->notes)
                                <p class="mt-2 text-sm text-slate-700">{{ $report->notes }}</p>
                            @endif
                        </div>

                        @if(in_array($report->status, $reviewableWorkStatuses, true))
                            <div class="grid gap-2 md:min-w-80">
                                <form method="POST" action="{{ route('obra_civil.field-review.reports.approve', [$obra, $report]) }}" class="flex gap-2" data-loading-message="Aprobando avance...">
                                    @csrf
                                    @method('PATCH')
                                    <input name="review_notes" type="text" placeholder="Nota opcional" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Aprobar</button>
                                </form>
                                <form method="POST" action="{{ route('obra_civil.field-review.reports.reject', [$obra, $report]) }}" class="flex gap-2" data-loading-message="Rechazando avance...">
                                    @csrf
                                    @method('PATCH')
                                    <input name="review_notes" type="text" placeholder="Motivo opcional" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-800">Rechazar</button>
                                </form>
                            </div>
                        @elseif($report->status === 'aprobado')
                            <form method="POST"
                                  action="{{ route('obra_civil.field-review.reports.convert-estimation', [$obra, $report]) }}"
                                  data-loading-message="Convirtiendo avance a estimacion..."
                                  onsubmit="return confirm('Convertir este avance aprobado a una estimacion confirmada?');">
                                @csrf
                                <button class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]">
                                    Convertir a estimacion
                                </button>
                            </form>
                        @elseif($report->status === 'convertido_a_estimacion' && !empty($report->metadata['estimation_id'] ?? null))
                            <a href="{{ route('obra_civil.estimations.show', [$obra, $report->metadata['estimation_id']]) }}"
                               class="rounded-lg border border-[#0B265A] px-4 py-2 text-sm font-semibold text-[#0B265A] hover:bg-blue-50">
                                Ver estimacion {{ $report->metadata['estimation_folio'] ?? '' }}
                            </a>
                        @endif
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full min-w-[900px] text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Clave</th>
                                    <th class="px-3 py-2 text-left">Concepto</th>
                                    <th class="px-3 py-2 text-left">Partida</th>
                                    <th class="px-3 py-2 text-right">Cantidad</th>
                                    <th class="px-3 py-2 text-left">Fotos</th>
                                    <th class="px-3 py-2 text-left">Notas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($report->items as $item)
                                    @php
                                        $snapshot = $item->concept_snapshot ?? [];
                                        $concept = $item->concept;
                                    @endphp
                                    <tr class="align-top">
                                        <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ $concept->excel_code ?? $snapshot['excel_code'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-slate-800">{{ $concept->description ?? $snapshot['description'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-slate-600">
                                            <div class="font-semibold">{{ $concept->partida->code ?? $snapshot['partida_code'] ?? '-' }}</div>
                                            <div class="text-xs">{{ $concept->partida->building->name ?? $snapshot['building'] ?? '' }}</div>
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums font-semibold">{{ number_format((float) $item->quantity, 4) }} {{ $item->unit }}</td>
                                        <td class="px-3 py-2">
                                            <div class="flex flex-wrap gap-2">
                                                @forelse($item->photos as $photo)
                                                    <a href="{{ Storage::disk('public')->url($photo->path) }}" target="_blank" class="rounded-md border border-slate-200 px-2 py-1 text-xs font-semibold text-[#0B265A] hover:bg-blue-50">
                                                        Foto {{ $loop->iteration }}
                                                    </a>
                                                @empty
                                                    <span class="text-xs text-slate-400">Sin fotos</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-slate-600">{{ $item->notes ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-500">Aun no hay reportes de avance desde campo.</div>
            @endforelse
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Solicitudes de material</h2>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($materialRequests as $request)
                <article class="space-y-4 px-5 py-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-sm font-bold text-[#0B265A]">{{ $request->folio }}</span>
                                <span class="rounded-full px-2 py-1 text-xs font-bold {{ $statusClass($request->status) }}">{{ str_replace('_', ' ', strtoupper($request->status)) }}</span>
                                <span class="text-sm font-semibold text-slate-900">{{ $request->empleado->nombre ?? $request->user->name ?? 'Residente' }}</span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Enviada: {{ optional($request->submitted_at ?? $request->created_at)->format('d/m/Y H:i') }}
                                @if($request->reviewed_at)
                                    / Revisada por {{ $request->reviewedBy->name ?? '-' }} el {{ $request->reviewed_at->format('d/m/Y H:i') }}
                                @endif
                            </div>
                            @if($request->notes)
                                <p class="mt-2 text-sm text-slate-700">{{ $request->notes }}</p>
                            @endif
                        </div>

                        @if(in_array($request->status, $reviewableMaterialStatuses, true))
                            <div class="grid gap-2 md:min-w-80">
                                <form method="POST" action="{{ route('obra_civil.field-review.material.approve', [$obra, $request]) }}" class="flex gap-2" data-loading-message="Aprobando solicitud...">
                                    @csrf
                                    @method('PATCH')
                                    <input name="review_notes" type="text" placeholder="Nota opcional" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Aprobar</button>
                                </form>
                                <form method="POST" action="{{ route('obra_civil.field-review.material.reject', [$obra, $request]) }}" class="flex gap-2" data-loading-message="Rechazando solicitud...">
                                    @csrf
                                    @method('PATCH')
                                    <input name="review_notes" type="text" placeholder="Motivo opcional" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-800">Rechazar</button>
                                </form>
                            </div>
                        @elseif($request->status === 'aprobada')
                            <form method="POST"
                                  action="{{ route('obra_civil.field-review.material.convert-oc', [$obra, $request]) }}"
                                  data-loading-message="Convirtiendo solicitud a orden de compra..."
                                  onsubmit="return confirm('Convertir esta solicitud aprobada a una orden de compra borrador?');">
                                @csrf
                                <button class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]">
                                    Convertir a OC
                                </button>
                            </form>
                        @elseif($request->status === 'convertida_a_oc' && $request->orden_compra_id)
                            <a href="{{ route('ordenes_compra.edit', $request->orden_compra_id) }}"
                               class="rounded-lg border border-[#0B265A] px-4 py-2 text-sm font-semibold text-[#0B265A] hover:bg-blue-50">
                                Ver OC {{ $request->metadata['orden_compra_folio'] ?? '' }}
                            </a>
                        @endif
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Codigo</th>
                                    <th class="px-3 py-2 text-left">Insumo</th>
                                    <th class="px-3 py-2 text-right">Cantidad</th>
                                    <th class="px-3 py-2 text-left">Notas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($request->items as $item)
                                    @php
                                        $snapshot = $item->insumo_snapshot ?? [];
                                        $insumo = $item->insumo;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ $insumo->codigo ?? $snapshot['codigo'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-slate-800">{{ $insumo->concepto ?? $snapshot['concepto'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums font-semibold">{{ number_format((float) $item->quantity, 4) }} {{ $item->unit }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $item->notes ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-500">Aun no hay solicitudes de material desde campo.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection


