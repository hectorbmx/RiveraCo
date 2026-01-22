@extends('layouts.admin')

@section('title', 'Reporte diario de maquinaria')

@section('content')
<form method="POST" action="{{ route('snapshots.store') }}">
  @csrf
  <input type="hidden" name="fecha" value="{{ $fecha }}">
  <button type="submit" class="px-4 py-2 rounded bg-gray-900 text-white text-sm">
    Guardar snapshot del día
  </button>
</form>
<div class="flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Reporte diario de maquinaria</h1>
        <p class="text-sm text-gray-600">Fecha: {{ $fecha }}</p>
    </div>

    <div class="flex items-center gap-2">
        {{-- Selector de fecha (único) para “ver” --}}
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="fecha" value="{{ $fecha }}"
                   class="rounded-lg border-gray-300 text-sm">
            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                Ir
            </button>
        </form>

        {{-- Guardar snapshot para la MISMA fecha visible --}}
        <form method="POST" action="{{ route('snapshots.store') }}">
            @csrf
            <input type="hidden" name="fecha" value="{{ $fecha }}">
            <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                Guardar snapshot
            </button>
        </form>
    </div>
</div>


    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="text-left px-3 py-3 whitespace-nowrap">#</th>    
                        <th class="text-left px-4 py-3 whitespace-nowrap">Obra</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Residente</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Pilas x Obra</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Hechas</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Avance</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">horoInicio</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">trabajadas</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Equipo</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Cliente</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Pagado</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Observaciones</th>
                        <th class="text-left px-4 py-3 whitespace-nowrap">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($rows as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $r['maquina_codigo'] }}</td>    
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if(!empty($r['obra_id']))
                                    <a href="{{ route('obras.edit', $r['obra_id']) }}"
                                    class="text-blue-600 hover:text-blue-800 font-medium">
                                        {{ $r['obra_nombre'] }}
                                    </a>
                                @else
                                    <span class="text-gray-500">
                                        {{ $r['obra_nombre'] }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">{{ $r['residente_nombre'] }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">{{ $r['pilas_programadas'] }}</td>

                            <td class="px-4 py-3 whitespace-nowrap text-center">{{ $r['pilas_ejecutadas'] }}</td>
                            <td class="px-4 py-3 min-w-[180px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div
                                            class="h-2 rounded-full transition-all"
                                            style="width: {{ min($r['avance_global_pct'], 100) }}%;
                                                background-color:
                                                    {{ $r['avance_global_pct'] >= 100 ? '#16a34a' : '#2563eb' }};">
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-700 font-medium whitespace-nowrap">
                                        {{ $r['avance_global_pct'] }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $r['horometro_inicio'] }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">{{ $r['horas_trabajadas'] }}</td>
                            <td class="px-4 py-3 min-w-[170px]">
                                @php $equipo = $r['equipo'] ?? collect(); @endphp

                                @if($equipo->isEmpty())
                                    <span class="text-gray-400 text-sm">—</span>
                                @else
                                    <div class="grid grid-cols-1 gap-1">
                                        @foreach($equipo as $p)
                                            <div class="text-sm text-gray-800 leading-tight">
                                                {{ $p->nombre }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $r['cliente_nombre'] ?? '—' }}</td>
                            <td class="px-4 py-3 min-w-[200px]">
                                @php
                                    $pct   = (int) ($r['pago_pct'] ?? 0);
                                    $total = (float) ($r['total_obra'] ?? 0);
                                    $cob   = (float) ($r['monto_cobrado'] ?? 0);
                                    $w     = min(max($pct, 0), 100);
                                @endphp

                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-xs text-gray-700">
                                        <span>${{ number_format($cob, 2) }} / ${{ number_format($total, 2) }}</span>
                                        <span class="font-medium">{{ $pct }}%</span>
                                    </div>

                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div
                                            class="h-2 rounded-full transition-all"
                                            style="width: {{ $w }}%;
                                                background-color: {{ $pct >= 100 ? '#16a34a' : '#0f172a' }};">
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                @if(!empty($r['observaciones']) && $r['observaciones'] !== '—')
                                    <div class="min-w-[250px] max-w-[400px] text-red-600 font-medium">
                                        {{ $r['observaciones'] }}
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($r['ya_guardado_hoy'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Guardado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        Pendiente
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-4 py-6 text-center text-gray-500">
                                No hay máquinas activas asignadas para este reporte.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection