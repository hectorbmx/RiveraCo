@extends('layouts.admin')

@section('title', 'Empleados')

@section('content')
<div class="max-w-8xl mx-auto">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Empleados</h1>
            <p class="text-sm text-slate-500">
                Catalogo de personal de Rivera Construcciones.
            </p>
        </div>

        <a href="{{ route('empleados.create') }}"
           class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
            + Nuevo empleado
        </a>
    </div>

    @php
        $areaFiltroOpciones = ['' => 'Todas'];

        foreach ($areas as $item) {
            $areaFiltroOpciones[$item->id] = trim(($item->codigo ? $item->codigo . ' - ' : '') . $item->nombre);
        }

        $documentosSortActual = $documentosSort ?? request('documentos_sort');
        $documentosNextSort = $documentosSortActual === 'desc' ? 'asc' : 'desc';
        $documentosSortUrl = route('empleados.index', array_merge(request()->except('page'), ['documentos_sort' => $documentosNextSort]));
    @endphp

    <x-filters.card action="{{ route('empleados.index') }}" class="mb-6">
        @if($documentosSortActual)
            <input type="hidden" name="documentos_sort" value="{{ $documentosSortActual }}">
        @endif
        <x-filters.input
            name="q"
            label="Buscar"
            :value="$search ?? ''"
            placeholder="Nombre, area o puesto..."
            span="md:col-span-5"
            type="search"
            glow />

        <x-filters.select
            name="estatus"
            label="Estatus"
            :value="$estatus ?? 'activo'"
            :options="['activo' => 'Activos', 'baja' => 'Baja', 'todos' => 'Todos']"
            span="md:col-span-2 md:max-w-44" />

        <x-filters.select
            name="area"
            label="Area"
            :value="$area ?? ''"
            :options="$areaFiltroOpciones"
            span="md:col-span-2 md:max-w-56" />

        <div class="md:col-span-3 flex items-end gap-2">
            <x-filters.actions
                submit-label="Filtrar"
                clear-url="{{ route('empleados.index') }}"
                class="flex-1" />

            <a href="{{ route('usuarios.export', request()->query()) }}"
               title="Exportar a Excel"
               class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-3 py-2 rounded-xl shadow transition flex items-center gap-1.5 text-xs h-[38px] whitespace-nowrap mb-[2px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Excel
            </a>
        </div>
    </x-filters.card>

    {{-- Mensaje flash --}}
    @if(session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <table class="min-w-full text-sm">
            <thead  class="bg-[#0B265A] text-white">
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-2 px-3">Empleado</th>
                    <th class="py-2 px-3">Área</th>
                    <th class="py-2 px-3">Puesto</th>
                    <th class="py-2 px-3">Sueldo</th>
                    <th class="py-2 px-3">
                        <a href="{{ $documentosSortUrl }}"
                           class="inline-flex items-center gap-1 text-white hover:text-[#FFC107] transition"
                           title="Ordenar por avance de documentos">
                            <span>Documentos</span>
                            @if($documentosSortActual === 'desc')
                                <span class="text-[10px]">&darr;</span>
                            @elseif($documentosSortActual === 'asc')
                                <span class="text-[10px]">&uarr;</span>
                            @else
                                <span class="text-[10px] opacity-60">&updownarrow;</span>
                            @endif
                        </a>
                    </th>
                    <th class="py-2 px-3">Estatus</th>
                    <th class="py-2 px-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($empleados as $emp)
                    <tr class="border-b last:border-b-0 hover:bg-slate-50">
                      <td class="py-2 px-3">
                        <!-- Enlace en el nombre -->
                        <a href="{{ route('empleados.edit', ['empleado' => $emp->id_Empleado, 'tab' => 'datos']) }}" 
                        class="font-medium text-slate-800 hover:text-blue-600 transition-colors">
                            {{ $emp->Apellidos }} {{ $emp->Nombre }}
                        </a>
                        <br>
                        <span class="text-xs text-slate-400">
                            ID: {{ $emp->id_Empleado }} · Ingreso:
                            {{ $emp->Fecha_ingreso ? $emp->Fecha_ingreso->format('d/m/Y') : '-' }}
                        </span>
                    </td>
                        <td class="py-2 px-3">{{ $emp->areaRef->nombre ?? '-' }}</td>
                        <td class="py-2 px-3">{{ $emp->Puesto ?? '-' }}</td>
                        <td class="py-2 px-3">
                            @if(!is_null($emp->Sueldo_real ?? $emp->Sueldo))
                                ${{ number_format($emp->Sueldo_real ?? $emp->Sueldo, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        @php
                            $obligatoriosIds = $documentosObligatorios
                                ->pluck('id')
                                ->toArray();

                            $documentosUltimosPorTipo = $emp->documentos
                                ->filter(fn($doc) => $doc->documento_tipo_id)
                                ->sortByDesc('created_at')
                                ->unique('documento_tipo_id');

                            $documentosCargadosIds = $documentosUltimosPorTipo
                                ->pluck('documento_tipo_id')
                                ->unique()
                                ->toArray();

                            $totalObligatorios = count($obligatoriosIds);

                            $documentosFaltantes = $documentosObligatorios
                                ->whereNotIn('id', $documentosCargadosIds)
                                ->pluck('nombre')
                                ->values();

                            $totalCargados = collect($obligatoriosIds)
                                ->filter(fn($id) => in_array($id, $documentosCargadosIds))
                                ->count();

                            $porcentajeDocumentos = $totalObligatorios > 0
                                ? round(($totalCargados / $totalObligatorios) * 100)
                                : 0;

                            $colorBarra = match (true) {
                                $porcentajeDocumentos >= 100 => 'bg-green-500',
                                $porcentajeDocumentos >= 70 => 'bg-yellow-500',
                                $porcentajeDocumentos >= 40 => 'bg-orange-500',
                                default => 'bg-red-500',
                            };

                            $colorTexto = match (true) {
                                $porcentajeDocumentos >= 100 => 'text-green-700',
                                $porcentajeDocumentos >= 70 => 'text-yellow-700',
                                $porcentajeDocumentos >= 40 => 'text-orange-700',
                                default => 'text-red-700',
                            };
                        @endphp

                        <td class="py-2 px-3">
                            <div class="group relative w-36 cursor-help">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold {{ $colorTexto }}">
                                        {{ $porcentajeDocumentos }}%
                                    </span>
                                    <span class="text-[11px] text-slate-500">
                                        {{ $totalCargados }}/{{ $totalObligatorios }}
                                    </span>
                                </div>

                                <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-2 {{ $colorBarra }} rounded-full"
                                         style="width: {{ $porcentajeDocumentos }}%">
                                    </div>
                                </div>

                                <div class="pointer-events-none absolute left-0 top-full z-30 mt-2 hidden w-72 rounded-xl border border-slate-200 bg-white p-3 text-left shadow-xl group-hover:block">
                                    @if($documentosFaltantes->isEmpty())
                                        <div class="text-xs font-semibold text-green-700">
                                            Expediente completo
                                        </div>
                                    @else
                                        <div class="mb-2 text-xs font-semibold text-slate-700">
                                            Documentos faltantes
                                        </div>
                                        <ul class="space-y-1 text-xs text-slate-600">
                                            @foreach($documentosFaltantes->take(5) as $documentoFaltante)
                                                <li class="flex gap-1.5">
                                                    <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-red-400"></span>
                                                    <span>{{ $documentoFaltante }}</span>
                                                </li>
                                            @endforeach
                                        </ul>

                                        @if($documentosFaltantes->count() > 5)
                                            <div class="mt-2 text-xs font-medium text-slate-500">
                                                +{{ $documentosFaltantes->count() - 5 }} más
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-2 px-3">
                            @if((int)$emp->Estatus === 2)
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">Baja</span>
                            @else
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Activo</span>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-right space-x-2">
                        <a href="{{ route('empleados.edit', ['empleado' => $emp->id_Empleado, 'tab' => 'datos']) }}"
                            title="Expediente"
                            class="inline-flex items-center p-1 text-blue-600 hover:text-blue-800 rounded transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </a>

                         <form action="{{ route('empleados.toggle-status', $emp->id_Empleado) }}"
      method="POST"
      class="inline-block"
      onsubmit="return confirm('¿Cambiar estatus de este empleado?')">
    @csrf
    @method('PATCH')

    <button type="submit"
            class="group relative inline-flex items-center p-1 rounded transition-colors {{ (int)$emp->Estatus === 2 ? 'text-green-600 hover:text-green-800' : 'text-red-600 hover:text-red-800' }}">
        
        @if((int)$emp->Estatus === 2)
            <!-- Ícono para Reactivar (Power/Activar) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
            </svg>
        @else
            <!-- Ícono para Dar de baja (Usuario desactivado/bloqueado) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
        @endif

        <!-- Tooltip dinámico -->
        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap shadow-lg z-10 font-normal">
            {{ (int)$emp->Estatus === 2 ? 'Reactivar' : 'Dar de baja' }}
        </span>
    </button>
</form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-500">
                            No hay empleados registrados todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $empleados->links() }}
    </div>
</div>
@endsection