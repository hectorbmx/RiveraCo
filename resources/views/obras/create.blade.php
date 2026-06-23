@extends('layouts.admin')

@section('title', 'Nueva Obra')

@section('content')

<div x-data="obraCreateForm()" class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#0B265A]">Nueva Obra</h1>

        <a href="{{ route('obras.index') }}"
           class="text-sm text-slate-600 hover:text-slate-900">
            ← Volver a la lista
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-sm">
                Hay errores en el formulario, revisa la información.
            </div>
        @endif

        <form action="{{ route('obras.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Cliente y clave --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="relative" @click.outside="clienteOpen = false">
                    <label for="cliente_id" class="block text-sm font-medium text-slate-700">
                        Cliente <span class="text-red-500">*</span>
                    </label>
                    <input type="hidden" id="cliente_id" name="cliente_id" x-model="clienteId" required>
                    <input type="text"
                           x-model="clienteSearch"
                           @focus="clienteOpen = true"
                           @input="clienteId = ''; clienteOpen = true"
                           autocomplete="off"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           placeholder="Buscar por cliente, razon social o RFC">

                    <div x-show="clienteOpen"
                         x-cloak
                         class="absolute z-40 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                        <template x-for="cliente in clientesFiltrados()" :key="cliente.id">
                            <button type="button"
                                    @click="selectCliente(cliente)"
                                    class="block w-full px-4 py-3 text-left text-sm hover:bg-slate-50">
                                <div class="font-medium text-slate-900" x-text="cliente.nombre"></div>
                                <div class="text-xs text-slate-500" x-text="cliente.rfc || '-'"></div>
                            </button>
                        </template>

                        <div x-show="clientesFiltrados().length === 0" class="px-4 py-3 text-sm text-slate-500">
                            Sin coincidencias
                        </div>
                    </div>
                    @error('cliente_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="clave_obra" class="block text-sm font-medium text-slate-700">
                        Clave de obra <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="clave_obra" name="clave_obra"
                           x-model="claveObra"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           readonly required>
                    <p class="mt-1 text-xs text-slate-500" x-text="folioMensaje"></p>
                    @error('clave_obra')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Nombre --}}
            <div>
                <label for="nombre" class="block text-sm font-medium text-slate-700">
                    Nombre de la obra <span class="text-red-500">*</span>
                </label>
                <input type="text" id="nombre" name="nombre"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       value="{{ old('nombre') }}" required>
                @error('nombre')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipo, status y responsable --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="tipo_obra" class="block text-sm font-medium text-slate-700">
                        Tipo de obra
                    </label>
                    <select id="tipo_obra" name="tipo_obra"
                            x-model="tipoObra"
                            @change="actualizarFolio()"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                   focus:border-[#FFC107] focus:ring-[#FFC107]">
                        <option value="">Selecciona tipo</option>
                        <option value="PILAS">Pilas</option>
                        <option value="POZOS">Pozos</option>
                    </select>
                    @error('tipo_obra')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

              <div class="mb-3">
    <label for="estatus_nuevo" class="block text-xs font-semibold text-slate-600 mb-1">
        Estatus de la obra
    </label>

    <select id="estatus_nuevo" name="estatus_nuevo"
        class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
               focus:border-[#FFC107] focus:ring-[#FFC107]">

        @foreach(\App\Models\Obra::$estatusLabels as $value => $label)
            <option value="{{ $value }}"
                @selected(old('estatus_nuevo', $obra->estatus_nuevo ?? 1) == $value)>
                {{ $label }}
            </option>
        @endforeach

    </select>
</div>


                <div class="relative" @click.outside="responsableOpen = false">
                    <label for="responsable_id" class="block text-sm font-medium text-slate-700">
                        Responsable
                    </label>
                    <input type="hidden" id="responsable_id" name="responsable_id" x-model="responsableId">
                    <input type="text"
                           x-model="responsableSearch"
                           @focus="responsableOpen = true"
                           @input="responsableId = ''; responsableOpen = true"
                           autocomplete="off"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           placeholder="Buscar responsable">

                    <div class="mt-1 flex justify-end">
                        <button type="button"
                                @click="clearResponsable()"
                                class="text-xs font-medium text-slate-500 hover:text-slate-700">
                            Sin asignar
                        </button>
                    </div>

                    <div x-show="responsableOpen"
                         x-cloak
                         class="absolute z-40 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                        <template x-for="responsable in responsablesFiltrados()" :key="responsable.id">
                            <button type="button"
                                    @click="selectResponsable(responsable)"
                                    class="block w-full px-4 py-3 text-left text-sm hover:bg-slate-50">
                                <div class="font-medium text-slate-900" x-text="responsable.nombre"></div>
                                <div class="text-xs text-slate-500" x-text="responsableSubtitulo(responsable)"></div>
                            </button>
                        </template>

                        <div x-show="responsablesFiltrados().length === 0" class="px-4 py-3 text-sm text-slate-500">
                            Sin coincidencias
                        </div>
                    </div>
                    @error('responsable_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Fechas --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="fecha_inicio_programada" class="block text-sm font-medium text-slate-700">
                        Inicio prog.
                    </label>
                    <input type="date" id="fecha_inicio_programada" name="fecha_inicio_programada"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('fecha_inicio_programada') }}">
                    @error('fecha_inicio_programada')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_inicio_real" class="block text-sm font-medium text-slate-700">
                        Inicio real
                    </label>
                    <input type="date" id="fecha_inicio_real" name="fecha_inicio_real"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('fecha_inicio_real') }}">
                    @error('fecha_inicio_real')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_fin_programada" class="block text-sm font-medium text-slate-700">
                        Fin prog.
                    </label>
                    <input type="date" id="fecha_fin_programada" name="fecha_fin_programada"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('fecha_fin_programada') }}">
                    @error('fecha_fin_programada')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_fin_real" class="block text-sm font-medium text-slate-700">
                        Fin real
                    </label>
                    <input type="date" id="fecha_fin_real" name="fecha_fin_real"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('fecha_fin_real') }}">
                    @error('fecha_fin_real')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Montos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="monto_contratado" class="block text-sm font-medium text-slate-700">
                        Monto contratado
                    </label>
                    <input type="number" step="0.01" id="monto_contratado" name="monto_contratado"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('monto_contratado') }}">
                    @error('monto_contratado')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="monto_modificado" class="block text-sm font-medium text-slate-700">
                        Monto modificado
                    </label>
                    <input type="number" step="0.01" id="monto_modificado" name="monto_modificado"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('monto_modificado') }}">
                    @error('monto_modificado')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Ubicación --}}
            <div>
                <label for="ubicacion" class="block text-sm font-medium text-slate-700">
                    Ubicación (texto libre)
                </label>
                <input type="text" id="ubicacion" name="ubicacion"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       value="{{ old('ubicacion') }}">
                @error('ubicacion')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- BOTONES --}}
            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('obras.index') }}"
                   class="px-4 py-2 rounded-xl border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-[#FFC107] text-[#0B265A] text-sm font-semibold
                               shadow hover:bg-[#e0ac05]">
                    Guardar Obra
                </button>
            </div>

        </form>
    </div>
</div>

@php
    $clientesBuscador = $clientes->map(function ($cliente) {
        $nombre = $cliente->razon_social ?: $cliente->nombre_comercial;

        return [
            'id' => (string) $cliente->id,
            'nombre' => $nombre,
            'rfc' => $cliente->rfc,
            'search' => trim($nombre . ' ' . $cliente->nombre_comercial . ' ' . $cliente->rfc),
        ];
    })->values();

    $responsablesBuscador = $responsables->map(function ($empleado) {
        $puesto = $empleado->puesto_base ?: $empleado->Puesto;

        return [
            'id' => (string) $empleado->id_Empleado,
            'nombre' => $empleado->nombre_completo,
            'email' => $empleado->Email,
            'puesto' => $puesto,
            'search' => trim($empleado->nombre_completo . ' ' . $empleado->Email . ' ' . $puesto),
        ];
    })->values();
@endphp

<script>
function obraCreateForm() {
    return {
        clienteId: @json((string) old('cliente_id', '')),
        clienteSearch: '',
        clienteOpen: false,
        clientes: @json($clientesBuscador),

        responsableId: @json((string) old('responsable_id', '')),
        responsableSearch: '',
        responsableOpen: false,
        responsables: @json($responsablesBuscador),
        tipoObra: @json((string) old('tipo_obra', '')),
        claveObra: @json((string) old('clave_obra', '')),
        folioMensaje: '',
        folioCargando: false,

        init() {
            const cliente = this.clientes.find((item) => item.id === this.clienteId);
            if (cliente) {
                this.clienteSearch = `${cliente.nombre} - ${cliente.rfc || 'Sin RFC'}`;
            }

            const responsable = this.responsables.find((item) => item.id === this.responsableId);
            if (responsable) {
                this.responsableSearch = `${responsable.nombre} - ${responsable.email || 'Sin correo'}`;
            }

            if (this.tipoObra && !this.claveObra) {
                this.actualizarFolio();
            }
        },

        clientesFiltrados() {
            const q = this.clienteSearch.toLowerCase().trim();

            if (!q) {
                return this.clientes.slice(0, 20);
            }

            return this.clientes
                .filter((cliente) => String(cliente.search || '').toLowerCase().includes(q))
                .slice(0, 20);
        },

        selectCliente(cliente) {
            this.clienteId = cliente.id;
            this.clienteSearch = `${cliente.nombre} - ${cliente.rfc || 'Sin RFC'}`;
            this.clienteOpen = false;
        },

        responsablesFiltrados() {
            const q = this.responsableSearch.toLowerCase().trim();

            if (!q) {
                return this.responsables.slice(0, 20);
            }

            return this.responsables
                .filter((responsable) => String(responsable.search || '').toLowerCase().includes(q))
                .slice(0, 20);
        },

        selectResponsable(responsable) {
            this.responsableId = responsable.id;
            this.responsableSearch = `${responsable.nombre} - ${responsable.puesto || responsable.email || 'Sin puesto'}`;
            this.responsableOpen = false;
        },

        responsableSubtitulo(responsable) {
            const partes = [responsable.puesto, responsable.email].filter(Boolean);
            return partes.length ? partes.join(' - ') : '-';
        },

        clearResponsable() {
            this.responsableId = '';
            this.responsableSearch = '';
            this.responsableOpen = false;
        },

        async actualizarFolio() {
            if (!this.tipoObra) {
                this.claveObra = '';
                this.folioMensaje = 'Selecciona el tipo de obra para generar la clave.';
                return;
            }

            this.folioCargando = true;
            this.folioMensaje = 'Calculando siguiente folio...';

            try {
                const url = new URL(@json(route('obras.folio-siguiente')), window.location.origin);
                url.searchParams.set('tipo_obra', this.tipoObra);

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('No se pudo generar el folio.');
                }

                const data = await response.json();
                this.claveObra = data.folio || '';
                this.folioMensaje = this.claveObra
                    ? 'Folio sugerido. Se reservara al guardar la obra.'
                    : 'No se pudo calcular el folio.';
            } catch (error) {
                this.folioMensaje = error.message || 'No se pudo generar el folio.';
            } finally {
                this.folioCargando = false;
            }
        },
    };
}
</script>

@endsection
