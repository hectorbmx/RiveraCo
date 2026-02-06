@extends('layouts.admin')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Documento #{{ $doc->id }}</h1>
            <p class="text-sm text-slate-500">Tipo: {{ $doc->tipo }} · Estado: {{ $doc->estado }}</p>
        </div>

        <a href="{{ route('inventario.documentos.index') }}"
           class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-sm">
            Volver
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        <div><span class="text-slate-500">Almacén:</span> <span class="text-slate-900 font-medium">{{ $doc->almacen?->nombre ?? '—' }}</span></div>
        <div><span class="text-slate-500">Folio:</span> <span class="text-slate-900 font-medium">{{ $doc->folio ?? '—' }}</span></div>
        <div><span class="text-slate-500">Referencia:</span> <span class="text-slate-900 font-medium">{{ $doc->referencia ?? '—' }}</span></div>
        <!-- <div><span class="text-slate-500">Observaciones:</span> <span class="text-slate-900 font-medium">{{ $doc->observaciones ?? '—' }}</span></div> -->
         <div><span class="text-slate-500">Motivo:</span> <span class="text-slate-900 font-medium">{{ $doc->motivo ?? '—' }}</span></div>
<div><span class="text-slate-500">Notas:</span> <span class="text-slate-900 font-medium">{{ $doc->notas ?? '—' }}</span></div>

    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left font-medium px-4 py-3">SKU</th>
                    <th class="text-left font-medium px-4 py-3">Descripción</th>
                    <th class="text-right font-medium px-4 py-3">Cantidad</th>
                    <th class="text-right font-medium px-4 py-3">Costo</th>
                    <th class="text-right font-medium px-4 py-3">Importe</th>
                </tr>
            </thead>
           <tbody class="divide-y divide-slate-100">
    @forelse($doc->detalles as $it)
        @php
            $sku  = $it->producto?->sku ?? '—';
            $desc = $it->producto?->nombre ?? ($it->producto?->descripcion ?? '—'); // ajusta al campo real
            $importe = (float)$it->cantidad * (float)$it->costo_unitario;
        @endphp

        <tr>
            <td class="px-4 py-3 font-mono">{{ $sku }}</td>
            <td class="px-4 py-3">
                {{ $desc }}
                @if(!empty($it->notas))
                    <div class="text-xs text-slate-500 mt-1">{{ $it->notas }}</div>
                @endif
            </td>
            <td class="px-4 py-3 text-right">{{ number_format((float)$it->cantidad, 2) }}</td>
            <td class="px-4 py-3 text-right">$ {{ number_format((float)$it->costo_unitario, 2) }}</td>
            <td class="px-4 py-3 text-right font-semibold">$ {{ number_format($importe, 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                Sin partidas capturadas.
            </td>
        </tr>
    @endforelse
</tbody>

        </table>
    </div>

</div>
@endsection
