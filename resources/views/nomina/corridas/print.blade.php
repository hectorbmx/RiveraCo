<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nomina - {{ $corrida->periodo_label ?? ('Corrida #'.$corrida->id) }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            background: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .toolbar button {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 12px;
            cursor: pointer;
        }

        .document {
            padding: 14px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 14px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .meta {
            color: #475569;
            line-height: 1.5;
        }

        .summary {
            min-width: 260px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
        }

        .summary-row {
            display: grid;
            grid-template-columns: 1fr 110px;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-row:last-child {
            border-bottom: 0;
        }

        .summary-row div {
            padding: 5px 7px;
        }

        .summary-row div:last-child {
            text-align: right;
            font-weight: 700;
        }

        .lista {
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .lista + .lista {
            page-break-before: always;
        }

        .lista-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 7px 8px;
            background: #0b265a;
            color: #ffffff;
            border: 1px solid #0b265a;
        }

        .lista-title {
            font-size: 13px;
            font-weight: 700;
        }

        .lista-meta {
            font-size: 10px;
            opacity: 0.9;
            white-space: nowrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        th {
            background: #e2e8f0;
            color: #0f172a;
            font-size: 9px;
            text-transform: uppercase;
            text-align: left;
        }

        td.num,
        th.num {
            text-align: right;
        }

        .employee {
            font-weight: 700;
        }

        .muted {
            color: #64748b;
            font-size: 9px;
        }

        .subtotal td {
            background: #f1f5f9;
            font-weight: 700;
        }

        .w-employee { width: 20%; }
        .w-id { width: 5%; }
        .w-money { width: 8%; }
        .w-notes { width: 15%; }

        @media print {
            .toolbar {
                display: none;
            }

            .document {
                padding: 0;
            }

            body {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
@php
    $listasRaya = collect($listasRaya ?? []);
    $totalGeneralBruto = $listasRaya->sum('total_bruto');
    $totalGeneralDeducciones = $listasRaya->sum('total_deducciones');
    $totalGeneralNeto = $listasRaya->sum('total_neto');
    $totalGeneralEmpleados = $listasRaya->sum('total_empleados');
@endphp

<div class="toolbar">
    <button type="button" onclick="window.print()">Imprimir</button>
</div>

<div class="document">
    <div class="header">
        <div>
            <h1 class="title">Nomina por listas de raya</h1>
            <div class="meta">
                <div><strong>{{ $corrida->periodo_label ?? ('Corrida #'.$corrida->id) }}</strong></div>
                <div>Tipo: {{ ucfirst($corrida->tipo_pago) }}</div>
                <div>Periodo: {{ optional($corrida->fecha_inicio)->format('d/m/Y') }} - {{ optional($corrida->fecha_fin)->format('d/m/Y') }}</div>
                @if($corrida->fecha_pago)
                    <div>Fecha de pago: {{ optional($corrida->fecha_pago)->format('d/m/Y') }}</div>
                @endif
                <div>Status: {{ strtoupper($corrida->status ?? 'abierta') }}</div>
            </div>
        </div>

        <div class="summary">
            <div class="summary-row"><div>Listas</div><div>{{ $listasRaya->count() }}</div></div>
            <div class="summary-row"><div>Empleados</div><div>{{ $totalGeneralEmpleados }}</div></div>
            <div class="summary-row"><div>Total bruto</div><div>${{ number_format($totalGeneralBruto, 2) }}</div></div>
            <div class="summary-row"><div>Deducciones</div><div>${{ number_format($totalGeneralDeducciones, 2) }}</div></div>
            <div class="summary-row"><div>Total neto</div><div>${{ number_format($totalGeneralNeto, 2) }}</div></div>
        </div>
    </div>

    @forelse($listasRaya as $grupo)
        <section class="lista">
            <div class="lista-head">
                <div>
                    <div class="lista-title">{{ $grupo->nombre }}</div>
                    <div class="lista-meta">Tipo: {{ ucfirst($grupo->tipo) }}</div>
                </div>
                <div class="lista-meta">
                    {{ $grupo->total_empleados }} empleado(s) | Neto: ${{ number_format($grupo->total_neto, 2) }}
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="w-employee">Empleado</th>
                        <th class="w-id">ID</th>
                        <th class="num w-money">IMSS</th>
                        <th class="num w-money">Complemento</th>
                        <th class="num w-money">Sueldo real</th>
                        <th class="num w-money">Horas extra</th>
                        <th class="num w-money">M. lineales</th>
                        <th class="num w-money">Comisiones</th>
                        <th class="num w-money">Extras</th>
                        <th class="num w-money">Bruto</th>
                        <th class="num w-money">Deducciones</th>
                        <th class="num w-money">Neto</th>
                        <th class="w-notes">Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo->recibos as $recibo)
                        @php
                            $extras = (float) $recibo->pagosExtra->sum('monto');
                        @endphp
                        <tr>
                            <td>
                                <div class="employee">{{ trim(($recibo->empleado?->Nombre ?? '') . ' ' . ($recibo->empleado?->Apellidos ?? '')) }}</div>
                                @if($recibo->obra)
                                    <div class="muted">{{ trim(($recibo->obra->clave_obra ? $recibo->obra->clave_obra . ' - ' : '') . ($recibo->obra->nombre ?? '')) }}</div>
                                @endif
                            </td>
                            <td>{{ $recibo->empleado_id }}</td>
                            <td class="num">${{ number_format((float) ($recibo->sueldo_imss_snapshot ?? 0), 2) }}</td>
                            <td class="num">${{ number_format((float) ($recibo->complemento_snapshot ?? 0), 2) }}</td>
                            <td class="num">${{ number_format((float) ($recibo->total_percepciones ?? 0), 2) }}</td>
                            <td class="num">${{ number_format((float) ($recibo->horas_extra ?? 0), 2) }}</td>
                            <td class="num">${{ number_format((float) ($recibo->metros_lin_monto ?? 0), 2) }}</td>
                            <td class="num">${{ number_format((float) ($recibo->comisiones_monto ?? 0), 2) }}</td>
                            <td class="num">${{ number_format($extras, 2) }}</td>
                            <td class="num">${{ number_format((float) ($recibo->print_bruto ?? 0), 2) }}</td>
                            <td class="num">${{ number_format((float) ($recibo->print_deducciones ?? 0), 2) }}</td>
                            <td class="num">${{ number_format((float) ($recibo->print_neto ?? 0), 2) }}</td>
                            <td>{{ $recibo->notas_legacy }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal">
                        <td colspan="9">Subtotal {{ $grupo->nombre }}</td>
                        <td class="num">${{ number_format($grupo->total_bruto, 2) }}</td>
                        <td class="num">${{ number_format($grupo->total_deducciones, 2) }}</td>
                        <td class="num">${{ number_format($grupo->total_neto, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </section>
    @empty
        <p>No hay recibos generados para esta corrida.</p>
    @endforelse
</div>
</body>
</html>