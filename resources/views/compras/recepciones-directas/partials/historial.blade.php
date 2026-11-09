@if($recepcionesDirectas !== null)
<section class="my-5 overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
        <div>
            <h3 class="font-semibold text-[#0B265A]">{{ ($mostrarMateriales ?? false) ? 'Recepciones directas en obra' : 'Historial de recepciones directas' }}</h3>
            <p class="text-xs text-slate-500">Consulta la entrega, su origen y documentos. Los importes registrados de recepciones revertidas no forman parte del monto neto entregado.</p>
        </div>
        @if($mostrarMateriales ?? false)
            <div class="text-right"><div class="text-xs text-slate-500">Monto neto entregado · MXN</div><div class="text-xl font-bold text-emerald-900">${{ number_format((float) $montoNeto, 2) }}</div><div class="text-xs text-slate-500">No representa consumo final</div></div>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr>
                <th class="px-4 py-3">Recepción / fecha</th><th class="px-4 py-3">OC / obra</th>
                <th class="px-4 py-3">Estado</th><th class="px-4 py-3">Receptor / confirmó</th>
                <th class="px-4 py-3">Documentos</th><th class="px-4 py-3 text-right">Importe registrado MXN</th>
            </tr></thead>
            <tbody>
            @forelse($recepcionesDirectas as $entregaDirecta)
                <tr class="border-t border-slate-200">
                    <td class="px-4 py-3">
                        @if($mostrarMateriales ?? false)
                            <button type="button" class="mb-1 block text-xs font-semibold text-blue-800 underline" aria-expanded="false" aria-controls="materiales-rdo-{{ $entregaDirecta->id }}" onclick="const row = document.getElementById(this.getAttribute('aria-controls')); row.hidden = !row.hidden; this.setAttribute('aria-expanded', String(!row.hidden)); this.textContent = row.hidden ? 'Ver materiales' : 'Ocultar materiales';">Ver materiales</button>
                        @endif
                        <a class="font-semibold text-blue-800 hover:underline" href="{{ route('ordenes_compra.recepciones_directas.show', [$entregaDirecta->orden_compra_id, $entregaDirecta]) }}">{{ $entregaDirecta->folio }}</a>
                        <div class="text-xs text-slate-500">{{ $entregaDirecta->fecha_recepcion?->format('d/m/Y') }}</div>
                    </td>
                    <td class="px-4 py-3"><a class="text-blue-800 hover:underline" href="{{ route('ordenes_compra.edit', $entregaDirecta->orden_compra_id) }}">{{ $entregaDirecta->ordenCompra?->folio ?? 'OC #'.$entregaDirecta->orden_compra_id }}</a><div class="text-xs text-slate-500">{{ $entregaDirecta->obra?->nombre ?? 'Obra #'.$entregaDirecta->obra_id }}</div></td>
                    <td class="px-4 py-3"><span class="rounded px-2 py-1 text-xs font-semibold {{ $entregaDirecta->estado === 'aplicada' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">{{ ucfirst($entregaDirecta->estado) }}</span></td>
                    <td class="px-4 py-3">{{ $entregaDirecta->receptor_nombre }}<div class="text-xs text-slate-500">Compras: {{ $entregaDirecta->confirmadoPor?->name ?? 'Sin registro' }}</div></td>
                    <td class="px-4 py-3 text-xs"><div>Entrada: {{ $entregaDirecta->entrada?->folio ?? 'No disponible' }}</div><div>Salida: {{ $entregaDirecta->salida?->folio ?? 'No disponible' }}</div></td>
                    <td class="px-4 py-3 text-right">${{ number_format((float) $entregaDirecta->total_mxn, 2) }}</td>
                </tr>
                @if($mostrarMateriales ?? false)
                    <tr id="materiales-rdo-{{ $entregaDirecta->id }}" hidden class="bg-slate-50">
                        <td colspan="6" class="px-4 py-3">
                            <div class="mb-2 text-xs font-semibold text-slate-600">Material de la recepción · cantidades e importes originales</div>
                            <table class="w-full text-left text-sm"><thead><tr class="text-slate-500"><th class="py-1">Material</th><th class="py-1 text-right">Cantidad entregada</th><th class="py-1 text-right">Importe original MXN</th></tr></thead><tbody>
                            @forelse($entregaDirecta->detalles as $material)
                                <tr class="border-t border-slate-200"><td class="py-2">{{ $material->descripcion }}</td><td class="py-2 text-right">{{ number_format((float) $material->cantidad_inventario, 3) }} {{ $material->unidad_inventario }}</td><td class="py-2 text-right">${{ number_format((float) $material->importe_mxn, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="py-2 text-slate-500">Sin partidas registradas.</td></tr>
                            @endforelse
                            </tbody></table>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Aún no hay recepciones directas registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($recepcionesDirectas->hasPages())<div class="border-t border-slate-200 p-4">{{ $recepcionesDirectas->links() }}</div>@endif
</section>
@endif
