<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de salida {{ $salida->folio }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111827; background: #f3f4f6; }
        .page { width: 216mm; min-height: 279mm; margin: 0 auto; padding: 14mm; background: #fff; }
        .topbar { display: flex; justify-content: space-between; gap: 16px; border-bottom: 2px solid #111827; padding-bottom: 12px; }
        .title { font-size: 20px; font-weight: 700; margin: 0; }
        .subtitle { font-size: 12px; color: #4b5563; margin-top: 4px; }
        .folio { text-align: right; font-size: 13px; }
        .folio strong { display: block; font-size: 18px; color: #0B265A; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 14px; }
        .box { border: 1px solid #d1d5db; padding: 8px; min-height: 48px; }
        .box.wide { grid-column: span 2; }
        .label { font-size: 10px; color: #6b7280; text-transform: uppercase; font-weight: 700; }
        .value { margin-top: 4px; font-size: 13px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 12px; }
        th { background: #f3f4f6; text-align: left; font-size: 10px; text-transform: uppercase; color: #374151; }
        th, td { border: 1px solid #d1d5db; padding: 7px; vertical-align: top; }
        .right { text-align: right; }
        .muted { color: #6b7280; font-size: 11px; }
        .observaciones { margin-top: 14px; border: 1px solid #d1d5db; padding: 10px; min-height: 52px; font-size: 12px; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-top: 34px; }
        .signature { text-align: center; font-size: 12px; padding-top: 36px; border-top: 1px solid #111827; }
        .actions { width: 216mm; margin: 12px auto; text-align: right; }
        .actions button { border: 1px solid #111827; background: #111827; color: #fff; border-radius: 4px; padding: 8px 12px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .page { margin: 0; width: auto; min-height: auto; padding: 10mm; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <main class="page">
        <header class="topbar">
            <div>
                <h1 class="title">Comprobante de salida a obra</h1>
                <div class="subtitle">HUENTITAN - {{ $almacen->nombre }}</div>
            </div>
            <div class="folio">
                Folio
                <strong>{{ $salida->folio }}</strong>
                <span>{{ strtoupper($salida->estado) }}</span>
            </div>
        </header>

        <section class="grid">
            <div class="box">
                <div class="label">Fecha</div>
                <div class="value">{{ $salida->fecha ? $salida->fecha->format('d/m/Y H:i') : '-' }}</div>
            </div>
            <div class="box">
                <div class="label">Destino</div>
                <div class="value">{{ ucfirst(str_replace('_', ' ', $salida->tipo_destino)) }}</div>
            </div>
            <div class="box wide">
                <div class="label">Obra</div>
                <div class="value">{{ $salida->obra ? trim(($salida->obra->clave_obra ? $salida->obra->clave_obra . ' - ' : '') . $salida->obra->nombre) : '-' }}</div>
            </div>
            <div class="box">
                <div class="label">Solicito</div>
                <div class="value">{{ $salida->usuario?->name ?? 'Sistema' }}</div>
            </div>
            <div class="box">
                <div class="label">Aplico</div>
                <div class="value">{{ $salida->aplicadaPor?->name ?? '-' }}</div>
            </div>
            <div class="box wide">
                <div class="label">Fecha de aplicacion</div>
                <div class="value">{{ $salida->fecha_aplicacion ? $salida->fecha_aplicacion->format('d/m/Y H:i') : '-' }}</div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th style="width: 36px;">#</th>
                    <th>Producto</th>
                    <th>Unidad</th>
                    <th class="right">Cantidad</th>
                    <th class="right">Costo</th>
                    <th class="right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salida->detalles as $idx => $detalle)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $detalle->producto?->nombre ?? $detalle->descripcion }}</strong>
                            <div class="muted">{{ $detalle->producto?->sku ?? '-' }}</div>
                        </td>
                        <td>{{ $detalle->unidad ?: '-' }}</td>
                        <td class="right">{{ number_format((float) $detalle->cantidad_salida, 3) }}</td>
                        <td class="right">${{ number_format((float) $detalle->costo_unitario, 4) }}</td>
                        <td class="right">${{ number_format((float) $detalle->importe, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; color:#6b7280;">Sin partidas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="right">Totales</th>
                    <th class="right">{{ number_format((float) $salida->total_salida, 3) }}</th>
                    <th></th>
                    <th class="right">${{ number_format((float) $salida->total_importe, 2) }}</th>
                </tr>
            </tfoot>
        </table>

        <section class="observaciones">
            <div class="label">Observaciones</div>
            <div style="margin-top: 6px;">{{ $salida->observaciones ?: '-' }}</div>
        </section>

        <section class="signatures">
            <div class="signature">Entrega almacen</div>
            <div class="signature">Recibe obra</div>
            <div class="signature">Autoriza / VoBo</div>
        </section>
    </main>
</body>
</html>
