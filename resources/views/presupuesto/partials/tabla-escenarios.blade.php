<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-[#0B265A] text-white">
            <tr><th class="px-4 py-3 text-left">{{ $esPila ? 'Concepto de perforación' : 'Partida / concepto' }}</th><th class="px-4 py-3 text-center">Unidad</th><th class="px-4 py-3 text-right">Cantidad</th><th class="px-4 py-3 text-right">{{ $esPila ? 'P.U.' : 'Costo unitario' }}</th><th class="px-4 py-3 text-right">Importe</th><th class="px-4 py-3 text-right">Optimista</th><th class="px-4 py-3 text-right">Pesimista</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($filas as $fila)
                @php
                    $precio = $esPila ? $fila->costo : $fila->precio_unitario;
                    $importe = $esPila ? $fila->total : $fila->importe;
                    $optimista = $esPila ? $fila->optimista : $fila->importe_optimista;
                    $pesimista = $esPila ? $fila->pesimista : $fila->importe_pesimista;
                @endphp
                <tr class="hover:bg-blue-50">
                    <td class="px-4 py-3">@if(! $esPila)<span class="mb-1 block text-xs font-semibold text-[#0B265A]">{{ $fila->partida }}</span>@endif<span class="text-slate-700">{{ $fila->concepto }}</span></td>
                    <td class="px-4 py-3 text-center text-slate-500">{{ $fila->unidad }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($fila->cantidad, 2) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${{ number_format($precio, 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold whitespace-nowrap">${{ number_format($importe, 2) }}</td>
                    <td class="px-4 py-3 text-right text-emerald-700 whitespace-nowrap">{{ $optimista === null ? '—' : '$' . number_format($optimista, 2) }}</td>
                    <td class="px-4 py-3 text-right text-red-700 whitespace-nowrap">{{ $pesimista === null ? '—' : '$' . number_format($pesimista, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No hay {{ $esPila ? 'conceptos de perforación' : 'costos' }} importados.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100">
            <tr><th colspan="4" class="px-4 py-4 text-right text-slate-700">{{ $esPila ? 'Total de perforación' : 'Total de costos' }}</th><td class="px-4 py-4 text-right font-bold text-[#0B265A] whitespace-nowrap">${{ number_format($filas->sum($esPila ? 'total' : 'importe'), 2) }}</td><td class="px-4 py-4 text-right font-semibold text-emerald-700 whitespace-nowrap">{{ $filas->whereNotNull($esPila ? 'optimista' : 'importe_optimista')->isEmpty() ? '—' : '$' . number_format($filas->sum($esPila ? 'optimista' : 'importe_optimista'), 2) }}</td><td class="px-4 py-4 text-right font-semibold text-red-700 whitespace-nowrap">{{ $filas->whereNotNull($esPila ? 'pesimista' : 'importe_pesimista')->isEmpty() ? '—' : '$' . number_format($filas->sum($esPila ? 'pesimista' : 'importe_pesimista'), 2) }}</td></tr>
        </tfoot>
    </table>
</div>
