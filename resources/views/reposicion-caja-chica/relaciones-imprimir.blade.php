<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Relacion caja chica {{ $relacion->folio }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; font-size: 12px; }
        .no-print { margin-bottom: 16px; text-align: right; }
        .btn { border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; padding: 8px 12px; font-weight: 700; cursor: pointer; }
        .header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #0B265A; padding-bottom: 14px; margin-bottom: 16px; }
        h1 { margin: 0; color: #0B265A; font-size: 22px; }
        .muted { color: #64748b; }
        .folio { text-align: right; font-size: 13px; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px; }
        .box { border: 1px solid #dbe3ef; border-radius: 6px; padding: 9px; }
        .label { color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
        .value { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #f1f5f9; color: #334155; font-size: 10px; text-transform: uppercase; text-align: left; }
        th, td { border: 1px solid #dbe3ef; padding: 7px; vertical-align: top; }
        .right { text-align: right; }
        .center { text-align: center; }
        .totals { width: 360px; margin-left: auto; margin-top: 14px; }
        .totals td { padding: 8px; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; margin-top: 56px; }
        .signature { border-top: 1px solid #111827; padding-top: 8px; text-align: center; font-size: 11px; }
        .signature-name { min-height: 18px; font-weight: 700; margin-bottom: 4px; }
        @media print {
            body { margin: 12mm; }
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
            <h1>Relacion de caja chica</h1>
            <div class="muted">Reposicion semanal de gastos autorizados y capturados</div>
        </div>
        <div class="folio">
            <div class="label">Folio</div>
            <div class="value">{{ $relacion->folio }}</div>
            <div class="muted">Generada: {{ optional($relacion->fecha_generacion ?? $relacion->created_at)->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="box">
            <div class="label">Semana</div>
            <div class="value">{{ optional($relacion->fecha_inicio)->format('d/m/Y') }} - {{ optional($relacion->fecha_fin)->format('d/m/Y') }}</div>
        </div>
        <div class="box">
            <div class="label">Responsable</div>
            <div class="value">{{ $relacion->responsable->name ?? '-' }}</div>
        </div>
        <div class="box">
            <div class="label">Almacen</div>
            <div class="value">{{ $relacion->almacen->nombre ?? '-' }}</div>
        </div>
        <div class="box">
            <div class="label">Estado</div>
            <div class="value">{{ str_replace('_', ' ', $relacion->estado) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Folio gasto</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>RFC</th>
                <th>Concepto</th>
                <th>Categoria</th>
                <th>Destino</th>
                <th class="right">Registrado</th>
                <th class="right">Autorizado</th>
                <th class="center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($relacion->gastos as $gasto)
                <tr>
                    <td>RCC-G-{{ str_pad($gasto->id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ optional($gasto->fecha_gasto)->format('d/m/Y') }}</td>
                    <td>{{ $gasto->proveedor_nombre }}</td>
                    <td>{{ $gasto->proveedor_rfc ?: '-' }}</td>
                    <td>{{ $gasto->concepto }}</td>
                    <td>{{ $gasto->categoria->nombre ?? '-' }}<br><span class="muted">{{ $gasto->subcategoria->nombre ?? 'Sin categoria' }}</span></td>
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
            @empty
                <tr>
                    <td colspan="10" class="center muted">Esta relacion no tiene gastos asociados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Total registrado</td>
            <td class="right">${{ number_format((float) $relacion->total_registrado, 2) }}</td>
        </tr>
        <tr>
            <td>Total autorizado</td>
            <td class="right">${{ number_format((float) $relacion->total_autorizado, 2) }}</td>
        </tr>
        <tr>
            <td>Total rechazado</td>
            <td class="right">${{ number_format((float) $relacion->total_rechazado, 2) }}</td>
        </tr>
        <tr>
            <td>Total pendiente</td>
            <td class="right">${{ number_format((float) $relacion->total_pendiente, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Monto reposicion</strong></td>
            <td class="right"><strong>${{ number_format((float) $relacion->monto_reposicion, 2) }}</strong></td>
        </tr>
    </table>

    <div class="signatures">
        <div class="signature">
            <div class="signature-name">{{ $firmasImpresas->get(\App\Models\DocumentoFirmante::CAMPO_ELABORO)?->user?->name }}</div>
            Elaboro
        </div>
        <div class="signature">
            <div class="signature-name">{{ $firmasImpresas->get(\App\Models\DocumentoFirmante::CAMPO_VOBO)?->user?->name }}</div>
            VoBo
        </div>
        <div class="signature">
            <div class="signature-name">{{ $firmasImpresas->get(\App\Models\DocumentoFirmante::CAMPO_AUTORIZO)?->user?->name }}</div>
            Autorizo
        </div>
    </div>
</body>
</html>
