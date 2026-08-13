@extends('layouts.admin')

@section('title', 'GIRALDA - Empleados')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Empleados Giralda</h1>
            <p class="text-sm text-slate-500">Lista operativa de personal, con acciones rapidas por empleado.</p>
        </div>
        <a href="{{ route('giralda.index') }}" class="text-sm text-slate-500 hover:text-slate-800">Volver a GIRALDA</a>
    </div>

    @if(!$areaGiralda)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded p-4 text-sm">
            No encontre un area con codigo GL o nombre Giralda. Crea/activa esa area para ver empleados.
        </div>
    @endif

    <div class="border-b flex gap-6 text-sm">
        @php
            $tabs = [
                'listado' => 'Listado',
                'asistencia' => 'Asistencia',
                'horas_extras' => 'Horas extras',
                'epp' => 'EPP',
            ];
        @endphp
        @foreach($tabs as $key => $label)
            <a href="{{ route('giralda.empleados', ['tab' => $key, 'estatus' => $estatus]) }}"
               class="pb-2 border-b-2 transition-all {{ $tab === $key ? 'border-[#FFC107] text-[#0B265A] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('giralda.empleados') }}" class="grid md:grid-cols-4 gap-3 items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div>
                <label class="block text-sm font-medium mb-1">Estatus</label>
                <select name="estatus" class="w-full border rounded p-2">
                    <option value="activo" @selected($estatus === 'activo')>Activos</option>
                    <option value="baja" @selected($estatus === 'baja')>Baja</option>
                    <option value="todos" @selected($estatus === 'todos')>Todos</option>
                </select>
            </div>
            @if(in_array($tab, ['horas_extras', 'asistencia'], true))
                <div>
                    <label class="block text-sm font-medium mb-1">Semana</label>
                    <input type="date" name="semana" value="{{ $semana }}" class="w-full border rounded p-2">
                </div>
            @endif
            <div class="{{ in_array($tab, ['horas_extras', 'asistencia'], true) ? 'md:col-span-2' : 'md:col-span-3' }} flex gap-2">
                <button class="px-4 py-2 rounded bg-[#0B265A] text-white">Filtrar</button>
                <a href="{{ route('giralda.empleados', ['tab' => $tab]) }}" class="px-4 py-2 rounded bg-slate-200">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-semibold text-[#0B265A]">
                    @if($tab === 'asistencia') Asistencia diaria
                    @elseif($tab === 'horas_extras') Horas extras por empleado
                    @elseif($tab === 'epp') Entrega de EPP por empleado
                    @else Listado filtrado
                    @endif
                </h2>
                <p class="text-xs text-slate-500">{{ $empleados->count() }} empleados encontrados</p>
            </div>
            @if(in_array($tab, ['horas_extras', 'asistencia'], true))
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <div class="mr-2 text-right">
                        <div class="text-xs uppercase tracking-wide text-slate-400">Semana seleccionada</div>
                        <div class="text-sm font-semibold text-[#0B265A]">{{ $semanaTitulo }}</div>
                    </div>
                    <a href="{{ route('giralda.empleados', ['tab' => $tab, 'estatus' => $estatus, 'semana' => $semanaAnterior]) }}" class="px-3 py-2 rounded border text-sm">Anterior</a>
                    <a href="{{ route('giralda.empleados', ['tab' => $tab, 'estatus' => $estatus, 'semana' => $semanaActual]) }}" class="px-3 py-2 rounded border text-sm">Semana actual</a>
                    @if($tab === 'asistencia' && $semanaSiguiente > $semanaActual)
                        <span class="px-3 py-2 rounded border text-sm text-slate-300 bg-slate-50 cursor-not-allowed">Siguiente</span>
                    @else
                        <a href="{{ route('giralda.empleados', ['tab' => $tab, 'estatus' => $estatus, 'semana' => $semanaSiguiente]) }}" class="px-3 py-2 rounded border text-sm">Siguiente</a>
                    @endif
                    @if($tab === 'horas_extras')
                        <a href="{{ route('giralda.horas-extras.print', ['desde' => $desde, 'hasta' => $hasta, 'empleado_id' => $empleadoId]) }}" target="_blank" class="px-3 py-2 rounded border text-sm">Imprimir semana</a>
                        <a href="{{ route('giralda.horas-extras.export', ['desde' => $desde, 'hasta' => $hasta, 'empleado_id' => $empleadoId]) }}" class="px-3 py-2 rounded border text-sm">Exportar CSV</a>
                    @elseif($tab === 'asistencia')
                        <a href="{{ route('giralda.asistencia.print', ['semana' => $semana, 'estatus' => $estatus]) }}" target="_blank" class="px-3 py-2 rounded border text-sm">Imprimir semana</a>
                    @endif
                </div>
            @endif
        </div>

        @if($tab === 'asistencia')
            <div class="px-4 py-3 border-b bg-blue-50 text-sm text-[#0B265A] flex flex-wrap items-center justify-between gap-3">
                <div>
                    {{ $puedeOverrideAsistencia ? 'Pase de lista semanal. Puedes corregir la semana actual o la semana anterior; el futuro queda bloqueado.' : 'Pase de lista semanal. Solo el dia actual se puede editar; pasado y futuro quedan bloqueados.' }}
                </div>
                @if($asistenciaEditableFechas->isNotEmpty())
                    <div class="font-semibold">
                        @if($puedeOverrideAsistencia)
                            Corrigiendo fechas permitidas
                        @else
                            Editando hoy: {{ \Carbon\Carbon::parse($asistenciaEditableFecha)->format('d/m/Y') }}
                        @endif
                    </div>
                @else
                    <div class="font-semibold text-slate-500">Semana historica, solo lectura.</div>
                @endif
            </div>
        @endif

        @if($tab === 'asistencia' && $asistenciaEditableFechas->isNotEmpty())
            <form method="POST" action="{{ route('giralda.asistencia.store') }}" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @foreach($asistenciaEditableFechas as $editableFecha)
                    <input type="hidden" name="fechas[]" value="{{ $editableFecha }}">
                @endforeach
                <input type="hidden" name="estatus" value="{{ $estatus }}">
                <input type="hidden" name="semana" value="{{ $semana }}">
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left p-3">Empleado</th>
                        <th class="text-left p-3">Puesto</th>
                        <th class="text-left p-3">Estatus</th>
                        @if($tab === 'asistencia')
                            @foreach($weekDays as $day)
                                <th class="text-center p-3 min-w-24">
                                    <div class="font-semibold uppercase">{{ $day->locale('es')->translatedFormat('D') }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $day->format('d/m') }}</div>
                                </th>
                            @endforeach
                        @elseif($tab === 'horas_extras')
                            <th class="text-right p-3">Horas semana</th>
                            <th class="text-right p-3">Accion</th>
                        @elseif($tab === 'epp')
                            <th class="text-right p-3">Entregas EPP</th>
                            <th class="text-right p-3">Accion</th>
                        @else
                            <th class="text-right p-3">EPP</th>
                            <th class="text-right p-3">HE</th>
                            <th class="text-right p-3">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($empleados as $empleado)
                        <tr>
                            <td class="p-3">
                                @if($tab === 'asistencia' && $asistenciaEditableFechas->isNotEmpty())
                                    <input type="hidden" name="empleados[]" value="{{ $empleado->id_Empleado }}">
                                @endif
                                <div class="font-medium text-slate-900">{{ $empleado->nombre_completo }}</div>
                                <div class="text-xs text-slate-500">ID {{ $empleado->id_Empleado }} - {{ $empleado->areaRef?->nombre ?? 'Giralda' }}</div>
                            </td>
                            <td class="p-3">{{ $empleado->Puesto ?? '-' }}</td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs {{ (int)$empleado->Estatus === 2 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ (int)$empleado->Estatus === 2 ? 'Baja' : 'Activo' }}
                                </span>
                            </td>

                            @if($tab === 'asistencia')
                                @foreach($weekDays as $day)
                                    @php
                                        $date = $day->toDateString();
                                        $registro = $asistencias->get($empleado->id_Empleado . '|' . $date);
                                        $isFuture = $day->isAfter(now()->startOfDay());
                                        $isEditable = $asistenciaEditableFechas->contains($date);
                                        $checked = $isEditable ? (($registro?->estado ?? 'presente') !== 'ausente') : ($registro?->estado === 'presente');
                                    @endphp
                                    <td class="p-3 text-center">
                                        @if($isFuture)
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded border border-slate-200 bg-slate-50 text-slate-300">-</span>
                                        @elseif($isEditable)
                                            <input type="checkbox" name="presentes[{{ $date }}][]" value="{{ $empleado->id_Empleado }}" @checked($checked) class="h-5 w-5 rounded border-slate-300 text-[#0B265A] focus:ring-[#FFC107]">
                                        @else
                                            <input type="checkbox" @checked($checked) disabled class="h-5 w-5 rounded border-slate-300 text-[#0B265A] disabled:opacity-70">
                                        @endif
                                    </td>
                                @endforeach
                            @elseif($tab === 'horas_extras')
                                <td class="p-3 text-right"><a href="{{ route('giralda.empleados.horas-extras', ['empleado' => $empleado->id_Empleado, 'semana' => $semana]) }}" class="inline-flex min-w-16 justify-center rounded bg-blue-50 px-3 py-1.5 font-semibold text-[#0B265A] hover:bg-blue-100">{{ number_format((float) ($empleado->giralda_horas_extras_semana_horas ?? 0), 2) }}</a></td>
                                <td class="p-3 text-right">
                                    @include('giralda.partials._modal_horas_extra', ['empleado' => $empleado, 'areaGiralda' => $areaGiralda, 'semana' => $semana])
                                </td>
                            @elseif($tab === 'epp')
                                <td class="p-3 text-right">
                                    @include('giralda.partials._modal_epp_historial', ['empleado' => $empleado])
                                </td>
                                <td class="p-3 text-right">
                                    @include('giralda.partials._modal_epp', ['empleado' => $empleado, 'areaGiralda' => $areaGiralda])
                                </td>
                            @else
                                <td class="p-3 text-right">
                                    @include('giralda.partials._modal_epp_historial', ['empleado' => $empleado])
                                </td>
                                <td class="p-3 text-right">{{ $empleado->giralda_horas_extras_count ?? 0 }}</td>
                                <td class="p-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('giralda.empleados', ['tab' => 'asistencia', 'estatus' => $estatus]) }}" class="px-3 py-1.5 rounded bg-green-50 text-green-700 hover:bg-green-100">Asistencia</a>
                                        <a href="{{ route('giralda.empleados', ['tab' => 'horas_extras', 'estatus' => $estatus]) }}" class="px-3 py-1.5 rounded bg-blue-50 text-blue-700 hover:bg-blue-100">Horas</a>
                                        <a href="{{ route('giralda.empleados', ['tab' => 'epp', 'estatus' => $estatus]) }}" class="px-3 py-1.5 rounded bg-amber-50 text-amber-700 hover:bg-amber-100">EPP</a>
                                        <a href="{{ route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'notas']) }}" class="px-3 py-1.5 rounded bg-slate-100 text-slate-700 hover:bg-slate-200">Notas</a>
                                        <a href="{{ route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'datos']) }}" class="px-3 py-1.5 rounded bg-slate-100 text-slate-700 hover:bg-slate-200">Fotos</a>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tab === 'asistencia' ? 10 : 6 }}" class="p-6 text-center text-slate-400">No hay empleados asignados a Giralda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tab === 'asistencia' && $asistenciaEditableFechas->isNotEmpty())
                <div class="border-t bg-slate-50 p-4 flex justify-end">
                    <button class="px-4 py-2 rounded bg-[#0B265A] text-white font-semibold disabled:cursor-not-allowed disabled:opacity-70" :disabled="submitting">
                        <span x-show="!submitting">{{ $puedeOverrideAsistencia ? 'Guardar asistencia semanal' : 'Guardar asistencia de hoy' }}</span>
                        <span x-show="submitting" x-cloak>Guardando...</span>
                    </button>
                </div>
                <div x-show="submitting"
                     x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal_guardando_asistencia_titulo">
                    <div class="w-full max-w-sm rounded-lg bg-white p-6 text-center shadow-xl">
                        <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-[#0B265A]"></div>
                        <h3 id="modal_guardando_asistencia_titulo" class="text-base font-semibold text-[#0B265A]">Guardando asistencia</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $puedeOverrideAsistencia ? 'Estamos registrando los ajustes de la semana visible.' : 'Estamos registrando el pase de lista de hoy.' }}</p>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
