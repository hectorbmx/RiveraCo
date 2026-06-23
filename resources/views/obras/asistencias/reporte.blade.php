<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de asistencia - {{ $obra->nombre }}</title>
    <style>
        @page { size: Letter landscape; margin: 7mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #fff;
            font-size: 8px;
        }
        .page {
            width: 265mm;
            min-height: 198mm;
            margin: 0 auto;
            border: 3px solid #0b3b96;
            padding: 0;
        }
        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 10px;
            border-bottom: 1px solid #d1d5db;
        }
        .toolbar button {
            border: 0;
            background: #0b3b96;
            color: #fff;
            padding: 8px 14px;
            border-radius: 4px;
            font-weight: 700;
            cursor: pointer;
        }
        .header {
            display: grid;
            grid-template-columns: 32% 43% 25%;
            align-items: start;
            gap: 12px;
            padding: 9mm 10mm 8mm;
            border-bottom: 2px solid #111827;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 50px;
        }
        .logo img {
            max-height: 45px;
            max-width: 170px;
            object-fit: contain;
        }
        .logo-fallback {
            color: #0b3b96;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 1px;
            line-height: .9;
        }
        .meta-row {
            display: grid;
            grid-template-columns: 90px 1fr;
            align-items: end;
            gap: 10px;
            margin: 4px 0;
            font-size: 10px;
            font-weight: 700;
        }
        .meta-row span:first-child { color: #0b3b96; }
        .line-value {
            border-bottom: 2px solid #333;
            min-height: 14px;
            text-align: center;
            padding: 0 8px 2px;
        }
        .nomina-box {
            display: grid;
            grid-template-columns: 115px 1fr;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        .nomina-label {
            background: #0b3b96;
            color: #fff;
            font-size: 12px;
            font-weight: 900;
            text-align: center;
            padding: 6px 8px;
        }
        .right-lines .line-value {
            margin: 5px 0;
            font-weight: 700;
            font-size: 9px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #222;
            padding: 2px 2px;
            vertical-align: middle;
            line-height: 1.05;
        }
        th {
            background: #0b3b96;
            color: #fff;
            font-weight: 900;
            text-align: center;
            font-size: 7px;
            letter-spacing: 0;
            overflow-wrap: anywhere;
        }
        td { font-size: 7px; }
        .col-no { width: 7mm; }
        .col-name { width: 47mm; }
        .col-cat { width: 22mm; }
        .col-sueldo { width: 19mm; }
        .col-day { width: 5mm; }
        .col-total-days { width: 13mm; }
        .col-day-pay { width: 18mm; }
        .col-pay { width: 22mm; }
        .col-discount { width: 20mm; }
        .col-total { width: 22mm; }
        .col-obs { width: 35mm; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .currency {
            display: block;
            white-space: nowrap;
            text-align: right;
        }
        .yellow-total {
            background: #ffc000;
            color: #0b3b96;
            font-weight: 900;
            font-size: 9px;
            text-align: center;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 120px;
            padding: 28mm 28mm 0;
            text-align: center;
            font-weight: 800;
        }
        .signature-line {
            border-top: 2px solid #333;
            padding-top: 8px;
            min-height: 46px;
        }
        @media print {
            .toolbar { display: none; }
            .page {
                width: 265mm;
                min-height: 198mm;
            }
        }
    </style>
</head>
<body>
@php
    $fmt = fn ($value) => number_format((float) $value, 2);
    $totalGeneral = $rows->sum('total_pagar');
    $logoPath = public_path('images/logo.png');
@endphp

<div class="page">
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir / guardar PDF</button>
    </div>

    <header class="header">
        <div class="logo">
            @if(file_exists($logoPath))
                <img src="{{ asset('images/logo.png') }}" alt="Rivera Construcciones">
            @else
                <div class="logo-fallback">RIVERA<br>CONSTRUCCIONES</div>
            @endif
        </div>

        <div>
            <div class="meta-row">
                <span>OBRA:</span>
                <div class="line-value">{{ mb_strtoupper($obra->nombre ?? '-') }}</div>
            </div>
            <div class="meta-row">
                <span>CLIENTE:</span>
                <div class="line-value">{{ mb_strtoupper($obra->cliente->nombre_comercial ?? $obra->cliente->razon_social ?? '-') }}</div>
            </div>
            <div class="meta-row">
                <span>SEMANA:</span>
                <div class="line-value">DEL {{ $desde->format('d/m/Y') }} AL {{ $hasta->format('d/m/Y') }}</div>
            </div>
        </div>

        <div>
            <div class="nomina-box">
                <div class="nomina-label">NOMINA:</div>
                <div></div>
            </div>
            <div class="right-lines">
                <div class="line-value">{{ now('America/Mexico_City')->format('d/m/Y') }}</div>
                <div class="line-value">{{ mb_strtoupper($obra->responsable->nombre_completo ?? $generadoPor ?: '') }}</div>
            </div>
        </div>
    </header>

    <table>
        <colgroup>
            <col class="col-no">
            <col class="col-name">
            <col class="col-cat">
            <col class="col-sueldo">
            @foreach($weekDays as $day)
                <col class="col-day">
            @endforeach
            <col class="col-total-days">
            <col class="col-day-pay">
            <col class="col-pay">
            <col class="col-discount">
            <col class="col-total">
            <col class="col-obs">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="3">NO.</th>
                <th rowspan="3">N O M B R E</th>
                <th rowspan="3">CATEGORIA</th>
                <th rowspan="3">SUELDO</th>
                <th colspan="7">SEMANA</th>
                <th rowspan="3">TOTAL<br>DIAS</th>
                <th colspan="1">SUELDO</th>
                <th colspan="3">I M P O R T E S</th>
                <th rowspan="3">OBSERVACIONES</th>
            </tr>
            <tr>
                @foreach($weekDays as $day)
                    <th>{{ $day['day'] }}</th>
                @endforeach
                <th>X</th>
                <th rowspan="2">SUELDO</th>
                <th rowspan="2">DESC.<br>INFONAVIT</th>
                <th>TOTAL</th>
            </tr>
            <tr>
                @foreach(['L', 'M', 'M', 'J', 'V', 'S', 'D'] as $dow)
                    <th>{{ $dow }}</th>
                @endforeach
                <th>DIA</th>
                <th>A PAGAR:</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-left">{{ mb_strtoupper(trim(($row->empleado->Apellidos ?? '') . ' ' . ($row->empleado->Nombre ?? ''))) }}</td>
                    <td class="text-left">{{ mb_strtoupper($row->empleado->puesto_base ?? $row->empleado->Puesto ?? '-') }}</td>
                    <td class="text-right"><div class="currency">$ {{ $fmt($row->sueldo_semanal) }}</div></td>
                    @foreach($weekDays as $day)
                        <td class="text-center">{{ ($row->dias[$day['date']]['presente'] ?? false) ? '1' : '' }}</td>
                    @endforeach
                    <td class="text-center">{{ $row->total_dias }}</td>
                    <td class="text-right"><div class="currency">$ {{ $fmt($row->sueldo_diario) }}</div></td>
                    <td class="text-right"><div class="currency">$ {{ $fmt($row->sueldo_periodo) }}</div></td>
                    <td class="text-right">{{ $row->descuento_infonavit > 0 ? '$ '.$fmt($row->descuento_infonavit) : '' }}</td>
                    <td class="text-right"><div class="currency">$ {{ $fmt($row->total_pagar) }}</div></td>
                    <td></td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="text-center" style="padding: 18px;">Sin asistencias registradas en esta semana.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="13" style="border: 0;"></td>
                <td colspan="3" class="yellow-total">TOTAL GENERAL</td>
                <td class="yellow-total">$ {{ $fmt($totalGeneral) }}</td>
            </tr>
        </tbody>
    </table>

    <section class="signatures">
        <div class="signature-line">
            ELABORO<br>
            {{ mb_strtoupper($generadoPor ?: ' ') }}
        </div>
        <div class="signature-line">
            REVISO<br>
            {{ mb_strtoupper($obra->responsable->nombre_completo ?? ' ') }}
        </div>
    </section>
</div>
</body>
</html>
