<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Formato de comisiones - {{ $comision->numero_formato ?? 'S/F' }}</title>

    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
        }
        .page {
            width: 1000px;
            margin: 0 auto;
            padding: 10px 20px;
        }
        .header-table,
        .block-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }
        .subtitle {
            font-size: 11px;
            text-align: center;
        }
        .right-info {
            text-align: right;
            font-size: 10px;
        }

        .section-title {
            background: #0b4f6c;
            color: #fff;
            font-weight: bold;
            text-align: left;
            padding: 3px 6px;
            margin-top: 10px;
            font-size: 11px;
        }

        .block-table th,
        .block-table td {
            border: 1px solid #000;
            padding: 2px 4px;
        }
        .block-table th {
            background: #e4eef5;
            font-weight: bold;
            text-align: center;
        }

        .no-border td {
            border: none !important;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .uppercase   { text-transform: uppercase; }

        .signatures {
            margin-top: 18px;
            width: 100%;
        }
        .signatures td {
            text-align: center;
            padding-top: 25px;
            font-size: 10px;
        }
        .sign-line {
            border-top: 1px solid #000;
            width: 90%;
            margin: 0 auto 3px auto;
        }

        @media print {
            body {
                margin: 0;
            }
            .page {
                width: 100%;
                padding: 0 10mm;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="page">

    {{-- ENCABEZADO PRINCIPAL --}}
    <table class="header-table">
        <tr>
            <td style="width: 30%;">
                {{-- Aquí puedes poner el logo real --}}
                {{-- <img src="{{ asset('images/logo-rivera.png') }}" style="max-width: 180px;"> --}}
                <div style="font-weight:bold; font-size:13px;">
                    RIVERA CONSTRUCCIONES S.A. DE C.V.
                </div>
                <div style="font-size:10px;">
                    CONSTRUYENDO LOS CIMIENTOS DE MÉXICO
                </div>
            </td>
            <td style="width: 40%;">
                <div class="title">FORMATO DE COMISIONES</div>
                <!-- <div class="subtitle">
                    No. Pila:
                    <strong>{{ $comision->pila->numero_pila ?? '––' }}</strong>
                </div> -->
            </td>
            <td style="width: 30%;" class="right-info">
                <div>JUSTO SIERRA NO 2643 COL LADRÓN DE GUEVARA</div>
                <div>GUADALAJARA, JALISCO, MÉXICO C.P. 44600</div>
                <div>TEL: (33) 3651-0741 3630-1056</div>
            </td>
        </tr>
    </table>

    {{-- DATOS DE CABECERA TIPO EXCEL --}}
    <table class="block-table" style="margin-top:8px;">
        <tr>
            <th style="width: 10%;">CLIENTE</th>
            <td style="width: 40%;">
                {{ $obra->cliente->nombre_comercial ?? $comision->cliente_nombre ?? '––' }}
            </td>
            <th style="width: 10%;">FECHA</th>
            <td style="width: 15%;">
                {{ $comision->fecha?->format('d-m-Y') ?? '––' }}
            </td>
            <th style="width: 10%;">No. FORMATO</th>
            <td style="width: 15%;">
                {{ $comision->numero_formato ?? '––' }}
            </td>
        </tr>
        <tr>
            <th>OBRA</th>
            <td>{{ $obra->nombre_obra ?? $obra->clave_obra ?? '––' }}</td>
            <th>PERFORADORA</th>
            <td colspan="3">
                {{-- si quieres mostrar al perforador después, aquí va --}}
                {{ $obra->maquinasAsignadas->first()?->maquina->nombre_maquina ?? '––' }}
            </td>
        </tr>
        <tr>
            <th>UBICACIÓN</th>
            <td>{{ $obra->ubicacion ?? '––' }}</td>
            <th>RESIDENTE</th>
            <td colspan="3">
                @php $res = $comision->residente; @endphp
                {{ $res ? $res->Nombre . ' ' . $res->Apellidos : '––' }}
            </td>
        </tr>
    </table>

    {{-- SECCIÓN: HORARIO / PERSONAL --}}
    <div class="section-title">HORARIO / PERSONAL EN LA OBRA</div>

    <table class="block-table">
        <thead>
            <tr>
                <th style="width: 16%;">PUESTO</th>
                <th style="width: 10%;">DE</th>
                <th style="width: 10%;">A</th>
                <th style="width: 12%;">COMIDA (hrs)</th>
                <th style="width: 14%;">HORAS LABORADAS</th>
                <th style="width: 14%;">TIEMPO EXTRA</th>
                <th>NOMBRE EMPLEADO</th>
            </tr>
        </thead>
        <tbody>
            @forelse($comision->personales as $pers)
                @php
                    $asig = $pers->asignacionEmpleado;
                    $emp  = $asig?->empleado;
                    $comidaHoras = $pers->comida_min ? $pers->comida_min / 60 : 0;
                @endphp
                <tr>
                    <td class="text-center">
                        {{-- puedes poner aquí el rol si quieres --}}
                        {{ $emp?->Puesto ?? $emp?->puesto_base ?? 'TRABAJADOR' }}
                    </td>
                    <td class="text-center">{{ $pers->hora_inicio ?? '––' }}</td>
                    <td class="text-center">{{ $pers->hora_fin ?? '––' }}</td>
                    <td class="text-center">
                        {{ $comidaHoras ? number_format($comidaHoras, 2) : '0.00' }}
                    </td>
                    <td class="text-center">
                        {{ number_format($pers->horas_laboradas ?? 0, 2) }}
                    </td>
                    <td class="text-center">
                        {{ number_format($pers->tiempo_extra ?? 0, 2) }}
                    </td>
                    <td>
                        @if($emp)
                            {{ $emp->Nombre }} {{ $emp->Apellidos }}
                        @else
                            –
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">
                        SIN REGISTROS DE PERSONAL
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- SECCIÓN: DETALLE DE PERFORACIÓN / DIÁMETROS --}}
    {{-- SECCIÓN: DETALLE DE PERFORACIÓN / DIÁMETROS + HORARIOS --}}
<div class="section-title">DETALLE DE PERFORACIÓN (DIÁMETROS, VOLÚMENES Y HORARIOS)</div>

@php
    // Emparejamos perforaciones con detalles por índice (mismo orden de guardado)
    $detalles      = $comision->detalles->values();
    $perforaciones = $comision->perforaciones->sortBy('id')->values();

    $totProf   = 0;
    $totMetros = 0;
    $totKg     = 0;
    $totBent   = 0;
    $totConc   = 0;
@endphp

<table class="block-table">
    <thead>
        <tr>
            <th>PILA</th>
            <th>INICIO</th>
            <th>TÉRMINO</th>
            <th>DIÁMETRO</th>
            <th>CANT</th>
            <th>PROF.</th>
            <th>METROS SUJETOS A COMISIÓN</th>
            <th>KG/ACERO</th>
            <th>VOL. BENTONITA</th>
            <th>VOL. CONCRETO</th>
            <th>ADICIONAL</th>
        </tr>
    </thead>
    <tbody>
        @forelse($detalles as $index => $det)
            @php
                $perf = $perforaciones[$index] ?? null;

                $totProf   += $det->profundidad ?? 0;
                $totMetros += $det->metros_sujetos_comision ?? 0;
                $totKg     += $det->kg_acero ?? 0;
                $totBent   += $det->vol_bentonita ?? 0;
                $totConc   += $det->vol_concreto ?? 0;
            @endphp
            <tr>
                {{-- Pila: código/tipo desde la cabecera (ajusta al mismo campo que usas en el show) --}}
                <td class="text-center">
                    <!-- {{ $comision->pila->codigo ?? $comision->pila->numero_pila ?? '––' }} -->
                    {{ optional($comision->pila->catalogo ?? null)->codigo ?? optional($comision->pila)->tipo ?? '—' }}

                </td>

                {{-- Horarios de perforación --}}
                <td class="text-center">
                    {{ $perf?->hora_inicio ?? '––' }}
                </td>
                <td class="text-center">
                    {{ $perf?->hora_termino ?? '––' }}
                </td>

                {{-- Detalle de perforación --}}
                <td class="text-center">{{ $det->diametro ?? '––' }}</td>
                <td class="text-center">{{ $det->cantidad ?? '' }}</td>
                <td class="text-center">{{ $det->profundidad ?? '' }}</td>
                <td class="text-center">{{ $det->metros_sujetos_comision ?? '' }}</td>
                <td class="text-center">{{ $det->kg_acero ?? '' }}</td>
                <td class="text-center">{{ $det->vol_bentonita ?? '' }}</td>
                <td class="text-center">{{ $det->vol_concreto ?? '' }}</td>
                <td class="text-center">{{ $det->adicional ?? '' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="text-center">
                    SIN DETALLE DE PERFORACIÓN
                </td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <th class="text-right">TOTAL:</th>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="text-center">{{ $totProf ?: '' }}</td>
            <td class="text-center">{{ $totMetros ?: '' }}</td>
            <td class="text-center">{{ $totKg ?: '' }}</td>
            <td class="text-center">{{ $totBent ?: '' }}</td>
            <td class="text-center">{{ $totConc ?: '' }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- OBSERVACIONES --}}
<table class="block-table no-border" style="margin-top:4px;">
    <tr>
        <td style="border:1px solid #000; padding:3px 4px;">
            <strong>OBSERVACIONES:</strong><br>
            {{ $comision->observaciones ?? '' }}
        </td>
    </tr>
</table>

    {{-- OBSERVACIONES --}}
    <table class="block-table no-border" style="margin-top:4px;">
        <tr>
            <td style="border:1px solid #000; padding:3px 4px;">
                <strong>OBSERVACIONES:</strong><br>
                {{ $comision->observaciones ?? '' }}
            </td>
        </tr>
    </table>

    {{-- SECCIÓN: REPORTE DE PERFORACIÓN --}}
    <div class="section-title">REPORTE DE PERFORACIÓN</div>

    <table class="block-table">
        <thead>
            <tr>
                <th>INICIO</th>
                <th>TÉRMINO</th>
                <th>INFORMACIÓN PILA</th>
            </tr>
        </thead>
        <tbody>
            @forelse($comision->perforaciones as $perf)
                <tr>
                    <td class="text-center">{{ $perf->hora_inicio ?? '––' }}</td>
                    <td class="text-center">{{ $perf->hora_termino ?? '––' }}</td>
                    <td>{{ $perf->informacion_pila ?? '––' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">
                        SIN REPORTE DE PERFORACIÓN
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- FIRMAS --}}
    <table class="signatures">
        <tr>
            <td>
                <div class="sign-line"></div>
                OPERADOR
            </td>
            <td>
                <div class="sign-line"></div>
                RESIDENTE
            </td>
            <td>
                <div class="sign-line"></div>
                REPRESENTANTE DEL CLIENTE
            </td>
        </tr>
    </table>

</div> {{-- .page --}}

</body>
</html>
