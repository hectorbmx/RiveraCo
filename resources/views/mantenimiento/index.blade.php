@extends('layouts.admin')

@section('content')
    <div class="max-w-6xl mx-auto py-8">

        {{-- Título y botón --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Mantenimientos</h1>
                <p class="text-sm text-slate-500">
                    Historial de mantenimientos de los vehículos.
                </p>
            </div>

            <a href="{{ route('mantenimiento.mantenimientos.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                + Programar mantenimiento
            </a>
        </div>

        {{-- Mensajes de éxito --}}
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">ID</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Vehículo</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Categoría</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Mecánico</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Estatus</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">KM Actual</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Programado / Inicio</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mantenimientos as $mantenimiento)
                        <tr class="border-b border-slate-100 hover:bg-slate-50/80">
                            <td class="px-4 py-2 text-slate-600">
                                {{ $mantenimiento->id }}
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex flex-col">
                                    <span class="font-medium text-slate-800">
                                        {{ $mantenimiento->vehiculo->marca ?? '—' }}
                                        {{ $mantenimiento->vehiculo->modelo ?? '' }}
                                    </span>
                                    <span class="text-xs text-slate-500">
                                        Placas: {{ $mantenimiento->vehiculo->placas ?? '—' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-slate-700">
                                {{ ucfirst($mantenimiento->tipo) }}
                            </td>
                            <td class="px-4 py-2 text-slate-500">
                                {{ $mantenimiento->categoria_mantenimiento ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-slate-500">
                                
                                {{ $mantenimiento->mecanico ? trim(($mantenimiento->mecanico->Nombre ?? '') . ' ' . ($mantenimiento->mecanico->Apellidos ?? '')) : '—' }}

                            </td>
                            <td class="px-4 py-2">
                                @php
                                    $statusClasses = [
                                        'pendiente'   => 'bg-amber-100 text-amber-700',
                                        'en_proceso'  => 'bg-blue-100 text-blue-700',
                                        'completado'  => 'bg-emerald-100 text-emerald-700',
                                        'cancelado'   => 'bg-slate-100 text-slate-600',
                                    ];
                                @endphp
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $statusClasses[$mantenimiento->estatus] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst(str_replace('_', ' ', $mantenimiento->estatus)) }}
                                </span>
                            </td>
                            
                            <td class="px-4 py-2 text-xs text-slate-500">
                                @if($mantenimiento->fecha_programada)
                                    <div>Prog: {{ $mantenimiento->fecha_programada->format('d/m/Y H:i') }}</div>
                                @endif
                                @if($mantenimiento->fecha_inicio)
                                    <div>Ini: {{ $mantenimiento->fecha_inicio->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('mantenimiento.mantenimientos.show', $mantenimiento) }}"
                                       class="text-xs px-2 py-1 rounded border border-slate-300 text-slate-700 hover:bg-slate-100">
                                        Ver
                                    </a>
                                    <a href="{{ route('mantenimiento.mantenimientos.edit', $mantenimiento) }}"
                                       class="text-xs px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-500">
                                No hay mantenimientos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($mantenimientos instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="px-4 py-3 border-t border-slate-100">
                    {{ $mantenimientos->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
