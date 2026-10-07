<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Formato administrativo caja chica {{ $fechaInicio->format('Ymd') }}-{{ $fechaFin->format('Ymd') }}</title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Arial, sans-serif; color: #111827; margin: 12px; font-size: 10.5px; }
        .no-print { margin-bottom: 14px; text-align: right; }
        .btn { border: 1px solid #cbd5e1; background: #fff; border-radius: 5px; padding: 7px 11px; font-weight: 700; cursor: pointer; }
        .sheet { page-break-after: always; page-break-inside: avoid; }
        .sheet:last-child { page-break-after: auto; }
        .brand { min-height: 58px; display: inline-flex; align-items: center; gap: 10px; color: #0B265A; }
        .brand img { display: block; width: 86px; max-height: 54px; object-fit: contain; }
        .brand-text { display: flex; flex-direction: column; line-height: .86; letter-spacing: 1px; }
        .brand-text .rivera { font-size: 32px; font-weight: 900; }
        .brand-text .construcciones { margin-top: 4px; font-size: 14px; font-weight: 900; letter-spacing: 1.4px; }
        .brand-fallback { color: #0B265A; font-weight: 800; font-size: 22px; letter-spacing: 1px; line-height: 1; }
        .title { color: #0B265A; text-align: center; font-size: 24px; font-weight: 800; margin: 10px 0 8px; letter-spacing: 1px; }
        .rule { border-top: 7px solid #d9d8f0; border-bottom: 4px double #0B265A; height: 12px; margin-bottom: 8px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .meta td { border: 1px solid #1f2937; padding: 5px 7px; height: 23px; }
        .meta .label { width: 90px; background: #e5e7eb; font-weight: 700; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #1f2937; padding: 2px 5px; height: 18px; line-height: 1.12; vertical-align: top; }
        .table th { background: #c8d8f2 !important; color: #111827; text-transform: uppercase; font-size: 10px; text-align: center; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .table .small-col { width: 58px; }
        .table .date-col { width: 78px; }
        .table .invoice-col { width: 74px; }
        .table .money-col { width: 82px; text-align: right; }
        .right { text-align: right; }
        .center { text-align: center; }
        .sum-label { background: #e5e7eb !important; font-weight: 700; text-align: center; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .observations { border: 1px solid #1f2937; border-top: 0; min-height: 62px; padding: 5px 6px; }
        .observations strong { color: #dc2626; font-size: 15px; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 48px; margin-top: 44px; align-items: end; }
        .signature { text-align: center; }
        .signature-line { border-top: 1px solid #111827; min-height: 18px; padding-top: 5px; font-weight: 700; }
        .signature-label { margin-top: 3px; font-size: 12px; }
        .muted { color: #64748b; }
        @media print {
            @page { size: letter portrait; margin: 6mm; }
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn" onclick="window.print()">Imprimir</button>
    </div>

    @php
        $grupoLabelMap = [
            'Con efectivo y factura' => 'Reposicion de caja chica',
            'Con tarjeta y factura' => 'Reposicion de caja chica 1',
            'Sin factura (reembolso)' => 'Reembolso',
            'Sin factura (viaticos)' => 'Viaticos',
        ];

        $firmanteRealizo = $firmasImpresas->get('realizo')?->user?->name
            ?? $firmasImpresas->get(\App\Models\DocumentoFirmante::CAMPO_ELABORO)?->user?->name;
        $firmanteVobo = $firmasImpresas->get(\App\Models\DocumentoFirmante::CAMPO_VOBO)?->user?->name;
        $firmanteReviso = $firmasImpresas->get('reviso')?->user?->name
            ?? $firmasImpresas->get(\App\Models\DocumentoFirmante::CAMPO_AUTORIZO)?->user?->name;
    @endphp

    @forelse($grupos as $grupo)
        @php
            $grupoLabel = $grupoLabelMap[trim((string) $grupo['nombre'])] ?? $grupo['nombre'];
            $totalGrupo = 0;
        @endphp

        <section class="sheet">
            <div class="brand">
                <img src="{{ asset('images/logoAzul.png') }}" alt="Rivera Construcciones" onerror="this.style.display='none';">
                <div class="brand-text">
                    <span class="rivera">RIVERA</span>
                    <span class="construcciones">CONSTRUCCIONES</span>
                </div>
            </div>
            <div class="title">{{ strtoupper($grupoLabel) }}</div>
            <div class="rule"></div>

            <table class="meta">
                <tr>
                    <td style="width: 62%;"><strong>OBRA:</strong> {{ $reporteContexto }}</td>
                    <td rowspan="2" class="center"><strong>No. Obra:</strong></td>
                </tr>
                <tr>
                    <td><span class="label">FECHA</span> {{ now()->translatedFormat('j \d\e F \d\e Y') }}</td>
                </tr>
            </table>

            <table class="table">
                <thead>
                    <tr>
                        <th class="small-col">Partida</th>
                        <th class="date-col">Fecha</th>
                        <th class="invoice-col">Factura</th>
                        <th>Concepto</th>
                        <th class="money-col">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo['gastos'] as $gasto)
                        @php
                            $importe = in_array($gasto->estado_autorizacion, ['autorizado', 'autorizado_parcial'], true) && $gasto->importe_autorizado !== null
                                ? (float) $gasto->importe_autorizado
                                : (float) $gasto->importe_registrado;
                            $totalGrupo += $importe;

                            $conceptoOriginal = trim((string) $gasto->concepto);
                            preg_match('/Folio:\s*([A-Za-z0-9\-]+)/i', $conceptoOriginal, $folioMatch);
                            $factura = $folioMatch[1] ?? (($gasto->categoria?->requiere_factura ?? false) ? 'C/F' : 'S/F');
                            $conceptoImpreso = trim(preg_replace('/Folio:\s*[A-Za-z0-9\-]+\s*/i', '', $conceptoOriginal));
                            $conceptoImpreso = \Illuminate\Support\Str::limit($conceptoImpreso ?: $conceptoOriginal, 90);
                        @endphp
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($gasto->subcategoria->nombre ?? $gasto->categoria->nombre ?? '-', 18) }}</td>
                            <td class="center">{{ optional($gasto->fecha_gasto)->format('d/m/y') }}</td>
                            <td class="center">{{ \Illuminate\Support\Str::limit($factura, 14, '') }}</td>
                            <td>{{ $conceptoImpreso }}</td>
                            <td class="right">${{ number_format($importe, 2) }}</td>
                        </tr>
                    @endforeach

                    @for($i = $grupo['gastos']->count(); $i < 18; $i++)
                        <tr>
                            <td>&nbsp;</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor

                    <tr>
                        <td colspan="3"></td>
                        <td class="sum-label">SUMA</td>
                        <td class="right"><strong>${{ number_format($totalGrupo, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="observations">
                <strong>Observaciones:</strong>
                <div class="muted">{{ $grupoLabel }} - Periodo {{ $fechaInicio->format('d/m/Y') }} al {{ $fechaFin->format('d/m/Y') }}</div>
            </div>

            <div class="signatures">
                <div class="signature">
                    <div class="signature-line">{{ $firmanteRealizo }}</div>
                    <div class="signature-label">Realizo</div>
                </div>
                <div class="signature">
                    <div class="signature-line">{{ $firmanteVobo }}</div>
                    <div class="signature-label">Vo. Bo.</div>
                </div>
                <div class="signature">
                    <div class="signature-line">{{ $firmanteReviso }}</div>
                    <div class="signature-label">Reviso</div>
                </div>
            </div>
        </section>
    @empty
        <section class="sheet">
            <div class="brand">
                <img src="{{ asset('images/logoAzul.png') }}" alt="Rivera Construcciones" onerror="this.style.display='none';">
                <div class="brand-text">
                    <span class="rivera">RIVERA</span>
                    <span class="construcciones">CONSTRUCCIONES</span>
                </div>
            </div>
            <div class="title">REPOSICION DE CAJA CHICA</div>
            <div class="rule"></div>
            <p class="center muted">No hay gastos para el periodo y filtros seleccionados.</p>
        </section>
    @endforelse
</body>
</html>






