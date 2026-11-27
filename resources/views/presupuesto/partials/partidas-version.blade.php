<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-[#0B265A] text-white">
            <tr>
                <th scope="col" class="px-4 py-3 text-left">Clave</th>
                <th scope="col" class="px-4 py-3 text-left">Partida o concepto</th>
                <th scope="col" class="px-4 py-3 text-left">Unidad</th>
                <th scope="col" class="px-4 py-3 text-right">Cantidad</th>
                <th scope="col" class="px-4 py-3 text-right">P. U.</th>
                <th scope="col" class="px-4 py-3 text-right">Importe</th>
            </tr>
        </thead>
        @forelse($partidas as $partida)
            <tbody>
                <tr class="bg-blue-50 text-[#0B265A]">
                    <th colspan="6" scope="rowgroup" class="px-4 py-3 text-left">{{ $partida['clave'] }}. {{ $partida['nombre'] }}</th>
                </tr>
                @foreach($partida['subpartidas'] as $fila)
                    <tr class="border-t border-slate-200">
                        <td class="px-4 py-3">{{ $fila['numero'] }}</td>
                        <th scope="row" class="whitespace-pre-line px-4 py-3 text-left font-medium">{{ $fila['concepto'] }}</th>
                        <td class="px-4 py-3">{{ $fila['unidad'] }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ number_format($fila['cantidad'], 2) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $moneda($fila['precio_unitario']) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $moneda($fila['importe']) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-slate-50 font-semibold">
                    <th colspan="5" scope="row" class="px-4 py-3 text-right">Subtotal {{ $partida['nombre'] }}</th>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $moneda($partida['subtotal']) }}</td>
                </tr>
            </tbody>
        @empty
            <tbody><tr><td colspan="6" class="p-5 text-center text-slate-600">Esta versión no contiene partidas.</td></tr></tbody>
        @endforelse
    </table>
</div>
