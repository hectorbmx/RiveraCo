@php($resumenMaterialDirecto = $materialDirectoObra ?? ['partidas' => collect(), 'total_mxn' => 0])
<section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-3 bg-emerald-50 px-4 py-4">
        <div>
            <h3 class="font-semibold text-emerald-900">Material recibido directamente en obra</h3>
            <p class="text-xs text-slate-600">Valor entregado, ajustado por devoluciones. El consumo final se controla por separado.</p>
        </div>
        <div class="text-right"><div class="text-xs text-slate-600">Monto neto entregado · MXN</div><div class="text-xl font-bold text-emerald-900">${{ number_format($resumenMaterialDirecto['total_mxn'], 2) }}</div></div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600"><tr>
                <th class="px-4 py-3">Recepción / OC</th><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Material</th>
                <th class="px-4 py-3 text-right">Cantidad neta</th><th class="px-4 py-3">Unidad</th><th class="px-4 py-3 text-right">Importe MXN</th>
            </tr></thead>
            <tbody>
                @forelse($resumenMaterialDirecto['partidas'] as $partidaDirecta)
                    <tr class="border-t border-slate-200">
                        <td class="px-4 py-3"><a class="font-semibold text-blue-800 hover:underline" href="{{ route('ordenes_compra.recepciones_directas.show', [$partidaDirecta->orden_compra_id, $partidaDirecta->recepcion_id]) }}">{{ $partidaDirecta->folio }}</a><div><a class="text-blue-800 hover:underline" href="{{ route('ordenes_compra.edit', $partidaDirecta->orden_compra_id) }}">{{ $partidaDirecta->orden_compra_folio }}</a></div></td>
                        <td class="whitespace-nowrap px-4 py-3">{{ \Carbon\Carbon::parse($partidaDirecta->fecha_recepcion)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $partidaDirecta->descripcion }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $partidaDirecta->cantidad_neta, 3) }}</td>
                        <td class="px-4 py-3">{{ $partidaDirecta->unidad_inventario }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format((float) $partidaDirecta->importe_neto, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Aún no hay entregas directas registradas para esta obra.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
