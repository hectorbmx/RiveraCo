<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagos programados</title>
    <style>
        @page { margin: 14mm 12mm; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: Arial, Helvetica, sans-serif; font-size: 11px; margin: 0; }
        .toolbar { display: flex; justify-content: flex-end; margin-bottom: 12px; }
        .toolbar button { background: #0B265A; border: 0; color: #fff; cursor: pointer; font-size: 11px; padding: 8px 14px; }
        .header { border-bottom: 3px solid #0B265A; display: flex; justify-content: space-between; padding-bottom: 10px; }
        .brand { color: #0B265A; font-size: 20px; font-weight: 700; }
        .title { color: #0B265A; font-size: 17px; font-weight: 700; margin-top: 5px; }
        .meta { color: #526174; line-height: 1.6; text-align: right; }
        .summary { display: flex; gap: 10px; margin: 16px 0; }
        .summary-box { border: 1px solid #d8e0eb; flex: 1; padding: 9px 11px; }
        .summary-label { color: #526174; font-size: 9px; text-transform: uppercase; }
        .summary-value { color: #0B265A; font-size: 15px; font-weight: 700; margin-top: 3px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #0B265A; color: #fff; font-size: 9px; padding: 8px 6px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #d8e0eb; padding: 7px 6px; vertical-align: top; }
        .right { text-align: right; }
        .muted { color: #607086; font-size: 9px; }
        .total-row td { border-top: 2px solid #0B265A; color: #0B265A; font-weight: 700; }
        .notes { border: 1px solid #d8e0eb; min-height: 48px; margin-top: 18px; padding: 8px; }
        .signatures { display: grid; gap: 28px; grid-template-columns: repeat(3, 1fr); margin-top: 48px; page-break-inside: avoid; }
        .signature { border-top: 1px solid #172033; padding-top: 7px; text-align: center; }
        .signature strong { display: block; font-size: 10px; }
        .signature span { color: #607086; display: block; font-size: 9px; margin-top: 4px; }
        .empty { color: #607086; padding: 20px; text-align: center; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <header class="header">
        <div>
            <div class="brand">RIVERA</div>
            <div class="title">Relación de pagos programados</div>
        </div>
        <div class="meta">
            Fecha de emisión: {{ now()->format('d/m/Y H:i') }}<br>
            Periodo:
            {{ $fechaInicio?->format('d/m/Y') ?? 'Sin límite inicial' }}
            al
            {{ $fechaFin?->format('d/m/Y') ?? 'Sin límite final' }}
            @if($proveedorFiltro)
                <br>Proveedor: {{ $proveedorFiltro }}
            @endif
        </div>
    </header>

    <section class="summary">
        <div class="summary-box">
            <div class="summary-label">Registros programados</div>
            <div class="summary-value">{{ number_format($pagos->count()) }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Total programado</div>
            <div class="summary-value">{{ '$' . number_format((float) $total, 2) }} MXN</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Estado del documento</div>
            <div class="summary-value">Pendiente de autorización</div>
        </div>
    </section>

    <table>
        <thead>
            <tr>
                <th>Fecha programada</th>
                <th>Orden</th>
                <th>Proveedor</th>
                <th>Destino</th>
                <th>Método</th>
                <th class="right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pagos as $pago)
                @php
                    $oc = $pago->ordenCompra;
                    $destino = $oc?->obra?->nombre
                        ?? ($oc?->centroCosto
                            ? (($oc->centroCosto->codigo ? $oc->centroCosto->codigo . ' - ' : '') . $oc->centroCosto->nombre)
                            : 'Compra general');
                @endphp
                <tr>
                    <td>{{ optional($pago->fecha_programada)->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $oc?->folio ?? ('OC-' . $pago->orden_compra_id) }}</strong>
                        @if($oc?->fecha)
                            <div class="muted">OC: {{ $oc->fecha->format('d/m/Y') }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $pago->proveedor?->nombre ?? '—' }}
                        @if($pago->proveedor?->rfc)
                            <div class="muted">{{ $pago->proveedor->rfc }}</div>
                        @endif
                    </td>
                    <td>{{ $destino }}</td>
                    <td>{{ $pago->metodo_pago ?: '—' }}</td>
                    <td class="right">{{ '$' . number_format((float) $pago->monto, 2) }} {{ $pago->moneda }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">No hay pagos programados para los filtros seleccionados.</td>
                </tr>
            @endforelse
            @if($pagos->isNotEmpty())
                <tr class="total-row">
                    <td colspan="5" class="right">Total programado</td>
                    <td class="right">{{ '$' . number_format((float) $total, 2) }} MXN</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="notes">
        <strong>Observaciones de autorización:</strong>
    </div>

    <div class="signatures">
        <div class="signature"><strong>Elaboró</strong><span>Nombre y firma</span></div>
        <div class="signature"><strong>Revisó</strong><span>Nombre y firma</span></div>
        <div class="signature"><strong>Autorizó</strong><span>Nombre, firma y fecha</span></div>
    </div>
</body>
</html>