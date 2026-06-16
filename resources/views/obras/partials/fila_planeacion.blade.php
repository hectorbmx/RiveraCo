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
    $totalProgramadoGuardado = 0;

    for ($semana = 1; $semana <= $semanas; $semana++) {
        $totalProgramadoGuardado += (float) ($planeacion[$gasto->id][$semana]->monto_programado ?? 0);
    }

    
    $porcentajeGastado = $tope > 0
    ? round(($gastadoReal / $tope) * 100, 2)
    : 0;
    $porcentajeProgramado = $tope > 0
        ? round(($totalProgramadoGuardado / $tope) * 100, 2)
        : 0;
    $anchoBarraProgramado = min($porcentajeProgramado, 100);
    $colorBarraProgramado = $porcentajeProgramado >= 90
        ? 'bg-red-300/80'
        : ($porcentajeProgramado >= 50 ? 'bg-amber-300/80' : 'bg-emerald-300/80');
    $colorTextoProgramado = $porcentajeProgramado >= 90
        ? 'text-red-700'
        : ($porcentajeProgramado >= 50 ? 'text-amber-700' : 'text-emerald-700');
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

        {{-- Indicador de programado contra tope --}}
        <div
            id="prog_bar_{{ $id_campo }}"
            class="absolute inset-y-0 left-0 {{ $colorBarraProgramado }} transition-all duration-300"
            style="width: {{ $anchoBarraProgramado }}%;"
        ></div>

        {{-- Texto encima --}}
        <div class="relative z-10">
            <div class="font-bold">
                ${{ number_format($tope, 2) }}
            </div>

            <div id="prog_label_{{ $id_campo }}" class="text-[10px] font-bold {{ $colorTextoProgramado }}">
                Programado: {{ number_format($porcentajeProgramado, 2) }}%
            </div>

            <div class="text-[10px] text-slate-500">
                Gastado: ${{ number_format($gastadoReal, 2) }}
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
            
            $montoSolicitado = (float) ($montosSolicitadosMap[$gasto->id][$i] ?? 0);
            $estaCompleto = ($montoSolicitado >= $valorGuardado && $valorGuardado > 0);
            $esParcial = ($montoSolicitado > 0 && $montoSolicitado < $valorGuardado);
        @endphp

        <td class="p-2 border relative group/cell {{ $estaCompleto ? 'bg-green-50' : ($esParcial ? 'bg-amber-50' : '') }}">
            @if($montoSolicitado > 0)
                <div class="absolute top-0 right-0 p-0.5 flex flex-col items-end" title="Solicitado: ${{ number_format($montoSolicitado, 2) }}">
                    @if($estaCompleto)
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
                    @endif
                </div>
            @endif
            
            <input
                type="text"
                name="plan[{{ $gasto->id }}][{{ $i }}]"
                value="{{ $valorGuardado > 0 ? number_format($valorGuardado, 2, '.', '') : '' }}"
                placeholder="0.00"
                data-tope="{{ $tope }}"
                data-id="{{ $id_campo }}"
                {{ $estaCompleto ? 'readonly' : '' }}
                oninput="calcularFila('{{ $id_campo }}')"
                onfocus="limpiarFormato(this)"
                onblur="aplicarFormato(this); calcularFila('{{ $id_campo }}')"
                class="input-semana input-semana-{{ $id_campo }} w-full p-1 text-right text-xs border border-transparent focus:border-blue-400 focus:ring-1 focus:ring-blue-200 rounded transition-all
                {{ $estaCompleto ? 'text-green-800 cursor-not-allowed font-semibold' : ($esParcial ? 'text-amber-800 font-semibold' : 'bg-transparent hover:bg-white') }}"
            >
            @if($esParcial)
                <div class="text-[9px] text-amber-600 text-right mt-0.5 leading-tight">
                    Sol: ${{ number_format($montoSolicitado, 2) }}
                </div>
            @endif
        </td>
    @endfor
</tr>
