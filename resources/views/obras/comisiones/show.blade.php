@extends('layouts.admin')

@section('title', 'Detalle de comisión')

@section('content')

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">
                Detalle de comisión – {{ $obra->nombre_obra ?? $obra->clave_obra ?? 'Obra' }}
            </h1>
            <p class="text-sm text-slate-500">
                Cliente: {{ $obra->cliente->nombre_comercial ?? 'N/D' }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'comisiones']) }}"
               class="text-sm text-slate-600 hover:text-slate-800">
                ← Volver a comisiones de la obra
            </a>

            <a href="{{ route('obras.comisiones.print', [$obra, $comision]) }}"target ="blank"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-700 text-white text-sm font-medium hover:bg-slate-800">
                Imprimir formato
            </a>
        </div>
    </div>

    {{-- ENCABEZADO --}}
    <div class="bg-white border rounded-xl shadow-sm p-6 mb-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">
            Datos generales del formato
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <div class="text-[11px] uppercase text-slate-500">Fecha</div>
                <div class="font-medium text-slate-800">
                    {{ $comision->fecha?->format('d/m/Y') ?? '—' }}
                </div>
            </div>

            <div>
                <div class="text-[11px] uppercase text-slate-500">Pila</div>
                <div class="font-medium text-slate-800">
                    {{ $comision->pila->numero_pila ?? '—' }}
                </div>
            </div>

            <div>
                <div class="text-[11px] uppercase text-slate-500">No. formato</div>
                <div class="font-medium text-slate-800">
                    {{ $comision->numero_formato ?? '—' }}
                </div>
            </div>

            <div>
                <div class="text-[11px] uppercase text-slate-500">Residente</div>
                <div class="font-medium text-slate-800">
                    @php $res = $comision->residente ?? null; @endphp
                    {{ $res ? $res->Nombre . ' ' . $res->Apellidos : '—' }}
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="text-[11px] uppercase text-slate-500">Nombre del cliente (en formato)</div>
                <div class="font-medium text-slate-800">
                    {{ $comision->cliente_nombre ?? ($obra->cliente->nombre_comercial ?? '—') }}
                </div>
            </div>
        </div>

        @if($comision->observaciones)
            <div class="mt-4">
                <div class="text-[11px] uppercase text-slate-500 mb-1">Observaciones</div>
                <p class="text-sm text-slate-700 whitespace-pre-line">
                    {{ $comision->observaciones }}
                </p>
            </div>
        @endif
    </div>

    {{-- PERSONAL EN LA OBRA --}}
    <div class="bg-white border rounded-xl shadow-sm p-6 mb-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">
            Personal en la obra
        </h2>

        @if($comision->personales->isEmpty())
            <p class="text-sm text-slate-500">
                No hay registros de personal para esta comisión.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs md:text-sm">
                    <thead class="bg-slate-50 border-b text-slate-500">
                        <tr>
                            <th class="py-2 px-2 text-left">Inicio</th>
                            <th class="py-2 px-2 text-left">Fin</th>
                            <th class="py-2 px-2 text-left">Comida (hrs)</th>
                            <th class="py-2 px-2 text-left">Horas laboradas</th>
                            <th class="py-2 px-2 text-left">Tiempo extra</th>
                            <th class="py-2 px-2 text-left">Personal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comision->personales as $pers)
                            @php
                                $asig = $pers->asignacionEmpleado ?? null;
                                $emp  = $asig?->empleado;
                            @endphp
                            <tr class="border-b">
                                <td class="py-2 px-2">{{ $pers->hora_inicio ?? '—' }}</td>
                                <td class="py-2 px-2">{{ $pers->hora_fin ?? '—' }}</td>
                                <td class="py-2 px-2"> {{ $pers->comida_min ? number_format($pers->comida_min / 60, 2) : '0.00' }}</td>
                                <td class="py-2 px-2">{{ number_format($pers->horas_laboradas ?? 0, 2) }}</td>
                                <td class="py-2 px-2">{{ number_format($pers->tiempo_extra ?? 0, 2) }}</td>
                                <td class="py-2 px-2">
                                    @if($emp)
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-800">
                                                {{ $emp->Nombre }} {{ $emp->Apellidos }}
                                            </span>
                                            <span class="text-[11px] text-slate-500">
                                                {{ $emp->Puesto ?? $emp->puesto_base }}
                                            </span>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- DETALLE DE PERFORACIÓN (DIÁMETROS / VOLÚMENES) --}}
    <div class="bg-white border rounded-xl shadow-sm p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-700 mb-3">
        Detalle de perforación (diámetros, volúmenes y horarios)
    </h2>

    @if($comision->detalles->isEmpty())
        <p class="text-sm text-slate-500">
            No hay detalles de perforación capturados.
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs md:text-sm">
                <thead class="bg-slate-50 border-b text-slate-500">
                    <tr>
                        <th class="py-2 px-2 text-left">Pila</th>
                        <th class="py-2 px-2 text-left">Inicio</th>
                        <th class="py-2 px-2 text-left">Fin</th>

                        <th class="py-2 px-2 text-left">Diámetro</th>
                        <th class="py-2 px-2 text-left">Cant</th>
                        <th class="py-2 px-2 text-left">Prof.</th>
                        <th class="py-2 px-2 text-left">Mts. comisión</th>
                        <th class="py-2 px-2 text-left">Kg/acero</th>
                        <th class="py-2 px-2 text-left">Vol. bent.</th>
                        <th class="py-2 px-2 text-left">Vol. concr.</th>
                        <th class="py-2 px-2 text-left">Mts. Adem.</th>
                        <th class="py-2 px-2 text-left">Cant. camp.</th>
                        <th class="py-2 px-2 text-left">Adic.</th>
                    </tr>
                </thead>

              <tbody>
    @foreach($comision->detalles as $det)
        <tr class="border-b">
            {{-- Pila (código catálogo desde la cabecera) --}}
            <td class="py-2 px-2 font-semibold">
                {{ optional($comision->pila->catalogo ?? null)->codigo ?? optional($comision->pila)->tipo ?? '—' }}
                {{-- o simplemente: {{ optional($comision->pila)->tipo ?? '—' }} si ya tienes el tipo aquí --}}
            </td>

            {{-- Inicio / Fin perforación: vienen "pegados" desde el controlador --}}
            <td class="py-2 px-2">
                @if($det->hora_inicio_perf)
                    {{ \Carbon\Carbon::parse($det->hora_inicio_perf)->format('H:i') }}
                @else
                    —
                @endif
            </td>
            <td class="py-2 px-2">
                @if($det->hora_fin_perf)
                    {{ \Carbon\Carbon::parse($det->hora_fin_perf)->format('H:i') }}
                @else
                    —
                @endif
            </td>

            {{-- Resto de columnas --}}
            <td class="py-2 px-2">{{ $det->diametro ?? '—' }}</td>
            <td class="py-2 px-2">{{ $det->cantidad ?? '—' }}</td>
            <td class="py-2 px-2">{{ $det->profundidad ?? '—' }}</td>
            <td class="py-2 px-2">{{ $det->metros_sujetos_comision ?? '—' }}</td>
            <td class="py-2 px-2">{{ $det->kg_acero ?? '—' }}</td>
            <td class="py-2 px-2">{{ $det->vol_bentonita ?? '—' }}</td>
            <td class="py-2 px-2">{{ $det->vol_concreto ?? '—' }}</td>
            <td class="py-2 px-2">{{ $det->ml_ademe_bauer ?? '—' }}</td>
            <td class="py-2 px-2">{{ $det->campana_pzas ?? '—' }}</td>
            <td class="py-2 px-2">{{ $det->adicional ?? '—' }}</td>
        </tr>
    @endforeach
