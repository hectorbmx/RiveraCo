<section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="border-b border-slate-200 p-4"><h2 class="font-semibold text-[#0B265A]">Partidas de esta entrega</h2><p class="text-xs text-slate-500">Las cantidades y costos de entrega son históricos. El avance de OC y saldo de requisición corresponden al momento de consulta.</p></div>
    <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3">Material / requisición</th><th class="px-4 py-3 text-right">Recibido de OC</th><th class="px-4 py-3 text-right">Entregado a obra</th><th class="px-4 py-3 text-right">Costo unitario MXN</th><th class="px-4 py-3 text-right">Importe MXN</th><th class="px-4 py-3">Avance actual</th></tr></thead>
        <tbody>
        @foreach($recepcion->detalles as $detalleEntrega)
            <tr class="border-t border-slate-200 align-top">
                <td class="px-4 py-3">{{ $detalleEntrega->descripcion }}<div class="text-xs">@include('compras.recepciones-directas.partials.requisicion', ['requisicionDetalle' => $detalleEntrega->requisicionDetalle])</div><div class="text-xs text-slate-500">Partida OC #{{ $detalleEntrega->orden_compra_detalle_id }}</div></td>
                <td class="px-4 py-3 text-right">{{ number_format((float) $detalleEntrega->cantidad_recibida, 3) }} {{ $detalleEntrega->unidad }}</td>
                <td class="px-4 py-3 text-right">{{ number_format((float) $detalleEntrega->cantidad_inventario, 3) }} {{ $detalleEntrega->unidad_inventario }}</td>
                <td class="px-4 py-3 text-right">${{ number_format((float) $detalleEntrega->costo_unitario_mxn, 8) }}<div class="text-xs text-slate-500">por {{ $detalleEntrega->unidad_inventario }}</div></td>
                <td class="px-4 py-3 text-right">${{ number_format((float) $detalleEntrega->importe_mxn, 2) }}</td>
                <td class="px-4 py-3 text-xs">
                    @if($detalleEntrega->ordenCompraDetalle)
                        @php($partidaOcActual = $detalleEntrega->ordenCompraDetalle)
                        <div>OC recibida: {{ number_format((float) $partidaOcActual->cantidad_recibida, 3) }} {{ $partidaOcActual->unidad }}</div>
                        <div>OC pendiente: {{ number_format(max(0, (float) $partidaOcActual->cantidad - (float) $partidaOcActual->cantidad_recibida), 3) }} {{ $partidaOcActual->unidad }}</div>
                    @else
                        <div>Partida OC no disponible</div>
                    @endif
                    @if($detalleEntrega->requisicionDetalle)
                        @php($requisicionActual = $detalleEntrega->requisicionDetalle)
                        <div>Requisición autorizada: {{ number_format((float) $requisicionActual->cantidad_autorizada, 3) }} {{ $requisicionActual->unidad }}</div>
                        <div>Requisición surtida: {{ number_format((float) $requisicionActual->cantidad_surtida, 3) }} {{ $requisicionActual->unidad }}</div>
                        <div>Requisición pendiente: {{ number_format(max(0, (float) $requisicionActual->cantidad_autorizada - (float) $requisicionActual->cantidad_surtida), 3) }} {{ $requisicionActual->unidad }}</div>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
