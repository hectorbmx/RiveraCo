@extends('layouts.admin')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Documento #{{ $doc->id }}</h1>
            <p class="text-sm text-slate-500">Tipo: {{ $doc->tipo }} · Estado: {{ $doc->estado }}</p>
        </div>

        <div class="flex items-center gap-2">
            @if($doc->estado === 'borrador')
                <form method="POST" action="{{ route('inventario.documentos.aplicar', $doc) }}"
                      onsubmit="return confirm('¿Aplicar documento #{{ $doc->id }}? Esto afectará stock y kardex.');">
                    @csrf
                    <button class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                        Aplicar
                    </button>
                </form>
            @endif

            <a href="{{ route('inventario.documentos.index') }}"
               class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-sm">
                Volver
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        <div><span class="text-slate-500">Almacén:</span> <span class="text-slate-900 font-medium">{{ $doc->almacen?->nombre ?? '—' }}</span></div>
        <div><span class="text-slate-500">Folio:</span> <span class="text-slate-900 font-medium">{{ $doc->folio ?? '—' }}</span></div>
        <div><span class="text-slate-500">Referencia:</span> <span class="text-slate-900 font-medium">{{ $doc->referencia ?? '—' }}</span></div>
        <div><span class="text-slate-500">Motivo:</span> <span class="text-slate-900 font-medium">{{ $doc->motivo ?? '—' }}</span></div>
        <div><span class="text-slate-500">Notas:</span> <span class="text-slate-900 font-medium">{{ $doc->notas ?? '—' }}</span></div>
    </div>

    @php
        $hayAjustables = $doc->estado === 'borrador' && $doc->detalles->contains(function ($detalle) {
            $oc = $detalle->ordenCompraDetalle;
            return $oc && max(0, (float) $oc->cantidad - (float) $oc->cantidad_recibida) > 0;
        });
    @endphp

    <form method="POST" action="{{ route('inventario.documentos.update', $doc) }}">
        @csrf
        @method('PUT')
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-[#0B265A] text-white">
                    <tr>
                        <th class="text-left font-semibold px-4 py-3">SKU</th>
                        <th class="text-left font-semibold px-4 py-3">Descripción</th>
                        <th class="text-left font-semibold px-4 py-3">Equipo destino</th>
                        <th class="text-right font-semibold px-4 py-3">Cantidad OC</th>
                        <th class="text-right font-semibold px-4 py-3">Cantidad a recibir</th>
                        <th class="text-right font-semibold px-4 py-3">Ya surtido</th>
                        <th class="text-right font-semibold px-4 py-3">Pendiente</th>
                        <th class="text-right font-semibold px-4 py-3">Costo</th>
                        <th class="text-right font-semibold px-4 py-3">Importe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($doc->detalles as $it)
                        @php
                            $sku = $it->producto?->sku ?? '—';
                            $desc = $it->producto?->nombre ?? ($it->producto?->descripcion ?? '—');
                            $oc = $it->ordenCompraDetalle;
                            $cantidadOc = $oc ? (float) $oc->cantidad : null;
                            $surtido = $oc ? (float) $oc->cantidad_recibida : null;
                            $pendiente = $oc ? max(0, $cantidadOc - $surtido) : null;
                            $editable = $doc->estado === 'borrador' && $pendiente !== null && $pendiente > 0;
                            $importe = (float) $it->cantidad * (float) $it->costo_unitario;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-mono">{{ $sku }}</td>
                            <td class="px-4 py-3">
                                {{ $desc }}
                                @if(!empty($it->notas))
                                    <div class="text-xs text-slate-500 mt-1">{{ $it->notas }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($it->equipo_tipo)
                                    <div class="font-medium text-slate-800">{{ $it->equipo_nombre_snapshot ?: ucfirst($it->equipo_tipo) }}</div>
                                    <div class="text-xs text-slate-500">{{ $it->equipo_identificador_snapshot ?: '—' }}</div>
                                @else
                                    <span class="text-slate-400">Sin asignar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ $cantidadOc !== null ? number_format($cantidadOc, 2) : '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($editable)
                                    <input type="number" step="0.001" min="0.001" max="{{ $pendiente }}"
                                           name="detalles[{{ $it->id }}][cantidad]"
                                           value="{{ (float) $it->cantidad }}"
                                           class="w-28 rounded-lg border border-slate-300 px-2 py-1 text-right focus:border-[#0B265A] focus:ring-[#0B265A]">
                                @else
                                    {{ number_format((float) $it->cantidad, 2) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ $surtido !== null ? number_format($surtido, 2) : '—' }}</td>
                            <td class="px-4 py-3 text-right font-medium {{ $pendiente !== null && $pendiente <= 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                {{ $pendiente !== null ? number_format($pendiente, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">$ {{ number_format((float) $it->costo_unitario, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold">$ {{ number_format($importe, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-slate-500">Sin partidas capturadas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($hayAjustables)
            <div class="mt-3 flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#0B265A] text-white text-sm font-semibold hover:opacity-95">
                    Guardar cantidades
                </button>
            </div>
        @endif
    </form>

</div>
@endsection