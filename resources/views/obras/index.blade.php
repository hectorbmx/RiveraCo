@extends('layouts.admin')

@section('title', 'Obras')

@section('content')

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
    <div>
        <h1 class="text-2xl font-bold text-[#0B265A]">Obras</h1>
        <p class="text-sm text-slate-500">Listado general de obras</p>
    </div>

    <a href="{{ route('obras.create') }}"
       class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
        + Nueva Obra
    </a>
</div>

@php
    $statusFiltroOpciones = ['' => 'Todos'];
    $availableStatuses = \App\Models\Obra::estatusSlugs();
    $statusLabels = \App\Models\Obra::estatusLabels();

    foreach ($availableStatuses as $key => $value) {
        $statusFiltroOpciones[$key] = $statusLabels[$value] ?? $key;
    }
@endphp

<x-filters.card action="{{ route('obras.index') }}" class="mb-6">
    <div class="md:col-span-4">
        <x-filters.input
            name="search"
            label="Buscar"
            :value="$search ?? ''"
            placeholder="Nombre, clave, cliente o empleado..."
            type="search"
            glow />
    </div>

    <div class="md:col-span-2">
        <div class="w-full">
            <x-filters.select
                name="status"
                label="Estatus"
                :value="$status ?? ''"
                :options="$statusFiltroOpciones"
                span="w-full" />
        </div>
    </div>

    <div class="md:col-span-2">
        <div class="w-full">
            <x-filters.select
                name="area_id"
                label="Área"
                :value="$areaId ?? ''"
                :options="collect($areas ?? [])->mapWithKeys(fn($area) => [$area->id => $area->nombre ?: $area->codigo])->prepend('Todos', '')->all()"
                span="w-full" />
        </div>
    </div>

    <div class="md:col-span-4 flex flex-wrap items-center gap-2 md:justify-end">
        <button type="submit" class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl text-sm hover:opacity-90 shadow-sm">
            Filtrar
        </button>
        <a href="{{ route('obras.index') }}" class="px-4 py-2 rounded-xl text-sm border border-white/25 bg-white/10 text-white hover:bg-white/20 shadow-sm">Limpiar</a>
        <button type="submit" name="exportar" value="csv" class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm hover:bg-emerald-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/></svg>
            Exportar CSV
        </button>
    </div>
</x-filters.card>
<details open class="mb-4 rounded-xl border border-slate-200 bg-white shadow-sm">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-[#0B265A]">
        <span>Resumen de obras <span class="ml-1 font-normal text-slate-500">· Según los filtros aplicados</span></span>
        <span class="text-xs font-medium text-slate-500">Mostrar / ocultar ▾</span>
    </summary>
    <div class="grid grid-cols-2 gap-2 border-t border-slate-100 p-3 md:grid-cols-4">
        @foreach([
            ['En ejecución', number_format($kpisObras['ejecucion']), 'text-blue-700'],
            ['Terminadas', number_format($kpisObras['terminadas']), 'text-emerald-700'],
            ['Obras Pilas', number_format($kpisObras['pilas']), 'text-[#0B265A]'],
            ['Obras Pozos', number_format($kpisObras['pozos']), 'text-[#0B265A]'],
            ['Facturado', '$' . number_format($kpisObras['facturado'], 2), 'text-blue-700'],
            ['Cobrado', '$' . number_format($kpisObras['cobrado'], 2), 'text-emerald-700'],
            ['Por cobrar', '$' . number_format($kpisObras['por_cobrar'], 2), 'text-amber-700'],
        ] as [$etiqueta, $valor, $color])
            <div class="min-w-0 rounded-lg bg-slate-50 px-3 py-2">
                <div class="text-xs font-medium text-slate-500">{{ $etiqueta }}</div>
                <div class="mt-0.5 break-words text-base font-bold {{ $color }}">{{ $valor }}</div>
            </div>
        @endforeach
    </div>
</details>


<div class="bg-white rounded-2xl shadow p-6">

    @if (session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="w-full overflow-x-auto rounded-xl border border-slate-200 shadow-sm bg-white">
        <table class="w-full min-w-[1200px] text-sm">
      <thead>
    <!-- Se cambia el fondo a gris oscuro (bg-slate-800) y el texto a blanco/gris claro -->
    <tr class="bg-slate-800 text-slate-200 text-xs uppercase tracking-wider font-semibold rounded-t-lg">
        <th class="py-3 px-3 text-left">Clave</th>
        <th class="py-3 px-3 text-left">Nombre</th>
        <th class="py-3 px-3 text-left">Cliente</th>
        <th class="py-3 px-3 text-left">Máquina asignada</th>
        <th class="py-3 px-3 text-left">Status</th>
        <th class="py-3 px-3 text-right">Valor obra</th>
        <th class="py-3 px-3 text-right">Facturado</th>
        <th class="py-3 px-3 text-right">Cobrado</th>
        <th class="py-3 px-3 text-right">Gastado</th>
        <th class="py-3 px-3 text-right">Por facturar</th>

        <!-- Columna Avance con ícono de ordenamiento y enlace dinámico -->
        <th class="py-3 px-3 text-center">
            @php
                $isSorting = request('sort_by') === 'avance';
                $nextOrder = $isSorting && request('order') === 'asc' ? 'desc' : 'asc';
            @endphp
            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'avance', 'order' => $nextOrder]) }}" 
               class="inline-flex items-center justify-center gap-1.5 hover:text-white transition-colors group">
                <span>Avance</span>
                <span class="inline-flex items-center">
                    @if($isSorting && request('order') === 'asc')
                        <!-- Flecha arriba (Menor a Mayor) -->
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                        </svg>
                    @elseif($isSorting && request('order') === 'desc')
                        <!-- Flecha abajo (Mayor a Menor) -->
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    @else
                        <!-- Ícono neutro/doble flecha al pasar el mouse -->
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                    @endif
                </span>
            </a>
        </th>

        <th class="py-3 px-3 text-right">Acciones</th>
    </tr>
