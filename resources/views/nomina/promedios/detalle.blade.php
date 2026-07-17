@extends('layouts.admin')

@section('title', 'Detalle de promedios')

@section('content')
<div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <div class="text-xs text-slate-500">Nomina / Promedios / Detalle</div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $empleadoModel->Nombre }} {{ $empleadoModel->Apellidos }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $tipoLabel }} - Rango: {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <form method="POST"
                  action="{{ route('nomina.promedios.empleados.recalcular', $empleadoModel->id_Empleado) }}"
                  onsubmit="return confirm('Se recalcularan los recibos del rango visible usando el Sueldo + Complemento actual del empleado. ¿Continuar?')">
                @csrf
                <input type="hidden" name="desde" value="{{ $desde }}">
                <input type="hidden" name="hasta" value="{{ $hasta }}">
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                <button type="submit"
                        class="px-4 py-2 bg-slate-800 text-white text-sm rounded-xl shadow-sm hover:bg-slate-900">
                    Recalcular recibos
                </button>
            </form>

            <a href="{{ route('nomina.promedios.index', array_filter(['desde' => $desde, 'hasta' => $hasta, 'tipo' => $tipo])) }}"
               class="px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm rounded-xl shadow-sm hover:bg-slate-50">
                Volver a promedios
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Recibos</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totales['recibos']) }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Sueldo + comp.</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($totales['pago_base'], 2) }}</div>
        </div>        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Prom. teórico</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($totales['promedio_teorico_mensual'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Prom. real</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($totales['promedio_real_mensual'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Variable</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($totales['variable'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Revisar sueldo</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($totales['ajuste_sueldo_real'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Deducciones</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($totales['deducciones'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Neto registrado</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($totales['neto'], 2) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <div class="text-sm font-semibold text-slate-900">Recibos considerados</div>
                <div class="text-xs text-slate-500">Corridas cerradas o pagadas dentro del periodo seleccionado.</div>
            </div>
            <div class="text-xs text-slate-500">ID empleado: {{ $empleadoModel->id_Empleado }}</div>
        </div>

        @if($recibos->isEmpty())
            <div class="p-8 text-center">
                <div class="text-sm font-semibold text-slate-800">No hay recibos para este empleado en el rango</div>
                <div class="text-xs text-slate-500 mt-1">Regresa al reporte y ajusta los filtros.</div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Periodo</th>
                            <th class="px-3 py-2 text-left font-semibold">Fechas</th>
                            <th class="px-3 py-2 text-left font-semibold">Pago</th>
                            <th class="px-3 py-2 text-center font-semibold">Corrida</th>
                            <th class="px-3 py-2 text-right font-semibold">Sueldo + comp.</th>
                            <th class="px-3 py-2 text-right font-semibold">Variable</th>
                            <th class="px-3 py-2 text-right font-semibold">Revisar sueldo</th>
                            <th class="px-3 py-2 text-right font-semibold">Percepciones</th>
                            <th class="px-3 py-2 text-right font-semibold">Deducciones</th>
                            <th class="px-3 py-2 text-right font-semibold">Neto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($recibos as $recibo)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">
                                    <div class="font-semibold text-slate-900">{{ $recibo->periodo_label ?: $recibo->corrida_label }}</div>
                                    <div class="text-[11px] text-slate-400">Recibo #{{ $recibo->id }}</div>
                                </td>
                                <td class="px-3 py-2 text-slate-700">
                                    {{ \Carbon\Carbon::parse($recibo->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($recibo->fecha_fin)->format('d/m/Y') }}
                                </td>
                                <td class="px-3 py-2 text-slate-700">
                                    {{ $recibo->fecha_pago ? \Carbon\Carbon::parse($recibo->fecha_pago)->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-[11px] font-semibold">
                                        {{ $recibo->corrida_status }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">${{ number_format($recibo->pago_base, 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($recibo->variable, 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($recibo->ajuste_sueldo_real, 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($recibo->total_percepciones, 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($recibo->total_deducciones, 2) }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-slate-900">${{ number_format($recibo->sueldo_neto, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 text-slate-900">
                        <tr>
                            <th class="px-3 py-2 text-left font-bold" colspan="4">Totales</th>
                            <th class="px-3 py-2 text-right font-bold">${{ number_format($totales['pago_base'], 2) }}</th>
                            <th class="px-3 py-2 text-right font-bold">${{ number_format($totales['variable'], 2) }}</th>
                            <th class="px-3 py-2 text-right font-bold">${{ number_format($totales['ajuste_sueldo_real'], 2) }}</th>
                            <th class="px-3 py-2 text-right font-bold">${{ number_format($totales['percepciones'], 2) }}</th>
                            <th class="px-3 py-2 text-right font-bold">${{ number_format($totales['deducciones'], 2) }}</th>
                            <th class="px-3 py-2 text-right font-bold">${{ number_format($totales['neto'], 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection