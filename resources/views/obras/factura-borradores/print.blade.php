<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Borrador de factura #{{ $borrador->id }}</title>
    <style>
        @page { margin: 16mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #172033;
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .toolbar button {
            border: 0;
            border-radius: 6px;
            background: #0B265A;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
            padding: 8px 12px;
        }
        .page { padding: 24px; }
        .header {
            align-items: flex-start;
            border-bottom: 3px solid #0B265A;
            display: flex;
            justify-content: space-between;
            padding-bottom: 14px;
        }
        h1 {
            color: #0B265A;
            font-size: 22px;
            margin: 0 0 4px;
        }
        .muted { color: #64748b; }
        .badge {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 999px;
            color: #92400e;
            display: inline-block;
            font-weight: 700;
            padding: 4px 10px;
        }
        .grid {
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr 1fr;
            margin-top: 18px;
        }
        .box {
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            padding: 12px;
        }
        .box h2 {
            color: #0B265A;
            font-size: 13px;
            margin: 0 0 10px;
            text-transform: uppercase;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 4px 0;
        }
        .label { color: #64748b; }
        table {
            border-collapse: collapse;
            margin-top: 18px;
            width: 100%;
        }
        th {
            background: #0B265A;
            color: #fff;
            font-size: 11px;
            padding: 8px;
            text-align: left;
        }
        td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px;
            vertical-align: top;
        }
        .right { text-align: right; }
        .totals {
            margin-left: auto;
            margin-top: 18px;
            width: 280px;
        }
        .totals .row {
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 0;
        }
        .totals .total {
            color: #0B265A;
            font-size: 16px;
            font-weight: 700;
        }
        .signatures {
            display: grid;
            gap: 40px;
            grid-template-columns: 1fr 1fr;
            margin-top: 64px;
        }
        .signature {
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            text-align: center;
        }
        .signature .name {
            color: #172033;
            font-weight: 700;
            margin-top: 6px;
        }
        .signature .role {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }
        @media print {
            .toolbar { display: none; }
            .page { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir / guardar PDF</button>
    </div>

    <main class="page">
        <section class="header">
            <div>
                <h1>Borrador de factura</h1>
                <div class="muted">Folio interno: BF-{{ str_pad((string) $borrador->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="muted">Fecha: {{ optional($borrador->fecha)->format('d/m/Y') }}</div>
            </div>
            <div class="right">
                <span class="badge">{{ \App\Models\ObraFacturaBorrador::estatusLabels()[$borrador->estatus] ?? ucfirst($borrador->estatus) }}</span>
                <div class="muted" style="margin-top: 8px;">Generado: {{ optional($borrador->created_at)->format('d/m/Y H:i') }}</div>
            </div>
        </section>

        <section class="grid">
            <div class="box">
                <h2>Obra</h2>
                <div class="row"><span class="label">Clave</span><strong>{{ $obra->clave_obra ?: '-' }}</strong></div>
                <div class="row"><span class="label">Nombre</span><strong>{{ $obra->nombre ?: '-' }}</strong></div>
                <div class="row"><span class="label">Tipo</span><span>{{ $obra->tipo_obra ?: '-' }}</span></div>
            </div>

            <div class="box">
                <h2>Cliente</h2>
                <div class="row"><span class="label">Razon social</span><strong>{{ $borrador->cliente?->razon_social ?: $borrador->cliente?->nombre_comercial ?: '-' }}</strong></div>
                <div class="row"><span class="label">RFC</span><span>{{ $borrador->cliente?->rfc ?: '-' }}</span></div>
                <div class="row"><span class="label">Regimen</span><span>{{ $regimenesFiscales[$borrador->regimen_fiscal] ?? ($borrador->regimen_fiscal ?: '-') }}</span></div>
            </div>
        </section>

        <section class="grid">
            <div class="box">
                <h2>Datos fiscales</h2>
                <div class="row"><span class="label">Uso CFDI</span><span>{{ $usosCfdi[$borrador->uso_cfdi] ?? $borrador->uso_cfdi }}</span></div>
                <div class="row"><span class="label">Metodo de pago</span><span>{{ $metodosPagoCfdi[$borrador->metodo_pago] ?? $borrador->metodo_pago }}</span></div>
                <div class="row"><span class="label">Forma de pago</span><span>{{ $formasPagoCfdi[$borrador->forma_pago] ?? ($borrador->forma_pago ?: '-') }}</span></div>
            </div>

            <div class="box">
                <h2>Control interno</h2>
                <div class="row"><span class="label">Solicitado por</span><span>{{ $borrador->creador?->name ?: '-' }}</span></div>
                <div class="row"><span class="label">Autorizado por</span><span>{{ $borrador->autorizador?->name ?: 'Pendiente' }}</span></div>
                <div class="row"><span class="label">Fecha autorizacion</span><span>{{ optional($borrador->autorizado_at)->format('d/m/Y H:i') ?: '-' }}</span></div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Concepto SAT</th>
                    <th>Descripcion</th>
                    <th class="right">Cantidad</th>
                    <th class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $borrador->conceptoSat?->codigo ?: $borrador->conceptoSat?->clave_producto_servicio ?: '-' }}</td>
                    <td>{{ $borrador->concepto_descripcion }}</td>
                    <td class="right">{{ number_format((float) $borrador->cantidad, 6) }}</td>
                    <td class="right">${{ number_format((float) $borrador->subtotal, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <section class="totals">
            <div class="row"><span>Subtotal</span><strong>${{ number_format((float) $borrador->subtotal, 2) }}</strong></div>
            <div class="row"><span>IVA{{ $borrador->iva_tasa !== null ? ' (' . rtrim(rtrim(number_format((float) $borrador->iva_tasa * 100, 4), '0'), '.') . '%)' : '' }}</span><strong>${{ number_format((float) $borrador->iva, 2) }}</strong></div>
            <div class="row"><span>Retenciones{{ $borrador->retencion_tipo && $borrador->retencion_tipo !== 'sin_retencion' ? ' - ' . (\App\Models\ObraFacturaBorrador::retencionTipoLabels()[$borrador->retencion_tipo] ?? $borrador->retencion_tipo) : '' }}</span><strong>${{ number_format((float) $borrador->retenciones, 2) }}</strong></div>
            <div class="row"><span>Descuentos</span><strong>${{ number_format((float) $borrador->descuentos, 2) }}</strong></div>
            <div class="row total"><span>Total</span><span>${{ number_format((float) $borrador->total, 2) }}</span></div>
        </section>

        <section class="signatures">
            <div class="signature">
                Solicita
                @if($borrador->estatus === \App\Models\ObraFacturaBorrador::ESTATUS_AUTORIZADO)
                    <div class="name">{{ $borrador->creador?->name ?: '-' }}</div>
                    <div class="role">Solicitante</div>
                @endif
            </div>
            <div class="signature">
                Autoriza
                @if($borrador->estatus === \App\Models\ObraFacturaBorrador::ESTATUS_AUTORIZADO)
                    <div class="name">{{ $borrador->autorizador?->name ?: '-' }}</div>
                    <div class="role">Autorizador</div>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
