@extends('layouts.admin')

@section('title', 'Maquinas')

@section('content')
<div class="max-w-7xl mx-auto" x-data="maquinasHorasModal()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Maquinas</h1>
            <p class="text-sm text-slate-500">Consulta general (solo lectura).</p>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">

        {{-- Total --}}
        <div class="rounded-xl border bg-white p-4">
            <div class="text-xs text-slate-500">Total de máquinas</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $total }}</div>
        </div>

        {{-- Asignadas --}}
        <div class="rounded-xl border bg-white p-4">
            <div class="text-xs text-slate-500">Asignadas a obra</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $asignadas }}</div>
        </div>

        {{-- En obra --}}
        <div class="rounded-xl border bg-white p-4">
            <div class="text-xs text-slate-500">Ubicación: En obra</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $porUbicacion['en_obra'] ?? 0 }}</div>
        </div>

        {{-- En reparación --}}
        <div class="rounded-xl border bg-white p-4">
            <div class="text-xs text-slate-500">Ubicación: En reparación</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $porUbicacion['en_reparacion'] ?? 0 }}</div>
        </div>

        {{-- En patio --}}
        <div class="rounded-xl border bg-white p-4">
            <div class="text-xs text-slate-500">Ubicación: En patio</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $porUbicacion['en_patio'] ?? 0 }}</div>
        </div>
    </div>

    <x-filters.card action="{{ route('maquinas.index') }}" class="mb-6">
        <x-filters.input
            name="search"
            label="Buscar"
            :value="$search ?? ''"
            placeholder="Codigo, nombre, tipo, placas, serie, ubicacion u obra..."
            span="md:col-span-9"
            type="search"
            glow />

        <x-filters.actions
            submit-label="Filtrar"
            clear-url="{{ route('maquinas.index') }}"
            new-url="{{ route('empresa_config.maquinas.create') }}"
            new-label="+NUEVA"
            span="md:col-span-3" />
    </x-filters.card>
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tabla --}}
    @php
        $sortUrl = function (string $column) use ($sort, $direction) {
            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

            return route('maquinas.index', array_merge(request()->query(), [
                'sort' => $column,
                'direction' => $nextDirection,
            ]));
        };

        $sortIcon = fn (string $column): string => $sort === $column
            ? ($direction === 'asc' ? '↑' : '↓')
            : '↕';
    @endphp
    <div class="rounded-xl border bg-white overflow-hidden">
        <div class="px-4 py-3 border-b">
            <div class="text-sm font-semibold text-slate-800">Listado general</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Código</th>
                        <th class="text-left px-4 py-3">
                            <a href="{{ $sortUrl('nombre') }}" class="inline-flex items-center gap-1 font-semibold text-slate-700 hover:text-[#0B265A]">
                                <span>Nombre</span>
                                <span class="text-xs">{{ $sortIcon('nombre') }}</span>
                            </a>
                        </th>
                        <th class="text-left px-4 py-3">Tipo</th>
                        <th class="text-left px-4 py-3">
                            <a href="{{ $sortUrl('estado') }}" class="inline-flex items-center gap-1 font-semibold text-slate-700 hover:text-[#0B265A]">
                                <span>Estado</span>
                                <span class="text-xs">{{ $sortIcon('estado') }}</span>
                            </a>
                        </th>
                        <th class="text-left px-4 py-3">
                            <a href="{{ $sortUrl('ubicacion') }}" class="inline-flex items-center gap-1 font-semibold text-slate-700 hover:text-[#0B265A]">
                                <span>Ubicación actual</span>
                                <span class="text-xs">{{ $sortIcon('ubicacion') }}</span>
                            </a>
                        </th>
                        <th class="text-left px-4 py-3">Horómetro actual</th>
                        <th class="text-left px-4 py-3">Servicio preventivo</th>
                        <th class="text-left px-4 py-3">Vence Seguro</th>
                        <th class="text-left px-4 py-3">Seguro</th>
                        <th class="text-left px-4 py-3">Detalles</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($maquinas as $m)
                        @php
                            $seguroSeleccionado = null;
                            $preventivoMaquina = $preventivos[$m->id] ?? null;
                            $horometroActual = $horometrosActuales[$m->id] ?? ($preventivoMaquina['horometro_actual'] ?? null);
                            $asignacionActiva = $m->asignacionActiva;
                            $nombreMaquina = trim(($m->codigo ?? '') . ' ' . ($m->nombre ?? '')) ?: 'Maquina';
                            $canRegistrarHoras = auth()->user()?->can('maquinas.horas.create.access') ?? false;
                            $canAsignarObra = auth()->user()?->can('maquinas.asignar_obra.access') ?? false;
                            $puedeRegistrarHoras = $canRegistrarHoras && $asignacionActiva && $horometroActual !== null;
                            $puedeAsignarObra = $canAsignarObra && !$asignacionActiva && ($m->estado ?? null) === 'operativa';
                            $horasModalPayload = $puedeRegistrarHoras ? json_encode([
                                'action' => route('maquinas.horas.store', $m),
                                'maquina' => $nombreMaquina,
                                'obra' => $asignacionActiva?->obra?->nombre ?? 'Obra',
                                'horometroActual' => (float) $horometroActual,
                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) : '{}';
                            $asignarObraPayload = $puedeAsignarObra ? json_encode([
                                'action' => route('maquinas.asignarObra', $m),
                                'maquina' => $nombreMaquina,
                                'horometroInicio' => (float) ($horometroActual ?? $m->horometro_base ?? 0),
                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) : '{}';

                            if ($m->seguros && $m->seguros->isNotEmpty()) {
                                $seguroSeleccionado = $m->seguros
                                    ->sortByDesc(function ($seguro) {
                                        return $seguro->vigencia_hasta ? $seguro->vigencia_hasta->format('Y-m-d') : '0000-00-00';
                                    })
                                    ->first(function ($seguro) {
                                        $hoy = \Carbon\Carbon::today();

                                        return in_array((string) ($seguro->estatus ?? ''), ['vigente', 'activo'], true)
                                            || (
                                                $seguro->vigencia_desde
                                                && $seguro->vigencia_hasta
                                                && $seguro->vigencia_desde->lte($hoy)
                                                && $seguro->vigencia_hasta->gte($hoy)
                                            );
                                    })
                                    ?? $m->seguros->sortByDesc(function ($seguro) {
                                        return $seguro->vigencia_hasta ? $seguro->vigencia_hasta->format('Y-m-d') : '0000-00-00';
                                    })->first();
                            }
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $m->codigo ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium">
                                <a href="{{ route('maquinas.show', ['maquina' => $m->id, 'tab' => 'general']) }}"
                                   class="text-[#0B265A] hover:text-blue-700 hover:underline underline-offset-4">
                                    {{ $m->nombre ?? '—' }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $m->tipo ?? '—' }}</td>

                            {{-- Estado --}}
                            <td class="px-4 py-3">
                                @php
                                    $estado = $m->estado ?? '';
                                    $estadoLabel = match($estado) {
                                        'operativa' => 'Operativa',
                                        'fuera_servicio' => 'Fuera de servicio',
                                        'baja_definitiva' => 'Baja definitiva',
                                        'en_reparacion' => 'En reparacion',
                                        default => $estado ?: '—',
                                    };

                                    $estadoClass = match($estado) {
                                        'operativa' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'fuera_servicio' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'en_reparacion' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'baja_definitiva' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-slate-50 text-slate-700 border-slate-200',
                                    };
                                @endphp

                                <span class="inline-flex items-center px-2 py-1 rounded-lg border text-xs {{ $estadoClass }}">
                                    {{ $estadoLabel }}
                                </span>
                            </td>

                            {{-- Ubicación actual --}}
                            <td class="px-4 py-3">
                                @php
                                    $obraActual = $m->asignacionActiva?->obra;
                                    $ubicLabel = $obraActual ? 'En obra' : 'En patio';
                                    $ubicClass = $obraActual
                                        ? 'bg-blue-50 text-blue-700 border-blue-200'
                                        : 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                @endphp

                                @if($obraActual)
                                    <a href="{{ route('obras.edit', ['obra' => $obraActual->id]) }}"
                                       class="font-medium text-[#0B265A] hover:text-blue-700 hover:underline underline-offset-4">
                                        {{ $obraActual->nombre ?? 'Obra' }}
                                    </a>
                                @elseif($puedeAsignarObra)
                                    <button type="button"
                                            class="inline-flex w-fit items-center gap-1.5 px-2 py-1 rounded-lg border text-xs font-medium {{ $ubicClass }} hover:bg-emerald-100 hover:border-emerald-300 transition"
                                            title="Asignar a una obra"
                                            @click.stop='openAsignarModal({!! $asignarObraPayload !!})'>
                                        <span>En patio</span>
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 2a.75.75 0 0 1 .75.75v6.5h6.5a.75.75 0 0 1 0 1.5h-6.5v6.5a.75.75 0 0 1-1.5 0v-6.5h-6.5a.75.75 0 0 1 0-1.5h6.5v-6.5A.75.75 0 0 1 10 2Z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                @else
                                    <span class="inline-flex w-fit items-center px-2 py-1 rounded-lg border text-xs {{ $ubicClass }}" title="Solo máquinas operativas pueden asignarse desde esta vista">
                                        En patio
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($horometroActual !== null)
                                    @if($puedeRegistrarHoras)
                                        <button
                                            type="button"
                                            class="font-semibold text-[#0B265A] hover:text-blue-700 hover:underline underline-offset-4"
                                            title="Registrar horas"
                                            @click.stop='openHorasModal({!! $horasModalPayload !!})'>
                                            {{ number_format($horometroActual, 1) }} h
                                        </button>
                                    @else
                                        <span class="text-slate-700">{{ number_format($horometroActual, 1) }} h</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 text-xs">Sin horómetro</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @include('maquinas.partials._preventivo_badge', ['preventivo' => $preventivoMaquina])
                            </td>
                            <td class="px-4 py-3">
                                @if($seguroSeleccionado && $seguroSeleccionado->vigencia_hasta)
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg border text-xs bg-sky-50 text-sky-700 border-sky-200">
                                        {{ $seguroSeleccionado->vigencia_hasta->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">Sin seguro</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($seguroSeleccionado && $seguroSeleccionado->documento_path)
                                    <a href="{{ Storage::disk('public')->url(ltrim($seguroSeleccionado->documento_path, '/')) }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       title="Ver documento del seguro"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 text-red-600 hover:bg-red-100 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4" aria-hidden="true">
                                            <path d="M7 3.5A2.5 2.5 0 0 1 9.5 1h5.25a.75.75 0 0 1 .53.22l3.5 3.5a.75.75 0 0 1 .22.53V18A2.5 2.5 0 0 1 17 20.5H9.5A2.5 2.5 0 0 1 7 18V3.5Zm2.5-.5a1 1 0 0 0-1 1V18a1 1 0 0 0 1 1H17a1 1 0 0 0 1-1V6.06L15.94 4H9.5a1 1 0 0 0-1 1Zm2.25 7.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1 0-1.5Zm0 3h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1 0-1.5ZM11 6.5h4.5a.75.75 0 0 1 0 1.5H11a.75.75 0 0 1 0-1.5Z"/>
                                        </svg>
                                    </a>
                                @else
                                    <span title="SIN SEGURO" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-amber-50 text-amber-600 border border-amber-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.485 2.5a1.5 1.5 0 0 1 2.03 0l5.905 5.8a1.5 1.5 0 0 1 .42 1.013v4.687A2.5 2.5 0 0 1 14.34 16.5H5.66A2.5 2.5 0 0 1 3.16 14v-4.687a1.5 1.5 0 0 1 .42-1.013l5.905-5.8Zm1.515 4.5a.75.75 0 0 0-.75.75v2.75a.75.75 0 0 0 1.5 0V7.75a.75.75 0 0 0-.75-.75Zm0 6.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('maquinas.show', ['maquina' => $m->id, 'tab' => 'general']) }}"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium
                                            bg-white hover:bg-slate-50 text-slate-700 border-slate-200">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-slate-500">
                                No hay máquinas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('maquinas.asignar_obra.access')
    <div
        x-data="{
            obras: @js(
                $obrasDisponibles->map(fn ($obra) => [
                    'id'     => $obra->id,
                    'clave'  => $obra->clave_obra ?? '',
                    'nombre' => $obra->nombre ?? '',
                    'texto'  => trim(($obra->clave_obra ?? '') . ' ' . ($obra->nombre ?? '')),
                ])->values()
            ),

            buscarObra: '',
            obraId: '',
            obraSeleccionada: null,
            mostrarOpciones: false,
            errorObra: '',

            cargandoPilas: false,
            pilasCargadas: false,
            errorPilas: '',
            pilasObra: [],

            resumenPilas: {
                tipos_activos: 0,
                programadas: 0,
                ejecutadas: 0,
                faltantes: 0
            },

          get obrasFiltradas() {
    const busqueda = this.buscarObra
        .toString()
        .trim()
        .toLowerCase();

    // No mostrar ninguna obra mientras no exista una búsqueda
    if (busqueda.length < 2) {
        return [];
    }

    return this.obras.filter(obra => {
        const texto = `${obra.clave} ${obra.nombre}`.toLowerCase();

        return texto.includes(busqueda);
    });
},

            seleccionarObra(obra) {
                this.obraId = obra.id;
                this.obraSeleccionada = obra;
                this.buscarObra = obra.texto;
                this.mostrarOpciones = false;
                this.errorObra = '';

                this.cargarPilasObra();
            },

            limpiarSeleccionObra() {
                this.buscarObra = '';
                this.obraId = '';
                this.obraSeleccionada = null;
                this.mostrarOpciones = false;
                this.errorObra = '';

                this.limpiarPilas();
            },

            limpiarPilas() {
                this.cargandoPilas = false;
                this.pilasCargadas = false;
                this.errorPilas = '';
                this.pilasObra = [];

                this.resumenPilas = {
                    tipos_activos: 0,
                    programadas: 0,
                    ejecutadas: 0,
                    faltantes: 0
                };
            },

            reiniciarModal() {
                this.limpiarSeleccionObra();
            },

            async cargarPilasObra() {
                const obraIdConsultada = this.obraId;

                this.limpiarPilas();

                if (!obraIdConsultada) {
                    return;
                }

                this.cargandoPilas = true;

                try {
                    const urlBase = @js(url('/maquinas/obras'));

                    const response = await fetch(
                        `${urlBase}/${obraIdConsultada}/pilas-activas`,
                        {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    );

                    if (!response.ok) {
                        throw new Error(
                            `No fue posible consultar las pilas. Código ${response.status}.`
                        );
                    }

                    const data = await response.json();

                    // Evita mostrar el resultado de una consulta anterior
                    // si el usuario seleccionó otra obra rápidamente.
                    if (String(this.obraId) !== String(obraIdConsultada)) {
                        return;
                    }

                    this.pilasObra = Array.isArray(data.pilas)
                        ? data.pilas
                        : [];

                    this.resumenPilas = {
                        tipos_activos: data.resumen?.tipos_activos ?? 0,
                        programadas: data.resumen?.programadas ?? 0,
                        ejecutadas: data.resumen?.ejecutadas ?? 0,
                        faltantes: data.resumen?.faltantes ?? 0
                    };

                    this.pilasCargadas = true;
                } catch (error) {
                    console.error(error);

                    if (String(this.obraId) === String(obraIdConsultada)) {
                        this.errorPilas =
                            error.message ||
                            'Ocurrió un error al consultar las pilas de la obra.';
                    }
                } finally {
                    if (String(this.obraId) === String(obraIdConsultada)) {
                        this.cargandoPilas = false;
                    }
                }
            },

            validarFormulario(event) {
                if (!this.obraId) {
                    event.preventDefault();

                    this.errorObra = 'Selecciona una obra de la lista.';
                    this.mostrarOpciones = true;

                    this.$nextTick(() => {
                        this.$refs.buscarObraInput?.focus();
                    });
                }
            },

            formatearCantidad(valor) {
                const numero = Number(valor ?? 0);

                return new Intl.NumberFormat('es-MX', {
                    maximumFractionDigits: 2
                }).format(numero);
            },

            formatearMedida(valor) {
                if (
                    valor === null ||
                    valor === undefined ||
                    valor === ''
                ) {
                    return '—';
                }

                return `${Number(valor).toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })} m`;
            }
        }"
        x-init="
            $watch('asignarModalOpen', value => {
                if (value) {
                    reiniciarModal();

                    $nextTick(() => {
                        $refs.buscarObraInput?.focus();
                    });
                }
            })
        "
    >
        <div
            x-cloak
            x-show="asignarModalOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4 py-6"
            @keydown.escape.window="
                reiniciarModal();
                closeAsignarModal();
            "
        >
            <div
                class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                @click.outside="
                    reiniciarModal();
                    closeAsignarModal();
                "
            >
                {{-- ENCABEZADO --}}
                <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-[#0B265A]">
                            Asignar máquina a obra
                        </h2>

                        <p
                            class="text-xs text-slate-500"
                            x-text="asignarForm.maquina"
                        ></p>
                    </div>

                    <button
                        type="button"
                        class="text-2xl leading-none text-slate-400 hover:text-slate-600"
                        @click="
                            reiniciarModal();
                            closeAsignarModal();
                        "
                    >
                        &times;
                    </button>
                </div>

                {{-- FORMULARIO --}}
                <form
                    method="POST"
                    :action="asignarForm.action"
                    class="flex min-h-0 flex-1 flex-col"
                    @submit="validarFormulario($event)"
                >
                    @csrf

                    <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5">

                        {{-- BUSCADOR DE OBRAS --}}
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">
                                Obra destino
                                <span class="text-red-500">*</span>
                            </label>

                            {{-- Valor que se envía al controlador --}}
                            <input
                                type="hidden"
                                name="obra_id"
                                :value="obraId"
                            >

                            <div
                                class="relative"
                                @click.outside="mostrarOpciones = false"
                            >
                                <div class="relative">
                                    <input
                                        x-ref="buscarObraInput"
                                        type="search"
                                        x-model="buscarObra"
                                        autocomplete="off"
                                        placeholder="Buscar por clave o nombre de la obra..."
                                        class="w-full rounded-lg border-slate-300 pr-20 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        :class="errorObra ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : ''"
                                        @focus="mostrarOpciones = true"
                                        @input="
                                            mostrarOpciones = true;

                                            if (
                                                obraSeleccionada &&
                                                buscarObra !== obraSeleccionada.texto
                                            ) {
                                                obraId = '';
                                                obraSeleccionada = null;
                                                limpiarPilas();
                                            }

                                            errorObra = '';
                                        "
                                        @keydown.escape.stop="mostrarOpciones = false"
                                        @keydown.arrow-down.prevent="
                                            mostrarOpciones = true;
                                            $refs.listaObras?.querySelector('button')?.focus();
                                        "
                                    >

                                    <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-2">
                                        <button
                                            x-show="buscarObra !== ''"
                                            x-cloak
                                            type="button"
                                            class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                            title="Limpiar selección"
                                            @click="limpiarSeleccionObra()"
                                        >
                                            &times;
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                            @click="mostrarOpciones = !mostrarOpciones"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- RESULTADOS DE LA BÚSQUEDA --}}
                                <div
                                    x-cloak
                                    x-show="mostrarOpciones"
                                    x-transition.opacity
                                    x-ref="listaObras"
                                    class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg"
                                >
                                    <template
                                        x-for="obra in obrasFiltradas"
                                        :key="obra.id"
                                    >
                                        <button
                                            type="button"
                                            class="block w-full border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-blue-50 focus:bg-blue-50 focus:outline-none"
                                            :class="String(obraId) === String(obra.id) ? 'bg-blue-50' : ''"
                                            @click="seleccionarObra(obra)"
                                            @keydown.arrow-down.prevent="
                                                $el.nextElementSibling?.focus()
                                            "
                                            @keydown.arrow-up.prevent="
                                                $el.previousElementSibling?.focus()
                                            "
                                            @keydown.escape.prevent="
                                                mostrarOpciones = false;
                                                $refs.buscarObraInput?.focus();
                                            "
                                        >
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div
                                                        class="truncate text-sm font-semibold text-slate-700"
                                                        x-text="obra.nombre || 'Obra sin nombre'"
                                                    ></div>

                                                    <div
                                                        class="mt-0.5 text-xs text-slate-500"
                                                        x-text="obra.clave || 'Sin clave'"
                                                    ></div>
                                                </div>

                                                <span
                                                    x-show="String(obraId) === String(obra.id)"
                                                    class="shrink-0 text-xs font-semibold text-blue-700"
                                                >
                                                    Seleccionada
                                                </span>
                                            </div>
                                        </button>
                                    </template>

                                    <div
                                        x-show="obrasFiltradas.length === 0"
                                        class="px-4 py-6 text-center"
                                    >
                                        <p class="text-sm font-medium text-slate-600">
                                            No se encontraron obras
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Intenta buscar por otra clave o nombre.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <p
                                x-show="errorObra"
                                x-cloak
                                class="mt-1 text-xs text-red-600"
                                x-text="errorObra"
                            ></p>

                            <p
                                x-show="obraSeleccionada"
                                x-cloak
                                class="mt-2 text-xs text-emerald-700"
                            >
                                Obra seleccionada:
                                <span
                                    class="font-semibold"
                                    x-text="obraSeleccionada?.texto"
                                ></span>
                            </p>
                        </div>

                        {{-- CARGANDO PILAS --}}
                        <div
                            x-show="cargandoPilas"
                            x-cloak
                            class="flex items-center justify-center gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-6"
                        >
                            <svg
                                class="h-5 w-5 animate-spin text-blue-700"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>

                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                ></path>
                            </svg>

                            <span class="text-sm font-medium text-blue-800">
                                Consultando pilas de la obra...
                            </span>
                        </div>

                        {{-- ERROR AL CONSULTAR --}}
                        <div
                            x-show="errorPilas"
                            x-cloak
                            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3"
                        >
                            <p
                                class="text-sm text-red-700"
                                x-text="errorPilas"
                            ></p>

                            <button
                                type="button"
                                class="mt-2 text-xs font-semibold text-red-700 underline"
                                @click="cargarPilasObra()"
                            >
                                Intentar nuevamente
                            </button>
                        </div>

                        {{-- INFORMACIÓN DE PILAS --}}
                        <section
                            x-show="pilasCargadas && !cargandoPilas && !errorPilas"
                            x-cloak
                            class="space-y-4"
                        >
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold text-[#0B265A]">
                                        Pilas activas de la obra
                                    </h3>

                                    <p class="text-xs text-slate-500">
                                        Desglose actual del proyecto seleccionado.
                                    </p>
                                </div>

                                <span
                                    class="text-xs font-medium text-slate-500"
                                    x-text="`${resumenPilas.tipos_activos} registro(s)`"
                                ></span>
                            </div>

                            {{-- RESUMEN --}}
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <p class="text-xs text-slate-500">
                                        Tipos activos
                                    </p>

                                    <p
                                        class="mt-1 text-lg font-bold text-slate-800"
                                        x-text="formatearCantidad(resumenPilas.tipos_activos)"
                                    ></p>
                                </div>

                                <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                                    <p class="text-xs text-blue-700">
                                        Programadas
                                    </p>

                                    <p
                                        class="mt-1 text-lg font-bold text-blue-900"
                                        x-text="formatearCantidad(resumenPilas.programadas)"
                                    ></p>
                                </div>

                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                    <p class="text-xs text-emerald-700">
                                        Hechas
                                    </p>

                                    <p
                                        class="mt-1 text-lg font-bold text-emerald-900"
                                        x-text="formatearCantidad(resumenPilas.ejecutadas)"
                                    ></p>
                                </div>

                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                                    <p class="text-xs text-amber-700">
                                        Faltantes
                                    </p>

                                    <p
                                        class="mt-1 text-lg font-bold text-amber-900"
                                        x-text="formatearCantidad(resumenPilas.faltantes)"
                                    ></p>
                                </div>
                            </div>

                            {{-- SIN PILAS --}}
                            <div
                                x-show="pilasObra.length === 0"
                                class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4"
                            >
                                <p class="text-sm font-semibold text-amber-800">
                                    Esta obra no tiene pilas activas asignadas.
                                </p>

                                <p class="mt-1 text-xs text-amber-700">
                                    Puedes continuar con la asignación de la máquina,
                                    pero no existe un desglose de pilas activo.
                                </p>
                            </div>

                            {{-- TABLA DE PILAS --}}
                            <div
                                x-show="pilasObra.length > 0"
                                class="overflow-hidden rounded-xl border border-slate-200"
                            >
                                <div class="max-h-72 overflow-auto">
                                    <table class="min-w-[850px] w-full text-sm">
                                        <thead class="sticky top-0 bg-slate-50">
                                            <tr class="border-b text-xs text-slate-500">
                                                <th class="px-3 py-2 text-left">
                                                    No.
                                                </th>

                                                <th class="px-3 py-2 text-left">
                                                    Tipo
                                                </th>

                                                <th class="px-3 py-2 text-center">
                                                    Proyecto
                                                </th>

                                                <th class="px-3 py-2 text-center">
                                                    Hechas
                                                </th>

                                                <th class="px-3 py-2 text-center">
                                                    Faltan
                                                </th>

                                                <th class="px-3 py-2 text-left">
                                                    Diámetro
                                                </th>

                                                <th class="px-3 py-2 text-left">
                                                    Profundidad
                                                </th>

                                                <th class="px-3 py-2 text-left">
                                                    Ubicación
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <template
                                                x-for="pila in pilasObra"
                                                :key="pila.id"
                                            >
                                                <tr class="border-b last:border-b-0 hover:bg-slate-50">
                                                    <td
                                                        class="px-3 py-2 font-medium text-slate-700"
                                                        x-text="pila.numero_pila ?? '—'"
                                                    ></td>

                                                    <td
                                                        class="px-3 py-2"
                                                        x-text="pila.tipo || '—'"
                                                    ></td>

                                                    <td
                                                        class="px-3 py-2 text-center"
                                                        x-text="formatearCantidad(pila.cantidad_programada)"
                                                    ></td>

                                                    <td
                                                        class="px-3 py-2 text-center text-emerald-700"
                                                        x-text="formatearCantidad(pila.cantidad_ejecutada)"
                                                    ></td>

                                                    <td
                                                        class="px-3 py-2 text-center font-semibold text-amber-700"
                                                        x-text="formatearCantidad(pila.cantidad_faltante)"
                                                    ></td>

                                                    <td
                                                        class="px-3 py-2"
                                                        x-text="formatearMedida(pila.diametro_proyecto)"
                                                    ></td>

                                                    <td
                                                        class="px-3 py-2"
                                                        x-text="formatearMedida(pila.profundidad_proyecto)"
                                                    ></td>

                                                    <td
                                                        class="px-3 py-2"
                                                        x-text="pila.ubicacion || '—'"
                                                    ></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>

                        {{-- DATOS DE LA ASIGNACIÓN --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">
                                    Fecha de inicio
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="fecha_inicio"
                                    required
                                    x-model="asignarForm.fechaInicio"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">
                                    Horómetro inicial
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="horometro_inicio"
                                    required
                                    min="0"
                                    step="0.01"
                                    x-model="asignarForm.horometroInicio"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">
                                Notas
                            </label>

                            <textarea
                                name="notas"
                                rows="3"
                                maxlength="1000"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Comentario opcional"
                            ></textarea>
                        </div>
                    </div>

                    {{-- ACCIONES --}}
                    <div class="flex justify-end gap-3 border-t border-slate-100 bg-white px-5 py-4">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            @click="
                                reiniciarModal();
                                closeAsignarModal();
                            "
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            :disabled="cargandoPilas"
                            class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span x-show="!cargandoPilas">
                                Asignar a obra
                            </span>

                            <span x-show="cargandoPilas" x-cloak>
                                Consultando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
    @can('maquinas.horas.create.access')
        <div
            x-cloak
            x-show="horasModalOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4 py-6"
            @keydown.escape.window="closeHorasModal()">
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl border border-slate-200" @click.outside="closeHorasModal()">
                <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-[#0B265A]">Registrar horas de maquina</h2>
                        <p class="text-xs text-slate-500" x-text="form.maquina"></p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="closeHorasModal()">&times;</button>
                </div>

                <form method="POST" :action="form.action" class="space-y-4 px-5 py-5">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="text-xs font-semibold text-slate-500">Obra actual</div>
                            <div class="font-medium text-slate-800" x-text="form.obra"></div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-500">Horometro actual</div>
                            <div class="font-medium text-slate-800"><span x-text="formatHoras(form.horometroActual)"></span> h</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nuevo horometro <span class="text-red-500">*</span></label>
                        <input type="number" name="horometro_fin" required min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" :placeholder="formatHoras(form.horometroActual)">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Inicio</label>
                            <input type="datetime-local" name="inicio" x-model="form.inicio" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Fin</label>
                            <input type="datetime-local" name="fin" x-model="form.fin" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Notas</label>
                        <textarea name="notas" rows="3" maxlength="500" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Comentario opcional"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="closeHorasModal()">Cancelar</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-[#0B265A] text-sm font-semibold text-white hover:bg-blue-900">Guardar horas</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
</div>
@endsection
@push('scripts')
<script>
    function maquinasHorasModal() {
        return {
            horasModalOpen: false,
            asignarModalOpen: false,
            form: {
                action: '',
                maquina: '',
                obra: '',
                horometroActual: 0,
                inicio: '',
                fin: '',
            },
            asignarForm: {
                action: '',
                maquina: '',
                fechaInicio: '',
                horometroInicio: 0,
            },
            openHorasModal(data) {
                const now = new Date();
                const localNow = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);

                this.form = {
                    action: data.action,
                    maquina: data.maquina || 'Maquina',
                    obra: data.obra || 'Obra',
                    horometroActual: Number(data.horometroActual || 0),
                    inicio: localNow,
                    fin: localNow,
                };
                this.horasModalOpen = true;
            },
            closeHorasModal() {
                this.horasModalOpen = false;
            },
            openAsignarModal(data) {
                const now = new Date();
                const today = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);

                this.asignarForm = {
                    action: data.action,
                    maquina: data.maquina || 'Maquina',
                    fechaInicio: today,
                    horometroInicio: Number(data.horometroInicio || 0),
                };
                this.asignarModalOpen = true;
            },
            closeAsignarModal() {
                this.asignarModalOpen = false;
            },
            formatHoras(value) {
                return Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                });
            },
        };
    }
</script>
@endpush