</thead>

            <tbody>
                @forelse($obras as $obra)
                    @php
                        [
                            'valorObra' => $valorObra, 'facturado' => $facturado,
                            'cobrado' => $cobrado, 'gastado' => $gastado,
                            'porFacturar' => $porFacturar, 'avancePct' => $avancePct,
                            'avancePilasPct' => $avancePilasPct,
                        ] = app(\App\Services\Obras\ReporteObrasService::class)->indicadores($obra);

                        $money = fn ($value) => '$' . number_format((float) $value, 2);
                    @endphp
                    <tr class="border-b hover:bg-slate-50 align-top">
                        <td class="py-3 px-2 font-medium text-slate-700">{{ $obra->clave_obra }}</td>

                        <td class="py-3 px-2">
                            <a href="{{ route('obras.edit', $obra) }}"
                               class="font-semibold text-slate-800 hover:text-blue-700 hover:underline">
                                {{ $obra->nombre }}
                            </a>
                        </td>
                        <td class="py-3 px-2">
                            @if($obra->cliente)
                                <a href="{{ route('clientes.edit', $obra->cliente) }}"
                                   class="text-slate-700 hover:text-blue-700 hover:underline">
                                    {{ $obra->cliente->nombre_comercial }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-2">
                            @php
                                $maquinasActivas = $obra->maquinasAsignadas->filter(fn ($asignacion) => $asignacion->maquina)->values();
                            @endphp

                            @if($maquinasActivas->isNotEmpty())
                                <div class="space-y-1 min-w-[160px]">
                                    @foreach($maquinasActivas->take(2) as $asignacion)
                                        <div class="text-slate-700 leading-tight">
                                            <span class="font-semibold">{{ $asignacion->maquina->nombre }}</span>
                                            <div class="text-[11px] text-slate-400">
                                                {{ collect([$asignacion->maquina->codigo, $asignacion->maquina->modelo])->filter()->join(' · ') ?: 'Sin código/modelo' }}
                                            </div>
                                        </div>
                                    @endforeach

                                    @if($maquinasActivas->count() > 2)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                            +{{ $maquinasActivas->count() - 2 }} más
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400">Sin máquina</span>
                            @endif
                        </td>
                        <td class="py-3 px-2">
                            @php
                                $val = (int)($obra->estatus_nuevo ?? 1);
                                $cls = \App\Models\Obra::estatusBadgeClasses()[$val] ?? 'bg-slate-100 text-slate-700';
                                $lbl = $obra->estatus_label;
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $cls }}">
                                {{ $lbl }}
                            </span>
                        </td>

                        <td class="py-3 px-2 text-right font-medium text-slate-800">
                            {{ $money($valorObra) }}
                        </td>

                        <td class="py-3 px-2 text-right font-medium text-blue-700">
                            {{ $money($facturado) }}
                        </td>

                        <td class="py-3 px-2 text-right font-medium text-emerald-700">
                            {{ $money($cobrado) }}
                        </td>

                        <td class="py-3 px-2 text-right font-medium text-orange-600">
                            {{ $money($gastado) }}
                        </td>

                        <td class="py-3 px-2 text-right font-medium text-amber-700">
                            {{ $money($porFacturar) }}
                        </td>

                        <td class="py-3 px-2">
                            <div class="min-w-[140px] space-y-2">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-[10px] text-slate-500">
                                        <span>Financiero</span>
                                        <span>{{ $avancePct }}%</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-[#0B265A] to-[#3B82F6]" style="width: {{ $avancePct }}%"></div>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-[10px] text-slate-500">
                                        <span>Obra</span>
                                        <span>{{ $avancePilasPct }}%</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-lime-400" style="width: {{ $avancePilasPct }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="py-3 px-2 text-right space-x-2">
                            <a href="{{ route('obras.edit', $obra) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                Detalles
                            </a>

                            <form action="{{ route('obras.destroy', $obra) }}"
                                  method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('¿Eliminar esta obra?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:text-red-800 font-medium text-sm">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="11" class="py-6 text-center text-slate-500">
                            No hay obras registradas aún.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $obras->links() }}
    </div>
</div>

@endsection



