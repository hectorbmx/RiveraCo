@php
    $ordenes = $ordenesCompraObra ?? collect();
    $totalOrdenes = $ordenes->count();
    $montoTotal = (float) $ordenes->sum('total');
    $ordenesAutorizadas = $ordenes->filter(fn ($orden) => strtolower((string) ($orden->estado ?? '')) === 'autorizada')->count();
    $ordenesPendientes = $ordenes->filter(fn ($orden) => ! in_array(strtolower((string) ($orden->estado ?? '')), ['autorizada', 'verificada', 'cancelada'], true))->count();

    $estadoBadge = function ($estado) {
        $estado = strtolower(trim((string) $estado));

        return match ($estado) {
            'autorizada', 'autorizado' => 'bg-green-100 text-green-800 border-green-200',
            'verificada', 'verificado' => 'bg-teal-100 text-teal-800 border-teal-200',
            'cancelada', 'cancelado' => 'bg-red-100 text-red-800 border-red-200',
            'borrador', 'programada' => 'bg-slate-100 text-slate-800 border-slate-200',
            'pendiente' => 'bg-amber-100 text-amber-800 border-amber-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    };

    $formaPagoLabel = function ($formaPago) {
        return match ((string) $formaPago) {
            '01' => 'Efectivo',
            '02' => 'Cheque',
            '03' => 'Transferencia',
            '04' => 'Tarjeta',
            '28' => 'Tarjeta débito',
            '99' => 'Por definir',
            default => 'Sin definir',
        };
    };
@endphp

<div class="space-y-5">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Órdenes de compra de la obra</h2>
            <p class="text-sm text-slate-500">Resumen de las compras asociadas a esta obra.</p>
        </div>
        <a href="{{ route('ordenes_compra.create', ['obra_id' => $obra->id]) }}"
           class="inline-flex items-center justify-center rounded-xl bg-[#FFC107] px-4 py-2 text-sm font-semibold text-[#0B265A] shadow hover:bg-[#e0ac05] transition">
            + Nueva orden
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total de órdenes</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $totalOrdenes }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Monto total</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">${{ number_format($montoTotal, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Autorizadas / pendientes</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $ordenesAutorizadas }} / {{ $ordenesPendientes }}</p>
        </div>
    </div>

    @if($ordenes->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <p class="text-sm text-slate-500">No hay órdenes de compra asociadas a esta obra.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Folio</th>
                            <th class="px-4 py-3 font-semibold">Proveedor</th>
                            <th class="px-4 py-3 font-semibold">Área</th>
                            <th class="px-4 py-3 font-semibold">Fecha</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 font-semibold">Pago</th>
                            <th class="px-4 py-3 text-right font-semibold">Total</th>
                            <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ordenes as $orden)
                            <tr class="border-t border-slate-200 hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold text-[#0B265A]">{{ $orden->folio }}</div>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @if($orden->es_caja_chica)
                                            <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-700">Caja chica</span>
                                        @endif
                                        @if($orden->gastos_sin_factura)
                                            <span class="rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-violet-700">Sin factura</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if($orden->proveedor)
                                        <div class="font-medium text-slate-800">{{ $orden->proveedor->nombre }}</div>
                                        @if($orden->proveedor->rfc)
                                            <div class="text-xs text-slate-500">{{ $orden->proveedor->rfc }}</div>
                                        @endif
                                    @else
                                        <span class="text-slate-400">Sin proveedor</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-slate-600">
                                    {{ $orden->areaCatalogo->nombre ?? $orden->area ?? 'Sin área' }}
                                </td>
                                <td class="px-4 py-3 align-top text-slate-600">
                                    {{ optional($orden->fecha)->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $estadoBadge($orden->estado_normalizado ?? $orden->estado ?? 'borrador') }}">
                                        {{ ucfirst((string) ($orden->estado ?? 'borrador')) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-600">
                                    {{ $formaPagoLabel($orden->forma_pago) }}
                                </td>
                                <td class="px-4 py-3 align-top text-right font-bold text-[#0B265A]">
                                    ${{ number_format((float) ($orden->total ?? 0), 2) }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('ordenes_compra.edit', $orden->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            Editar
                                        </a>
                                        <a href="{{ route('ordenes_compra.print', $orden->id) }}" target="_blank" class="text-sm font-medium text-slate-600 hover:text-slate-800">
                                            PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
