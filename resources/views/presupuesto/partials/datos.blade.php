@php
    $existente = $presupuesto->exists;
    $importado = $presupuesto->esImportado();
    $folioAsignado = $presupuesto->folio_serie_id !== null;
    $tipo = $folioAsignado ? $presupuesto->tipo : old('tipo', $presupuesto->tipo);
    $tipoRequerido = ! $existente || $presupuesto->tipo !== null;
    $tipoInicial = is_string($tipo) && isset(\App\Models\Presupuesto::TIPOS[$tipo]) ? $tipo : '';
@endphp

<form method="POST" action="{{ $existente ? route('presupuesto.update', $presupuesto) : route('presupuesto.store') }}" class="space-y-4"
      @unless($existente) x-data="{ tipoElegido: @js($tipoInicial), folios: @js(array_map(fn ($serie) => $serie['siguiente'], $foliosNuevos)) }" @endunless>
    @csrf
    @if($existente)
        @method('PATCH')
        <input type="hidden" name="tab" value="{{ $tabInicial }}" :value="tab">
    @endif
    @if($existente)
        <div class="max-w-sm">
            <label for="presupuesto-tipo" class="block text-sm font-medium text-slate-700">Tipo de presupuesto</label>
            @if($folioAsignado)
                <input id="presupuesto-tipo" readonly value="{{ $tipo }}" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-100 text-sm">
            @else
                <select id="presupuesto-tipo" name="tipo" @required($tipoRequerido) class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    <option value="" @selected($tipoInicial === '') @disabled($tipoRequerido)>{{ $tipoRequerido ? 'Selecciona un tipo' : 'Sin clasificar' }}</option>
                    @foreach(array_keys(\App\Models\Presupuesto::TIPOS) as $opcion)
                        <option value="{{ $opcion }}" @selected($tipo === $opcion)>{{ $opcion }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    @else
        <fieldset>
            <legend class="text-sm font-medium text-slate-700">Tipo de presupuesto</legend>
            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach(array_keys(\App\Models\Presupuesto::TIPOS) as $opcion)
                    <label @class(['flex min-h-24 cursor-pointer flex-col rounded-lg border p-2 text-sm font-semibold text-slate-800',
                               'justify-center' => !isset($imagenesTipoPresupuesto[$opcion]),
                               'justify-between' => isset($imagenesTipoPresupuesto[$opcion])])
                           :class="tipoElegido === @js($opcion) ? 'border-blue-700 bg-blue-50' : 'border-slate-300 bg-white'">
                        @include('presupuesto.partials.imagen-tipo', ['tipoImagen' => $opcion, 'clasesImagen' => 'mx-auto h-12 w-12 object-contain'])
                        <span @class(['flex items-center justify-center gap-1.5', 'pt-2' => isset($imagenesTipoPresupuesto[$opcion])])>
                            <input type="radio" name="tipo" value="{{ $opcion }}" required x-model="tipoElegido" @checked($tipoInicial === $opcion)
                                   @if($tipoInicial === $opcion || ($tipoInicial === '' && $loop->first)) autofocus @endif>
                            {{ $opcion }}
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif
    @error('tipo', 'datosPresupuesto')
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
    <fieldset @unless($existente) :disabled="!tipoElegido" @endunless
              @class(['grid min-w-0 gap-4 disabled:opacity-60', 'md:grid-cols-2' => empty($enModal)])>
        <div>
            <label for="presupuesto-folio" class="block text-sm font-medium text-slate-700">Folio</label>
            @if(! $existente)
                <input id="presupuesto-folio" readonly :value="folios[tipoElegido] ?? ''"
                       value="{{ $foliosNuevos[$tipoInicial]['siguiente'] ?? '' }}" placeholder="Selecciona un tipo"
                       class="mt-1 w-full rounded-lg border-slate-300 bg-slate-100 text-sm">
                <p class="mt-1 text-xs text-slate-600">Folio estimado. Se asigna al guardar.</p>
            @elseif($importado || $folioAsignado)
                <input id="presupuesto-folio" readonly value="{{ $presupuesto->codigo_proyecto }}" class="mt-1 w-full rounded-lg border-slate-300 bg-slate-100 text-sm">
            @else
                <input id="presupuesto-folio" name="codigo_proyecto" required maxlength="255" value="{{ old('codigo_proyecto', $presupuesto->codigo_proyecto) }}"
                       placeholder="Ej. PI2607-05" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            @endif
            @error('codigo_proyecto', 'datosPresupuesto')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>
        <div x-data="{
            seleccionado: @js($clienteSeleccionado),
            resultados: [],
            consulta: null,
            mensaje: '',
            limpiarBusqueda() {
                this.consulta?.abort();
                this.$refs.busqueda.value = '';
                this.resultados = [];
                this.mensaje = '';
            },
            async buscar() {
                this.consulta?.abort();
                const texto = this.$refs.busqueda.value.trim();
                this.resultados = [];
                this.mensaje = '';
                if (texto.length &lt; 2) return;
                const consulta = new AbortController();
                this.consulta = consulta;
                this.mensaje = 'Buscando clientes…';
                try {
                    const url = new URL(this.$refs.busqueda.dataset.url, window.location.href);
                    url.searchParams.set('q', texto);
                    const respuesta = await fetch(url, { headers: { Accept: 'application/json' }, signal: consulta.signal });
                    if (!respuesta.ok) throw new Error();
                    const clientes = await respuesta.json();
                    if (consulta.signal.aborted || texto !== this.$refs.busqueda.value.trim()) return;
                    this.resultados = clientes;
                    this.mensaje = clientes.length ? '' : 'No se encontraron clientes.';
                } catch {
                    if (!consulta.signal.aborted) this.mensaje = 'No se pudo buscar. Vuelve a intentarlo.';
                }
            },
            elegir(cliente) {
                this.seleccionado = cliente;
                this.limpiarBusqueda();
                this.$refs.busqueda.focus();
            }
        }">
            <label for="presupuesto-buscar-cliente" class="block text-sm font-medium text-slate-700">Cliente</label>
            <input type="hidden" name="cliente_id" value="{{ $clienteSeleccionado?->id }}" :value="seleccionado?.id ?? ''">
            <div x-show="seleccionado" x-cloak class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                <span class="font-semibold text-[#0B265A]" x-text="seleccionado?.nombre_comercial"></span>
                <button type="button" @click="elegir(null)" class="text-sm text-slate-600 underline">Quitar selección</button>
            </div>
            <input id="presupuesto-buscar-cliente" type="search" x-ref="busqueda" autocomplete="off" maxlength="255"
                   data-url="{{ route('presupuesto.clientes.buscar') }}"
                   @input.debounce.200ms="buscar()" @keydown.enter.prevent="buscar()"
                   @keydown.escape="if ($el.value || resultados.length) { $event.stopPropagation(); $event.preventDefault(); limpiarBusqueda(); }"
                   placeholder="Buscar por nombre, razón social o RFC" aria-controls="presupuesto-clientes-resultados"
                   class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            <p role="status" x-text="mensaje" x-show="mensaje" x-cloak class="mt-1 text-sm text-slate-600"></p>
            <ul id="presupuesto-clientes-resultados" x-show="resultados.length" x-cloak class="mt-1 max-h-60 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200 bg-white">
                <template x-for="cliente in resultados" :key="cliente.id">
                    <li>
                        <button type="button" @click="elegir(cliente)" class="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50 focus:bg-blue-50">
                            <span class="block font-semibold" x-text="cliente.nombre_comercial"></span>
                            <span class="text-slate-600" x-text="[cliente.razon_social, cliente.rfc].filter(Boolean).join(' · ')"></span>
                        </button>
                    </li>
                </template>
            </ul>
            @if($importado)
                <p class="mt-1 text-sm text-slate-500">Cliente importado: {{ $presupuesto->nombre_cliente }}</p>
            @endif
            @error('cliente_id', 'datosPresupuesto')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="presupuesto-nombre-obra" class="block text-sm font-medium text-slate-700">Nombre de obra</label>
            <input id="presupuesto-nombre-obra" name="nombre_obra" maxlength="255" @required(! $importado)
                   value="{{ old('nombre_obra', $presupuesto->nombre_obra) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            @error('nombre_obra', 'datosPresupuesto')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label for="presupuesto-estado" class="block text-sm font-medium text-slate-700">Estado de la obra</label>
                <select id="presupuesto-estado" name="estado" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Seleccionar estado</option>
                    @foreach(config('sat_catalogs.estados') as $clave => $estado)
                        <option value="{{ $clave }}" @selected(old('estado', $presupuesto->estado) === (string) $clave)>{{ \Illuminate\Support\Str::after($estado, ' - ') }}</option>
                    @endforeach
                </select>
                @error('estado', 'datosPresupuesto')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="presupuesto-ciudad" class="block text-sm font-medium text-slate-700">Ciudad de la obra</label>
                <input id="presupuesto-ciudad" name="ciudad" maxlength="120" value="{{ old('ciudad', $presupuesto->ciudad) }}"
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                @error('ciudad', 'datosPresupuesto')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div>
            <label for="presupuesto-equipo" class="block text-sm font-medium text-slate-700">Equipo propuesto</label>
            <select id="presupuesto-equipo" name="equipo_propuesto" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                <option value="">Por definir</option>
                @foreach(\App\Models\Presupuesto::EQUIPOS_PROPUESTOS as $codigo => $equipo)
                    <option value="{{ $codigo }}" @selected(old('equipo_propuesto', $presupuesto->equipo_propuesto) === $codigo)>{{ $equipo }}</option>
                @endforeach
            </select>
            @error('equipo_propuesto', 'datosPresupuesto')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>
    </fieldset>
    <div @class(['flex flex-wrap gap-3', 'justify-end' => ! empty($enModal)])>
        @if(! empty($enModal))
            <button type="button" @click="$el.closest('dialog').close()"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
        @endif
        <button type="submit" @unless($existente) :disabled="!tipoElegido" @endunless
                class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 disabled:opacity-60">
            {{ $existente ? 'Guardar datos' : 'Crear presupuesto' }}
        </button>
    </div>
</form>
