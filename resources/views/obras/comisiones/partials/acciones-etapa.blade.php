@if($puedeCapturarEtapas && $accionEtapa)
    @if($accionEtapa['accion'] === 'activar')
        <form method="POST" action="{{ route('obras.comisiones.etapas.activar', [$obra, $comision, $accionEtapa['etapa']]) }}"
              x-data="comisionEtapaFormulario" @submit="enviar($event)" @pageshow.window="guardando = false"
              data-loading-message="Activando {{ $etapaLabels[$accionEtapa['etapa']] ?? 'etapa' }}…">
            @csrf
            <button type="submit" :disabled="guardando" class="inline-flex items-center rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 disabled:opacity-50">
                <span x-text="guardando ? 'Activando…' : 'Activar'">Activar</span>
            </button>
        </form>
    @elseif(in_array($accionEtapa['accion'], ['registrar', 'continuar'], true))
        <a href="{{ route('obras.comisiones.show', [$obra, $comision, 'etapa' => $accionEtapa['etapa']]) }}#registro-etapa"
           class="inline-flex items-center rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900"
           @if(($etapaSeleccionada['etapa'] ?? null) === $accionEtapa['etapa']) aria-current="true" @endif>
            {{ $accionEtapa['accion'] === 'continuar' ? 'Continuar' : 'Registrar' }}
        </a>
    @elseif($accionEtapa['estado'] !== 'completada')
        <p class="text-xs text-slate-500">Completa primero la etapa anterior.</p>
    @endif
@endif
