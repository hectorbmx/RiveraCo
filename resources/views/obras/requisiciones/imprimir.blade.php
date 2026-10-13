<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Requisicion {{ $requisicion->folio }}</title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Arial, sans-serif; color: #111827; margin: 14px; font-size: 11px; }
        .no-print { margin-bottom: 14px; text-align: right; }
        .btn { border: 1px solid #cbd5e1; background: #fff; border-radius: 5px; padding: 7px 11px; font-weight: 700; cursor: pointer; }
        .brand { min-height: 58px; display: inline-flex; align-items: center; gap: 10px; color: #0B265A; }
        .brand img { display: block; width: 86px; max-height: 54px; object-fit: contain; }
        .brand-text { display: flex; flex-direction: column; line-height: .86; letter-spacing: 1px; }
        .brand-text .rivera { font-size: 32px; font-weight: 900; }
        .brand-text .construcciones { margin-top: 4px; font-size: 14px; font-weight: 900; letter-spacing: 1.4px; }
        .title { color: #0B265A; text-align: center; font-size: 22px; font-weight: 800; margin: 10px 0 8px; letter-spacing: 1px; text-transform: uppercase; }
        .rule { border-top: 7px solid #d9d8f0; border-bottom: 4px double #0B265A; height: 12px; margin-bottom: 12px; }
        .meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px; }
        .box { border: 1px solid #cbd5e1; min-height: 54px; padding: 8px; }
        .box-wide { grid-column: span 2; }
        .label { color: #64748b; font-size: 9px; text-transform: uppercase; font-weight: 800; margin-bottom: 4px; }
        .value { color: #111827; font-weight: 800; line-height: 1.25; }
        .muted { color: #64748b; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        th { background: #c8d8f2 !important; color: #111827; text-align: left; text-transform: uppercase; font-size: 10px; }
        td { line-height: 1.25; }
        .center { text-align: center; }
        .right { text-align: right; }
        .item-title { font-weight: 800; }
        .snapshot { color: #64748b; font-size: 10px; margin-top: 2px; }
        .observaciones { border: 1px solid #cbd5e1; margin-top: 14px; padding: 9px; min-height: 58px; white-space: pre-line; }
        .signatures { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-top: 58px; }
        .signature { text-align: center; }
        .line { border-top: 1px solid #111827; min-height: 20px; padding-top: 6px; text-align: center; font-weight: 700; }
        .signature-label { margin-top: 4px; font-size: 11px; font-weight: 700; }
        @media print {
            @page { size: letter portrait; margin: 7mm; }
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    @php
        use App\Models\DocumentoFirmante;
        use App\Models\ObraRequisicion;

        $estadoLabel = match ((string) $requisicion->estado) {
            ObraRequisicion::ESTADO_PENDIENTE_REVISION => 'Pendiente de revision',
            ObraRequisicion::ESTADO_AUTORIZADA => 'Autorizada',
            ObraRequisicion::ESTADO_RECHAZADA => 'Rechazada',
            ObraRequisicion::ESTADO_EN_SURTIDO => 'En surtido',
            ObraRequisicion::ESTADO_SURTIDA_PARCIAL => 'Surtida parcial',
            ObraRequisicion::ESTADO_SURTIDA => 'Surtida',
            ObraRequisicion::ESTADO_RECIBIDA => 'Recibida',
            ObraRequisicion::ESTADO_CANCELADA => 'Cancelada',
            default => ucfirst(str_replace('_', ' ', (string) $requisicion->estado)),
        };

        $elaboraNombre = $firmasImpresas->get(DocumentoFirmante::CAMPO_ELABORO)?->user?->name
            ?? $requisicion->solicitadoPor?->name
            ?? 'Elabora';
        $autorizaNombre = $firmasImpresas->get(DocumentoFirmante::CAMPO_AUTORIZO)?->user?->name ?? 'Autoriza';
        $voboNombre = $firmasImpresas->get(DocumentoFirmante::CAMPO_VOBO)?->user?->name ?? 'VoBo';
        $recibeNombre = $firmasImpresas->get('recibe')?->user?->name ?? 'Recibe';
    @endphp

    <div class="no-print">
        <button class="btn" onclick="window.print()">Imprimir</button>
    </div>

    <div class="brand">
        <img src="{{ asset('images/logoAzul.png') }}" alt="Rivera Construcciones" onerror="this.style.display='none';">
        <div class="brand-text">
            <span class="rivera">RIVERA</span>
            <span class="construcciones">CONSTRUCCIONES</span>
        </div>
    </div>

    <div class="title">Requisicion de obra</div>
    <div class="rule"></div>

    <div class="meta">
        <div class="box">
            <div class="label">Folio</div>
            <div class="value">{{ $requisicion->folio }}</div>
        </div>
        <div class="box">
            <div class="label">Estado</div>
            <div class="value">{{ $estadoLabel }}</div>
        </div>
        <div class="box">
            <div class="label">Fecha solicitud</div>
            <div class="value">{{ optional($requisicion->fecha_solicitud)->format('d/m/Y') ?? '-' }}</div>
        </div>
        <div class="box">
            <div class="label">Fecha requerida</div>
            <div class="value">{{ optional($requisicion->fecha_requerida)->format('d/m/Y') ?? '-' }}</div>
        </div>
        <div class="box box-wide">
            <div class="label">Obra</div>
            <div class="value">{{ $obra->nombre }}</div>
            <div class="muted">{{ $obra->clave_obra ?? '-' }}</div>
        </div>
        <div class="box">
            <div class="label">Tipo</div>
            <div class="value">{{ $requisicion->tipo?->nombre ?? '-' }}</div>
            <div class="muted">Catalogo {{ $requisicion->numero_catalogo ?? '-' }}</div>
        </div>
        <div class="box">
            <div class="label">Solicita</div>
            <div class="value">{{ $requisicion->solicitadoPor?->name ?? '-' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 42px;" class="center">#</th>
                <th>Partida</th>
                <th style="width: 90px;">Tipo</th>
                <th style="width: 90px;">Unidad</th>
                <th style="width: 92px;" class="right">Cantidad</th>
                <th style="width: 110px;">Estado</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requisicion->detalles as $detalle)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>
                        <div class="item-title">{{ $detalle->descripcion }}</div>
                        @if($detalle->descripcion_snapshot)
                            <div class="snapshot">{{ $detalle->descripcion_snapshot }}</div>
                        @endif
                    </td>
                    <td>{{ ucfirst((string) ($detalle->item_type ?? '-')) }}</td>
                    <td>{{ $detalle->unidad ?? '-' }}</td>
                    <td class="right">{{ number_format((float) $detalle->cantidad_solicitada, 3) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', (string) $detalle->estado)) }}</td>
                    <td>{{ $detalle->observaciones ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="center muted">Esta requisicion no tiene partidas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="observaciones">
        <div class="label">Observaciones generales</div>
        {{ $requisicion->observaciones ?: '-' }}
    </div>

    <div class="signatures">
        <div class="signature">
            <div class="line">{{ $elaboraNombre }}</div>
            <div class="signature-label">Elabora</div>
        </div>
        <div class="signature">
            <div class="line">{{ $autorizaNombre }}</div>
            <div class="signature-label">Autoriza</div>
        </div>
        <div class="signature">
            <div class="line">{{ $voboNombre }}</div>
            <div class="signature-label">VoBo</div>
        </div>
        <div class="signature">
            <div class="line">{{ $recibeNombre }}</div>
            <div class="signature-label">Recibe</div>
        </div>
    </div>
</body>
</html>
