<section class="my-5 overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="border-b border-slate-200 px-4 py-3"><h3 class="font-semibold text-[#0B265A]">Avance de entrega directa por partida</h3><p class="text-xs text-slate-500">Cantidades de OC en su unidad de compra. El saldo de requisición se muestra en su propia unidad.</p></div>
    <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3">Material / origen</th><th class="px-4 py-3">Unidad OC</th><th class="px-4 py-3 text-right">Ordenado</th><th class="px-4 py-3 text-right">Recibido</th><th class="px-4 py-3 text-right">Pendiente OC</th><th class="px-4 py-3">Saldo actual de requisición</th></tr></thead>
        <tbody>
        @forelse($oc->detalles as $detalleCompra)
            <tr class="border-t border-slate-200">
                <td class="px-4 py-3">{{ $detalleCompra->descripcion }}<div class="text-xs">@include('compras.recepciones-directas.partials.requisicion', ['requisicionDetalle' => $detalleCompra->requisicionDetalle])</div></td>
                <td class="px-4 py-3">{{ $detalleCompra->unidad }}</td>
                <td class="px-4 py-3 text-right">{{ number_format((float) $detalleCompra->cantidad, 3) }}</td>
                <td class="px-4 py-3 text-right">{{ number_format((float) $detalleCompra->cantidad_recibida, 3) }}</td>
                <td class="px-4 py-3 text-right">{{ number_format(max(0, (float) $detalleCompra->cantidad - (float) $detalleCompra->cantidad_recibida), 3) }}</td>
                <td class="px-4 py-3 text-xs">
                    @if($detalleCompra->requisicionDetalle)
                        @php($origenDetalle = $detalleCompra->requisicionDetalle)
                        <div>Autorizado: {{ number_format((float) $origenDetalle->cantidad_autorizada, 3) }} {{ $origenDetalle->unidad }}</div>
                        <div>Surtido: {{ number_format((float) $origenDetalle->cantidad_surtida, 3) }} {{ $origenDetalle->unidad }}</div>
                        <div>Pendiente: {{ number_format(max(0, (float) $origenDetalle->cantidad_autorizada - (float) $origenDetalle->cantidad_surtida), 3) }} {{ $origenDetalle->unidad }}</div>
                    @else
                        <span class="text-slate-500">No aplica</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">La OC no tiene partidas.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
