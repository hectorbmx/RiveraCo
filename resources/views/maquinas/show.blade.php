@extends('layouts.admin')

@section('title', 'Detalle de máquina')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">
                Máquina: {{ $maquina->nombre ?? '—' }}
            </h1>
            <p class="text-sm text-slate-500">
                Código: {{ $maquina->codigo ?? '—' }} ·
                Estado: <span class="font-medium text-slate-700">{{ $maquina->estado ?? '—' }}</span> ·
                Ubicación: <span class="font-medium text-slate-700">{{ $maquina->ubicacion ?? '—' }}</span>
            </p>

            @if($maquina->asignacionActiva && $maquina->asignacionActiva->obra)
                <p class="mt-1 text-sm text-slate-600">
                    Asignada a: <span class="font-semibold">{{ $maquina->asignacionActiva->obra->nombre ?? 'Obra' }}</span>
                </p>
            @endif
        </div>

        <div class="flex gap-2">
            <a href="{{ route('maquinas.index') }}"
               class="px-3 py-2 rounded-lg border text-sm bg-white hover:bg-slate-50 text-slate-700 border-slate-200">
                ← Volver
            </a>
        </div>
    </div>

    {{-- Tabs --}}
    @php
        $tabs = [
            'general'  => 'General',
            'servicios'=> 'Servicios',
            'obras'    => 'Obras',
            'seguros'  => 'Seguros',
            'kardex'   =>  'Kardex',
        ];
    @endphp

    <div class="border-b mb-4">
        <nav class="flex gap-2">
            @foreach($tabs as $key => $label)
                <a href="{{ route('maquinas.show', ['maquina' => $maquina->id, 'tab' => $key]) }}"
                   class="px-4 py-2 text-sm font-medium rounded-t-lg
                          {{ $tab === $key ? 'bg-white border border-b-0 text-[#0B265A]' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Tab content --}}
    @if($tab === 'general')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl border bg-white p-4">
                <div class="text-sm font-semibold text-slate-800 mb-3">Datos generales</div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <div class="text-xs text-slate-500">Tipo</div>
                        <div class="font-medium text-slate-900">{{ $maquina->tipo ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Marca / Modelo</div>
                        <div class="font-medium text-slate-900">{{ $maquina->marca ?? '—' }} {{ $maquina->modelo ?? '' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">No. serie</div>
                        <div class="font-medium text-slate-900">{{ $maquina->numero_serie ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Placas</div>
                        <div class="font-medium text-slate-900">{{ $maquina->placas ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Color</div>
                        <div class="font-medium text-slate-900">{{ $maquina->color ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Horómetro base</div>
                        <div class="font-medium text-slate-900">{{ $maquina->horometro_base ?? '—' }}</div>
                    </div>
                </div>

                @if($maquina->notas)
                    <div class="mt-4">
                        <div class="text-xs text-slate-500">Notas</div>
                        <div class="text-sm text-slate-800 whitespace-pre-line">{{ $maquina->notas }}</div>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border bg-white p-4">
                <div class="text-sm font-semibold text-slate-800 mb-3">Resumen</div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Estado</span>
                        <span class="font-medium text-slate-900">{{ $maquina->estado ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Ubicación</span>
                        <span class="font-medium text-slate-900">{{ $maquina->ubicacion ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Asignación activa</span>
                        <span class="font-medium text-slate-900">
                            {{ $maquina->asignacionActiva ? 'Sí' : 'No' }}
                        </span>
                    </div>
                </div>

                {{-- aquí después meteremos el toggle (por ahora no) --}}
                <div class="mt-4 text-xs text-slate-500">
                    {{-- Toggle servicio --}}
<div class="mt-4 pt-4 border-t">
    <div class="text-sm font-semibold text-slate-800 mb-2">Acciones</div>

    <form method="POST" action="{{ route('maquinas.toggleServicio', $maquina) }}" class="space-y-3">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Motivo (opcional)</label>
                <input
                    type="text"
                    name="motivo"
                    class="w-full rounded-lg border-slate-200 text-sm"
                    placeholder="Ej. Falla hidráulica"
                    value="{{ old('motivo') }}"
                />
            </div>

            <div>
                <label class="block text-xs text-slate-500 mb-1">Notas (opcional)</label>
                <input
                    type="text"
                    name="notas"
                    class="w-full rounded-lg border-slate-200 text-sm"
                    placeholder="Detalle breve"
                    value="{{ old('notas') }}"
                />
            </div>
        </div>

        @if($maquina->estado === 'operativa')
            <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium
                           bg-amber-600 text-white hover:bg-amber-700">
                Marcar como fuera de servicio
            </button>
        @elseif($maquina->estado === 'fuera_servicio')
            <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium
                           bg-emerald-600 text-white hover:bg-emerald-700">
                Regresar a operativa
            </button>
        @else
            <button type="button"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium
                           bg-slate-200 text-slate-600 cursor-not-allowed"
                    disabled>
                Estado no editable
            </button>
        @endif
    </form>

    <p class="mt-3 text-xs text-slate-500">
        * Las asignaciones cambian ubicación automáticamente. Aquí solo se cambia el estado (operativa / fuera de servicio).
    </p>
</div>
                </div>
            </div>
        </div>
    @endif

    @if($tab === 'servicios')
        <div class="rounded-xl border bg-white overflow-hidden">
            <div class="px-4 py-3 border-b">
                <div class="text-sm font-semibold text-slate-800">Servicios realizados</div>
                <div class="text-xs text-slate-500">Mantenimientos ligados a esta máquina.</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Folio</th>
                            <th class="text-left px-4 py-3">Tipo</th>
                            <th class="text-left px-4 py-3">Estatus</th>
                            <th class="text-left px-4 py-3">Fecha</th>
                            <th class="text-left px-4 py-3">Detalles</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($maquina->mantenimientos ?? [] as $mtto)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">{{ $mtto->folio ?? $mtto->id }}</td>
                                <td class="px-4 py-3">{{ $mtto->tipo ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $mtto->estatus ?? '—' }}</td>
                                <td class="px-4 py-3">{{ optional($mtto->fecha_programada)->format('Y-m-d') ?? ($mtto->created_at?->format('Y-m-d') ?? '—') }}</td>
                                <td class="px-4 py-3 text-slate-500">—</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Sin servicios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($tab === 'obras')
        <div class="rounded-xl border bg-white overflow-hidden">
            <div class="px-4 py-3 border-b">
                <div class="text-sm font-semibold text-slate-800">Historial de obras</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Obra</th>
                            <th class="text-left px-4 py-3">Inicio</th>
                            <th class="text-left px-4 py-3">Fin</th>
                            <th class="text-left px-4 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($maquina->asignaciones ?? [] as $a)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    {{ $a->obra->nombre ?? '—' }}
                                </td>
                                <td class="px-4 py-3">{{ $a->fecha_inicio?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $a->fecha_fin?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $a->estado ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">Sin historial de obras.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($tab === 'seguros')
        <div class="rounded-xl border bg-white overflow-hidden">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-800">Historial de seguros</div>
                    <div class="text-xs text-slate-500">Aquí después habilitamos “Agregar seguro”.</div>
                </div>
                {{-- Botón lo agregamos después --}}
                <button class="px-3 py-2 rounded-lg border text-sm bg-slate-100 text-slate-500 cursor-not-allowed" disabled>
                    + Agregar seguro
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Aseguradora</th>
                            <th class="text-left px-4 py-3">Póliza</th>
                            <th class="text-left px-4 py-3">Inicio</th>
                            <th class="text-left px-4 py-3">Fin</th>
                            <th class="text-left px-4 py-3">Archivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($maquina->seguros ?? [] as $s)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">{{ $s->aseguradora ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $s->numero_poliza ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $s->vigencia_inicio?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $s->vigencia_fin?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">—</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Sin seguros registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    @if($tab === 'kardex')
<div class="rounded-xl border bg-white overflow-hidden">
    <div class="px-4 py-3 border-b">
        <div class="text-sm font-semibold text-slate-800">Kardex / Historial de movimientos</div>
        <div class="text-xs text-slate-500">
            Registro completo de cambios de estado y ubicación.
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Fecha</th>
                    <th class="text-left px-4 py-3">Tipo</th>
                    <th class="text-left px-4 py-3">Ubicación</th>
                    <th class="text-left px-4 py-3">Estado</th>
                    <th class="text-left px-4 py-3">Obra</th>
                    <th class="text-left px-4 py-3">Usuario</th>
                    <th class="text-left px-4 py-3">Motivo</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($maquina->movimientos ?? [] as $mov)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $mov->fecha_evento?->format('Y-m-d H:i') ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ ucfirst(str_replace('_', ' ', $mov->tipo)) }}
                        </td>

                        <td class="px-4 py-3">
                            @if($mov->ubicacion_anterior || $mov->ubicacion_nueva)
                                <span class="text-slate-600 text-xs">
                                    {{ $mov->ubicacion_anterior ?? '—' }}
                                    →
                                    <strong>{{ $mov->ubicacion_nueva ?? '—' }}</strong>
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            @if($mov->estado_anterior || $mov->estado_nuevo)
                                <span class="text-slate-600 text-xs">
                                    {{ $mov->estado_anterior ?? '—' }}
                                    →
                                    <strong>{{ $mov->estado_nuevo ?? '—' }}</strong>
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            {{ $mov->obra->nombre ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $mov->user->name ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-slate-500">
                            {{ $mov->motivo ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">
                            Sin movimientos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

</div>
@endsection