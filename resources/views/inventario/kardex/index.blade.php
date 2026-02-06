@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Inventario — Kardex</h1>
            <p class="text-sm text-slate-500">Bitácora de movimientos (por producto y almacén).</p>
        </div>

        <a href="{{ route('inventario.documentos.index') }}"
           class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-sm">
            Ver Documentos
        </a>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 grid grid-cols-1 md:grid-cols-5 gap-3 text-sm">
        <div>
            <label class="text-slate-500 text-xs">Almacén</label>
            <select name="almacen_id" class="w-full mt-1 rounded-xl border-slate-300">
                <option value="">Todos</option>
                @foreach($almacenes as $a)
                    <option value="{{ $a->id }}" @selected((int)request('almacen_id') === (int)$a->id)>
                        {{ $a->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-slate-500 text-xs">Producto ID</label>
            <input type="number" name="producto_id" value="{{ request('producto_id') }}"
                   class="w-full mt-1 rounded-xl border-slate-300"
                   placeholder="Ej: 5">
            <p class="text-xs text-slate-400 mt-1">Luego lo cambiamos a buscador por SKU.</p>
        </div>

        <div>
            <label class="text-slate-500 text-xs">Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}"
                   class="w-full mt-1 rounded-xl border-slate-300">
        </div>

        <div>
            <label class="text-slate-500 text-xs">Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}"
                   class="w-full mt-1 rounded-xl border-slate-300">
        </div>

        <div class="flex items-end gap-2">
            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm">Filtrar</button>
            <a href="{{ route('inventario.kardex.index') }}"
               class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-sm">
                Limpiar
            </a>
        </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left font-medium px-4 py-3">Fecha</th>
                    <th class="text-left font-medium px-4 py-3">Almacén</th>
                    <th class="text-left font-medium px-4 py-3">Producto</th>
                    <th class="text-left font-medium px-4 py-3">Doc</th>
                    <th class="text-center font-medium px-4 py-3">Mov</th>
                    <th class="text-right font-medium px-4 py-3">Cantidad</th>
                    <th class="text-right font-medium px-4 py-3">Costo</th>
                    <th class="text-right font-medium px-4 py-3">Importe</th>
                    <th class="text-right font-medium px-4 py-3">Saldo</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($movimientos as $m)
                    @php
                        $cantidad = (float)$m->cantidad;
                        $costo    = (float)$m->costo_unitario;
                        $importe  = $cantidad * $costo;
                        $isIn     = $m->tipo_movimiento === 'in';
                    @endphp
                    <tr>
                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($m->fecha)->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $m->almacen_nombre ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs text-slate-500">{{ $m->producto_sku ?? '—' }}</div>
                            <div class="text-slate-900">{{ $m->producto_nombre ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($m->documento_id)
                                <a class="underline text-slate-900"
                                   href="{{ route('inventario.documentos.show', $m->documento_id) }}">
                                    #{{ $m->documento_id }}
                                </a>
                                <div class="text-xs text-slate-500">
                                    {{ $m->documento_tipo ?? '—' }} · {{ $m->documento_estado ?? '—' }}
                                </div>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-xs {{ $isIn ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $isIn ? 'IN' : 'OUT' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ number_format($cantidad, 2) }}</td>
                        <td class="px-4 py-3 text-right">$ {{ number_format($costo, 2) }}</td>
                        <td class="px-4 py-3 text-right">$ {{ number_format($importe, 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format((float)$m->saldo_cantidad, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-slate-500">
                            Sin movimientos para los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $movimientos->links() }}
    </div>

</div>
@endsection
