@php
    $recepcionesOrden = ($recepcionesPorOrden ?? collect())->get($ordenTrazada->id, collect());
    $documentosOrden = ($documentosPorOrden ?? collect())->get($ordenTrazada->id, collect());
    $entradasHuentitan = ($entradasHuentitanPorOrden ?? collect())->get($ordenTrazada->id, collect());
    $salidasHuentitan = ($salidasHuentitanPorOrden ?? collect())->get($ordenTrazada->id, collect());
@endphp
<div class="space-y-4 text-sm">
    @if(auth()->user()?->can(\App\Models\CompraRecepcionDirecta::PERMISO_VER))
        @if($ordenTrazada->modalidad_entrega === 'obra_directa')
            @php($netoOrden = ($materialDirectoObra['partidas'] ?? collect())->where('orden_compra_id', $ordenTrazada->id)->sum('importe_neto'))
            <p class="font-semibold text-emerald-800">Material directo neto: ${{ number_format((float) $netoOrden, 2) }} MXN <span class="font-normal text-slate-500">· No es consumo final ni se suma al total de la OC.</span></p>
        @endif
        @forelse($recepcionesOrden as $recepcionOrden)
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <div class="flex flex-wrap justify-between gap-2">
                    <div><a class="font-semibold text-blue-700 underline" href="{{ route('ordenes_compra.recepciones_directas.show', [$ordenTrazada->id, $recepcionOrden]) }}">{{ $recepcionOrden->folio }}</a>
                        · {{ $recepcionOrden->fecha_recepcion?->format('d/m/Y') }} · <strong>{{ ucfirst($recepcionOrden->estado) }}</strong></div>
                    <div>Importe original: ${{ number_format((float) $recepcionOrden->total_mxn, 2) }} MXN</div>
                </div>
                <p class="mt-1 text-xs text-slate-600">Recibió: {{ $recepcionOrden->receptor_nombre }} · Confirmó: {{ $recepcionOrden->confirmadoPor?->name ?? 'Sin registro' }} · Entrada: {{ $recepcionOrden->entrada?->folio ?? 'No disponible' }} · Salida: {{ $recepcionOrden->salida?->folio ?? 'No disponible' }}</p>
                @if($recepcionOrden->estado !== 'aplicada')<p class="mt-1 text-xs font-semibold text-amber-700">No integra el monto neto entregado.</p>@endif
                <table class="mt-2 w-full text-left text-xs"><thead><tr class="text-slate-500"><th>Material</th><th class="text-right">Cantidad original</th><th class="text-right">Importe original MXN</th></tr></thead><tbody>
                    @forelse($recepcionOrden->detalles as $material)
                        <tr class="border-t border-slate-100"><td class="py-2">{{ $material->descripcion }}<div>@include('compras.recepciones-directas.partials.requisicion', ['requisicionDetalle' => $material->requisicionDetalle])</div></td><td class="py-2 text-right">{{ number_format((float) $material->cantidad_inventario, 3) }} {{ $material->unidad_inventario }}</td><td class="py-2 text-right">${{ number_format((float) $material->importe_mxn, 2) }}</td></tr>
                    @empty <tr><td colspan="3" class="py-2 text-slate-500">Sin partidas registradas.</td></tr> @endforelse
                </tbody></table>
            </div>
        @empty
            @if($ordenTrazada->modalidad_entrega === 'obra_directa')<p class="text-slate-500">Sin recepciones directas registradas.</p>@endif
        @endforelse
    @endif
    @if($documentosOrden->isNotEmpty())
        <div><p class="font-semibold text-slate-700">Documentos de almacén vinculados a esta OC</p>
            <ul class="mt-1 space-y-1">@foreach($documentosOrden as $documento)
                <li><a class="text-blue-700 underline" href="{{ route('inventario.documentos.show', $documento->id) }}">{{ $documento->folio ?: 'Documento #'.$documento->id }}</a> · {{ ucfirst($documento->tipo) }} · {{ ucfirst($documento->estado) }} · {{ $documento->fecha?->format('d/m/Y') }}</li>
            @endforeach</ul>
        </div>
    @elseif($ordenTrazada->modalidad_entrega !== 'obra_directa' && $entradasHuentitan->isEmpty() && $salidasHuentitan->isEmpty())
        <p class="text-slate-500">Sin documentos de almacén vinculados a esta OC.</p>
    @endif
    @if($entradasHuentitan->isNotEmpty() || $salidasHuentitan->isNotEmpty())
        <div><p class="font-semibold text-slate-700">Documentos Huentitán vinculados</p><ul class="mt-1 space-y-1">
            @foreach($entradasHuentitan as $entradaHuentitan)
                <li><a class="text-blue-700 underline" href="{{ route('huentitan.entradas.show', $entradaHuentitan->id) }}">{{ $entradaHuentitan->folio }}</a> · Entrada · {{ ucfirst($entradaHuentitan->estado) }} · {{ $entradaHuentitan->fecha?->format('d/m/Y') }}</li>
            @endforeach
            @foreach($salidasHuentitan as $salidaHuentitan)
                <li><a class="text-blue-700 underline" href="{{ route('huentitan.salidas.show', $salidaHuentitan->id) }}">{{ $salidaHuentitan->folio }}</a> · Salida a obra · {{ ucfirst($salidaHuentitan->estado) }} · {{ $salidaHuentitan->fecha?->format('d/m/Y') }}</li>
            @endforeach
        </ul></div>
    @endif
    <p class="text-xs text-slate-500">Solo se muestran documentos con vínculo registrado. Una salida a obra puede existir sin vínculo con la OC.</p>
</div>
