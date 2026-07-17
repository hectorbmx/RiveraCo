@extends('layouts.admin')

@section('title', 'Promedios de nomina')

@section('content')
<div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <div class="text-xs text-slate-500">Nomina / Promedios</div>
            <h1 class="text-2xl font-bold text-slate-900">Promedios de empleados</h1>
            <p class="mt-1 text-sm text-slate-500">
                Calculo basado en corridas cerradas o pagadas, usando el periodo del recibo.
            </p>
        </div>

        <a href="{{ route('nomina.generador.index') }}"
           class="px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm rounded-xl shadow-sm hover:bg-slate-50">
            Volver a nomina
        </a>
    </div>

    <form method="GET" action="{{ route('nomina.promedios.index') }}" class="mb-6">
        <div class="bg-white rounded-2xl shadow p-4 grid grid-cols-1 md:grid-cols-5 gap-4 items-end border border-slate-100">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}"
                       class="w-full rounded-xl border-slate-200 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}"
                       class="w-full rounded-xl border-slate-200 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo de sueldo</label>
                <select name="tipo" class="w-full rounded-xl border-slate-200 text-sm">
                    <option value="" @selected($tipo === '')>Todos</option>
                    <option value="1" @selected((string) $tipo === '1')>Semanal</option>
                    <option value="2" @selected((string) $tipo === '2')>Quincenal</option>
                    <option value="3" @selected((string) $tipo === '3')>Mensual</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Empleado</label>
                <select name="empleado_id" class="w-full rounded-xl border-slate-200 text-sm">
                    <option value="">Todos</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id_Empleado }}" @selected((string) $empleadoId === (string) $empleado->id_Empleado)>
                            {{ $empleado->Nombre }} {{ $empleado->Apellidos }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2 justify-end">
                <a href="{{ route('nomina.promedios.index') }}"
                   class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm hover:bg-slate-50">
                    Limpiar
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-slate-800 text-white text-sm rounded-xl shadow hover:bg-slate-900">
                    Aplicar
                </button>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Empleados</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totales['empleados']) }}</div>
        </div>

        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Recibos considerados</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totales['recibos']) }}</div>
        </div>

        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Base para promedio</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($totales['base_promedio'], 2) }}</div>
        </div>

        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Promedio mensual medio</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($totales['promedio_mensual'], 2) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <div class="text-sm font-semibold text-slate-900">Resultados</div>
                <div class="text-xs text-slate-500">
                    Rango: {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
                </div>
            </div>
            <div class="text-xs text-slate-500">
                Estado fuente: <span class="font-semibold text-slate-700">corridas cerradas/pagadas</span>
            </div>
        </div>

        @if($rows->isEmpty())
            <div class="p-8 text-center">
                <div class="text-sm font-semibold text-slate-800">No hay recibos considerados en este rango</div>
                <div class="text-xs text-slate-500 mt-1">Ajusta filtros o confirma que existan corridas cerradas/pagadas en el periodo.</div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Empleado</th>
                            <th class="px-3 py-2 text-center font-semibold">Tipo</th>
                            <th class="px-3 py-2 text-right font-semibold">Recibos</th>
                            <th class="px-3 py-2 text-right font-semibold">Sueldo + comp.</th>
                            <th class="px-3 py-2 text-right font-semibold">Revisar sueldo</th>
                            <th class="px-3 py-2 text-right font-semibold">Neto registrado</th>
                            <th class="px-3 py-2 text-right font-semibold">Prom. mensual base</th>
                            <th class="px-3 py-2 text-right font-semibold">Base por recibo</th>
                            <th class="px-3 py-2 text-right font-semibold">Teorico diario</th>
                            <th class="px-3 py-2 text-right font-semibold">Teorico mensual</th>
                            <th class="px-3 py-2 text-right font-semibold">Deducciones</th>
                            <th class="px-3 py-2 text-right font-semibold">Extras/variable</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($rows as $row)
                            @php
                                $tipoLabel = match ((int) $row->sueldo_tipo) {
                                    1 => 'Semanal',
                                    2 => 'Quincenal',
                                    3 => 'Mensual',
                                    default => 'Sin tipo',
                                };
                                $pagoBase = (float) $row->sueldo_imss + (float) $row->complemento;
                                $variable = (float) $row->horas_extra
                                    + (float) $row->metros_lin_monto
                                    + (float) $row->comisiones_monto;
                                $detalleUrl = route('nomina.promedios.empleados.show', array_filter([
                                    'empleado' => $row->empleado_id,
                                    'desde' => $desde,
                                    'hasta' => $hasta,
                                    'tipo' => $tipo,
                                ]));
                            @endphp
                            <tr class="hover:bg-slate-50 cursor-pointer" onclick="window.location='{{ $detalleUrl }}'">
                                <td class="px-3 py-2">
                                    <a href="{{ $detalleUrl }}" class="font-semibold text-slate-900 hover:text-blue-700 hover:underline" onclick="event.stopPropagation()">{{ $row->nombre }} {{ $row->apellidos }}</a>
                                    <div class="text-[11px] text-slate-400">ID: {{ $row->empleado_id }}</div>
                                </td>
                                <td class="px-3 py-2 text-center">{{ $tipoLabel }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($row->recibos_count) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($pagoBase, 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($row->ajuste_sueldo_real, 2) }}</td>
                                <td class="px-3 py-2 text-right font-semibold">${{ number_format($row->total_neto, 2) }}</td>
                                <td class="px-3 py-2 text-right font-bold text-slate-900">${{ number_format($row->promedio_mensual_real, 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($row->promedio_por_recibo, 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($row->sueldo_teorico_diario, 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($row->sueldo_teorico_mensual, 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($row->total_deducciones, 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($variable, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

