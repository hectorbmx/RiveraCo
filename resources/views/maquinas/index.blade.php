@extends('layouts.admin')

@section('title', 'Maquinas')

@section('content')
<div class="max-w-7xl mx-auto">

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

    {{-- Tabla --}}
    <div class="rounded-xl border bg-white overflow-hidden">
        <div class="px-4 py-3 border-b">
            <div class="text-sm font-semibold text-slate-800">Listado general</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Código</th>
                        <th class="text-left px-4 py-3">Nombre</th>
                        <th class="text-left px-4 py-3">Tipo</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Ubicación</th>
                        <th class="text-left px-4 py-3">Obra actual</th>
                        <th class="text-left px-4 py-3">Detalles</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($maquinas as $m)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $m->codigo ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $m->nombre ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $m->tipo ?? '—' }}</td>

                            {{-- Estado --}}
                            <td class="px-4 py-3">
                                @php
                                    $estado = $m->estado ?? '';
                                    $estadoLabel = match($estado) {
                                        'operativa' => 'Operativa',
                                        'fuera_servicio' => 'Fuera de servicio',
                                        'baja_definitiva' => 'Baja definitiva',
                                        default => $estado ?: '—',
                                    };

                                    $estadoClass = match($estado) {
                                        'operativa' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'fuera_servicio' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'baja_definitiva' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-slate-50 text-slate-700 border-slate-200',
                                    };
                                @endphp

                                <span class="inline-flex items-center px-2 py-1 rounded-lg border text-xs {{ $estadoClass }}">
                                    {{ $estadoLabel }}
                                </span>
                            </td>

                            {{-- Ubicación --}}
                            
                        <td class="px-4 py-3">
                            @php
                                $ubic = $m->ubicacion ?? '';

                                $ubicLabel = match($ubic) {
                                    'en_obra'       => 'En obra',
                                    'en_camino'     => 'En camino',
                                    'en_reparacion' => 'En reparación',
                                    'en_patio'      => 'En patio',
                                    default         => $ubic ?: '—',
                                };

                                $ubicClass = match($ubic) {
                                    'en_obra'       => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'en_camino'     => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'en_reparacion' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'en_patio'      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    default         => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp

                            <span class="inline-flex items-center px-2 py-1 rounded-lg border text-xs {{ $ubicClass }}">
                                {{ $ubicLabel }}
                            </span>
                        </td>

                            {{-- Obra actual --}}
                            <td class="px-4 py-3">
                                @if($m->asignacionActiva && $m->asignacionActiva->obra)
                                    <span class="text-slate-900 font-medium">
                                        {{ $m->asignacionActiva->obra->nombre ?? 'Obra' }}
                                    </span>
                                @else
                                    <span class="text-slate-500">—</span>
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
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                                No hay máquinas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection