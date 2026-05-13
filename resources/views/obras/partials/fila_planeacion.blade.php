@php
    // Normalizamos la referencia
    $gasto = $item;

    // ID único por fila para JS
    $id_campo = 'gasto_' . $gasto->id;

    // Campos base con fallback por si pila/detalle cambian ligeramente
    $concepto = $gasto->concepto ?? $gasto->descripcion ?? $gasto->nombre ?? ('Concepto #' . $gasto->id);
    $unidad   = $gasto->unidad ?? '-';
    $cantidad = $gasto->cantidad ?? 0;
    $tope     = (float) ($gasto->monto_programado ?? $gasto->importe ?? $gasto->total ?? 0);
    $gastadoReal = (float) ($gastadoReposicionPorPartida[$gasto->id] ?? 0);

    
    $porcentajeGastado = $tope > 0
    ? round(($gastadoReal / $tope) * 100, 2)
    : 0;
    $anchoBarra = min($porcentajeGastado, 100);
    $totalProg = 0;
@endphp

<tr class="hover:bg-slate-50 border-b group">
    {{-- Concepto --}}
    <td class="p-3 border sticky left-0 bg-white group-hover:bg-slate-50 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
        <div class="flex flex-col">
            <span class="text-slate-700 font-medium line-clamp-1" title="{{ $concepto }}">
                {{ $concepto }}
            </span>

            <span class="text-slate-400 text-[10px]">
                {{ strtoupper($tipo ?? 'item') }} · {{ $unidad }} · cant: {{ $cantidad }}
            </span>
        </div>
    </td>

    {{-- Tope --}}
    <td class="p-3 border text-right font-mono text-slate-700 bg-slate-50 relative overflow-hidden">

        {{-- Barra de gasto --}}
      <div
    class="absolute inset-y-0 left-0 {{ $porcentajeGastado > 100 ? 'bg-red-200/80' : 'bg-green-200/70' }}"
    style="width: {{ $anchoBarra }}%;"
></div>

        {{-- Texto encima --}}
        <div class="relative z-10">
            <div class="font-bold">
                ${{ number_format($tope, 2) }}
            </div>

            <div class="text-[10px] text-slate-500">
                Gastado: ${{ number_format($gastadoReal, 2) }}
            </div>

           <div class="text-[10px] font-bold {{ $porcentajeGastado >= 100 ? 'text-red-600' : 'text-green-700' }}">
    {{ number_format($porcentajeGastado, 2) }}%
</div>
        </div>

    </td>

    {{-- Total programado --}}
    <td class="p-3 border text-right font-bold text-blue-800 bg-blue-50/30" id="total_prog_{{ $id_campo }}">
        $0.00
    </td>

    {{-- Diferencia --}}
    <td class="p-3 border text-right font-bold text-green-600" id="diff_{{ $id_campo }}">
        $0.00
    </td>

    {{-- Inputs por semana --}}
    @for($i = 1; $i <= $semanas; $i++)
     @php
            $valorGuardado = $planeacion[$gasto->id][$i]->monto_programado ?? 0;
            $totalProg += $valorGuardado;
        @endphp

        <td class="p-2 border">
            <input
                type="text"
                name="plan[{{ $gasto->id }}][{{ $i }}]"
                value="{{ $valorGuardado > 0 ? number_format($valorGuardado, 2, '.', '') : '' }}"
                placeholder="0.00"
                data-tope="{{ $tope }}"
                data-id="{{ $id_campo }}"
                class="input-semana input-semana-{{ $id_campo }} w-full p-1 text-right text-xs border border-transparent focus:border-blue-400 focus:ring-1 focus:ring-blue-200 rounded bg-transparent hover:bg-white transition-all"
            >
        </td>
    @endfor
</tr>