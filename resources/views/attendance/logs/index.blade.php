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
<div class="md:col-span-3 relative" id="employee-container">
    <label class="block text-sm font-semibold text-slate-700 mb-1">Empleado</label>
    <input
        type="text"
        id="employeeSearch"
        class="w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200"
        placeholder="Buscar empleado..."
        autocomplete="off"
        value="{{ $selectedEmployee?->name ?? '' }}"
    />
    <input type="hidden" name="employee_id" id="employeeId" value="{{ $employeeId ?? '' }}"/>

    {{-- Lista de resultados --}}
    <div id="employeeResults" 
         class="absolute z-[100] w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 hidden max-h-60 overflow-y-auto">
    </div>
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
          {{-- Botones --}}
<div class="md:col-span-2 flex items-end gap-2">
    {{-- Botón Filtrar --}}
    <button type="submit"
            class="flex-1 bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
        Filtrar
    </button>

    {{-- Botón Exportar --}}
    <button type="button" 
            onclick="exportData()"
            class="flex-1 bg-green-600 text-white font-semibold px-4 py-2 rounded-xl shadow hover:bg-green-700 transition flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        Excel
    </button>
</div>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('employeeSearch');
    const hidden = document.getElementById('employeeId');
    const results = document.getElementById('employeeResults');
    const deviceSelect = document.querySelector('select[name="device_id"]');

    if (!input || !results) return;

    let timer = null;
    let lastQuery = '';

    function closeResults() {
        results.classList.add('hidden');
        results.innerHTML = '';
    }

    function openResults() {
        results.classList.remove('hidden');
    }

    async function search(q) {
        const deviceId = deviceSelect ? deviceSelect.value : '';
        const url = new URL("{{ route('attendance.employees.search') }}", window.location.origin);
        url.searchParams.set('q', q);
        
        if (deviceId && deviceId !== '' && deviceId !== '0') {
            url.searchParams.set('device_id', deviceId);
        }

        try {
            const res = await fetch(url.toString(), { 
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();
            return (json && json.data) ? json.data : [];
        } catch (e) {
            console.error("Error en búsqueda:", e);
            return [];
        }
    }

    function render(items) {
        results.innerHTML = '';
        if (!items.length) {
            results.innerHTML = `<div class="p-3 text-sm text-slate-500">Sin resultados</div>`;
            openResults();
            return;
        }

        items.forEach(it => {
            const btn = document.createElement('button');
            btn.type = 'button';
            // Estilo Tailwind para los items
            btn.className = 'w-full text-left px-4 py-2 text-sm hover:bg-slate-100 border-b border-slate-50 last:border-none transition';
            btn.textContent = it.text;
            
            btn.onclick = () => {
                input.value = it.name;
                hidden.value = it.id;
                closeResults();
            };
            results.appendChild(btn);
        });
        openResults();
    }

    input.addEventListener('input', () => {
        hidden.value = ''; // Limpiar ID si el usuario escribe
        const q = input.value.trim();
        
        if (q.length < 2) { 
            closeResults(); 
            return; 
        }

        if (timer) clearTimeout(timer);
        timer = setTimeout(async () => {
            if (q === lastQuery) return;
            lastQuery = q;
            const items = await search(q);
            render(items);
        }, 300);
    });

    if (deviceSelect) {
        deviceSelect.addEventListener('change', () => {
            input.value = '';
            hidden.value = '';
            closeResults();
        });
    }

    // Cerrar al hacer click afuera
    document.addEventListener('click', (ev) => {
        if (!results.contains(ev.target) && ev.target !== input) {
            closeResults();
        }
    });
});
function exportData() {
    // Obtener los valores actuales de los filtros
    const deviceId = document.querySelector('select[name="device_id"]').value;
    const employeeId = document.getElementById('employeeId').value;
    const from = document.querySelector('input[name="from"]').value;
    const to = document.querySelector('input[name="to"]').value;

    // Construir la URL con los parámetros
    const url = new URL("{{ route('attendance.logs.export') }}", window.location.origin);
    if (deviceId) url.searchParams.set('device_id', deviceId);
    if (employeeId) url.searchParams.set('employee_id', employeeId);
    if (from) url.searchParams.set('from', from);
    if (to) url.searchParams.set('to', to);

    // Redirigir para descargar
    window.location.href = url.toString();
}
</script>