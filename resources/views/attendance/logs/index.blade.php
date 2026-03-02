@extends('layouts.admin')

@section('title', 'Checadas')

@section('content')

{{-- ENCABEZADO --}}
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-[#0B265A]">Checadas</h1>

    {{-- botón opcional: refrescar --}}
    <a href="{{ route('attendance.logs.index') }}"
       class="bg-slate-100 text-slate-800 font-semibold px-4 py-2 rounded-xl shadow hover:bg-slate-200 transition">
        Limpiar filtros
    </a>
</div>

{{-- ALERTAS --}}
@if (session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-sm">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-sm">
        {{ session('error') }}
    </div>
@endif

{{-- FILTROS --}}
<div class="bg-white rounded-2xl shadow p-6 mb-6">
    <form method="GET" action="{{ route('attendance.logs.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">

        {{-- Dispositivo --}}
        <div class="md:col-span-3">
            <label class="block text-sm font-semibold text-slate-700 mb-1">Dispositivo</label>
            <select name="device_id" class="w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200">
                <option value="">Todos</option>
                @foreach($devices as $d)
                    <option value="{{ $d->id }}" @selected((string)$deviceId === (string)$d->id)>
                        {{ $d->name }} ({{ $d->ip }}:{{ $d->port }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Empleado --}}
        <div class="md:col-span-4">
            <label class="block text-sm font-semibold text-slate-700 mb-1">Empleado</label>
            <select name="employee_id" class="w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200">
                <option value="">Todos</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" @selected((string)$employeeId === (string)$e->id)>
                        {{ $e->name ?? '—' }} — #{{ $e->enroll_id }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1">
                Tip: si filtras por dispositivo, la lista de empleados se reduce.
            </p>
        </div>

        {{-- Desde --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-1">Desde</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200" />
        </div>

        {{-- Hasta --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-1">Hasta</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200" />
        </div>

        {{-- Botón --}}
        <div class="md:col-span-1 flex items-end">
            <button type="submit"
                    class="w-full bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
                Filtrar
            </button>
        </div>
    </form>
</div>

{{-- TABLA --}}
<div class="bg-white rounded-2xl shadow p-6">

    <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-slate-600">
            Mostrando <span class="font-semibold">{{ $logs->count() }}</span> de
            <span class="font-semibold">{{ $logs->total() }}</span> registros
        </div>

        {{-- paginación arriba (opcional) --}}
        <div>
            {{ $logs->links() }}
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-slate-600 border-b">
                    <th class="px-4 py-3">Fecha/Hora</th>
                    <th class="px-4 py-3">Empleado</th>
                    <th class="px-4 py-3">Enroll</th>
                    <th class="px-4 py-3">Dispositivo</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($logs as $log)
                    @php
                        $emp = $log->user; // relación user() en AttendanceLog
                    @endphp

                    <tr class="border-t hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800 whitespace-nowrap">
                            {{ optional($log->checked_at)->format('Y-m-d H:i:s') ?? $log->checked_at }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">
                                {{ $emp->name ?? '—' }}
                            </div>
                            <div class="text-xs text-slate-500">
                                UID reloj: {{ $log->device_uid }}
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-700">
                                {{ $log->enroll_id }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="text-slate-800 font-semibold">
                                {{ $log->device->name ?? ('Device #'.$log->attendance_device_id) }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $log->device->ip ?? '—' }}:{{ $log->device->port ?? '—' }}
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-700">
                                {{ $log->state ?? '—' }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-700">
                                {{ $log->type ?? '—' }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-right">
                            @if($emp)
                                <a href="{{ route('attendance.employees.show', $emp) }}"
                                   class="bg-slate-100 hover:bg-slate-200 text-slate-800 px-3 py-1 rounded-lg text-xs font-semibold transition">
                                    Ver empleado
                                </a>
                            @else
                                <span class="text-slate-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                            No hay registros con esos filtros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>

@endsection