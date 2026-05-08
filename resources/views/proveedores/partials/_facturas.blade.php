<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-lg font-semibold text-[#0B265A]">Facturas SAT del proveedor</h2>
        <p class="text-xs text-slate-500">
            CFDIs recibidos donde el RFC emisor coincide con el RFC del proveedor.
        </p>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Fecha</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">UUID</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Serie/Folio</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Receptor</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Total</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Método</th>
                <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Acciones</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
            @forelse($facturas as $cfdi)
                <tr class="hover:bg-slate-50/60">
                    <td class="px-4 py-2 whitespace-nowrap">
                        {{ $cfdi->fecha_emision ? \Carbon\Carbon::parse($cfdi->fecha_emision)->format('d/m/Y') : '-' }}
                    </td>

                    <td class="px-4 py-2">
                        <div class="max-w-[220px] truncate font-mono text-xs text-slate-700"
                             title="{{ $cfdi->uuid }}">
                            {{ $cfdi->uuid }}
                        </div>
                    </td>

                    <td class="px-4 py-2 whitespace-nowrap">
                        {{ trim(($cfdi->serie ?? '') . ' ' . ($cfdi->folio ?? '')) ?: '-' }}
                    </td>

                    <td class="px-4 py-2">
                        <div class="font-medium text-slate-800">
                            {{ $cfdi->receptor_nombre ?? '-' }}
                        </div>
                        <div class="text-xs text-slate-500">
                            {{ $cfdi->rfc_receptor ?? '-' }}
                        </div>
                    </td>

                    <td class="px-4 py-2 text-right whitespace-nowrap font-semibold">
                        ${{ number_format((float) $cfdi->total, 2) }}
                    </td>

                    <td class="px-4 py-2 text-center">
                        <span class="inline-flex rounded-lg bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                            {{ $cfdi->metodo_pago ?? '-' }}
                        </span>
                    </td>

                    <td class="px-4 py-2 text-center whitespace-nowrap">
                        <a href="{{ route('proveedores.facturas.show', [$proveedor->id, $cfdi->id]) }}"
                            class="inline-flex items-center rounded-lg bg-[#0B265A] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#12387f]">
                                Ver
                            </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                        No hay facturas SAT relacionadas con este proveedor.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($facturas)
    <div class="mt-4">
        {{ $facturas->links() }}
    </div>
@endif