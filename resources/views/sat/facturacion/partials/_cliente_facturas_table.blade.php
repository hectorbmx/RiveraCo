<table class="w-full text-sm">
    <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-slate-500 uppercase text-xs tracking-wide">
            <th class="px-5 py-4 text-left">Folio</th>
            <th class="px-5 py-4 text-left">Fecha</th>
            <th class="px-5 py-4 text-left">Empresa</th>
            <th class="px-5 py-4 text-left">Obra</th>
            <th class="px-5 py-4 text-right">Total</th>
            <th class="px-5 py-4 text-left">Estado</th>
            <th class="px-5 py-4 text-right">Acciones</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
        @forelse($facturas as $factura)
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-4 font-semibold">
                    {{ $factura->serie }}-{{ $factura->folio }}
                </td>
                <td class="px-5 py-4">
                    {{ $factura->fecha_emision?->format('d/m/Y') ?? $factura->created_at->format('d/m/Y') }}
                </td>
                <td class="px-5 py-4">
                    {{ $factura->empresa->nombre ?? '---' }}
                </td>
                <td class="px-5 py-4">
                    {{ $factura->obra->nombre ?? $factura->obra->Nombre ?? '---' }}
                </td>
                <td class="px-5 py-4 text-right font-semibold">
                    ${{ number_format($factura->total, 2) }}
                </td>
                <td class="px-5 py-4">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                        @class([
                            'bg-emerald-50 text-emerald-700 border border-emerald-200' => $factura->estado === 'timbrada',
                            'bg-red-50 text-red-700 border border-red-200' => $factura->estado === 'cancelada',
                            'bg-amber-50 text-amber-700 border border-amber-200' => $factura->estado === 'cancelacion_solicitada',
                        ])">
                        {{ $factura->estado === 'cancelacion_solicitada' ? 'En cancelacion' : ucfirst($factura->estado) }}
                    </span>
                </td>
                <td class="px-5 py-4 text-right">
                    <a href="{{ route('sat.facturacion.show', $factura) }}"
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        Ver
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                    {{ $emptyText }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
