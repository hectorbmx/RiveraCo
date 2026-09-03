@extends('layouts.admin')

@section('content')
    <div class="max-w-6xl mx-auto py-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#0B265A]">Vehiculos</h1>
                <p class="text-sm text-slate-500">Catalogo de vehiculos de la empresa.</p>
            </div>

            <a href="{{ route('mantenimiento.vehiculos.create') }}"
               class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
                + Registrar vehiculo
            </a>
        </div>

        <x-filters.card action="{{ route('mantenimiento.vehiculos.index') }}" class="mb-6">
            <x-filters.input
                name="search"
                label="Buscar"
                :value="$search ?? ''"
                placeholder="Placas, vehiculo, ano o asignado a..."
                span="md:col-span-9"
                type="search"
                glow />

            <x-filters.actions
                submit-label="Filtrar"
                clear-url="{{ route('mantenimiento.vehiculos.index') }}"
                span="md:col-span-3" />
        </x-filters.card>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">ID</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Vehiculo</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Placas</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Asignado a</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Ano</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Serie</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Tipo</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">KM</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Servicio</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Atencion documental</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Estatus</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehiculos as $vehiculo)
                            @php
                                $preventivo = $vehiculo->preventivo_km ?? [];
                                $kmActual = $preventivo['km_actual'] ?? null;
                            @endphp
                            <tr class="border-b border-slate-100 hover:bg-slate-50/80">
                                <td class="px-4 py-2 text-center text-slate-600">{{ $vehiculo->id }}</td>

                                <td class="px-4 py-2 text-center">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-800">
                                            {{ trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? '')) ?: 'Vehiculo' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-2 text-center text-slate-700">
                                    {{ $vehiculo->placas }}
                                </td>

                                <td class="px-4 py-2 text-center text-slate-700">
                                    @if($vehiculo->asignacionActual && $vehiculo->asignacionActual->empleado)
                                        {{ $vehiculo->asignacionActual->empleado->Nombre }}
                                        {{ $vehiculo->asignacionActual->empleado->Apellidos }}
                                    @else
                                        <span class="text-slate-400 text-xs">No asignado</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 text-center">
                                    @if($vehiculo->anio)
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                            {{ $vehiculo->anio }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 text-center">{{ $vehiculo->serie ?: '-' }}</td>
                                <td class="px-4 py-2 text-center text-slate-500">{{ $vehiculo->tipo ?: '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $kmActual !== null ? number_format($kmActual) : '-' }}</td>

                                <td class="px-4 py-2 text-center">
                                    <a href="{{ route('mantenimiento.vehiculos.edit', ['vehiculo' => $vehiculo->id, 'tab' => 'mantenimientos']) }}"
                                       class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $vehiculo->preventivo_badge_class ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                        {{ $vehiculo->preventivo_badge_label ?? 'Sin kilometraje' }}
                                    </a>
                                </td>

                                <td class="px-4 py-2 text-center">
                                    @if(!empty($vehiculo->documentos_alertas))
                                        <div class="flex flex-col items-center gap-1">
                                            @foreach($vehiculo->documentos_alertas as $alerta)
                                                <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $alerta['clase'] }}">
                                                    {{ $alerta['texto'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">
                                            Documentos OK
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 text-center">
                                    @php
                                        $badgeClasses = [
                                            'activo' => 'bg-emerald-100 text-emerald-700',
                                            'baja' => 'bg-slate-100 text-slate-600',
                                            'en_taller' => 'bg-amber-100 text-amber-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses[$vehiculo->estatus] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ ucfirst(str_replace('_', ' ', $vehiculo->estatus)) }}
                                    </span>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('mantenimiento.vehiculos.edit', $vehiculo) }}"
                                       class="text-xs px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                                        Detalles
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-6 text-center text-sm text-slate-500">
                                    No hay vehiculos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($vehiculos instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="px-4 py-3 border-t border-slate-100">
                    {{ $vehiculos->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
