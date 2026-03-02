@extends('layouts.admin')

@section('title', 'Empleado - Checadas')

@section('content')

{{-- ENCABEZADO --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-[#0B265A]">
            {{ $employee->name ?? 'Empleado' }}
        </h1>
        <div class="text-sm text-slate-600 mt-1">
            Enroll: <span class="font-semibold">{{ $employee->enroll_id }}</span>
            @if($employee->device)
                · Dispositivo: <span class="font-semibold">{{ $employee->device->name }}</span>
                <span class="text-slate-500">({{ $employee->device->ip }}:{{ $employee->device->port }})</span>
            @endif
        </div>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('attendance.logs.index', ['employee_id' => $employee->id]) }}"
           class="bg-slate-100 text-slate-800 font-semibold px-4 py-2 rounded-xl shadow hover:bg-slate-200 transition">
            Ver en listado
        </a>

        <a href="{{ route('attendance.logs.index') }}"
           class="bg-slate-100 text-slate-800 font-semibold px-4 py-2 rounded-xl shadow hover:bg-slate-200 transition">
            Volver
        </a>
    </div>
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
    <form method="GET" action="{{ route('attendance.employees.show', $employee) }}"
          class="grid grid-cols-1 md:grid-cols-12 gap-4">

        {{-- Desde --}}
        <div class="md:col-span-4">
            <label class="block text-sm font-semibold text-slate-700 mb-1">Desde</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200" />
        </div>

        {{-- Hasta --}}
        <div class="md:col-span-4">
            <label class="block text-sm font-semibold text-slate-700 mb-1">Hasta</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200" />
        </div>

        <div class="md:col-span-4 flex items-end gap-2">
            <button type="submit"
                    class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
                Filtrar
            </button>

            <a href="{{ route('attendance.employees.show', $employee) }}"
               class="bg-slate-100 text-slate-800 font-semibold px-4 py-2 rounded-xl shadow hover:bg-slate-200 transition">
                Limpiar
            </a>
        </div>
    </form>
</div>

{{-- TABLA --}}
<div class="bg-white rounded-2xl shadow p-6">

    <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-slate-600">
            Mostrando <span class="font-semibold">{{ $logs->count() }}</span> de
            <span class="font-semibold">{{ $logs->total() }}</span> checadas
        </div>

        <div>
            {{ $logs->links() }}
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-slate-600 border-b">
                    <th class="px-4 py-3">Fecha/Hora</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">UID reloj</th>
                </tr>
            </thead>

            <tbody>
                @forelse($logs as $log)
                    <tr class="border-t hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800 whitespace-nowrap">
                            {{-- si checked_at es datetime casteado a Carbon, format funciona;
                                 si no, muestra el string --}}
                            {{ $log->checked_at instanceof \Carbon\Carbon ? $log->checked_at->format('Y-m-d H:i:s') : $log->checked_at }}
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

                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-700">
                                {{ $log->device_uid ?? '—' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                            No hay checadas con esos filtros.
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