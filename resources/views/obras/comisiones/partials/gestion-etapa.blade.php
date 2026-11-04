@if(in_array($etapa->etapa, ['perforacion', 'acero', 'colado'], true) && in_array($etapa->estado, ['en_proceso', 'completada'], true) && $comision->estado !== 'cancelada')
    <div class="mt-3 flex flex-wrap items-center gap-3">
        @can('editar_' . $etapa->etapa . '.access')
            @if($gestionEtapas[$etapa->etapa]['editar'] ?? false)
                <a href="{{ route('obras.comisiones.etapas.editar', [$obra, $comision, $etapa->etapa]) }}"
                   class="text-sm font-semibold text-blue-700 hover:underline">Editar etapa</a>
            @endif
        @endcan
        @can('borrar_' . $etapa->etapa . '.access')
            @if($gestionEtapas[$etapa->etapa]['borrar'] ?? false)
                @php $posterioresIniciadas = $comision->etapas->contains(fn ($posterior) => $posterior->orden > $etapa->orden && in_array($posterior->estado, ['en_proceso', 'completada'], true)); @endphp
                <form method="POST" action="{{ route('obras.comisiones.etapas.borrar', [$obra, $comision, $etapa->etapa]) }}"
                      x-data="comisionEtapaFormulario" @pageshow.window="guardando = false" data-loading-message="Borrando captura de la etapa…"
                      @submit="if (confirm('Se borrarán horarios, producción, personal y fotos de esta etapa. Quedará pendiente para capturarla de nuevo. ¿Continuar?')) { enviar($event) } else { $event.preventDefault() }">
                    @csrf @method('DELETE')
                    <button type="submit" @disabled($posterioresIniciadas) :disabled="guardando || @js($posterioresIniciadas)"
                            @if($posterioresIniciadas) title="Hay una etapa posterior iniciada o completada." @endif
                            class="text-sm font-semibold text-red-700 hover:underline disabled:opacity-50">Borrar captura</button>
                </form>
            @endif
        @endcan
    </div>
@endif
