@if(in_array($etapa->etapa, ['perforacion', 'acero', 'colado'], true))
@can('borrar_fotos_' . $etapa->etapa . '.access')
    @if($gestionEtapas[$etapa->etapa]['fotos'] ?? false)
        <form method="POST" action="{{ route('obras.comisiones.etapas.fotos.borrar', [$obra, $comision, $etapa->etapa, $foto]) }}"
              x-data="comisionEtapaFormulario" @pageshow.window="guardando = false" data-loading-message="Borrando fotografía…"
              @submit="if (confirm('¿Borrar esta fotografía de la comisión?')) { enviar($event) } else { $event.preventDefault() }">
            @csrf @method('DELETE')
            <button type="submit" :disabled="guardando" class="mt-1 text-xs font-semibold text-red-700 hover:underline disabled:opacity-50">Borrar foto</button>
        </form>
    @endif
@endcan
@endif
