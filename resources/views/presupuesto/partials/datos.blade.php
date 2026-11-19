@php
    $existente = $presupuesto->exists;
    $importado = $presupuesto->esImportado();
@endphp

<form method="POST" action="{{ $existente ? route('presupuesto.update', $presupuesto) : route('presupuesto.store') }}" class="space-y-4">
    @csrf
    @if($existente)
        @method('PATCH')
        <input type="hidden" name="tab" value="{{ $tabInicial }}" :value="tab">
    @endif
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="presupuesto-folio" class="block text-sm font-medium text-slate-700">Folio</label>
            @if($importado)
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
                   @input.debounce.200ms="buscar()" @keydown.enter.prevent="buscar()" @keydown.escape.stop.prevent="limpiarBusqueda()"
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
    </div>
    <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900">
        {{ $existente ? 'Guardar datos' : 'Crear presupuesto' }}
    </button>
</form>
