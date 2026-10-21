<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de servicio #{{ $mantenimiento->id }}</title>
    <style>
        @page { size: Letter; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { background: #e5e7eb; color: #172033; font-family: Arial, sans-serif; font-size: 12px; margin: 0; }
        .page { background: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, .16); margin: 24px auto; min-height: 11in; padding: .55in; width: 8.5in; }
        h1, h2 { color: #0B265A; margin: 0; }
        h1 { font-size: 21px; }
        h2 { font-size: 15px; margin: 22px 0 8px; }
        .muted { color: #667085; }
        .header { border-bottom: 3px solid #0B265A; display: flex; justify-content: space-between; align-items: end; padding-bottom: 12px; }
        .brand { color: #0B265A; display: flex; align-items: center; gap: 9px; margin-bottom: 8px; }
        .brand img { width: 70px; max-height: 45px; object-fit: contain; }
        .brand-text { line-height: .9; letter-spacing: 1px; }
        .rivera { font-size: 21px; font-weight: 900; display: block; }
        .construcciones { font-size: 9px; font-weight: 900; display: block; margin-top: 4px; }
        .folio { color: #0B265A; font-size: 14px; font-weight: bold; text-align: right; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px 22px; margin-top: 18px; }
        .item { border-bottom: 1px solid #e5e7eb; padding: 6px 0; }
        .label { color: #667085; font-size: 10px; text-transform: uppercase; }
        .documents { font-size: 10px; line-height: 1.35; }
        .documents-group { margin-top: 4px; }
        .documents-group strong { color: #0B265A; display: block; font-size: 9px; text-transform: uppercase; }
        .document-row { display: flex; justify-content: space-between; gap: 6px; border-bottom: 1px solid #eef2f6; padding: 2px 0; }
        .document-row:last-child { border-bottom: 0; }
        .document-state { color: #667085; white-space: nowrap; }
        table { border-collapse: collapse; margin-top: 8px; width: 100%; }
        th { background: #0B265A !important; color: #fff !important; font-size: 10px; padding: 6px; text-align: left; text-transform: uppercase; }
        td { border: .5px solid #cbd5e1; padding: 7px; vertical-align: top; }
        .right { text-align: right; }
        .footer { border-top: 1px solid #d0d5dd; margin-top: 28px; padding-top: 10px; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 48px; text-align: center; }
        .signature { border-top: 1px solid #7b8494; padding-top: 8px; font-size: 11px; }
        .report-actions { display: flex; gap: 8px; }
        .report-actions button { border: 0; border-radius: 6px; padding: 8px 14px; font-weight: 700; cursor: pointer; }
        .report-actions .back { background: #fff; border: 1px solid #cbd5e1; color: #334155; }
        .report-actions .print { background: #0B265A; color: #fff; }
        @media screen and (max-width: 900px) {
            .page { margin: 12px auto; max-width: calc(100vw - 24px); min-height: auto; padding: 28px; width: auto; }
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media screen and (max-width: 600px) {
            .grid { grid-template-columns: 1fr; }
        }
        @media print {
            body { background: #fff; }
            .no-print { display: none; }
            .page { box-shadow: none; margin: 0; min-height: 11in; padding: .55in; width: 8.5in; }
        }
    </style>
</head>
<body>
    <div class="no-print report-actions" style="position: fixed; bottom: 22px; right: max(22px, calc((100vw - 8.5in) / 2 + 22px)); z-index: 20;">
        <button type="button" class="back" onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ route('mantenimiento.mantenimientos.index') }}'">Regresar</button>
        <button type="button" class="print" onclick="window.print()">Imprimir reporte</button>
    </div>

    <div class="page">
    <div class="header">
        <div>
            <div class="brand">
                <img src="{{ asset('images/logoAzul.png') }}" alt="Rivera Construcciones" onerror="this.style.display='none';">
                <div class="brand-text"><span class="rivera">RIVERA</span><span class="construcciones">CONSTRUCCIONES</span></div>
            </div>
            <h1>Reporte de servicio ejecutado</h1>
            <div class="muted">Bitácora de materiales, horas y responsable</div>
        </div>
        <div class="folio">SERV-{{ str_pad((string) $mantenimiento->id, 6, '0', STR_PAD_LEFT) }}<br><span class="muted">{{ $mantenimiento->fecha_fin?->format('d/m/Y H:i') ?? $mantenimiento->updated_at?->format('d/m/Y H:i') }}</span></div>
    </div>

    <div class="grid">
        <div class="item"><div class="label">{{ $mantenimiento->maquina ? 'Máquina' : 'Vehículo' }}</div>{{ $mantenimiento->maquina ? trim(($mantenimiento->maquina->nombre ?? '—') . ($mantenimiento->maquina->codigo ? ' · ' . $mantenimiento->maquina->codigo : '')) : (($mantenimiento->vehiculo?->marca ?? '') . ' ' . ($mantenimiento->vehiculo?->modelo ?? '')) }}</div>
        <div class="item"><div class="label">Estado</div>{{ ucfirst(str_replace('_', ' ', $mantenimiento->estatus)) }}</div>
        <div class="item documents">
            <div class="label">Documentos relacionados</div>
            <div class="documents-group">
                <strong>Órdenes de compra</strong>
                @forelse($ordenesCompra as $orden)
                    <div class="document-row"><span>{{ $orden->folio }}</span><span class="document-state">{{ ucfirst(strtolower($orden->estado)) }}</span></div>
                @empty
                    <div class="muted">—</div>
                @endforelse
            </div>
            <div class="documents-group">
                <strong>Entradas de almacén</strong>
                @forelse($documentosInventario->where('tipo', 'entrada') as $documento)
                    <div class="document-row"><span>{{ $documento->folio }}</span><span class="document-state">{{ ucfirst($documento->estado) }}</span></div>
                @empty
                    <div class="muted">—</div>
                @endforelse
            </div>
            <div class="documents-group">
                <strong>Salidas de almacén</strong>
                @forelse($documentosInventario->where('tipo', 'salida') as $documento)
                    <div class="document-row"><span>{{ $documento->folio }}</span><span class="document-state">{{ ucfirst($documento->estado) }}</span></div>
                @empty
                    <div class="muted">—</div>
                @endforelse
            </div>
        </div>
        <div class="item"><div class="label">Servicio / categoría</div>{{ $mantenimiento->categoria_mantenimiento ?: $mantenimiento->servicioPreventivoTipo?->nombre ?: '—' }}</div>
        <div class="item"><div class="label">Mecánico</div>{{ $mantenimiento->mecanico ? trim(($mantenimiento->mecanico->Nombre ?? '') . ' ' . ($mantenimiento->mecanico->Apellidos ?? '')) : '—' }}</div>
        <div class="item"><div class="label">Horómetro / kilometraje</div>{{ $mantenimiento->maquina ? ($mantenimiento->horometro !== null ? number_format((float) $mantenimiento->horometro, 2) . ' h' : '—') : ($mantenimiento->km_actuales !== null ? number_format((float) $mantenimiento->km_actuales) . ' km' : '—') }}</div>
        <div class="item"><div class="label">Ejecución</div>{{ $mantenimiento->fecha_inicio?->format('d/m/Y H:i') ?? '—' }} a {{ $mantenimiento->fecha_fin?->format('d/m/Y H:i') ?? '—' }}<br><span class="muted">{{ $mantenimiento->duracion_en_minutos !== null ? number_format($mantenimiento->duracion_en_minutos / 60, 2) . ' horas trabajadas' : 'Duración no registrada' }}</span></div>
        <div class="item"><div class="label">Costo de mano de obra</div>${{ number_format((float) ($manoObraCalculada['costo_total'] ?? $mantenimiento->costo_mano_obra ?? 0), 2) }}</div>
        <div class="item"><div class="label">Costo de insumos</div>${{ number_format($costoInsumos > 0 ? $costoInsumos : (float) ($mantenimiento->costo_refacciones ?? 0), 2) }}</div>
    </div>

    <h2>Material utilizado</h2>
    <table>
        <thead><tr><th>Producto</th><th>SKU</th><th>Unidad</th><th class="right">Solicitado</th><th class="right">Surtido</th></tr></thead>
        <tbody>
            @forelse($materiales as $material)
                <tr><td>{{ $material['nombre'] }}</td><td>{{ $material['sku'] ?: '—' }}</td><td>{{ $material['unidad'] ?: '—' }}</td><td class="right">{{ number_format($material['solicitada'], 3) }}</td><td class="right">{{ number_format($material['atendida'], 3) }}</td></tr>
            @empty
                <tr><td colspan="5">No hay materiales registrados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Mano de obra</h2>
    <table>
        <thead><tr><th>Concepto</th><th class="right">Horas</th><th class="right">Costo por hora</th><th class="right">Importe</th></tr></thead>
        <tbody>
            @php($manoObra = $mantenimiento->detalles->first(fn ($detalle) => $detalle->tipo === 'mano_obra' && str_starts_with((string) $detalle->concepto, 'Mano de obra:')))
            @if($manoObra)
                <tr><td>{{ $manoObra->concepto }}</td><td class="right">{{ number_format((float) $manoObra->cantidad, 2) }}</td><td class="right">${{ number_format((float) $manoObra->costo_unitario, 2) }}</td><td class="right">${{ number_format((float) $manoObra->costo_total, 2) }}</td></tr>
            @elseif($manoObraCalculada)
                <tr><td>Mano de obra: {{ $manoObraCalculada['nombre'] }}</td><td class="right">{{ number_format($manoObraCalculada['horas'], 2) }}</td><td class="right">${{ number_format($manoObraCalculada['costo_hora'], 2) }}</td><td class="right">${{ number_format($manoObraCalculada['costo_total'], 2) }}</td></tr>
            @else
                <tr><td colspan="4">No hay mano de obra calculada.</td></tr>
            @endif
        </tbody>
    </table>

    <h2>Descripción del trabajo</h2>
    <div style="border: .5px solid #cbd5e1; min-height: 54px; padding: 9px;">{{ $mantenimiento->descripcion ?: '—' }}</div>

    <div class="signatures">
        <div class="signature">Solicita</div>
        <div class="signature">Autoriza</div>
        <div class="signature">Ejecuta / mecánico</div>
    </div>
    <div class="footer muted">Documento recuperable por folio. Este reporte resume la ejecución del servicio y sus materiales asociados.</div>
    </div>
</body>
</html>
