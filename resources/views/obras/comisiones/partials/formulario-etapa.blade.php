@php
    $esEdicion = $esEdicion ?? false;
    $produccion = $etapaSeleccionada['produccion'] ?? null;
    $nombreEtapa = $etapaLabels[$etapaSeleccionada['etapa']] ?? ucfirst($etapaSeleccionada['etapa']);
    $esColado = ($produccion['campo'] ?? null) === 'vol_concreto';
    $personalPrevio = $etapa->personal->pluck('obra_empleado_id');
    $personalHistorico = $etapa->personal->whereNotIn('obra_empleado_id', $personalDisponibleEtapa->pluck('id'));
    $recuperarPersonal = old('etapa_formulario') === $etapaSeleccionada['etapa'];
    $idsSeleccionados = $recuperarPersonal
        ? collect(old('personal_ids', []))
        : (! $esEdicion && $personalPrevio->isEmpty() ? $personalDisponibleEtapa->pluck('id') : $personalPrevio);
@endphp

<section id="registro-etapa" tabindex="-1" class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4 sm:p-5" aria-label="Registro de {{ $nombreEtapa }}">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h4 class="text-base font-semibold text-[#0B265A]">
            {{ $esEdicion ? 'Editar' : ($etapaSeleccionada['accion'] === 'continuar' ? 'Continuar' : 'Registrar') }} {{ $nombreEtapa }}
        </h4>
        <a class="text-sm text-blue-700 hover:underline" href="{{ route('obras.comisiones.show', [$obra, $comision]) }}#etapa-{{ $etapaSeleccionada['etapa'] }}">Cerrar captura</a>
    </div>

    <form x-data="comisionEtapaFormulario" method="POST" enctype="multipart/form-data"
          action="{{ route($esEdicion ? 'obras.comisiones.etapas.actualizar' : 'obras.comisiones.etapas.guardar', [$obra, $comision, $etapaSeleccionada['etapa']]) }}"
          @submit="enviar($event)" @pageshow.window="guardando = false" data-loading-message="Guardando {{ $nombreEtapa }} y fotografías…">
        @csrf
        @if($esEdicion) @method('PUT') @endif
        <input type="hidden" name="etapa_formulario" value="{{ $etapaSeleccionada['etapa'] }}">
        @if($errors->any())
            <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <p class="font-semibold">Revisa los siguientes datos:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
                <p class="mt-2">Si agregaste fotos nuevas, vuelve a seleccionarlas antes de guardar.</p>
            </div>
        @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label for="etapa-hora-inicio" class="block text-sm font-medium text-slate-700 mb-1">Hora de inicio <span aria-hidden="true">*</span></label>
                <input id="etapa-hora-inicio" name="hora_inicio" type="time" required
                       value="{{ old('hora_inicio', $etapaSeleccionada['hora_inicio'] ?? '') }}"
                       class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="etapa-hora-fin" class="block text-sm font-medium text-slate-700 mb-1">Hora de fin @if(! $esEdicion || $etapa->estado === 'completada')<span aria-hidden="true">*</span>@endif</label>
                <input id="etapa-hora-fin" name="hora_fin" type="time" @required(! $esEdicion || $etapa->estado === 'completada') @readonly($esEdicion && $etapa->estado === 'en_proceso')
                       value="{{ old('hora_fin', $etapaSeleccionada['hora_fin'] ?? '') }}"
                       class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            @if($produccion)
                <div>
                    <label for="etapa-produccion" class="block text-sm font-medium text-slate-700 mb-1">
                        {{ $produccion['label'] }} ({{ $produccion['unidad'] }}) <span aria-hidden="true">*</span>
                    </label>
                    <input id="etapa-produccion" name="{{ $produccion['campo'] }}" type="number"
                           step="{{ $produccion['input']['step'] }}" min="{{ $esColado ? '0.01' : $produccion['input']['min'] }}"
                           required
                           value="{{ old($produccion['campo'], $produccion['valor']) }}"
                           class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                           aria-describedby="etapa-produccion-ayuda">
                    <p id="etapa-produccion-ayuda" class="mt-1 text-xs text-slate-500">
                        {{ $esColado ? 'Captura un volumen de concreto mayor a cero.' : 'Cantidad correspondiente a esta etapa.' }}
                    </p>
                </div>
            @endif
        </div>

        @if($esEdicion && $etapa->etapa === 'perforacion')
            @php $detalleEdicion = $comision->detalles->first(); @endphp
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="text-sm font-medium text-slate-700">Diámetro
                    <input type="number" name="diametro" min="0" step="0.01" value="{{ old('diametro', $detalleEdicion?->diametro) }}" class="mt-1 block w-full rounded-xl border-slate-300">
                </label>
                <label class="text-sm font-medium text-slate-700">Profundidad (m)
                    <input type="number" name="profundidad" min="0" max="999999.99" step="0.01" value="{{ old('profundidad', $detalleEdicion?->profundidad) }}" class="mt-1 block w-full rounded-xl border-slate-300">
                </label>
                <label class="text-sm font-medium text-slate-700">Campana (piezas)
                    <input type="number" name="campana_pzas" min="0" step="1" value="{{ old('campana_pzas', (int) ($detalleEdicion?->campana_pzas ?? 0)) }}" class="mt-1 block w-full rounded-xl border-slate-300">
                </label>
                <label class="text-sm font-medium text-slate-700">Adicional
                    <input type="text" name="adicional" maxlength="100" value="{{ old('adicional', $detalleEdicion?->adicional) }}" class="mt-1 block w-full rounded-xl border-slate-300">
                </label>
            </div>
        @endif

        <div class="mt-4">
            <label for="etapa-observaciones" class="block text-sm font-medium text-slate-700 mb-1">Observaciones</label>
            <textarea id="etapa-observaciones" name="observaciones" rows="3"
                      class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="Detalles del trabajo realizado…">{{ old('observaciones', $etapaSeleccionada['observaciones'] ?? '') }}</textarea>
        </div>

        <fieldset class="mt-5">
            <legend class="text-sm font-semibold text-[#0B265A]">Personal involucrado</legend>
            <p class="mt-1 mb-3 text-xs text-slate-500">Selecciona al personal que participó en esta etapa.</p>
            @if($personalDisponibleEtapa->isEmpty())
                <p class="text-sm text-slate-500">No hay personal con asignación activa disponible para seleccionar.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($personalDisponibleEtapa as $asignacion)
                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
                            <input type="checkbox" name="personal_ids[]" value="{{ $asignacion->id }}"
                                   @checked($idsSeleccionados->contains($asignacion->id))
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>
                                <span class="block text-sm font-medium text-slate-800">{{ $asignacion->empleado->nombre_completo }}</span>
                                <span class="block text-xs text-slate-500">{{ $asignacion->rol?->nombre ?? 'Sin rol' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
            @if($personalHistorico->isNotEmpty())
                <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold text-slate-600">Personal ya registrado, fuera de la selección activa</p>
                    @foreach($personalHistorico as $participante)
                        <p class="mt-1 text-sm text-slate-600">{{ $participante->empleado?->nombre_completo ?? $participante->asignacionEmpleado?->empleado?->nombre_completo ?? 'Empleado no disponible' }} · {{ $participante->rol?->nombre ?? 'Sin rol' }}</p>
                    @endforeach
                </div>
            @endif
        </fieldset>

        <fieldset class="mt-5">
            <legend class="text-sm font-semibold text-[#0B265A]">Fotografías de la etapa</legend>
            @if($etapa->fotos->isNotEmpty())
                <p class="mt-2 mb-2 text-xs text-slate-500">Ya registradas ({{ $etapa->fotos->count() }})</p>
                <div class="flex flex-wrap gap-3">
                    @foreach($etapa->fotos as $fotoRegistrada)
                        @php $urlFoto = Storage::disk($fotoRegistrada->disk ?? 'public')->url($fotoRegistrada->path); @endphp
                        <div x-data="{ quitar: @js($esEdicion && collect(old('fotos_borrar', []))->contains($fotoRegistrada->id)) }" class="rounded-lg border border-slate-200 bg-white p-2">
                            <a href="{{ $urlFoto }}" target="_blank" rel="noopener" class="block" :class="{ 'opacity-40': quitar }">
                                <img src="{{ $urlFoto }}" alt="Foto registrada de {{ $nombreEtapa }}" class="h-24 w-24 rounded-lg border border-slate-200 object-cover">
                            </a>
                            @if($esEdicion)
                                @can('borrar_fotos_' . $etapa->etapa . '.access')
                                    @if($gestionEtapas[$etapa->etapa]['fotos'] ?? false)
                                        <label class="mt-2 flex items-center gap-2 text-xs font-medium text-red-700">
                                            <input type="checkbox" name="fotos_borrar[]" value="{{ $fotoRegistrada->id }}" x-model="quitar"
                                                   @checked(collect(old('fotos_borrar', []))->contains($fotoRegistrada->id))
                                                   class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                            Quitar al guardar
                                        </label>
                                    @endif
                                @endcan
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-2 text-sm text-slate-500">Sin fotos registradas.</p>
            @endif
            @include('obras.comisiones.partials.fotos-nuevas')
            @if($esEdicion)
                <p class="mt-2 text-xs text-slate-500">Las fotos existentes se conservan. Las que marques para quitar se borrarán al guardar los cambios.</p>
            @endif
        </fieldset>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button type="submit" :disabled="guardando" class="rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 disabled:opacity-50">
                <span x-text="guardando ? 'Guardando…' : @js($esEdicion ? 'Guardar cambios' : 'Guardar y completar etapa')">{{ $esEdicion ? 'Guardar cambios' : 'Guardar y completar etapa' }}</span>
            </button>
            <p class="text-xs text-slate-500">{{ $esEdicion ? 'Se guardarán los datos y los cambios de fotografías sin alterar el estado de la etapa.' : 'Se guardarán los datos, el personal seleccionado y las nuevas fotografías de esta etapa.' }}</p>
        </div>
    </form>
</section>
