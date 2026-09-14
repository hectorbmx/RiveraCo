@extends('layouts.admin')

@section('content')
    <div class="max-w-8xl mx-auto py-8">
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
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Tarjeta Circulación</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Seguro</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Estatus</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehiculos as $vehiculo)
                            @php
                                $preventivo = $vehiculo->preventivo_km ?? [];
                                $kmActual = $preventivo['km_actual'] ?? null;
                                $isBaja = strtolower((string) ($vehiculo->estatus ?? '')) === 'baja';
                                $tarjetaDocumento = $vehiculo->documentoTarjetaCirculacionVigente;
                                $seguroDocumento = $vehiculo->seguros
                                    ->filter(fn ($seguro) => $seguro->documento_path)
                                    ->sortByDesc(fn ($seguro) => $seguro->vigencia_hasta ? $seguro->vigencia_hasta->format('Y-m-d') : '0000-00-00')
                                    ->first();
                            @endphp
                            <tr class="{{ $isBaja ? 'bg-red-50 border-b border-red-200 hover:bg-red-100/90 text-red-900' : 'border-b border-slate-100 hover:bg-slate-50/80 text-slate-700' }}">
                                <td class="px-4 py-2 text-center {{ $isBaja ? 'text-red-700' : 'text-slate-600' }}">{{ $vehiculo->id }}</td>

                                <td class="px-4 py-2 text-center">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-800">
                                            {{ trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? '')) ?: 'Vehiculo' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-2 text-center {{ $isBaja ? 'text-red-700' : 'text-slate-700' }}">
                                    {{ $vehiculo->placas }}
                                </td>

                                <td class="px-4 py-2 text-center {{ $isBaja ? 'text-red-700' : 'text-slate-700' }}">
                                    @if($vehiculo->asignacionActual && $vehiculo->asignacionActual->empleado)
                                        {{ $vehiculo->asignacionActual->empleado->Nombre }}
                                        {{ $vehiculo->asignacionActual->empleado->Apellidos }}
                                    @else
                                        <span class="text-slate-400 text-xs">No asignado</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 text-center">
                                    @if($vehiculo->anio)
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isBaja ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ $vehiculo->anio }}
                                        </span>
                                    @else
                                        <span class="{{ $isBaja ? 'text-red-400' : 'text-slate-400' }}">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 text-center {{ $isBaja ? 'text-red-700' : '' }}">{{ $vehiculo->serie ?: '-' }}</td>
                                <td class="px-4 py-2 text-center {{ $isBaja ? 'text-red-600' : 'text-slate-500' }}">{{ $vehiculo->tipo ?: '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $kmActual !== null ? number_format($kmActual) : '-' }}</td>

                                <td class="px-4 py-2 text-center">
                                    <a href="{{ route('mantenimiento.vehiculos.edit', ['vehiculo' => $vehiculo->id, 'tab' => 'mantenimientos']) }}"
                                       class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $vehiculo->preventivo_badge_class ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                        {{ $vehiculo->preventivo_badge_label ?? 'Sin kilometraje' }}
                                    </a>
                                </td>

                                <td class="px-4 py-2 text-center">
                                    @if($tarjetaDocumento && $tarjetaDocumento->archivo_path)
                                        <a href="{{ Storage::url($tarjetaDocumento->archivo_path) }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           title="Ver tarjeta de circulación"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 text-red-600 hover:bg-red-100 transition"
                                           aria-label="Ver tarjeta de circulación">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4" aria-hidden="true">
                                                <path d="M7 3.5A2.5 2.5 0 0 1 9.5 1h5.25a.75.75 0 0 1 .53.22l3.5 3.5a.75.75 0 0 1 .22.53V18A2.5 2.5 0 0 1 17 20.5H9.5A2.5 2.5 0 0 1 7 18V3.5Zm2.5-.5a1 1 0 0 0-1 1V18a1 1 0 0 0 1 1H17a1 1 0 0 0 1-1V6.06L15.94 4H9.5a1 1 0 0 0-1 1Zm2.25 7.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1 0-1.5Zm0 3h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1 0-1.5ZM11 6.5h4.5a.75.75 0 0 1 0 1.5H11a.75.75 0 0 1 0-1.5Z"/>
                                            </svg>
                                        </a>
                                    @else
                                        <span title="SIN TARJETA" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-amber-50 text-amber-600 border border-amber-200" aria-label="Sin tarjeta de circulación">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M8.485 2.5a1.5 1.5 0 0 1 2.03 0l5.905 5.8a1.5 1.5 0 0 1 .42 1.013v4.687A2.5 2.5 0 0 1 14.34 16.5H5.66A2.5 2.5 0 0 1 3.16 14v-4.687a1.5 1.5 0 0 1 .42-1.013l5.905-5.8Zm1.515 4.5a.75.75 0 0 0-.75.75v2.75a.75.75 0 0 0 1.5 0V7.75a.75.75 0 0 0-.75-.75Zm0 6.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 text-center">
                                    @if($seguroDocumento && $seguroDocumento->documento_path)
                                        <a href="{{ Storage::url($seguroDocumento->documento_path) }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           title="Ver seguro"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 text-red-600 hover:bg-red-100 transition"
                                           aria-label="Ver documento del seguro">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4" aria-hidden="true">
                                                <path d="M7 3.5A2.5 2.5 0 0 1 9.5 1h5.25a.75.75 0 0 1 .53.22l3.5 3.5a.75.75 0 0 1 .22.53V18A2.5 2.5 0 0 1 17 20.5H9.5A2.5 2.5 0 0 1 7 18V3.5Zm2.5-.5a1 1 0 0 0-1 1V18a1 1 0 0 0 1 1H17a1 1 0 0 0 1-1V6.06L15.94 4H9.5a1 1 0 0 0-1 1Zm2.25 7.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1 0-1.5Zm0 3h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1 0-1.5ZM11 6.5h4.5a.75.75 0 0 1 0 1.5H11a.75.75 0 0 1 0-1.5Z"/>
                                            </svg>
                                        </a>
                                    @else
                                        <span title="SIN SEGURO" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-amber-50 text-amber-600 border border-amber-200" aria-label="Sin seguro">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M8.485 2.5a1.5 1.5 0 0 1 2.03 0l5.905 5.8a1.5 1.5 0 0 1 .42 1.013v4.687A2.5 2.5 0 0 1 14.34 16.5H5.66A2.5 2.5 0 0 1 3.16 14v-4.687a1.5 1.5 0 0 1 .42-1.013l5.905-5.8Zm1.515 4.5a.75.75 0 0 0-.75.75v2.75a.75.75 0 0 0 1.5 0V7.75a.75.75 0 0 0-.75-.75Zm0 6.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 text-center">
                                    @php
                                        $badgeClasses = [
                                            'activo' => 'bg-emerald-100 text-emerald-700',
                                            'baja' => 'bg-red-100 text-red-700',
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
                                <td colspan="13" class="px-4 py-6 text-center text-sm text-slate-500">
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
