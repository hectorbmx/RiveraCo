<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de servicio {{ $mantenimiento->orden_servicio_folio }}</title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { background: #e5e7eb; color: #111827; font-family: Arial, sans-serif; margin: 24px auto; }
        .sheet { background: #fff; box-shadow: 0 8px 24px rgba(15,23,42,.16); margin: 0 auto; min-height: 11in; padding: .5in; width: 8.5in; }
        .no-print { position: fixed; bottom: 22px; right: max(22px, calc((100vw - 8.5in) / 2 + 22px)); z-index: 20; }
        .btn { background: #0B265A; border: 0; border-radius: 5px; color: #fff; cursor: pointer; font-weight: 700; padding: 9px 13px; }
        .header { align-items: end; border-bottom: 5px solid #0B265A; display: flex; justify-content: space-between; padding-bottom: 12px; }
        .brand { align-items: center; color: #0B265A; display: inline-flex; gap: 9px; }
        .brand img { display: block; max-height: 52px; object-fit: contain; width: 80px; }
        .brand-text { display: flex; flex-direction: column; line-height: .86; }
        .rivera { font-size: 27px; font-weight: 900; letter-spacing: 1px; }
        .construcciones { font-size: 12px; font-weight: 900; letter-spacing: 1.2px; margin-top: 4px; }
        h1 { color: #0B265A; font-size: 20px; margin: 12px 0 3px; }
        .muted { color: #64748b; }
        .folio { color: #0B265A; font-size: 14px; font-weight: 800; text-align: right; }
        .grid { display: grid; gap: 0 28px; grid-template-columns: 1fr 1fr; margin-top: 20px; }
        .field { border-bottom: 1px solid #cbd5e1; min-height: 45px; padding: 7px 0; }
        .label { color: #64748b; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .value { font-size: 12px; font-weight: 700; margin-top: 4px; }
        h2 { color: #0B265A; font-size: 15px; margin: 24px 0 7px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #0B265A; color: #fff; font-size: 9px; text-align: left; text-transform: uppercase; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        .right { text-align: right; }
        .center { text-align: center; }
        .description { border: 1px solid #cbd5e1; min-height: 70px; padding: 9px; }
        .notice { border-left: 4px solid #2563eb; background: #eff6ff; color: #1e3a8a; font-size: 10px; margin-top: 20px; padding: 9px 11px; }
        .signatures { display: grid; gap: 28px; grid-template-columns: repeat(3, 1fr); margin-top: 68px; }
        .signature { border-top: 1px solid #111827; padding-top: 6px; text-align: center; }
        .signature-label { font-size: 10px; font-weight: 700; }
        @media print {
            @page { margin: 6mm; size: letter portrait; }
            body { background: #fff; margin: 0; }
            .sheet { box-shadow: none; margin: 0; min-height: 11in; padding: .5in; width: 8.5in; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print"><button class="btn" onclick="window.print()">Imprimir orden</button></div>
    <section class="sheet">
        <div class="header">
            <div>
                <div class="brand">
                    <img src="{{ asset('images/logoAzul.png') }}" alt="Rivera Construcciones" onerror="this.style.display='none';">
                    <div class="brand-text"><span class="rivera">RIVERA</span><span class="construcciones">CONSTRUCCIONES</span></div>
                </div>
                <h1>Orden de servicio</h1>
                <div class="muted">Documento de inicio de mantenimiento</div>
            </div>
            <div class="folio">{{ $mantenimiento->orden_servicio_folio }}<br><span class="muted">{{ optional($mantenimiento->orden_servicio_generada_at)->format('d/m/Y H:i') }}</span></div>
        </div>

        <div class="grid">
            <div class="field"><div class="label">{{ $ordenServicioDatos['es_maquina'] ? 'Máquina' : 'Vehículo' }}</div><div class="value">{{ $ordenServicioDatos['equipo'] ?: '—' }}</div></div>
            <div class="field"><div class="label">Código / placas</div><div class="value">{{ $ordenServicioDatos['codigo'] ?: '—' }}</div></div>
            <div class="field"><div class="label">Tipo de servicio</div><div class="value">{{ $mantenimiento->servicioPreventivoTipo?->nombre ?? ucfirst($mantenimiento->tipo) }}</div></div>
            <div class="field"><div class="label">Ubicación</div><div class="value">{{ $ordenServicioDatos['ubicacion'] ?: '—' }}</div></div>
            <div class="field"><div class="label">{{ $ordenServicioDatos['lectura_label'] }}</div><div class="value">{{ $ordenServicioDatos['lectura_actual'] !== null ? number_format((float) $ordenServicioDatos['lectura_actual'], 2) : '—' }}</div></div>
            <div class="field"><div class="label">Último servicio equivalente</div><div class="value">{{ $ordenServicioDatos['ultimo_servicio']?->fecha_fin?->format('d/m/Y H:i') ?? $ordenServicioDatos['ultimo_servicio']?->fecha_programada?->format('d/m/Y H:i') ?? 'Sin registro' }}</div></div>
            <div class="field"><div class="label">Mecánico asignado</div><div class="value">{{ $mantenimiento->mecanico ? trim(($mantenimiento->mecanico->Nombre ?? '') . ' ' . ($mantenimiento->mecanico->Apellidos ?? '')) : '—' }}</div></div>
            <div class="field"><div class="label">Fecha programada</div><div class="value">{{ optional($mantenimiento->fecha_programada)->format('d/m/Y H:i') ?: '—' }}</div></div>
        </div>

        <h2>Descripción inicial</h2>
        <div class="description">{{ $mantenimiento->descripcion ?: 'Sin descripción inicial.' }}</div>

        <h2>Materiales sugeridos</h2>
        @if($materialesOrden->isNotEmpty())
            <table>
                <thead><tr><th>Producto</th><th>SKU</th><th>Unidad</th><th class="right">Cantidad</th></tr></thead>
                <tbody>
                    @foreach($materialesOrden as $material)
                        <tr><td>{{ $material->producto_nombre_snapshot ?: 'Producto no disponible' }}</td><td>{{ $material->producto_sku_snapshot ?: '—' }}</td><td>{{ $material->unidad ?: '—' }}</td><td class="right">{{ number_format((float) $material->cantidad_requerida, 3) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="description muted">Este servicio no tiene materiales preventivos configurados. Los materiales deberán capturarse manualmente.</div>
        @endif

        <div class="notice">Esta orden inicia el servicio y no confirma la ejecución ni el surtido de materiales. Las cantidades podrán ajustarse antes de generar la solicitud de abastecimiento.</div>

        <div class="signatures">
            <div class="signature"><div class="signature-label">Solicita</div></div>
            <div class="signature"><div class="signature-label">Autoriza</div></div>
            <div class="signature"><div class="signature-label">Recibe / ejecuta</div></div>
        </div>
    </section>
</body>
</html>
