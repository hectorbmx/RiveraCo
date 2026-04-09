@php
    $id_campo = ($tipo === 'pila') ? 'pila_' . $item->id : 'det_' . $item->id;
    $tope = ($tipo === 'pila') ? $item->total : $item->importe;
@endphp

<tr class="hover:bg-slate-50 border-b group">
    <td class="p-3 border sticky left-0 bg-white group-hover:bg-slate-50 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
        <span class="block font-bold text-[10px] text-blue-700 uppercase">{{ $item->partida ?? 'PERFORACIÓN' }}</span>
        <span class="text-slate-600 line-clamp-1" title="{{ $item->concepto }}">{{ $item->concepto }}</span>
    </td>

    <td class="p-3 border text-right font-mono bg-slate-50/50">
        ${{ number_format($tope, 2) }}
    </td>

    <td class="p-3 border text-right font-bold text-blue-900 bg-blue-50/20" id="total_prog_{{ $id_campo }}">
        $0.00
    </td>

    <td class="p-3 border text-right font-bold" id="diff_{{ $id_campo }}">
        $0.00
    </td>

   @for($i = 1; $i <= $semanas; $i++)
    @php
        // Buscamos si hay un valor guardado para esta fila y esta semana
        $item_key = ($tipo === 'detalle') ? $item->id : 'pila_' . $item->id;
        $valorGuardado = $planeacion[$item_key][$i]->monto_programado ?? 0;
    @endphp
    <td class="p-2 border">
      <input type="text" 
       name="plan[{{ $tipo }}][{{ $item->id }}][{{ $i }}]" 
       value="{{ number_format($valorGuardado, 2) }}" {{-- Formato inicial desde PHP --}}
       data-valor="{{ $valorGuardado }}"
       data-tope="{{ $tope }}"
       data-id="{{ $id_campo }}"
       onfocus="limpiarFormato(this)"
       onblur="aplicarFormato(this); calcularFila('{{ $id_campo }}')"
       class="w-full p-1 text-right border-transparent focus:border-blue-500 focus:ring-0 rounded bg-transparent hover:bg-white transition-all input-semana-{{ $id_campo }}">
    </td>
@endfor
</tr>