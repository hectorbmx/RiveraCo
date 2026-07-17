@extends('layouts.admin')

@section('title', 'Telefonia')

@section('content')
<div class="max-w-7xl mx-auto space-y-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Telefonia</h1>
            <p class="text-sm text-slate-500">Grandstream UCM conectado a SIRICO.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <div class="font-semibold">Revisa lo siguiente:</div>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 sm:px-6">
            @php
                $tabs = [
                    'extensions' => 'Extensiones',
                    'calls' => 'Llamadas',
                    'missed' => 'Perdidas',
                    'settings' => 'Configuracion',
                ];
            @endphp

            <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('telephony.extensions.index', ['tab' => $key]) }}"
                       class="whitespace-nowrap border-b-2 py-4 text-sm font-medium transition {{ $tab === $key ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        @if ($tab === 'extensions')
            <div class="p-4 sm:p-6">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Extensiones</h2>
                        <p class="text-sm text-slate-500">{{ $extensions->count() }} extensiones sincronizadas desde UCM.</p>
                    </div>
                    <form method="POST" action="{{ route('telephony.extensions.sync') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">
                            Sincronizar extensiones
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Extension</th>
                                <th class="px-4 py-3">Nombre UCM</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">IP / Addr</th>
                                <th class="px-4 py-3">Usuario SIRICO</th>
                                <th class="px-4 py-3 text-right">Accion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($extensions as $extension)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $extension->extension }}</td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <a href="{{ route('telephony.extensions.show', $extension) }}" class="font-medium text-blue-700 hover:text-blue-900 hover:underline">
                                            {{ $extension->fullname ?: 'Sin nombre' }}
                                        </a>
                                        @if ($extension->user_name)
                                            <div class="text-xs text-slate-400">{{ $extension->user_name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $extension->out_of_service ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                                            {{ $extension->out_of_service ? 'Fuera de servicio' : ($extension->status ?: 'Disponible') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $extension->addr ?: '-' }}</td>
                                    <td class="px-4 py-3">
                                        <form id="assign-extension-{{ $extension->id }}" method="POST" action="{{ route('telephony.extensions.assign', $extension) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="user_id" class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">Sin asignar</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}" @selected((int) $extension->user_id === (int) $user->id)>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="submit" form="assign-extension-{{ $extension->id }}" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                                            Guardar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No hay extensiones sincronizadas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif ($tab === 'calls')
            <div class="p-4 sm:p-6 space-y-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Llamadas</h2>
                        <p class="text-sm text-slate-500">CDR importados desde Grandstream y guardados en SIRICO.</p>
                    </div>
                    <form method="POST" action="{{ route('telephony.calls.import-today') }}">
                        @csrf
                        <input type="hidden" name="tab" value="calls">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">
                            Importar llamadas de hoy
                        </button>
                    </form>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total filtrado</div>
                        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $callStats['total'] }}</div>
                    </div>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Contestadas</div>
                        <div class="mt-1 text-2xl font-semibold text-emerald-900">{{ $callStats['answered'] }}</div>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Perdidas/no contestadas</div>
                        <div class="mt-1 text-2xl font-semibold text-amber-900">{{ $callStats['missed'] }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sin usuario</div>
                        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $callStats['unassigned'] }}</div>
                    </div>
                </div>

                <form method="GET" action="{{ route('telephony.extensions.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <input type="hidden" name="tab" value="calls">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Desde</span>
                            <input type="date" name="date_from" value="{{ $callFilters['date_from'] }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Hasta</span>
                            <input type="date" name="date_to" value="{{ $callFilters['date_to'] }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Direccion</span>
                            <select name="direction" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todas</option>
                                @foreach ($directionOptions as $direction)
                                    <option value="{{ $direction }}" @selected($callFilters['direction'] === $direction)>{{ ['incoming' => 'Entrante', 'outgoing' => 'Saliente', 'internal' => 'Interna'][$direction] ?? ucfirst($direction) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Estado</span>
                            <select name="status" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos</option>
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}" @selected($callFilters['status'] === $status)>{{ ['answered' => 'Contestada', 'no_answer' => 'No contestada', 'busy' => 'Ocupado', 'failed' => 'Fallida'][$status] ?? str_replace('_', ' ', ucfirst($status)) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Extension</span>
                            <select name="extension_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todas</option>
                                @foreach ($extensions as $extension)
                                    <option value="{{ $extension->id }}" @selected((string) $callFilters['extension_id'] === (string) $extension->id)>
                                        {{ $extension->extension }}{{ $extension->fullname ? ' - '.$extension->fullname : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Usuario</span>
                            <select name="user_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) $callFilters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm xl:col-span-2">
                            <span class="mb-1 block font-medium text-slate-700">Buscar</span>
                            <input type="search" name="search" value="{{ $callFilters['search'] }}" placeholder="Numero, extension, nombre o sesion" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filtrar</button>
                        <a href="{{ route('telephony.extensions.index', ['tab' => 'calls']) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Limpiar</a>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Inicio</th>
                                <th class="px-4 py-3">Direccion</th>
                                <th class="px-4 py-3">Origen</th>
                                <th class="px-4 py-3">Destino</th>
                                <th class="px-4 py-3">Relacionado</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Duracion</th>
                                <th class="px-4 py-3">Usuario</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($calls as $call)
                                @php
                                    $directionLabels = [
                                        'incoming' => 'Entrante',
                                        'outgoing' => 'Saliente',
                                        'internal' => 'Interna',
                                    ];
                                    $statusLabels = [
                                        'answered' => 'Contestada',
                                        'no_answer' => 'No contestada',
                                        'busy' => 'Ocupado',
                                        'failed' => 'Fallida',
                                    ];
                                    $entityLabels = [
                                        \App\Models\Cliente::class => 'Cliente',
                                        \App\Models\Proveedor::class => 'Proveedor',
                                        \App\Models\Empleado::class => 'Empleado',
                                    ];
                                    $directionClass = [
                                        'incoming' => 'bg-blue-50 text-blue-700',
                                        'outgoing' => 'bg-violet-50 text-violet-700',
                                        'internal' => 'bg-slate-100 text-slate-700',
                                    ][$call->direction] ?? 'bg-slate-100 text-slate-700';
                                    $statusClass = [
                                        'answered' => 'bg-emerald-50 text-emerald-700',
                                        'no_answer' => 'bg-amber-50 text-amber-700',
                                        'busy' => 'bg-orange-50 text-orange-700',
                                        'failed' => 'bg-red-50 text-red-700',
                                    ][$call->status] ?? 'bg-slate-100 text-slate-700';
                                    $duration = (int) $call->duration_seconds;
                                    $durationLabel = sprintf('%02d:%02d:%02d', floor($duration / 3600), floor(($duration % 3600) / 60), $duration % 60);
                                    $relatedMatches = $call->relationLoaded('telephonyMatches') ? $call->getRelation('telephonyMatches') : collect();
                                @endphp
                                <tr class="align-top hover:bg-slate-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                                        <div>{{ optional($call->started_at)->format('Y-m-d') ?: '-' }}</div>
                                        <div class="text-xs text-slate-400">{{ optional($call->started_at)->format('H:i:s') }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $directionClass }}">
                                            {{ $directionLabels[$call->direction] ?? 'N/D' }}
                                        </span>
                                        @if ($call->ucm_userfield)
                                            <div class="mt-1 text-xs text-slate-400">{{ $call->ucm_userfield }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <div class="font-medium text-slate-900">{{ $call->source_number ?: $call->source_extension ?: '-' }}</div>
                                        @if ($call->source_extension)
                                            <div class="text-xs text-slate-400">Ext. {{ $call->source_extension }}</div>
                                        @endif
                                        @if ($call->caller_name)
                                            <div class="text-xs text-slate-400">{{ $call->caller_name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <div class="font-medium text-slate-900">{{ $call->destination_number ?: $call->destination_extension ?: '-' }}</div>
                                        @if ($call->destination_extension)
                                            <div class="text-xs text-slate-400">Ext. {{ $call->destination_extension }}</div>
                                        @endif
                                        @if ($call->answered_by)
                                            <div class="text-xs text-slate-400">Contesto: {{ $call->answered_by }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        @if ($relatedMatches->count())
                                            <div class="flex max-w-xs flex-wrap gap-1.5">
                                                @foreach ($relatedMatches as $match)
                                                    @php
                                                        $entityLabel = $entityLabels[$match->phoneable_type] ?? class_basename($match->phoneable_type);
                                                        $entityName = $match->display_name ?: optional($match->phoneable)->nombre_comercial ?: optional($match->phoneable)->nombre ?: ('#' . $match->phoneable_id);
                                                    @endphp
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700" title="{{ $match->raw_number }}">
                                                        {{ $entityLabel }}: {{ $entityName }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400">Sin relacion</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ $statusLabels[$call->status] ?? ($call->disposition ?: 'N/D') }}
                                        </span>
                                        @if ($call->disposition && $call->status !== $call->disposition)
                                            <div class="mt-1 text-xs text-slate-400">{{ $call->disposition }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                                        <div>{{ $durationLabel }}</div>
                                        <div class="text-xs text-slate-400">Tiempo hablado: {{ (int) $call->billsec }}s</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <div>{{ $call->user?->name ?: 'Sin usuario' }}</div>
                                        @if ($call->extension)
                                            <div class="text-xs text-slate-400">Ext. {{ $call->extension->extension }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No hay llamadas importadas con esos filtros.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $calls->links() }}
                </div>
            </div>
        @elseif ($tab === 'missed')
            <div class="p-4 sm:p-6 space-y-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Llamadas perdidas</h2>
                        <p class="text-sm text-slate-500">Entrantes no contestadas, ocupadas o fallidas registradas por el UCM.</p>
                    </div>
                    <form method="POST" action="{{ route('telephony.calls.import-today') }}">
                        @csrf
                        <input type="hidden" name="tab" value="missed">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">
                            Importar llamadas de hoy
                        </button>
                    </form>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total filtrado</div>
                        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $missedStats['total'] }}</div>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Numero privado/desconocido</div>
                        <div class="mt-1 text-2xl font-semibold text-amber-900">{{ $missedStats['unknown'] }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sin usuario interno</div>
                        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $missedStats['unassigned'] }}</div>
                    </div>
                    <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">Con usuario interno</div>
                        <div class="mt-1 text-2xl font-semibold text-blue-900">{{ $missedStats['with_user'] }}</div>
                    </div>
                </div>

                <form method="GET" action="{{ route('telephony.extensions.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <input type="hidden" name="tab" value="missed">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Desde</span>
                            <input type="date" name="missed_date_from" value="{{ $missedFilters['date_from'] }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Hasta</span>
                            <input type="date" name="missed_date_to" value="{{ $missedFilters['date_to'] }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Extension destino</span>
                            <select name="missed_extension_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todas</option>
                                @foreach ($extensions as $extension)
                                    <option value="{{ $extension->id }}" @selected((string) $missedFilters['extension_id'] === (string) $extension->id)>
                                        {{ $extension->extension }}{{ $extension->fullname ? ' - '.$extension->fullname : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Usuario interno</span>
                            <select name="missed_user_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) $missedFilters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium text-slate-700">Buscar numero</span>
                            <input type="search" name="missed_search" value="{{ $missedFilters['search'] }}" placeholder="Quien marco, extension o CDR" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filtrar</button>
                        <a href="{{ route('telephony.extensions.index', ['tab' => 'missed']) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Limpiar</a>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Quien marco</th>
                                <th class="px-4 py-3">Destino interno</th>
                                <th class="px-4 py-3">Usuario</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Duracion</th>
                                <th class="px-4 py-3">CDR</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($missedCalls as $call)
                                @php
                                    $statusClass = [
                                        'no_answer' => 'bg-amber-50 text-amber-700',
                                        'busy' => 'bg-orange-50 text-orange-700',
                                        'failed' => 'bg-red-50 text-red-700',
                                    ][$call->status] ?? 'bg-slate-100 text-slate-700';
                                    $duration = (int) $call->duration_seconds;
                                    $durationLabel = sprintf('%02d:%02d:%02d', floor($duration / 3600), floor(($duration % 3600) / 60), $duration % 60);
                                    $caller = $call->source_number ?: $call->source_extension ?: 'Numero desconocido';
                                @endphp
                                <tr class="align-top hover:bg-slate-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                                        <div>{{ optional($call->started_at)->format('Y-m-d') ?: '-' }}</div>
                                        <div class="text-xs text-slate-400">{{ optional($call->started_at)->format('H:i:s') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <div class="font-semibold text-slate-900">{{ $caller }}</div>
                                        @if ($call->caller_name)
                                            <div class="text-xs text-slate-400">{{ $call->caller_name }}</div>
                                        @endif
                                        @if ($call->clid && $call->clid !== $caller)
                                            <div class="mt-1 max-w-xs truncate text-xs text-slate-400" title="{{ $call->clid }}">{{ $call->clid }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <div class="font-medium text-slate-900">{{ $call->destination_number ?: $call->destination_extension ?: '-' }}</div>
                                        @if ($call->destination_extension)
                                            <div class="text-xs text-slate-400">Ext. {{ $call->destination_extension }}</div>
                                        @endif
                                        @if ($call->extension)
                                            <div class="text-xs text-slate-400">{{ $call->extension->fullname ?: $call->extension->extension }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $call->user?->name ?: 'Sin usuario' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ $call->status ? str_replace('_', ' ', ucfirst($call->status)) : ($call->disposition ?: 'N/D') }}
                                        </span>
                                        @if ($call->disposition && $call->status !== $call->disposition)
                                            <div class="mt-1 text-xs text-slate-400">{{ $call->disposition }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                                        <div>{{ $durationLabel }}</div>
                                        <div class="text-xs text-slate-400">Billsec: {{ (int) $call->billsec }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500">
                                        <div class="font-mono text-slate-700">{{ $call->ucm_cdr_id }}</div>
                                        @if ($call->session)
                                            <div class="mt-1 max-w-xs truncate" title="{{ $call->session }}">{{ $call->session }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No hay llamadas perdidas con esos filtros.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $missedCalls->links() }}
                </div>
            </div>
        @else
            <div class="p-8 text-center text-sm text-slate-500">
                Este tab se construira en una siguiente iteracion.
            </div>
        @endif
    </div>
</div>
@endsection
