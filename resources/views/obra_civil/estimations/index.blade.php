@extends('layouts.admin')

@section('title', 'Estimaciones de obra civil')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? '-' }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Estimaciones</h1>
            <p class="text-sm text-slate-500">{{ $obra->nombre }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('obra_civil.details', $obra) }}"
               class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]">
                Generar estimacion
            </a>
            <a href="{{ route('obra_civil.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Estimaciones</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totals['count']) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Conceptos</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totals['items']) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Cantidad</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totals['quantity'], 4) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Subtotal</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($totals['subtotal'], 2) }}</div>
        </div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Listado de estimaciones</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Folio</th>
                        <th class="px-5 py-3 text-left">Catalogo</th>
                        <th class="px-5 py-3 text-left">Estado</th>
                        <th class="px-5 py-3 text-left">Usuario</th>
                        <th class="px-5 py-3 text-right">Conceptos</th>
                        <th class="px-5 py-3 text-right">Cantidad</th>
                        <th class="px-5 py-3 text-right">Subtotal</th>
                        <th class="px-5 py-3 text-right">Fecha</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($estimations as $estimation)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $estimation->folio }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $estimation->catalogImport->filename ?? '-' }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">
                                    {{ $estimation->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $estimation->createdBy->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($estimation->total_items) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ number_format((float) $estimation->total_quantity, 4) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums font-semibold">${{ number_format((float) $estimation->subtotal, 2) }}</td>
                            <td class="px-5 py-3 text-right">{{ $estimation->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center justify-end gap-3">
                                    <a href="{{ route('obra_civil.estimations.show', [$obra, $estimation]) }}"
                                       class="font-semibold text-[#0B265A] hover:underline">
                                        Ver detalle
                                    </a>
                                    <a href="{{ route('obra_civil.estimations.show', [$obra, $estimation]) }}?print=1"
                                       class="font-semibold text-slate-600 hover:underline">
                                        Imprimir
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-sm text-slate-500">
                                Aun no hay estimaciones generadas para esta obra.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection