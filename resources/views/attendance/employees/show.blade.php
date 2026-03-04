@extends('layouts.admin')

@section('title', 'Empleado - Checadas')

@section('content')

{{-- ENCABEZADO --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-[#0B265A]">
            {{ $employee->name ?? 'Empleado' }}
        </h1>
        <div class="text-sm text-slate-600 mt-1 flex items-center gap-2">
            <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-bold">Enroll: {{ $employee->enroll_id }}</span>
            @if($employee->device)
                <span>· {{ $employee->device->name }}</span>
                <span class="text-slate-400">({{ $employee->device->ip }})</span>
            @endif
        </div>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('attendance.logs.index', ['employee_id' => $employee->id]) }}"
           class="bg-slate-100 text-slate-800 font-semibold px-4 py-2 rounded-xl shadow-sm hover:bg-slate-200 transition text-sm">
            Ver en listado global
        </a>
        <a href="{{ route('attendance.logs.index') }}"
           class="bg-[#0B265A] text-white font-semibold px-4 py-2 rounded-xl shadow-sm hover:opacity-90 transition text-sm">
            Volver
        </a>
    </div>
</div>

{{-- KPIs INTERACTIVOS --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    {{-- Card Días --}}
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
        <div class="text-slate-500 text-sm font-medium mb-1">Días Trabajados</div>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold text-[#0B265A]">{{ $workedDays }}</span>
            <span class="text-slate-400 text-sm">en el periodo</span>
        </div>
    </div>

    {{-- Card Horas --}}
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
        <div class="text-slate-500 text-sm font-medium mb-1">Total Horas (Est.)</div>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold text-green-600">{{ $totalHours }}</span>
            <span class="text-slate-400 text-sm">hrs acumuladas</span>
        </div>
    </div>

    {{-- Card Promedio Entrada --}}
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
        <div class="text-slate-500 text-sm font-medium mb-1">Promedio de Entrada</div>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold text-orange-500">{{ $avgEntry }}</span>
            <span class="text-slate-400 text-sm">hora usual</span>
        </div>
    </div>
</div>

{{-- FILTROS --}}
<div class="bg-white rounded-2xl shadow-sm p-6 mb-6 border border-slate-100">
    <form method="GET" action="{{ route('attendance.employees.show', $employee) }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-4">
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Desde</label>
            <input type="date" name="from" value="{{ $from }}" class="w-full rounded-xl border-slate-200 focus:ring-[#FFC107] transition" />
        </div>
        <div class="md:col-span-4">
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Hasta</label>
            <input type="date" name="to" value="{{ $to }}" class="w-full rounded-xl border-slate-200 focus:ring-[#FFC107] transition" />
        </div>
        <div class="md:col-span-4 flex items-end gap-2">
            <button type="submit" class="bg-[#FFC107] text-[#0B265A] font-bold px-6 py-2 rounded-xl shadow-sm hover:bg-[#e0ac05] transition flex-1">
                Filtrar Periodo
            </button>
            <a href="{{ route('attendance.employees.show', $employee) }}" class="bg-slate-100 text-slate-600 font-bold px-4 py-2 rounded-xl hover:bg-slate-200 transition">
                Limpiar
            </a>
        </div>
    </form>
</div>

{{-- TABLA DE REGISTROS --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-4 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
        <h3 class="font-bold text-[#0B265A] text-sm uppercase tracking-wider">Historial Detallado</h3>
        <span class="text-xs font-medium text-slate-500">{{ $logs->total() }} registros encontrados</span>
        
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full">
       <thead>
            <tr class="bg-white text-left text-xs font-bold text-slate-400 uppercase tracking-wider border-b">
                <th class="px-6 py-4">Día</th>
                <th class="px-6 py-4">Fecha</th>
                <th class="px-6 py-4">Entrada</th>
                <th class="px-6 py-4">Salida</th>
                <th class="px-6 py-4 text-right">Horas</th>
                <th class="px-6 py-4 text-center">Registros</th>
            </tr>
        </thead>
       <tbody class="divide-y divide-slate-100">
@forelse($logs as $row)
    <tr class="hover:bg-blue-50/30 transition">
        <td class="px-6 py-4">
            <span class="text-sm font-bold text-slate-700 capitalize">{{ $row->day_name }}</span>
        </td>

        <td class="px-6 py-4 text-sm text-slate-600">
            {{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}
        </td>

        <td class="px-6 py-4 text-sm text-slate-600">
            <span class="font-bold text-[#0B265A]">{{ $row->entry_at->format('H:i:s') }}</span>
        </td>

        <td class="px-6 py-4 text-sm text-slate-600">
            @if($row->exit_at)
                <span class="font-bold text-[#0B265A]">{{ $row->exit_at->format('H:i:s') }}</span>
            @else
                <span class="text-slate-400">—</span>
            @endif
        </td>

        <td class="px-6 py-4 text-right text-sm font-bold text-slate-700">
            {{ number_format($row->hours, 2) }}
        </td>

        <td class="px-6 py-4 text-center">
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                {{ $row->count }}
            </span>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-6 py-12 text-center">
            <div class="text-slate-400">No hay registros en este rango de fechas.</div>
        </td>
    </tr>
@endforelse
</tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="p-4 border-t border-slate-50 bg-slate-50/30">
            {{ $logs->links() }}
        </div>
    @endif
</div>

@endsection