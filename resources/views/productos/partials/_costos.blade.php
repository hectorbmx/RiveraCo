<div class="bg-white border border-slate-200 rounded-2xl p-4">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-800">Historial de costos por proveedor</h3>
            <p class="text-xs text-slate-500">
                Registro automático cuando se actualiza el precio/moneda del proveedor para este producto.
            </p>
        </div>

        <div class="text-xs text-slate-500">
            Total: {{ number_format($historialCostos->total()) }}
        </div>
    </div>

    <div class="overflow-auto border border-slate-200 rounded-2xl">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Fecha</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Proveedor</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">RFC</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Precio</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Moneda</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Origen</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($historialCostos as $h)
                    <tr>
                        <td class="px-4 py-2 text-slate-700 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($h->created_at)->format('d/m/Y H:i') }}
                        </td>

                        <td class="px-4 py-2 text-slate-900 font-semibold">
                            {{ $h->proveedor_nombre }}
                        </td>

                        <td class="px-4 py-2 text-slate-600 text-xs">
                            {{ $h->proveedor_rfc ?? '—' }}
                        </td>

                        <td class="px-4 py-2 text-right font-semibold text-slate-900">
                            {{ number_format((float)$h->precio, 4) }}
                        </td>

                        <td class="px-4 py-2 text-slate-700">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg border border-slate-200 bg-white text-xs">
                                {{ $h->moneda }}
                            </span>
                        </td>

                        <td class="px-4 py-2 text-slate-700 text-xs">
                            @if($h->orden_compra_id)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700">
                                    OC #{{ $h->orden_compra_id }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-amber-50 text-amber-800">
                                    Manual
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                            Aún no hay historial de costos para este producto.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($historialCostos->hasPages())
        <div class="mt-4">
            {{ $historialCostos->withQueryString()->links() }}
        </div>
    @endif
</div>
