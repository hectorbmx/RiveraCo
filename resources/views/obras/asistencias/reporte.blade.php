<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de asistencia - {{ $obra->nombre }}</title>
    <style>
        @page { size: landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #fff;
            font-size: 11px;
        }
        .page {
            min-height: 100vh;
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
            padding: 10px 12px 16px;
            border-bottom: 2px solid #111827;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 72px;
        }
        .logo img {
            max-height: 66px;
            max-width: 230px;
            object-fit: contain;
        }
        .logo-fallback {
            color: #0b3b96;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 1px;
            line-height: .9;
        }
        .meta-row {
            display: grid;
            grid-template-columns: 90px 1fr;
            align-items: end;
            gap: 10px;
            margin: 6px 0;
            font-size: 13px;
            font-weight: 700;
        }
        .meta-row span:first-child { color: #0b3b96; }
        .line-value {
            border-bottom: 2px solid #333;
            min-height: 18px;
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
            font-size: 18px;
            font-weight: 900;
            text-align: center;
            padding: 10px 8px;
        }
        .right-lines .line-value {
            margin: 7px 0;
            font-weight: 700;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #222;
            padding: 4px 3px;
            vertical-align: middle;
        }
        th {
            background: #0b3b96;
            color: #fff;
            font-weight: 900;
            text-align: center;
        }
        .name-col { width: 255px; }
        .cat-col { width: 115px; }
        .money-col { width: 88px; }
        .day-col { width: 26px; }
        .total-days-col { width: 62px; }
        .obs-col { width: 150px; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .currency {
            display: grid;
            grid-template-columns: 14px 1fr;
            gap: 3px;
            align-items: center;
        }
        .yellow-total {
            background: #ffc000;
            color: #0b3b96;
            font-weight: 900;
            font-size: 14px;
            text-align: center;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 120px;
            padding: 90px 36px 0;
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
            .page { min-height: auto; }
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
                <div class="line-value">{{ mb_strtoupper($obra->responsable->name ?? $generadoPor ?: '') }}</div>
            </div>
        </div>
    </header>

    <table>
        <thead>
            <tr>
                <th rowspan="3" style="width: 34px;">NO.</th>
                <th rowspan="3" class="name-col">N O M B R E</th>
                <th rowspan="3" class="cat-col">CATEGORIA</th>
                <th rowspan="3" class="money-col">SUELDO</th>
                <th colspan="7">SEMANA</th>
                <th rowspan="3" class="total-days-col">TOTAL<br>DIAS</th>
                <th colspan="1">SUELDO</th>
                <th colspan="3">I M P O R T E S</th>
                <th rowspan="3" class="obs-col">OBSERVACIONES</th>
            </tr>
            <tr>
                @foreach($weekDays as $day)
                    <th class="day-col">{{ $day['day'] }}</th>
                @endforeach
                <th class="money-col">X</th>
                <th class="money-col" rowspan="2">SUELDO</th>
                <th class="money-col" rowspan="2">DESCUENTO<br>INFONAVIT</th>
                <th class="money-col">TOTAL</th>
            </tr>
            <tr>
                @foreach(['L', 'M', 'M', 'J', 'V', 'S', 'D'] as $dow)
                    <th class="day-col">{{ $dow }}</th>
                @endforeach
                <th class="money-col">DIA</th>
                <th class="money-col">A PAGAR:</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-left">{{ mb_strtoupper(trim(($row->empleado->Apellidos ?? '') . ' ' . ($row->empleado->Nombre ?? ''))) }}</td>
                    <td class="text-left">{{ mb_strtoupper($row->empleado->puesto_base ?? $row->empleado->Puesto ?? '-') }}</td>
                    <td class="text-right"><div class="currency"><span>$</span><span>{{ $fmt($row->sueldo_semanal) }}</span></div></td>
                    @foreach($weekDays as $day)
                        <td class="text-center">{{ ($row->dias[$day['date']]['presente'] ?? false) ? '1' : '' }}</td>
                    @endforeach
                    <td class="text-center">{{ $row->total_dias }}</td>
                    <td class="text-right"><div class="currency"><span>$</span><span>{{ $fmt($row->sueldo_diario) }}</span></div></td>
                    <td class="text-right"><div class="currency"><span>$</span><span>{{ $fmt($row->sueldo_periodo) }}</span></div></td>
                    <td class="text-right">{{ $row->descuento_infonavit > 0 ? '$ '.$fmt($row->descuento_infonavit) : '' }}</td>
                    <td class="text-right"><div class="currency"><span>$</span><span>{{ $fmt($row->total_pagar) }}</span></div></td>
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
            {{ mb_strtoupper($obra->responsable->name ?? ' ') }}
        </div>
    </section>
</div>
</body>
</html>
