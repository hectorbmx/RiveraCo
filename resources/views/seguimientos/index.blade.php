@extends('layouts.admin')

@section('title', 'Seguimientos')

@section('content')
@php
    $piloto = isset($hilosPiloto);
    $estados = [
        'pendiente' => ['nombre' => 'Sin respuesta', 'color' => 'bg-amber-50 text-amber-900 ring-amber-200', 'punto' => 'bg-amber-500'],
        'revision' => ['nombre' => 'Por revisar', 'color' => 'bg-violet-50 text-violet-800 ring-violet-200', 'punto' => 'bg-violet-500'],
        'espera' => ['nombre' => 'Esperando al cliente', 'color' => 'bg-sky-50 text-sky-800 ring-sky-200', 'punto' => 'bg-sky-500'],
        ...($piloto ? ['respondido' => ['nombre' => 'Respondidas', 'color' => 'bg-blue-50 text-blue-800 ring-blue-200', 'punto' => 'bg-blue-500']] : []),
        'cerrado' => ['nombre' => 'Cerradas', 'color' => 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'punto' => 'bg-emerald-500'],
    ];

    $hilos = $hilosPiloto ?? collect([
        [
            'id' => 1, 'cliente' => 'Constructora del Valle', 'asunto' => 'Cotización de pilas para Parque Industrial Norte',
            'estado' => 'pendiente', 'responsable' => 'Ana López', 'actividad' => 'Hoy, 09:14',
            'plazo' => 'Hoy, 16:00',
            'proxima' => 'Enviar disponibilidad y solicitar el estudio de mecánica de suelos.',
            'historial' => [
                ['tipo' => 'Solicitud recibida', 'autor' => 'Carlos Méndez', 'fecha' => 'Hoy, 09:14',
                    'nota' => 'Solicita precio y disponibilidad para 32 pilas de cimentación. Comparte el plano preliminar y pide confirmar si podemos iniciar en diciembre.'],
            ],
        ],
        [
            'id' => 2, 'cliente' => 'Infraestructura Sierra', 'asunto' => 'Disponibilidad de equipo para perforación',
            'estado' => 'pendiente', 'responsable' => null, 'actividad' => 'Ayer, 16:32',
            'plazo' => 'Hoy, 10:00', 'vencido' => true,
            'proxima' => 'Asignar responsable y confirmar fechas de movilización.',
            'historial' => [
                ['tipo' => 'Solicitud recibida', 'autor' => 'Mariana Ruiz', 'fecha' => 'Ayer, 16:32',
                    'nota' => 'Pregunta si Rivera puede movilizar una perforadora a la obra durante la primera semana de diciembre.'],
            ],
        ],
        [
            'id' => 3, 'cliente' => 'Proyectos Mirador', 'asunto' => 'Cimentación para ampliación de nave',
            'estado' => 'espera', 'responsable' => 'Luis Ramírez', 'actividad' => 'Ayer, 12:48',
            'proxima' => 'El cliente debe confirmar el diámetro de la pila y la fecha objetivo.',
            'historial' => [
                ['tipo' => 'Solicitud recibida', 'autor' => 'Elena Soto', 'fecha' => '23 nov, 09:22',
                    'nota' => 'Solicita una propuesta para la cimentación de una nave en ampliación.'],
                ['tipo' => 'Respuesta enviada', 'autor' => 'Luis Ramírez', 'fecha' => '23 nov, 12:48',
                    'nota' => 'Envió una propuesta preliminar en PDF y pidió confirmar el diámetro de la pila y la fecha objetivo.'],
            ],
        ],
        [
            'id' => 4, 'cliente' => 'Plataforma de Licitaciones', 'asunto' => 'Invitación a participar en una obra industrial',
            'estado' => 'revision', 'responsable' => null, 'actividad' => 'Ayer, 10:05',
            'proxima' => 'Confirmar si la invitación incluye trabajos de cimentación.',
            'historial' => [
                ['tipo' => 'Correo recibido', 'autor' => 'Plataforma de Licitaciones', 'fecha' => 'Ayer, 10:05',
                    'nota' => 'Invitación a una licitación de obra industrial. El correo no especifica las partidas solicitadas.'],
            ],
        ],
        [
            'id' => 5, 'cliente' => 'Grupo Terranova', 'asunto' => 'Ficha técnica de perforación',
            'estado' => 'cerrado', 'responsable' => 'Ana López', 'actividad' => '20 nov, 11:46',
            'proxima' => 'Solicitud resuelta. El cliente confirmó que recibió la ficha técnica.',
            'historial' => [
                ['tipo' => 'Solicitud recibida', 'autor' => 'Javier Mora', 'fecha' => '20 nov, 08:35',
                    'nota' => 'Solicita profundidades de perforación y los diámetros disponibles.'],
                ['tipo' => 'Respuesta enviada', 'autor' => 'Ana López', 'fecha' => '20 nov, 10:28',
                    'nota' => 'Envió la ficha técnica y las especificaciones de los equipos disponibles.'],
                ['tipo' => 'Confirmación recibida', 'autor' => 'Javier Mora', 'fecha' => '20 nov, 11:46',
                    'nota' => 'Confirmó que la información recibida era suficiente.'],
            ],
        ],
    ]);

    $filtros = request()->query();
    $estadoActivo = $filtros['estado'] ?? '';
    $estadoActivo = is_string($estadoActivo) && isset($estados[$estadoActivo]) ? $estadoActivo : '';
    $busqueda = is_string($filtros['buscar'] ?? null) ? trim($filtros['buscar']) : '';
    $visibles = $hilos->filter(fn ($hilo) =>
        ($estadoActivo === '' || $hilo['estado'] === $estadoActivo)
        && ($busqueda === '' || str_contains(
            mb_strtolower($hilo['cliente'] . ' ' . $hilo['asunto'] . ' ' . $hilo['responsable']),
            mb_strtolower($busqueda),
        ))
    );
    $hiloId = filter_var($filtros['hilo'] ?? null, FILTER_VALIDATE_INT);
    $seleccionado = $hiloId ? $visibles->firstWhere('id', $hiloId) : null;
    $seleccionado ??= $visibles->first();
    $conteos = $hilos->countBy('estado');
    $url = fn (array $params = []) => route('seguimientos.index', array_filter($params));
    $empleadosResponsables ??= [
        ['id' => 1, 'nombre' => 'Ana López', 'puesto' => 'Presupuestos'],
        ['id' => 2, 'nombre' => 'Luis Ramírez', 'puesto' => 'Ingeniería'],
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Seguimiento de correos</h1>
            <p class="mt-1 text-sm text-slate-600">Solicitudes de clientes en <span class="font-medium text-slate-800">{{ $mailbox ?? 'pilas@riveraco.com.mx' }}</span></p>
        </div>
        <p class="rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">
            {{ $piloto ? ($pilotoLocal ? 'Piloto local · Correos reales' : 'Correos reales · Importación manual') : 'Vista previa · Datos de ejemplo' }}
        </p>
    </header>

    <nav aria-label="Filtrar por estado" class="grid grid-cols-2 gap-3 {{ $piloto ? 'xl:grid-cols-5' : 'xl:grid-cols-4' }}">
        @foreach($estados as $clave => $estado)
            <a href="{{ $url(['estado' => $clave, 'buscar' => $busqueda]) }}"
               @if($estadoActivo === $clave) aria-current="true" @endif
               class="rounded-xl border bg-white px-4 py-3.5 shadow-sm transition hover:border-blue-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 {{ $estadoActivo === $clave ? 'border-blue-500 ring-1 ring-blue-500' : 'border-slate-200' }}">
                <span class="flex items-center gap-2 text-sm font-medium text-slate-600">
                    <span class="h-2 w-2 shrink-0 rounded-full {{ $estado['punto'] }}" aria-hidden="true"></span>
                    {{ $estado['nombre'] }}
                </span>
                <span class="mt-1 block text-2xl font-bold tabular-nums text-[#0B265A]">{{ $conteos->get($clave, 0) }}</span>
            </a>
        @endforeach
    </nav>

    <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]"
         x-data="{
             empleados: @js($empleadosResponsables),
             responsablePropuesto: null,
             buscarEmpleado: '',
             normalizar(texto) {
                 return texto.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
             },
             get empleadosFiltrados() {
                 const consulta = this.normalizar(this.buscarEmpleado.trim());
                 return this.empleados.filter(empleado =>
                     this.normalizar(empleado.nombre + ' ' + empleado.puesto).includes(consulta)
                 );
             }
         }">
        <section aria-labelledby="seguimientos-lista" class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="space-y-3 border-b border-slate-200 px-4 py-4">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="seguimientos-lista" class="font-semibold text-slate-900">Solicitudes <span class="ml-1 text-sm font-normal text-slate-500">{{ $visibles->count() }}</span></h2>
                    @if($estadoActivo !== '' || $busqueda !== '')
                        <a href="{{ $url() }}" class="text-sm font-semibold text-blue-800 hover:underline">Ver todas</a>
                    @endif
                </div>
                <form method="GET" action="{{ $url() }}" role="search" class="flex flex-wrap gap-2">
                    @if($estadoActivo !== '')
                        <input type="hidden" name="estado" value="{{ $estadoActivo }}">
                    @endif
                    <label for="buscar-seguimiento" class="sr-only">Buscar solicitudes</label>
                    <input type="search" name="buscar" id="buscar-seguimiento" value="{{ $busqueda }}" maxlength="120"
                           placeholder="Cliente, asunto o responsable" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <button type="submit" class="rounded-lg bg-[#0B265A] px-3.5 py-2 text-sm font-semibold text-white hover:bg-blue-900">Buscar</button>
                </form>
            </div>

            <ul class="divide-y divide-slate-100">
                @forelse($visibles as $hilo)
                    @php($activo = $seleccionado['id'] === $hilo['id'])
                    <li>
                        <a href="{{ $url(['estado' => $estadoActivo, 'buscar' => $busqueda, 'hilo' => $hilo['id']]) }}"
                           @if($activo) aria-current="true" @endif
                           class="block border-l-4 px-4 py-3.5 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600 {{ $activo ? 'border-blue-700 bg-blue-50' : 'border-transparent hover:bg-slate-50' }}">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                <p class="text-xs font-semibold text-slate-600">{{ $hilo['cliente'] }}</p>
                                <p class="text-xs tabular-nums text-slate-500">{{ $hilo['actividad'] }}</p>
                            </div>
                            <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $hilo['asunto'] }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $estados[$hilo['estado']]['color'] }}">{{ $estados[$hilo['estado']]['nombre'] }}</span>
                                <span class="text-xs text-slate-600"
                                      @if($activo) x-text="responsablePropuesto?.nombre ?? @js($hilo['responsable'] ?? 'Sin asignar')" @endif>{{ $hilo['responsable'] ?? 'Sin asignar' }}</span>
                                @if($hilo['vencido'] ?? false)
                                    <span class="text-xs font-semibold text-rose-700">Vencida</span>
                                @endif
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-10 text-center text-sm text-slate-600">
                        {{ $hilos->isEmpty() ? 'Todavía no hay solicitudes en la muestra sincronizada.' : 'No hay solicitudes que coincidan con tu búsqueda.' }}
                    </li>
                @endforelse
            </ul>
        </section>

        @if($seleccionado)
            <section id="detalle-seguimiento" aria-labelledby="seguimiento-asunto" class="min-w-0 rounded-xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 px-5 py-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-600">{{ $seleccionado['cliente'] }}</p>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $estados[$seleccionado['estado']]['color'] }}">{{ $estados[$seleccionado['estado']]['nombre'] }}</span>
                    </div>
                    <h2 id="seguimiento-asunto" class="mt-2 break-words text-xl font-bold leading-tight text-[#0B265A]">{{ $seleccionado['asunto'] }}</h2>
                    <dl class="mt-4 grid grid-cols-2 gap-3">
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-slate-500">Responsable</dt>
                            <dd class="mt-0.5 flex items-start gap-2">
                                <span class="min-w-0" aria-live="polite">
                                    <span class="block break-words text-sm font-semibold"
                                          x-text="responsablePropuesto?.nombre ?? @js($seleccionado['responsable'] ?? 'Sin asignar')">{{ $seleccionado['responsable'] ?? 'Sin asignar' }}</span>
                                    <span x-cloak x-show="responsablePropuesto" class="mt-0.5 block text-xs text-slate-500">Selección sin guardar</span>
                                </span>
                                <button type="button" x-ref="botonResponsable" title="Asignar responsable" aria-label="Asignar responsable"
                                        aria-haspopup="dialog" aria-controls="asignar-responsable"
                                        @click="buscarEmpleado = ''; $refs.modalResponsable.showModal()"
                                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0B265A] hover:border-blue-300 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                                        <circle cx="9" cy="7" r="3"/>
                                        <path d="M3 20v-2a5 5 0 0 1 5-5h2a5 5 0 0 1 5 5v2m4-14v6m-3-3h6"/>
                                    </svg>
                                </button>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Última actividad</dt>
                            <dd class="mt-0.5 text-sm font-semibold">{{ $seleccionado['actividad'] }}</dd>
                        </div>
                    </dl>
                </header>

                <div class="space-y-5 px-5 py-5">
                    @if($piloto)
                        <div class="rounded-lg bg-slate-50 px-4 py-3 ring-1 ring-inset ring-slate-200">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-xs font-semibold text-[#0B265A]">Motivo de clasificación</h3>
                                <p class="text-xs text-slate-600">{{ $seleccionado['fuente'] }}</p>
                            </div>
                            <p class="mt-1 break-words text-sm leading-relaxed text-slate-700">{{ $seleccionado['clasificacion'] }}</p>
                            @if($seleccionado['evidencia'])
                                <p class="mt-2 break-words text-xs leading-relaxed text-slate-600">«{{ $seleccionado['evidencia'] }}»</p>
                            @endif
                        </div>
                    @else
                    <div class="rounded-lg bg-slate-50 px-4 py-3 ring-1 ring-inset ring-slate-200">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-xs font-semibold text-[#0B265A]">{{ $seleccionado['estado'] === 'cerrado' ? 'Resolución' : 'Próxima acción' }}</h3>
                            @if(isset($seleccionado['plazo']))
                                <p class="text-xs font-medium {{ ($seleccionado['vencido'] ?? false) ? 'text-rose-700' : 'text-slate-600' }}">
                                    Plazo: {{ $seleccionado['plazo'] }}
                                </p>
                            @endif
                        </div>
                        <p class="mt-1 text-sm leading-relaxed text-slate-700">{{ $seleccionado['proxima'] }}</p>
                    </div>
                    @endif

                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Historial del correo</h3>
                        <ol class="ml-1 mt-4 space-y-4 border-l border-slate-200 pl-4">
                            @foreach($seleccionado['historial'] as $evento)
                                <li class="relative">
                                    <span aria-hidden="true" class="absolute -left-[21px] top-1.5 h-2.5 w-2.5 rounded-full ring-2 ring-white {{ $evento['tipo'] === 'Respuesta enviada' ? 'bg-blue-700' : 'bg-slate-400' }}"></span>
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                        <p class="text-sm font-semibold text-slate-900">{{ $evento['tipo'] }}</p>
                                        <p class="text-xs tabular-nums text-slate-500">{{ $evento['fecha'] }}</p>
                                    </div>
                                    <p class="mt-0.5 text-xs font-medium text-slate-600">{{ $evento['autor'] }}</p>
                                    @if(!empty($evento['asunto']) && $evento['asunto'] !== $seleccionado['asunto'])
                                        <p class="mt-2 break-words text-sm font-medium text-slate-800">{{ $evento['asunto'] }}</p>
                                    @endif
                                    @if($evento['nota'])
                                        <p class="mt-2 break-words text-sm leading-relaxed text-slate-700">{{ $evento['nota'] }}</p>
                                    @endif
                                    @if(!empty($evento['enlace']))
                                        <a href="{{ $evento['enlace'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-xs font-semibold text-blue-800 hover:underline">Abrir en Outlook</a>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </section>
            <dialog id="asignar-responsable" x-ref="modalResponsable" @close="$refs.botonResponsable.focus()"
                    aria-labelledby="titulo-asignar-responsable" aria-describedby="nota-asignar-responsable"
                    class="w-[calc(100%-2rem)] max-w-md rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/55">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="titulo-asignar-responsable" class="text-lg font-semibold text-[#0B265A]">Asignar responsable</h2>
                        <button type="button" @click="$refs.modalResponsable.close()"
                                class="rounded-md px-2 py-1 text-sm font-medium text-slate-600 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600">
                            Cerrar
                        </button>
                    </div>
                    <p id="nota-asignar-responsable" class="mt-1 text-sm text-slate-600">La selección se muestra en esta vista y se pierde al recargar.</p>
                    <label for="buscar-empleado-seguimiento" class="mt-4 block text-sm font-medium text-slate-700">Buscar empleado</label>
                    <input id="buscar-empleado-seguimiento" type="search" autofocus x-model="buscarEmpleado"
                           placeholder="Nombre o puesto" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                </div>
                <p x-show="empleadosFiltrados.length === 0" role="status" class="px-5 py-8 text-center text-sm text-slate-600">No se encontraron empleados.</p>
                <ul x-show="empleadosFiltrados.length > 0" class="max-h-72 divide-y divide-slate-100 overflow-y-auto">
                    <template x-for="empleado in empleadosFiltrados" :key="empleado.id">
                        <li>
                            <button type="button" @click="responsablePropuesto = empleado; $refs.modalResponsable.close()"
                                    class="flex w-full items-center justify-between gap-3 px-5 py-3 text-left hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                <span class="min-w-0">
                                    <span x-text="empleado.nombre" class="block break-words text-sm font-semibold text-slate-900"></span>
                                    <span x-show="empleado.puesto" x-text="empleado.puesto" class="mt-0.5 block break-words text-xs text-slate-600"></span>
                                </span>
                                <span x-show="responsablePropuesto?.id === empleado.id" class="shrink-0 text-xs font-medium text-blue-800">Seleccionado</span>
                            </button>
                        </li>
                    </template>
                </ul>
            </dialog>
        @endif
    </div>
</div>
@endsection
