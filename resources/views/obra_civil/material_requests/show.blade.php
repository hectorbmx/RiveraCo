@extends('layouts.admin')

@section('title', 'Detalle solicitud de material')

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

    $statusLabel = str_replace('_', ' ', strtoupper($materialRequest->status ?: 'sin estado'));
    $reviewNotes = $materialRequest->metadata['review_notes'] ?? null;
    $approvalNotes = $materialRequest->metadata['approval']['notes'] ?? null;
    $rejectionReason = $materialRequest->metadata['rejection']['reason'] ?? null;
    $isEditable = in_array($materialRequest->status, [
        \App\Models\ObraCivilMaterialRequest::STATUS_ENVIADA,
        \App\Models\ObraCivilMaterialRequest::STATUS_EN_REVISION,
    ], true);

    $totalRequested = $materialRequest->items->sum(fn ($item) => (float) $item->quantity);
    $totalApproved = $materialRequest->items->sum(fn ($item) => (float) ($item->approved_quantity ?? 0));
    $totalNotApproved = max(0, $totalRequested - $totalApproved);
    $ordenesSolicitud = $materialRequest->items
        ->flatMap(fn ($item) => $item->ordenCompraDetalles)
        ->map(fn ($detalle) => $detalle->orden)
        ->filter()
        ->unique('id')
        ->values();
@endphp

