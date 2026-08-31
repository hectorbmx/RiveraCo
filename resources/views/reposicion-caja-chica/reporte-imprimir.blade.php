<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte caja chica {{ $fechaInicio->format('Ymd') }}-{{ $fechaFin->format('Ymd') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; font-size: 12px; }
        .no-print { margin-bottom: 16px; text-align: right; }
        .btn { border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; padding: 8px 12px; font-weight: 700; cursor: pointer; }
        .header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #0B265A; padding-bottom: 14px; margin-bottom: 16px; }
        h1 { margin: 0; color: #0B265A; font-size: 22px; }
        h2 { margin: 22px 0 8px; color: #0B265A; font-size: 15px; }
        .muted { color: #64748b; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px; }
        .box { border: 1px solid #dbe3ef; border-radius: 6px; padding: 9px; }
        .label { color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
        .value { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th { background: #f1f5f9; color: #334155; font-size: 10px; text-transform: uppercase; text-align: left; }
        th, td { border: 1px solid #dbe3ef; padding: 6px; vertical-align: top; }
        .right { text-align: right; }
        .center { text-align: center; }
        .summary { width: 360px; margin-left: auto; margin-top: 16px; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; margin-top: 56px; }
        .signature { border-top: 1px solid #111827; padding-top: 8px; text-align: center; font-size: 11px; }
        @media print {
            body { margin: 10mm; }
            .no-print { display: none; }
            a { color: inherit; text-decoration: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn" onclick="window.print()">Imprimir</button>
    </div>

    <div class="header">
        <div>
            <h1>Reposicion de caja chica</h1>
            <div class="muted">Reporte semanal agrupado por tipo de comprobacion</div>
        </div>
        <div>
            <div class="label">Periodo de captura</div>
            <div class="value">{{ $fechaInicio->format('d/m/Y') }} al {{ $fechaFin->format('d/m/Y') }}</div>
            <div class="muted">Generado: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="box">
            <div class="label">Registrado</div>
            <div class="value">${{ number_format((float) $stats['registrado'], 2) }}</div>
        </div>
        <div class="box">
            <div class="label">Autorizado</div>
            <div class="value">${{ number_format((float) $stats['autorizado'], 2) }}</div>
        </div>
        <div class="box">
            <div class="label">Pendientes</div>
            <div class="value">{{ number_format($stats['pendiente']) }}</div>
        </div>
        <div class="box">
            <div class="label">Gastos</div>
            <div class="value">{{ number_format($gastos->count()) }}</div>
        </div>
    </div>

    @forelse($grupos as $grupo)
        <h2>{{ $grupo['nombre'] }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha gasto</th>
                    <th>Proveedor</th>
                    <th>RFC</th>
                    <th>Concepto</th>
                    <th>Categoria</th>
                    <th>Forma pago</th>
                    <th>Destino</th>
                    <th class="right">Registrado</th>
                    <th class="right">Autorizado</th>
                    <th class="center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grupo['gastos'] as $gasto)
                    <tr>
                        <td>RCC-G-{{ str_pad($gasto->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ optional($gasto->fecha_gasto)->format('d/m/Y') }}</td>
                        <td>{{ $gasto->proveedor_nombre }}</td>
                        <td>{{ $gasto->proveedor_rfc ?: '-' }}</td>
                        <td>{{ $gasto->concepto }}</td>
                        <td>{{ $gasto->subcategoria->nombre ?? 'Sin categoria' }}</td>
                        <td>{{ ucfirst((string) $gasto->forma_pago) }}</td>
                        <td>
                            @if($gasto->destino === 'obra')
                                {{ $gasto->obra->nombre ?? 'Obra no definida' }}
                            @else
                                {{ $gasto->almacen->nombre ?? 'Almacen no definido' }}
                            @endif
                        </td>
                        <td class="right">${{ number_format((float) $gasto->importe_registrado, 2) }}</td>
                        <td class="right">${{ number_format((float) ($gasto->importe_autorizado ?? 0), 2) }}</td>
                        <td class="center">{{ str_replace('_', ' ', $gasto->estado_autorizacion) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="8"><strong>Total {{ $grupo['nombre'] }}</strong></td>
                    <td class="right"><strong>${{ number_format((float) $grupo['total_registrado'], 2) }}</strong></td>
                    <td class="right"><strong>${{ number_format((float) $grupo['total_autorizado'], 2) }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @empty
        <p class="muted center">No hay gastos para el periodo y filtros seleccionados.</p>
    @endforelse

    <table class="summary">
        <tr>
            <td>Total registrado</td>
            <td class="right">${{ number_format((float) $stats['registrado'], 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total autorizado</strong></td>
            <td class="right"><strong>${{ number_format((float) $stats['autorizado'], 2) }}</strong></td>
        </tr>
    </table>

    <div class="signatures">
        <div class="signature">Elaboro</div>
        <div class="signature">Reviso</div>
        <div class="signature">Autorizo</div>
    </div>
</body>
</html>