</tbody>


            </table>
        </div>
    @endif
</div>
{{-- COSTOS / ACTIVIDADES POR EMPLEADO --}}
<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Empleado</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Rol</th>

                @foreach($columnas as $key => $label)
                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">{{ $label }}</th>
                @endforeach

                <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">T. extra</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-slate-700">Total</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
            @forelse($matriz as $r)
                <tr>
                    <td class="px-4 py-2 whitespace-nowrap">{{ $r['empleado'] }}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-slate-600">{{ $r['rol'] }}</td>

                    @foreach($columnas as $key => $label)
                        <td class="px-4 py-2 text-right">
                            {{ number_format($r['conceptos'][$key] ?? 0, 2) }}
                        </td>
                    @endforeach

                    <td class="px-4 py-2 text-right">{{ number_format($r['importe_extra'] ?? 0, 2) }}</td>
                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($r['total'] ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-6 text-center text-slate-500" colspan="{{ 4 + count($conceptos) }}">
                        No hay personal registrado en esta comisión.
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if(count($matriz))
            <tfoot class="bg-slate-50 border-t border-slate-200">
                <tr>
                    <td class="px-4 py-2 font-semibold text-slate-700" colspan="2">Totales</td>

                    @foreach($columnas as $key => $label)
                        <td class="px-4 py-2 text-right font-semibold">
                            {{ number_format($totalesCols[$key] ?? 0, 2) }}
                        </td>
                    @endforeach

                    <td class="px-4 py-2"></td>
                    <td class="px-4 py-2 text-right font-bold">
                        {{ number_format($granTotal ?? 0, 2) }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>


    <!-- {{-- REPORTE DE PERFORACIÓN --}}
    <div class="bg-white border rounded-xl shadow-sm p-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">
            Reporte de perforación
        </h2>

        @if($comision->perforaciones->isEmpty())
            <p class="text-sm text-slate-500">
                No hay registros de reporte de perforación.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs md:text-sm">
                    <thead class="bg-slate-50 border-b text-slate-500">
                        <tr>
                            <th class="py-2 px-2 text-left">Inicio</th>
                            <th class="py-2 px-2 text-left">Término</th>
                            <th class="py-2 px-2 text-left">Información pila</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comision->perforaciones as $perf)
                            <tr class="border-b">
                                <td class="py-2 px-2">{{ $perf->inicio ?? '—' }}</td>
                                <td class="py-2 px-2">{{ $perf->termino ?? '—' }}</td>
                                <td class="py-2 px-2">{{ $perf->info_pila ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div> -->

@endsection
