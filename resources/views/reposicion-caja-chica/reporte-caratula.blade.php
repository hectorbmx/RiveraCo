<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Carátula semanal de gastos {{ $fechaInicio->format('Ymd') }}-{{ $fechaFin->format('Ymd') }}</title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { background: #e5e7eb; color: #111827; font-family: Arial, sans-serif; font-size: 9px; margin: 24px auto; }
        .sheet { background: #fff; box-shadow: 0 8px 24px rgba(15,23,42,.16); margin: 0 auto 24px; min-height: 11in; padding: .42in; width: 8.5in; }
        .no-print { position: fixed; bottom: 22px; right: max(22px, calc((100vw - 8.5in) / 2 + 22px)); z-index: 20; }
        .btn { background: #0B265A; border: 0; border-radius: 5px; color: #fff; cursor: pointer; font-weight: 700; padding: 8px 12px; }
        .header { align-items: end; border-bottom: 4px solid #0B265A; display: flex; justify-content: space-between; padding-bottom: 9px; }
        .brand { align-items: center; color: #0B265A; display: inline-flex; gap: 8px; }
        .brand img { display: block; max-height: 42px; object-fit: contain; width: 65px; }
        .brand-text { display: flex; flex-direction: column; line-height: .86; }
        .rivera { font-size: 22px; font-weight: 900; letter-spacing: 1px; }
        .construcciones { font-size: 10px; font-weight: 900; letter-spacing: 1px; margin-top: 3px; }
        h1 { color: #0B265A; font-size: 16px; margin: 7px 0 2px; }
        .muted { color: #64748b; }
        .period { font-size: 10px; text-align: right; }
        h2 { color: #0B265A; font-size: 12px; margin: 15px 0 5px; }
        table { border-collapse: collapse; page-break-inside: auto; width: 100%; }
        tr { page-break-inside: avoid; }
        th { background: #0B265A; color: #fff; font-size: 8px; text-transform: uppercase; }
        th, td { border: 1px solid #cbd5e1; padding: 3px 4px; vertical-align: top; }
        .date { width: 54px; }
        .provider { width: 82px; }
        .equipment { width: 72px; }
        .qty { text-align: center; width: 36px; }
        .money { text-align: right; white-space: nowrap; width: 64px; }
        .subtotal td { background: #e8eef8; font-weight: 700; }
        .summary { margin-top: 15px; }
        .summary table { width: 55%; }
        .summary th { text-align: left; }
        .summary .money { width: 90px; }
        .total td { background: #dbe7f8; color: #0B265A; font-size: 10px; font-weight: 800; }
        .signatures { display: grid; gap: 18px; grid-template-columns: repeat(4, 1fr); margin-top: 38px; }
        .signature { border-top: 1px solid #111827; min-height: 28px; padding-top: 5px; text-align: center; }
        .signature-name { font-size: 8px; font-weight: 700; min-height: 12px; }
        .signature-label { font-size: 8px; margin-top: 3px; }
        @media print {
            @page { margin: 6mm; size: letter portrait; }
            body { background: #fff; margin: 0; }
            .sheet { box-shadow: none; margin: 0; min-height: 11in; padding: .42in; width: 8.5in; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print"><button class="btn" onclick="window.print()">Imprimir</button></div>

    @php
        $firmanteRealizo = $firmasImpresas->get('realizo')?->user?->name
            ?? $firmasImpresas->get(\App\Models\DocumentoFirmante::CAMPO_ELABORO)?->user?->name;
        $firmanteVobo = $firmasImpresas->get(\App\Models\DocumentoFirmante::CAMPO_VOBO)?->user?->name;
        $firmanteVobo2 = $firmasImpresas->get(\App\Models\DocumentoFirmante::CAMPO_VOBO_2)?->user?->name;
        $firmanteReviso = $firmasImpresas->get('reviso')?->user?->name
            ?? $firmasImpresas->get(\App\Models\DocumentoFirmante::CAMPO_AUTORIZO)?->user?->name;
        $importeDe = static function ($gasto): float {
            return in_array($gasto->estado_autorizacion, ['autorizado', 'autorizado_parcial'], true)
                && $gasto->importe_autorizado !== null
                ? (float) $gasto->importe_autorizado
                : (float) $gasto->importe_registrado;
        };
        $gruposDestino = $gastos->groupBy(function ($gasto) {
            if ($gasto->destino === 'obra') {
                $obra = $gasto->obra;
                return 'Obra: ' . trim(($obra?->clave_obra ? $obra->clave_obra . ' - ' : '') . ($obra?->nombre ?? 'Obra no definida'));
            }

            if ($gasto->destino === 'almacen') {
                return 'Almacén: ' . ($gasto->almacen?->nombre ?? 'Almacén no definido');
            }

            return 'Sin destino definido';
        })->sortKeys();
        $granTotal = $gastos->sum($importeDe);
    @endphp

    <section class="sheet">
        <div class="header">
            <div>
                <div class="brand">
                    <img src="{{ asset('images/logoAzul.png') }}" alt="Rivera Construcciones" onerror="this.style.display='none';">
                    <div class="brand-text"><span class="rivera">RIVERA</span><span class="construcciones">CONSTRUCCIONES</span></div>
                </div>
                <h1>Relación semanal de gastos</h1>
                <div class="muted">Carátula general de gastos registrados</div>
            </div>
            <div class="period"><strong>Periodo</strong><br>{{ $fechaInicio->format('d/m/Y') }} al {{ $fechaFin->format('d/m/Y') }}<br><span class="muted">Generado: {{ now()->format('d/m/Y H:i') }}</span></div>
        </div>

        @forelse($gruposDestino as $destino => $items)
            @php $subtotal = $items->sum($importeDe); @endphp
            <h2>{{ $destino }}</h2>
            <table>
                <thead>
                    <tr>
                        <th class="date">Fecha</th>
                        <th class="provider">Proveedor</th>
                        <th>Concepto</th>
                        <th class="equipment">Equipo</th>
                        <th class="qty">Cant.</th>
                        <th class="money">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $gasto)
                        <tr>
                            <td>{{ optional($gasto->fecha_gasto)->format('d/m/Y') }}</td>
                            <td>{{ $gasto->proveedor_nombre ?: '-' }}</td>
                            <td>{{ $gasto->concepto ?: '-' }}</td>
                            <td>{{ $gasto->activo_operativo_label !== '-' ? $gasto->activo_operativo_label : '-' }}</td>
                            <td class="qty">1</td>
                            <td class="money">${{ number_format($importeDe($gasto), 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal"><td colspan="5">Subtotal</td><td class="money">${{ number_format($subtotal, 2) }}</td></tr>
                </tbody>
            </table>
        @empty
            <p class="muted">No hay gastos para el periodo y filtros seleccionados.</p>
        @endforelse

        <div class="summary">
            <h2>Resumen por destino</h2>
            <table>
                <thead><tr><th>Destino</th><th class="money">Total</th></tr></thead>
                <tbody>
                    @foreach($gruposDestino as $destino => $items)
                        <tr><td>{{ $destino }}</td><td class="money">${{ number_format($items->sum($importeDe), 2) }}</td></tr>
                    @endforeach
                    <tr class="total"><td>GRAN TOTAL</td><td class="money">${{ number_format($granTotal, 2) }}</td></tr>
                </tbody>
            </table>
        </div>

        <div class="signatures">
            <div class="signature"><div class="signature-name">{{ $firmanteRealizo ?: 'Pendiente de asignar' }}</div><div class="signature-label">Realizó</div></div>
            <div class="signature"><div class="signature-name">{{ $firmanteVobo ?: 'Pendiente de asignar' }}</div><div class="signature-label">VoBo 1</div></div>
            <div class="signature"><div class="signature-name">{{ $firmanteVobo2 ?: 'Pendiente de asignar' }}</div><div class="signature-label">VoBo 2</div></div>
            <div class="signature"><div class="signature-name">{{ $firmanteReviso ?: 'Pendiente de asignar' }}</div><div class="signature-label">Revisó</div></div>
        </div>
    </section>
</body>
</html>