<div class="mx-auto max-w-7xl space-y-6" data-material-request-review>
    <div data-loading-overlay class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 backdrop-blur-sm">
        <div class="rounded-xl bg-white px-6 py-5 text-center shadow-xl">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-blue-200 border-t-[#0B265A]"></div>
            <div class="mt-3 text-sm font-semibold text-slate-800">Procesando solicitud...</div>
            <div class="mt-1 text-xs text-slate-500">Un momento, estamos guardando la revision.</div>
        </div>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? '-' }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Solicitud {{ $materialRequest->folio }}</h1>
            <p class="text-sm text-slate-500">Revision administrativa del material solicitado desde Ionic.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('obra_civil.material-requests.index', $obra) }}"
               class="rounded-lg border border-[#0B265A] px-4 py-2 text-sm font-semibold text-[#0B265A] hover:bg-blue-50">
                Volver a solicitudes
            </a>
            <a href="{{ route('obra_civil.insumos.index', $obra) }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Ver insumos
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <div class="font-bold">Hay detalles por corregir:</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-4">
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Estado</div>
                <div class="mt-2"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $statusClass($materialRequest->status) }}">{{ $statusLabel }}</span></div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Fecha envio</div>
                <div class="mt-1 font-semibold text-slate-900">{{ optional($materialRequest->submitted_at ?? $materialRequest->created_at)->format('d/m/Y H:i') }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Solicitante</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $materialRequest->empleado->nombre ?? $materialRequest->user->name ?? 'Residente' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Partidas</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format($materialRequest->items->count()) }}</div>
            </div>
        </div>

        @if($materialRequest->reviewed_at || $reviewNotes || $approvalNotes || $rejectionReason || $materialRequest->ordenCompra || $ordenesSolicitud->isNotEmpty())
            <div class="space-y-1 border-t border-slate-100 px-5 py-3 text-sm text-slate-600">
                @if($materialRequest->reviewed_at)
                    <div>
                        Revisada por <span class="font-semibold text-slate-900">{{ $materialRequest->reviewedBy->name ?? '-' }}</span>
                        el {{ $materialRequest->reviewed_at->format('d/m/Y H:i') }}.
                    </div>
                @endif
                @if($reviewNotes)
                    <div><span class="font-semibold text-slate-900">Nota revision:</span> {{ $reviewNotes }}</div>
                @endif
                @if($approvalNotes)
                    <div><span class="font-semibold text-slate-900">Nota aprobacion:</span> {{ $approvalNotes }}</div>
                @endif
                @if($rejectionReason)
                    <div><span class="font-semibold text-slate-900">Motivo rechazo:</span> {{ $rejectionReason }}</div>
                @endif
                @if($ordenesSolicitud->isNotEmpty())
                    <div>
                        <span class="font-semibold text-slate-900">Ordenes vinculadas:</span>
                        <div class="mt-1 flex flex-wrap gap-2">
                            @foreach($ordenesSolicitud as $ordenSolicitud)
                                @php
                                    $estadoOc = strtoupper((string) ($ordenSolicitud->estado ?? 'BORRADOR'));
                                    $estadoOcClass = in_array($estadoOc, ['AUTORIZADA', 'VERIFICADA'], true)
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : ($estadoOc === 'CANCELADA' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800');
                                @endphp
                                <a href="{{ route('ordenes_compra.edit', $ordenSolicitud) }}" class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                    {{ $ordenSolicitud->folio ?? 'Ver OC' }}
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $estadoOcClass }}">{{ $estadoOc }}</span>
                                </a>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Solo las OCs autorizadas/verificadas afectan la cantidad usada y disponible en insumos.</p>
                    </div>
                @elseif($materialRequest->ordenCompra)
                    <a href="{{ route('ordenes_compra.edit', $materialRequest->ordenCompra) }}" class="inline-flex font-semibold text-blue-700 hover:underline">
                        Ver orden {{ $materialRequest->ordenCompra->folio ?? '' }}
                    </a>
                @endif
            </div>
        @endif
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:col-span-2">
            <div class="text-xs font-semibold uppercase text-slate-500">Notas generales del residente</div>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $materialRequest->notes ?: 'Sin notas generales.' }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Solicitado</div>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalRequested, 4) }}</p>
            <p class="text-xs text-slate-500">Suma general de cantidades.</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Autorizado</div>
            <p data-approved-total class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($totalApproved, 4) }}</p>
            <p data-not-approved-total class="text-xs text-slate-500">No autorizado: {{ number_format($totalNotApproved, 4) }}</p>
        </div>
    </section>

    @if($isEditable)
        <section class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <div class="font-bold">Regla operativa</div>
            <p class="mt-1">La cantidad no autorizada queda cerrada en esta revision. No se arrastra como pendiente; si obra necesita mas material, el residente debera solicitarlo despues.</p>
        </section>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Contenido de la solicitud</h2>
                <p class="text-sm text-slate-500">Captura la cantidad autorizada por administracion.</p>
            </div>
            @if($isEditable)
                <form method="POST" action="{{ route('obra_civil.material-requests.approve-full', [$obra, $materialRequest]) }}" data-loading-form>
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        Aprobar completo
                    </button>
                </form>
            @endif
        </div>

        <form method="POST" action="{{ route('obra_civil.material-requests.approve-quantities', [$obra, $materialRequest]) }}" data-loading-form>
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1200px] text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Codigo</th>
                            <th class="px-4 py-3 text-left">Concepto</th>
                            <th class="px-4 py-3 text-left">Unidad</th>
                            <th class="px-4 py-3 text-right">Solicitado</th>
                            <th class="px-4 py-3 text-right">Autorizado</th>
                            <th class="px-4 py-3 text-right">No autorizado</th>
                            <th class="px-4 py-3 text-left">Orden compra</th>
                            <th class="px-4 py-3 text-left">Notas residente</th>
                            <th class="px-4 py-3 text-left">Notas aprobacion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($materialRequest->items as $index => $item)
                            @php
                                $insumo = $item->insumo;
                                $snapshot = $item->insumo_snapshot ?? [];
                                $requested = (float) $item->quantity;
                                $approvedValue = old("items.$index.approved_quantity", $item->approved_quantity !== null ? (float) $item->approved_quantity : $requested);
                                $notApproved = max(0, $requested - (float) $approvedValue);
                                $detallesOc = $item->ordenCompraDetalles;
                                $cantidadOcFinal = $detallesOc
                                    ->filter(fn ($detalle) => in_array(strtoupper((string) ($detalle->orden->estado ?? '')), ['AUTORIZADA', 'VERIFICADA'], true))
                                    ->sum(fn ($detalle) => (float) $detalle->cantidad);
                                $cantidadOcBorrador = $detallesOc
                                    ->filter(fn ($detalle) => in_array(strtoupper((string) ($detalle->orden->estado ?? '')), ['BORRADOR', 'PROGRAMADA'], true))
                                    ->sum(fn ($detalle) => (float) $detalle->cantidad);
                            @endphp
                            <tr class="align-top hover:bg-slate-50" data-request-item data-requested="{{ $requested }}">
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-700">
                                    {{ $insumo->codigo ?? $snapshot['codigo'] ?? '-' }}
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                </td>
                                <td class="px-4 py-3 text-slate-800">
                                    <div class="max-w-xl font-semibold">{{ $insumo->concepto ?? $snapshot['concepto'] ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->unit ?: ($insumo->unidad ?? $snapshot['unidad'] ?? '-') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($requested, 4) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <input
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        max="{{ $requested }}"
                                        name="items[{{ $index }}][approved_quantity]"
                                        value="{{ $approvedValue }}"
                                        data-approved-input
                                        @disabled(! $isEditable)
                                        class="w-32 rounded-lg border border-slate-300 bg-white px-3 py-2 text-right font-semibold text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:bg-slate-100 disabled:text-slate-500"
                                    >
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span data-not-approved class="font-semibold text-amber-700">{{ number_format($notApproved, 4) }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if($detallesOc->isNotEmpty())
                                        <div class="space-y-2">
                                            @foreach($detallesOc as $detalleOc)
                                                @php
                                                    $ordenDetalle = $detalleOc->orden;
                                                    $estadoDetalle = strtoupper((string) ($ordenDetalle->estado ?? 'BORRADOR'));
                                                    $estadoDetalleClass = in_array($estadoDetalle, ['AUTORIZADA', 'VERIFICADA'], true)
                                                        ? 'bg-emerald-100 text-emerald-800'
                                                        : ($estadoDetalle === 'CANCELADA' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800');
                                                @endphp
                                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                                    <a href="{{ $ordenDetalle ? route('ordenes_compra.edit', $ordenDetalle) : '#' }}" class="font-semibold text-blue-700 hover:underline">
                                                        {{ $ordenDetalle->folio ?? 'OC sin folio' }}
                                                    </a>
                                                    <span class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $estadoDetalleClass }}">{{ $estadoDetalle }}</span>
                                                    <div class="mt-1 text-xs text-slate-500">Cantidad: {{ number_format((float) $detalleOc->cantidad, 4) }} {{ $item->unit ?: ($insumo->unidad ?? $snapshot['unidad'] ?? '') }}</div>
                                                </div>
                                            @endforeach
                                            <div class="text-xs text-slate-500">
                                                En OC autorizada/verificada: <span class="font-semibold text-emerald-700">{{ number_format($cantidadOcFinal, 4) }}</span><br>
                                                En OC borrador/programada: <span class="font-semibold text-amber-700">{{ number_format($cantidadOcBorrador, 4) }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">Sin OC</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="max-w-xs whitespace-pre-line">{{ $item->notes ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <textarea
                                        name="items[{{ $index }}][approval_notes]"
                                        rows="2"
                                        @disabled(! $isEditable)
                                        class="w-72 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:bg-slate-100 disabled:text-slate-500"
                                        placeholder="Motivo si se modifica cantidad"
                                    >{{ old("items.$index.approval_notes", $item->approval_notes) }}</textarea>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-500">Esta solicitud no tiene insumos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($isEditable)
                <div class="space-y-3 border-t border-slate-100 px-5 py-4">
                    <label class="block text-sm font-semibold text-slate-700" for="approval_notes">Nota general de aprobacion</label>
                    <textarea id="approval_notes" name="approval_notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Opcional: criterio general de autorizacion">{{ old('approval_notes') }}</textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-[#0B265A] px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-900">
                            Aprobar cantidades capturadas
                        </button>
                    </div>
                </div>
            @endif
        </form>
    </section>

    @if($isEditable)
        <section class="rounded-lg border border-red-200 bg-white p-5 shadow-sm">
            <form method="POST" action="{{ route('obra_civil.material-requests.reject', [$obra, $materialRequest]) }}" data-loading-form class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-red-800" for="rejection_reason">Rechazar solicitud</label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="3" class="mt-2 w-full rounded-lg border border-red-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-100" placeholder="Opcional: motivo de rechazo">{{ old('rejection_reason') }}</textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg border border-red-300 px-5 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                        Rechazar solicitud
                    </button>
                </div>
            </form>
        </section>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-material-request-review]');
        if (!root) return;

        const overlay = root.querySelector('[data-loading-overlay]');
        const formatter = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 4,
            maximumFractionDigits: 4,
        });

        const recalculate = () => {
            let approvedTotal = 0;
            let notApprovedTotal = 0;

            root.querySelectorAll('[data-request-item]').forEach((row) => {
                const requested = Number.parseFloat(row.dataset.requested || '0') || 0;
                const input = row.querySelector('[data-approved-input]');
                const approved = Math.max(0, Number.parseFloat(input?.value || '0') || 0);
                const notApproved = Math.max(0, requested - approved);

                approvedTotal += approved;
                notApprovedTotal += notApproved;

                const badge = row.querySelector('[data-not-approved]');
                if (badge) badge.textContent = formatter.format(notApproved);
            });

            const approvedTotalNode = root.querySelector('[data-approved-total]');
            if (approvedTotalNode) approvedTotalNode.textContent = formatter.format(approvedTotal);

            const notApprovedTotalNode = root.querySelector('[data-not-approved-total]');
            if (notApprovedTotalNode) notApprovedTotalNode.textContent = `No autorizado: ${formatter.format(notApprovedTotal)}`;
        };

        root.querySelectorAll('[data-approved-input]').forEach((input) => {
            input.addEventListener('input', recalculate);
        });

        root.querySelectorAll('[data-loading-form]').forEach((form) => {
            form.addEventListener('submit', () => {
                overlay?.classList.remove('hidden');
                overlay?.classList.add('flex');
                form.querySelectorAll('button[type="submit"]').forEach((button) => {
                    button.disabled = true;
                    button.classList.add('opacity-70', 'cursor-not-allowed');
                });
            });
        });

        recalculate();
    });
</script>
@endsection

