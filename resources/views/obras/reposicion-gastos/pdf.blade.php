<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reposición de gastos</title>

    <style>
        @page {
            margin: 22px 28px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .logo-box {
            width: 35%;
            vertical-align: top;
        }

        .logo-title {
            font-size: 22px;
            font-weight: bold;
            color: #1e3a8a;
            line-height: 1;
        }

        .logo-subtitle {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 1px;
        }

        .folio-box {
            width: 65%;
            text-align: right;
            vertical-align: top;
            font-size: 10px;
            color: #475569;
        }

        .title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 8px 0 8px 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .blue-line {
            border-top: 4px solid #1e3a8a;
            border-bottom: 1px solid #1e3a8a;
            height: 3px;
            margin-bottom: 12px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .info-table td {
            border: 1px solid #cbd5e1;
            padding: 6px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            color: #1e3a8a;
            font-size: 10px;
            text-transform: uppercase;
        }

        .value {
            font-weight: bold;
            color: #111827;
            margin-top: 2px;
        }

        .concept-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .concept-table th {
            background: #dbeafe;
            border: 1px solid #111827;
            padding: 6px 4px;
            font-size: 10px;
            text-align: center;
            color: #111827;
            text-transform: uppercase;
        }

        .concept-table td {
            border: 1px solid #111827;
            padding: 6px 4px;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .small {
            font-size: 9px;
            color: #475569;
        }

        .total-row td {
            font-weight: bold;
            background: #f8fafc;
        }

        .observaciones {
            margin-top: 12px;
            border: 1px solid #111827;
            min-height: 70px;
        }

        .observaciones-title {
            color: red;
            font-weight: bold;
            padding: 6px;
            border-bottom: 1px solid #111827;
        }

        .observaciones-body {
            padding: 8px;
            min-height: 45px;
        }

        .firmas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 70px;
        }

        .firmas td {
            width: 33.33%;
            text-align: center;
            padding: 0 18px;
            vertical-align: bottom;
        }

        .firma-linea {
            border-top: 1px solid #111827;
            padding-top: 6px;
            font-weight: bold;
            font-size: 10px;
        }

        .firma-cargo {
            font-size: 9px;
            color: #475569;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .footer {
            position: fixed;
            bottom: -5px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #64748b;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td class="logo-box">
                <div class="logo-title">RIVERA</div>
                <div class="logo-subtitle">CONSTRUCCIONES</div>
            </td>

            <td class="folio-box">
                <div><strong>Folio:</strong> REP-{{ str_pad($reposicion->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div><strong>Fecha impresión:</strong> {{ now()->format('d/m/Y H:i') }}</div>
                <div><strong>Estatus:</strong> {{ strtoupper(str_replace('_', ' ', $reposicion->estatus)) }}</div>
            </td>
        </tr>
    </table>

    <div class="title">
        Reembolso / Reposición de gastos
    </div>

    <div class="blue-line"></div>

    {{-- DATOS GENERALES --}}
    <table class="info-table">
        <tr>
            <td width="50%">
                <div class="label">Obra</div>
                <div class="value">{{ $obra->nombre ?? '-' }}</div>
            </td>

            <td width="25%">
                <div class="label">Clave</div>
                <div class="value">{{ $obra->clave_obra ?? '-' }}</div>
            </td>

            <td width="25%">
                <div class="label">Semana</div>
                <div class="value">{{ $reposicion->semana ?? '-' }}</div>
            </td>
        </tr>

        <tr>
            <td>
                <div class="label">Solicitó / Residente</div>
                <div class="value">{{ $reposicion->solicitadoPor->name ?? '-' }}</div>
            </td>

            <td>
                <div class="label">Fecha solicitud</div>
                <div class="value">
                    {{ optional($reposicion->solicitado_at)->format('d/m/Y H:i') ?? '-' }}
                </div>
            </td>

            <td>
                <div class="label">Total</div>
                <div class="value">${{ number_format($reposicion->total, 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- CONCEPTOS --}}
    <table class="concept-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="12%">Tipo</th>
                <th width="18%">Partida</th>
                <th width="11%">Fecha</th>
                <th width="14%">RFC</th>
                <th width="15%">Nota / UUID</th>
                <th width="17%">Concepto</th>
                <th width="8%">Importe</th>
            </tr>
        </thead>

        <tbody>
            @php
                $acumulado = 0;
            @endphp

            @forelse($reposicion->detalles as $index => $detalle)
                @php
                    $acumulado += $detalle->monto;
                @endphp

                <tr>
                    <td class="text-center">
                        {{ $index + 1 }}
                    </td>

                    <td class="text-center">
                        {{ $detalle->tipo ?? '-' }}
                    </td>

                    <td>
                        <div class="bold">
                            {{ $detalle->partida->partida ?? 'SIN PARTIDA' }}
                        </div>
                        <div class="small">
                            {{ $detalle->partida->concepto ?? '-' }}
                        </div>
                    </td>

                    <td class="text-center">
                        {{ optional($detalle->fecha)->format('d/m/Y') ?? '-' }}
                    </td>

                    <td class="text-center">
                        {{ $detalle->rfc ?? '-' }}
                    </td>

                    <td>
                        @if($detalle->uuid)
                            <div class="small">{{ $detalle->uuid }}</div>
                        @else
                            -
                        @endif
                    </td>

                    <td>
                        <div class="bold">
                            {{ $detalle->proveedor ?? '-' }}
                        </div>
                        <div class="small">
                            {{ $detalle->descripcion ?? '-' }}
                        </div>
                    </td>

                    <td class="text-right bold">
                        ${{ number_format($detalle->monto, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">
                        No hay conceptos registrados.
                    </td>
                </tr>
            @endforelse

            <tr class="total-row">
                <td colspan="7" class="text-right">
                    SUMA
                </td>
                <td class="text-right">
                    ${{ number_format($reposicion->total, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- OBSERVACIONES --}}
    <div class="observaciones">
        <div class="observaciones-title">
            Observaciones:
        </div>

        <div class="observaciones-body">
            {{ $reposicion->observaciones ?? '' }}
        </div>
    </div>

    {{-- FIRMAS --}}
    <table class="firmas">
        <tr>
            <td>
                <div class="firma-linea">
                    {{ $reposicion->solicitadoPor->name ?? 'Solicitante' }}
                </div>
                <div class="firma-cargo">
                    Realizó
                </div>
            </td>

            <td>
                <div class="firma-linea">
                    {{ $reposicion->revisadoPor->name ?? 'Vo. Bo.' }}
                </div>
                <div class="firma-cargo">
                    Vo. Bo.
                </div>
            </td>

            <td>
                <div class="firma-linea">
                    {{ $reposicion->aprobadoPor->name ?? 'Revisó / Autorizó' }}
                </div>
                <div class="firma-cargo">
                    Revisó
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        SIRICO · Rivera Construcciones · Documento generado automáticamente
    </div>

</body>
</html>