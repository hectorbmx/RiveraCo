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
            <div class="md:col-span-3 flex gap-2">
                <button class="px-4 py-2 rounded bg-[#0B265A] text-white">Filtrar</button>
                <a href="{{ route('giralda.empleados', ['tab' => $tab]) }}" class="px-4 py-2 rounded bg-slate-200">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-semibold text-[#0B265A]">
                    @if($tab === 'horas_extras') Horas extras por empleado
                    @elseif($tab === 'epp') Entrega de EPP por empleado
                    @else Listado filtrado
                    @endif
                </h2>
                <p class="text-xs text-slate-500">{{ $empleados->count() }} empleados encontrados</p>
            </div>
            @if($tab === 'horas_extras')
                <div class="flex gap-2">
                    <a href="{{ route('giralda.horas-extras.print') }}" target="_blank" class="px-3 py-2 rounded border text-sm">Imprimir historial</a>
                    <a href="{{ route('giralda.horas-extras.export') }}" class="px-3 py-2 rounded border text-sm">Exportar CSV</a>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left p-3">Empleado</th>
                        <th class="text-left p-3">Puesto</th>
                        <th class="text-left p-3">Estatus</th>
                        @if($tab === 'horas_extras')
                            <th class="text-right p-3">Registros HE</th>
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
                                <div class="font-medium text-slate-900">{{ $empleado->nombre_completo }}</div>
                                <div class="text-xs text-slate-500">ID {{ $empleado->id_Empleado }} · {{ $empleado->areaRef?->nombre ?? 'Giralda' }}</div>
                            </td>
                            <td class="p-3">{{ $empleado->Puesto ?? '-' }}</td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs {{ (int)$empleado->Estatus === 2 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ (int)$empleado->Estatus === 2 ? 'Baja' : 'Activo' }}
                                </span>
                            </td>

                            @if($tab === 'horas_extras')
                                <td class="p-3 text-right">{{ $empleado->giralda_horas_extras_count ?? 0 }}</td>
                                <td class="p-3 text-right">
                                    @include('giralda.partials._modal_horas_extra', ['empleado' => $empleado])
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
                            <td colspan="6" class="p-6 text-center text-slate-400">No hay empleados asignados a Giralda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection