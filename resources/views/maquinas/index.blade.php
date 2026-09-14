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
                        <th class="text-left px-4 py-3">Servicio preventivo</th>
                        <th class="text-left px-4 py-3">Seguro</th>
                        <th class="text-left px-4 py-3">Detalles</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($maquinas as $m)
                        @php
                            $seguroSeleccionado = null;

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
                                    <a href="{{ route('obras.edit', ['obra' => $m->asignacionActiva->obra->id]) }}"
                                       class="font-medium text-[#0B265A] hover:text-blue-700 hover:underline underline-offset-4">
                                        {{ $m->asignacionActiva->obra->nombre ?? 'Obra' }}
                                    </a>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @include('maquinas.partials._preventivo_badge', ['preventivo' => $preventivos[$m->id] ?? null])
                            </td>
                            <td class="px-4 py-3">
                                @if($seguroSeleccionado && $seguroSeleccionado->documento_path)
                                    <a href="{{ Storage::url($seguroSeleccionado->documento_path) }}"
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
                            <td colspan="9" class="px-4 py-6 text-center text-slate-500">
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

