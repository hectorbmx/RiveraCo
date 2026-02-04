@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Inventario — Stock</h1>
            <p class="text-sm text-slate-500">Existencias, costo promedio y valor total (promedio ponderado).</p>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs font-medium text-slate-600">Almacén</label>
                <select name="almacen_id" class="mt-1 w-full rounded-xl border-slate-300">
                    <option value="">Todos</option>
                    @foreach($almacenes as $a)
                        <option value="{{ $a->id }}" @selected((int)$almacenId === (int)$a->id)>
                            {{ $a->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600">Buscar (SKU / descripción)</label>
                <input name="q" value="{{ $q }}" class="mt-1 w-full rounded-xl border-slate-300" placeholder="Ej. CEM-001 o Cemento">
            </div>

            <div class="flex items-end gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="minimos" value="1" class="rounded border-slate-300" @checked((int)$minimos===1)>
                    Solo mínimos
                </label>

                <button class="ml-auto inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm">
                    Filtrar
                </button>
            </div>
        </div>
    </form>
@if(isset($totalesPorAlmacen) && $totalesPorAlmacen->count())
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach($almacenes as $a)
            @php $t = $totalesPorAlmacen[$a->id] ?? null; @endphp
            @if(!$t) @continue @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                <div class="text-xs text-slate-500">Almacén</div>
                <div class="text-base font-semibold text-slate-900">{{ $a->nombre }}</div>

                <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <div class="text-xs text-slate-500">Stock total</div>
                        <div class="font-semibold text-slate-900">{{ number_format((float)$t->stock_total, 2) }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-slate-500">Valor total</div>
                        <div class="font-semibold text-slate-900">$ {{ number_format((float)$t->valor_total, 2) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left font-medium px-4 py-3">Almacén</th>
                        <th class="text-left font-medium px-4 py-3">SKU</th>
                        <th class="text-left font-medium px-4 py-3">Descripción</th>
                        <th class="text-right font-medium px-4 py-3">Existencia</th>
                        <th class="text-right font-medium px-4 py-3">Mínimo</th>
                        <th class="text-right font-medium px-4 py-3">Costo Prom.</th>
                        <th class="text-right font-medium px-4 py-3">Valor Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stocks as $s)
                        @php
                            $isMin = $s->stock_minimo !== null && $s->existencia <= $s->stock_minimo;
                        @endphp
                        <tr class="{{ $isMin ? 'bg-amber-50/60' : '' }}">
                            <td class="px-4 py-3 text-slate-700">{{ $s->almacen?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700 font-mono">{{ $s->sku }}</td>
                            <td class="px-4 py-3 text-slate-900">{{ $s->descripcion }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">{{ number_format((float)$s->existencia, 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float)$s->stock_minimo, 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">$ {{ number_format((float)$s->costo_promedio, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">$ {{ number_format((float)$s->valor_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Sin resultados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-200">
            {{ $stocks->links() }}
        </div>
    </div>

</div>
@endsection
