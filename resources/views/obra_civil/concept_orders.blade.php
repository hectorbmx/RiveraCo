@extends('layouts.admin')

@section('title', 'Ordenes por concepto civil')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->nombre }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Ordenes de compra del concepto</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $concept->excel_code }} - {{ $concept->description }}</p>
        </div>
        <a href="{{ route('obra_civil.details', $obra) }}"
           class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Volver a detalles
        </a>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-4">
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Presupuesto</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format((float) $balance['budget_quantity'], 4) }} {{ $concept->unit }}</div>
                <div class="text-xs text-slate-500">${{ number_format((float) $balance['budget_amount'], 2) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Usado</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format((float) $balance['used_quantity'], 4) }} {{ $concept->unit }}</div>
                <div class="text-xs text-slate-500">${{ number_format((float) $balance['used_amount'], 2) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Disponible</div>
                <div class="mt-1 font-semibold {{ (float) $balance['available_quantity'] < 0 || (float) $balance['available_amount'] < 0 ? 'text-red-700' : 'text-slate-900' }}">
                    {{ number_format((float) $balance['available_quantity'], 4) }} {{ $concept->unit }}
                </div>
                <div class="text-xs text-slate-500">${{ number_format((float) $balance['available_amount'], 2) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Ordenes</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format((int) $balance['orders_count']) }}</div>
                <div class="text-xs text-slate-500">Autorizadas/verificadas afectan saldo</div>
            </div>
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Movimientos en ordenes de compra</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">OC</th>
                        <th class="px-5 py-3 text-left">Proveedor</th>
                        <th class="px-5 py-3 text-left">Estado</th>
                        <th class="px-5 py-3 text-left">Fecha</th>
                        <th class="px-5 py-3 text-right">Cantidad</th>
                        <th class="px-5 py-3 text-right">Precio</th>
                        <th class="px-5 py-3 text-right">Importe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detalles as $detalle)
                        <tr>
                            <td class="px-5 py-3 font-semibold text-[#0B265A]">
                                <a href="{{ route('ordenes_compra.edit', $detalle->orden_compra_id) }}" class="hover:underline">
                                    {{ $detalle->folio }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $detalle->proveedor_nombre ?? '-' }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $detalle->estado }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $detalle->fecha ? \Carbon\Carbon::parse($detalle->fecha)->format('d/m/Y') : '-' }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ number_format((float) $detalle->cantidad, 4) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">${{ number_format((float) $detalle->precio_unitario, 4) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums font-semibold">${{ number_format((float) $detalle->importe, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">Este concepto aun no tiene ordenes de compra relacionadas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection