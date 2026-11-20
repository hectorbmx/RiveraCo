@php
    $moneda = fn ($valor) => $valor === null ? '—' : '$' . number_format($valor, 2);
    $presentacionVersion = $version->datos['presentacion'] ?? null;
    $venta = $presentacionVersion
        ? \App\Support\PresentacionPresupuesto::numerar(collect($presentacionVersion['filas']))
        : collect($version->datos['propuesta']['filas'])->where('cantidad', '>', 0);
@endphp
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-[#0B265A] text-white"><tr>@if($presentacionVersion)<th class="px-3 py-3 text-left">Partida</th>@endif<th class="px-4 py-3 text-left">Concepto</th><th class="py-3">Unidad</th><th class="px-4 py-3 text-right">Cantidad</th><th class="px-4 py-3 text-right">P.U. de venta</th><th class="px-4 py-3 text-right">Importe</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($venta as $fila)
                @if($presentacionVersion && $fila['numero_encabezado'] !== null)
                    <tr class="bg-slate-50"><th scope="row" class="px-3 py-3 text-left align-top">{{ $fila['numero_encabezado'] }}</th><td colspan="5" class="whitespace-pre-line px-4 py-3">{{ $presentacionVersion['descripcion_perforacion'] }}</td></tr>
                @endif
                <tr>
                    @if($presentacionVersion)
                        <td class="px-3 py-3 align-top">{{ $fila['numero'] }}{{ $fila['partida'] === 'perforacion' ? ')' : '' }}</td>
                    @endif
                    <th scope="row" class="whitespace-pre-line px-4 py-3 text-left font-medium">{{ $fila['concepto'] }}</th><td class="text-center">{{ $fila['unidad'] }}</td><td class="px-4 py-3 text-right">{{ number_format($fila['cantidad'], 2) }}</td><td class="px-4 py-3 text-right">{{ $moneda($fila['precio_unitario']) }}</td><td class="px-4 py-3 text-right">{{ $moneda($fila['importe']) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $presentacionVersion ? 6 : 5 }}" class="p-5 text-center text-slate-500">Esta versión no tiene partidas de venta activas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
