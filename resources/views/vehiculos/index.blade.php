@extends('layouts.admin')

@section('content')
    <div class="max-w-6xl mx-auto py-8">

        {{-- Título y botón --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Vehículos</h1>
                <p class="text-sm text-slate-500">
                    Catálogo de vehículos de la empresa.
                </p>
            </div>

            <a href="{{ route('mantenimiento.vehiculos.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                + Registrar vehículo
            </a>
        </div>

        {{-- Mensajes de éxito --}}
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Tabla --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">ID</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Vehículo</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Placas</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Asignado a</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Asignado</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Serie</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Tipo</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">KM</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Estatus</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
@forelse($vehiculos as $vehiculo)
    <tr class="border-b border-slate-100 hover:bg-slate-50/80">
        
        {{-- ID --}}
        <td class="px-4 py-2 text-center text-slate-600">
            {{ $vehiculo->id }}
        </td>

        {{-- Vehículo --}}
        <td class="px-4 py-2 text-center">
            <div class="flex flex-col">
                <span class="font-medium text-slate-800">
                    {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                </span>
                <span class="text-xs text-slate-500">
                    @if($vehiculo->anio)
                        Año {{ $vehiculo->anio }}
                    @else
                        Sin año
                    @endif
                </span>
            </div>
        </td>

        {{-- Placas --}}
        <td class="px-4 py-2 text-center text-slate-700">
            {{ $vehiculo->placas }}
        </td>

        {{-- Asignado a --}}
        <td class="px-4 py-2 text-center text-slate-700">
            @if($vehiculo->asignacionActual && $vehiculo->asignacionActual->empleado)
                {{ $vehiculo->asignacionActual->empleado->Nombre }}
            @else
                <span class="text-slate-400 text-xs">No asignado</span>
            @endif
        </td>

        {{-- Columna asignado --}}
        <td class="px-4 py-2 text-center">
            @php
                $asignado = $vehiculo->asignacionActual ? true : false;
            @endphp

            @if($asignado)
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                    Sí
                </span>
            @else
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                    No
                </span>
            @endif
        </td>
        <td class="px-4 py-2 text-center">{{ $vehiculo->serie }}</td>

        {{-- Tipo --}}
        <td class="px-4 py-2 text-center text-slate-500">
            {{ $vehiculo->tipo ?? '—' }}
        </td>
        <td class="px-3 py-2">
          @php
            $asig = $vehiculo->asignacionActual;
            $kmActual = $asig ? ($asig->km_final ?? $asig->km_inicial) : null;
        @endphp
{{ $kmActual !== null ? number_format($kmActual) : '—' }}

            </td>


        {{-- Estatus --}}
        <td class="px-4 py-2 text-center">
            @php
                $badgeClasses = [
                    'activo'    => 'bg-emerald-100 text-emerald-700',
                    'baja'      => 'bg-slate-100 text-slate-600',
                    'en_taller' => 'bg-amber-100 text-amber-700',
                ];
            @endphp

            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                  {{ $badgeClasses[$vehiculo->estatus] ?? 'bg-slate-100 text-slate-600' }}">
                {{ ucfirst(str_replace('_', ' ', $vehiculo->estatus)) }}
            </span>
        </td>

        {{-- Acciones --}}
        <td class="px-4 py-2 text-right">
            <div class="inline-flex items-center gap-2">
                <!-- <a href="{{ route('mantenimiento.vehiculos.show', $vehiculo) }}"
                   class="text-xs px-2 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-100">
                    Ver
                </a> -->
                <a href="{{ route('mantenimiento.vehiculos.edit', $vehiculo) }}"
                   class="text-xs px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                    Detalles
                </a>
            </div>
        </td>

    </tr>
@empty
    <tr>
        <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-500">
            No hay vehículos registrados.
        </td>
    </tr>
@endforelse
</tbody>

            </table>

            @if($vehiculos instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="px-4 py-3 border-t border-slate-100">
                    {{ $vehiculos->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
