@extends('layouts.admin')

@section('title', 'Detalle de estimacion')

@section('content')
<style>
@media print {
    body { background: #fff !important; }
    .print-hidden, aside, nav, header { display: none !important; }
    main, .content, .max-w-7xl { max-width: none !important; margin: 0 !important; padding: 0 !important; }
    .print-surface { border: 0 !important; box-shadow: none !important; }
}
</style>

<div class="max-w-7xl mx-auto space-y-6">
    <div class="print-hidden flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? '-' }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">{{ $estimation->folio }}</h1>
            <p class="text-sm text-slate-500">Detalle de estimacion</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]"
                    onclick="window.print()">
                Imprimir
            </button>
            <a href="{{ route('obra_civil.estimations.index', $obra) }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="print-hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <section class="print-surface rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Estimacion {{ $estimation->folio }}</h2>
                    <p class="text-sm text-slate-500">{{ $obra->nombre }}</p>
                </div>
                <div class="text-sm text-slate-600 md:text-right">
                    <div>{{ $estimation->created_at->format('d/m/Y H:i') }}</div>
                    <div>{{ $estimation->createdBy->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 border-b border-slate-100 p-5 md:grid-cols-4">
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Estado</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $estimation->status }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Conceptos</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format($estimation->total_items) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Cantidad</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format((float) $estimation->total_quantity, 4) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Subtotal</div>
                <div class="mt-1 font-semibold text-slate-900">${{ number_format((float) $estimation->subtotal, 2) }}</div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Clave</th>
                        <th class="px-4 py-3 text-left">Partida</th>
                        <th class="px-4 py-3 text-left">Descripcion</th>
                        <th class="px-4 py-3 text-left">Unidad</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                        <th class="px-4 py-3 text-right">Precio</th>
                        <th class="px-4 py-3 text-right">Importe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($estimation->items as $item)
                        @php
                            $snapshot = $item->concept_snapshot ?? [];
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $snapshot['excel_code'] ?? $item->concept->excel_code ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                <div class="font-semibold">{{ $snapshot['partida_code'] ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $snapshot['partida_name'] ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-800">{{ $snapshot['description'] ?? $item->concept->description ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $snapshot['unit'] ?? $item->concept->unit ?? '-' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float) $item->quantity, 4) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">${{ number_format((float) $item->unit_price, 4) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold">${{ number_format((float) $item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 font-bold text-slate-900">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-right">Total estimado</td>
                        <td class="px-4 py-3 text-right tabular-nums">${{ number_format((float) $estimation->subtotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</div>

@if(request()->boolean('print'))
    <script>
        window.addEventListener('load', () => window.print());
    </script>
@endif
@endsection