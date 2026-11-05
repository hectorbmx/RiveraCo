<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @php
        $tituloRequisicion = match ($requisicion->tipo?->item_type) {
            'producto' => 'Requisición de material',
            'maquina' => 'Requisición de maquinaria',
            'empleado' => 'Requisición de personal',
            default => 'Requisición de ' . ($requisicion->tipo?->nombre ?: 'obra'),
        };
    @endphp
    <title>{{ $tituloRequisicion }} {{ $requisicion->folio }}</title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { background: #e5e7eb; font-family: Arial, sans-serif; color: #111827; margin: 24px auto; font-size: 10.5px; }
        .sheet { background: #fff; box-shadow: 0 8px 24px rgba(15,23,42,.16); margin: 0 auto 24px; min-height: 11in; padding: .45in; width: 8.5in; }
        .no-print { position: fixed; bottom: 22px; right: max(22px, calc((100vw - 8.5in) / 2 + 22px)); z-index: 20; }
        .btn { border: 1px solid #cbd5e1; background: #fff; border-radius: 5px; padding: 7px 11px; font-weight: 700; cursor: pointer; }
        @include('impresion.cabecera-estilos')
        .meta { margin-bottom: 0; }
        .meta td { padding: 5px 7px; height: 23px; }
        .meta .label { width: 17%; background: #e5e7eb; font-weight: 700; text-transform: uppercase; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { border: 1px solid #1f2937; padding: 2px 5px; height: 18px; line-height: 1.12; vertical-align: top; }
        th { background: #c8d8f2 !important; color: #111827; text-align: center; text-transform: uppercase; font-size: 10px; }
        .partidas td { overflow-wrap: anywhere; }
        .partidas thead { display: table-header-group; }
        .partidas { margin-top: -1px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .item-title { font-weight: 600; }
        .snapshot { color: #64748b; font-size: 10px; margin-top: 2px; }
        .observaciones { border: 1px solid #1f2937; border-top: 0; padding: 5px 6px; min-height: 62px; }
        .observaciones strong { color: #dc2626; font-size: 15px; }
        .observaciones-texto { white-space: pre-line; overflow-wrap: anywhere; }
        .signatures { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-top: 44px; align-items: end; break-inside: avoid; }
        .signature { text-align: center; }
        .line { border-top: 1px solid #111827; min-height: 18px; padding-top: 5px; text-align: center; font-weight: 700; }
        .signature-label { margin-top: 3px; font-size: 12px; }
        @media print {
            @page { size: letter portrait; margin: 6mm; }
            body { background: #fff; margin: 0; }
            .sheet { box-shadow: none; margin: 0; min-height: auto; padding: .45in; width: 100%; }
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

    <main class="sheet">
    @include('impresion.cabecera', ['tituloDocumento' => $tituloRequisicion])

    <table class="meta">
        <tr>
            <td class="label">Obra</td>
            <td colspan="3"><strong>{{ $obra->nombre }}</strong> <span class="muted">— {{ $obra->clave_obra ?? '-' }}</span></td>
        </tr>
        <tr>
            <td class="label">Folio</td>
            <td>{{ $requisicion->folio }}</td>
            <td class="label">Estado</td>
            <td>{{ $estadoLabel }}</td>
        </tr>
        <tr>
            <td class="label">Fecha solicitud</td>
            <td>{{ optional($requisicion->fecha_solicitud)->format('d/m/Y') ?? '-' }}</td>
            <td class="label">Fecha requerida</td>
            <td>{{ optional($requisicion->fecha_requerida)->format('d/m/Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tipo</td>
            <td>{{ $requisicion->tipo?->nombre ?? '-' }} <span class="muted">(Catálogo {{ $requisicion->numero_catalogo ?? '-' }})</span></td>
            <td class="label">Solicita</td>
            <td>{{ $requisicion->solicitadoPor?->name ?? '-' }}</td>
        </tr>
    </table>

    <table class="partidas">
        <thead>
            <tr>
                <th style="width: 30px;" class="center">#</th>
                <th>Partida</th>
                <th style="width: 60px;">Tipo</th>
                <th style="width: 50px;">Unidad</th>
                <th style="width: 68px;" class="right">Cantidad</th>
                @if($requisicion->tipo?->item_type === 'producto')
                    <th>Equipo destino</th>
                @endif
                <th style="width: 85px;">Estado</th>
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
                    @if($requisicion->tipo?->item_type === 'producto')
                        <td>
                            @if($detalle->equipo_tipo)
                                <div class="item-title">{{ $detalle->equipo_nombre_snapshot ?: '-' }}</div>
                                @if($detalle->equipo_identificador_snapshot)
                                    <div class="snapshot">{{ $detalle->equipo_identificador_snapshot }}</div>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    @endif
                    <td>{{ ucfirst(str_replace('_', ' ', (string) $detalle->estado)) }}</td>
                    <td>{{ $detalle->observaciones ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $requisicion->tipo?->item_type === 'producto' ? 8 : 7 }}" class="center muted">Esta requisicion no tiene partidas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="observaciones">
        <strong>Observaciones:</strong>
        <div class="observaciones-texto">{{ $requisicion->observaciones ?: '-' }}</div>
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
    </main>
</body>
</html>
