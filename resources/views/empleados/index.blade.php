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
    @endphp

    <x-filters.card action="{{ route('empleados.index') }}" class="mb-6">
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

        <x-filters.actions
            submit-label="Filtrar"
            clear-url="{{ route('empleados.index') }}"
            span="md:col-span-3" />
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
            <thead class="bg-slate-50">
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-2 px-3">Empleado</th>
                    <th class="py-2 px-3">Área</th>
                    <th class="py-2 px-3">Puesto</th>
                    <th class="py-2 px-3">Sueldo</th>
                    <th class="py-2 px-3">Documentos</th>
                    <th class="py-2 px-3">Estatus</th>
                    <th class="py-2 px-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($empleados as $emp)
                    <tr class="border-b last:border-b-0 hover:bg-slate-50">
                        <td class="py-2 px-3">
                            <span class="font-medium">
                                {{ $emp->Nombre }} {{ $emp->Apellidos }}
                            </span><br>
                            <span class="text-xs text-slate-400">
                                ID: {{ $emp->id_Empleado }} · Ingreso:
                                {{ $emp->Fecha_ingreso ? $emp->Fecha_ingreso->format('d/m/Y') : '-' }}
                            </span>
                        </td>
                        <!-- <td class="py-2 px-3">{{ $emp->Area ?? '-' }}</td> -->
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
                            <div class="w-36">
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
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                Expediente
                            </a>



                          <form action="{{ route('empleados.toggle-status', $emp->id_Empleado) }}"
                                method="POST"
                                class="inline-block"
                                onsubmit="return confirm('¿Cambiar estatus de este empleado?')">
                            @csrf
                            @method('PATCH')

                            <button class="text-xs text-slate-600 hover:text-slate-900 font-medium">
                                {{ (int)$emp->Estatus === 2 ? 'Reactivar' : 'Dar de baja' }}
                            </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-500">
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

