<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold text-[#0B265A]">Productos del proveedor</h2>

    <p class="text-xs text-slate-500">
        (Relación producto_proveedor: precio_lista, moneda, entrega, notas)
    </p>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Producto</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">SKU</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Precio lista</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Moneda</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Entrega (días)</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Activo</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($proveedor->productos as $prod)
                <tr class="hover:bg-slate-50/60">
                    <td class="px-4 py-2">
                        <div class="font-semibold text-slate-900">{{ $prod->nombre }}</div>
                        @if($prod->pivot->notas)
                            <div class="text-xs text-slate-500">{{ $prod->pivot->notas }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $prod->sku ?? '-' }}</td>
                    <td class="px-4 py-2 text-right">
                        ${{ number_format((float)$prod->pivot->precio_lista, 2) }}
                    </td>
                    <td class="px-4 py-2 text-center">{{ $prod->pivot->moneda ?? 'MXN' }}</td>
                    <td class="px-4 py-2 text-center">{{ $prod->pivot->tiempo_entrega_dias ?? '-' }}</td>
                    <td class="px-4 py-2 text-center">
                        @if((int)$prod->pivot->activo === 1)
                            <span class="text-green-700 font-semibold">Sí</span>
                        @else
                            <span class="text-red-700 font-semibold">No</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                        Este proveedor aún no tiene productos vinculados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
