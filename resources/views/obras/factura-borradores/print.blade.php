<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Previsualizacion BF-{{ str_pad((string) $borrador->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page { margin: 10mm; size: letter; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f1f5f9;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            line-height: 1.32;
        }
        .toolbar {
            align-items: center;
            background: #0f172a;
            color: #fff;
            display: flex;
            justify-content: space-between;
            padding: 10px 18px;
        }
        .toolbar .hint { color: #cbd5e1; font-size: 12px; }
        .toolbar button {
            background: #facc15;
            border: 0;
            border-radius: 6px;
            color: #111827;
            cursor: pointer;
            font-weight: 700;
            padding: 8px 12px;
        }
        .sheet {
            background: #fff;
            margin: 18px auto;
            max-width: 816px;
            min-height: 1056px;
            padding: 24px 28px;
            position: relative;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .14);
        }
        .test-warning {
            border: 1px solid #f59e0b;
            color: #92400e;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 12px;
            padding: 6px 8px;
            text-align: center;
        }
        .watermark {
            color: rgba(220, 38, 38, .12);
            font-size: 84px;
            font-weight: 900;
            left: 50%;
            letter-spacing: 8px;
            position: absolute;
            text-transform: uppercase;
            top: 48%;
            transform: translate(-50%, -50%) rotate(-28deg);
            white-space: nowrap;
            z-index: 0;
        }
        .content { position: relative; z-index: 1; }
        .top-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: 1.25fr .95fr;
        }
        h1 {
            color: #111827;
            font-size: 24px;
            line-height: 1;
            margin: 0 0 12px;
        }
        h2 {
            color: #374151;
            font-size: 12px;
            margin: 0 0 7px;
            text-transform: uppercase;
        }
        .issuer-name {
            color: #111827;
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .box {
            border: 1px solid #d1d5db;
            padding: 9px;
        }
        .box + .box { margin-top: 10px; }
        .fiscal-box {
            border: 1px solid #cbd5e1;
            display: grid;
            grid-template-columns: 118px 1fr;
        }
        .fiscal-box .label,
        .fiscal-box .value {
            border-bottom: 1px solid #e5e7eb;
            padding: 5px 7px;
        }
        .fiscal-box .label {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
        }
        .fiscal-box .value { font-weight: 700; }
        .fiscal-box .label:nth-last-child(-n+2),
        .fiscal-box .value:nth-last-child(-n+1) { border-bottom: 0; }
        .stamp {
            border: 1px solid #dc2626;
            color: #b91c1c;
            display: inline-block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            margin-top: 8px;
            padding: 4px 7px;
            text-transform: uppercase;
        }
        .receiver {
            margin-top: 14px;
        }
        .receiver-grid {
            display: grid;
            gap: 8px 14px;
            grid-template-columns: 1fr 1fr;
        }
        .field-label { color: #64748b; font-size: 9.5px; font-weight: 700; text-transform: uppercase; }
        .field-value { font-weight: 700; margin-top: 1px; }
        table {
            border-collapse: collapse;
            margin-top: 14px;
            width: 100%;
        }
        th {
            background: #111827;
            border: 1px solid #111827;
            color: #fff;
            font-size: 9.5px;
            padding: 6px 5px;
            text-align: left;
            text-transform: uppercase;
        }
        td {
            border: 1px solid #d1d5db;
            padding: 7px 5px;
            vertical-align: top;
        }
        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        .concept-description { font-size: 10.5px; font-weight: 700; white-space: pre-line; }
        .tax-line { color: #334155; font-size: 9.5px; margin-top: 5px; }
        .bottom-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: 1.1fr 280px;
            margin-top: 13px;
        }
        .payment-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr 1fr;
        }
        .totals {
            border: 1px solid #cbd5e1;
        }
        .total-row {
            display: grid;
            grid-template-columns: 1fr 110px;
        }
        .total-row span {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 8px;
        }
        .total-row span:first-child {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
        }
        .total-row span:last-child {
            font-weight: 800;
            text-align: right;
        }
        .total-row.grand span {
            background: #111827;
            border-bottom: 0;
            color: #fff;
            font-size: 13px;
        }
        .amount-words {
            border: 1px solid #d1d5db;
            margin-top: 10px;
            padding: 8px;
        }
        .seal-section {
            border-top: 1px solid #d1d5db;
            margin-top: 16px;
            padding-top: 10px;
        }
        .seal-title {
            color: #475569;
            font-size: 9.5px;
            font-weight: 800;
            margin: 10px 0 4px;
            text-transform: uppercase;
        }
        .seal-placeholder {
            border: 1px dashed #cbd5e1;
            color: #94a3b8;
            font-size: 9.5px;
            min-height: 32px;
            padding: 7px;
        }
        .construction {
            margin-top: 14px;
        }
        .construction-grid {
            display: grid;
            gap: 6px 12px;
            grid-template-columns: repeat(3, 1fr);
        }
        .footer {
            color: #64748b;
            display: flex;
            justify-content: space-between;
            margin-top: 14px;
            font-size: 9.5px;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet {
                box-shadow: none;
                margin: 0;
                max-width: none;
                min-height: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    @php
        $cliente = $borrador->cliente ?: $obra->cliente;
        $concepto = $borrador->conceptoSat;
        $cantidad = max((float) $borrador->cantidad, 0.000001);
        $precioUnitario = $cantidad > 0 ? (float) $borrador->subtotal / $cantidad : 0;
        $tipoIva = $borrador->tipo_iva_resolved;
        $ivaLabel = $borrador->tipo_iva_label;
        $ivaBase = (float) $borrador->subtotal;
        $folio = 'BF-' . str_pad((string) $borrador->id, 5, '0', STR_PAD_LEFT);
        $estatusLabel = \App\Models\ObraFacturaBorrador::estatusLabels()[$borrador->estatus] ?? ucfirst((string) $borrador->estatus);
        $retencionLabel = $borrador->retencion_tipo && $borrador->retencion_tipo !== 'sin_retencion'
            ? (\App\Models\ObraFacturaBorrador::retencionTipoLabels()[$borrador->retencion_tipo] ?? $borrador->retencion_tipo)
            : 'Sin retencion';
    @endphp

    <div class="toolbar">
        <div>
            <strong>{{ $folio }}</strong>
            <span class="hint">Previsualizacion de borrador administrativo</span>
        </div>
        <button type="button" onclick="window.print()">Imprimir / guardar PDF</button>
    </div>

    <main class="sheet">
        <div class="watermark">Borrador</div>
        <div class="content">
            <div class="test-warning">Este comprobante es una previsualizacion interna de borrador y no tiene validez fiscal.</div>

            <section class="top-grid">
                <div>
                    <h1>Factura</h1>
                    <h2>Emisor</h2>
                    <div class="issuer-name">RIVERA CONSTRUCCIONES</div>
                    <div>RFC: RCO820921T66</div>
                    <div>Regimen Fiscal: 601 - General de Ley Personas Morales</div>
                    <div>Lugar emision: Guadalajara, Jalisco, 44600</div>
                    <div class="stamp">{{ $estatusLabel }} - No timbrado</div>
                </div>

                <div class="fiscal-box">
                    <div class="label">Folio Fiscal</div>
                    <div class="value muted">Pendiente al timbrar</div>
                    <div class="label">Tipo de CFDI</div>
                    <div class="value">I (Ingreso)</div>
                    <div class="label">Version CFDI</div>
                    <div class="value">4.0</div>
                    <div class="label">Serie/Folio</div>
                    <div class="value">{{ $folio }}</div>
                    <div class="label">Fecha emision</div>
                    <div class="value">{{ optional($borrador->fecha)->format('Y-m-d') ?: optional($borrador->created_at)->format('Y-m-d') }}</div>
                    <div class="label">Fecha certific.</div>
                    <div class="value muted">Pendiente</div>
                    <div class="label">Serie CSD emisor</div>
                    <div class="value muted">Pendiente</div>
                </div>
            </section>

            <section class="box receiver">
                <h2>Receptor</h2>
                <div class="receiver-grid">
                    <div>
                        <div class="field-label">Razon social</div>
                        <div class="field-value">{{ $cliente?->razon_social ?: $cliente?->nombre_comercial ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="field-label">RFC</div>
                        <div class="field-value">{{ $cliente?->rfc ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="field-label">Regimen Fiscal</div>
                        <div class="field-value">{{ $regimenesFiscales[$borrador->regimen_fiscal] ?? ($borrador->regimen_fiscal ?: ($cliente?->regimen_fiscal ?: '-')) }}</div>
                    </div>
                    <div>
                        <div class="field-label">Domicilio</div>
                        <div class="field-value">{{ $cliente?->codigo_postal ?: '-' }}, MEX</div>
                    </div>
                    <div>
                        <div class="field-label">Uso del CFDI</div>
                        <div class="field-value">{{ $usosCfdi[$borrador->uso_cfdi] ?? $borrador->uso_cfdi }}</div>
                    </div>
                    <div>
                        <div class="field-label">Obra</div>
                        <div class="field-value">{{ $obra->clave_obra ?: '-' }} - {{ $obra->nombre ?: '-' }}</div>
                    </div>
                </div>
            </section>

            <table>
                <thead>
                    <tr>
                        <th style="width: 96px;">Clave ProdServ</th>
                        <th>Concepto</th>
                        <th class="center" style="width: 55px;">Objeto</th>
                        <th class="right" style="width: 62px;">Cant.</th>
                        <th style="width: 82px;">Unidad</th>
                        <th class="right" style="width: 92px;">Precio Unitario</th>
                        <th class="right" style="width: 88px;">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $concepto?->clave_producto_servicio ?: $concepto?->codigo ?: '84111506' }}</td>
                        <td>
                            <div class="concept-description">{{ $borrador->concepto_descripcion }}</div>
                            <div class="tax-line">
                                {{ $ivaLabel }}, Base: ${{ number_format($ivaBase, 2) }}
                                @if($tipoIva === 'exento')
                                    - Exento
                                @elseif($tipoIva === 'sin_iva')
                                    - No objeto
                                @else
                                    - ${{ number_format((float) $borrador->iva, 6) }}
                                @endif
                            </div>
                        </td>
                        <td class="center">{{ $concepto?->objeto_impuesto ?: ($tipoIva === 'sin_iva' ? '01' : '02') }}</td>
                        <td class="right">{{ number_format($cantidad, 6) }}</td>
                        <td>{{ $concepto?->clave_unidad ?: 'E48' }} - {{ $concepto?->unidad ?: 'SERVICIO' }}</td>
                        <td class="right">${{ number_format($precioUnitario, 6) }}</td>
                        <td class="right">${{ number_format((float) $borrador->subtotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <section class="bottom-grid">
                <div>
                    <div class="payment-grid">
                        <div class="box">
                            <h2>Forma de pago</h2>
                            <div>{{ $borrador->forma_pago ?: '03' }} {{ $formasPagoCfdi[$borrador->forma_pago] ?? 'Transferencia electronica de fondos' }}</div>
                        </div>
                        <div class="box">
                            <h2>Metodo de pago</h2>
                            <div>{{ $borrador->metodo_pago }} {{ $metodosPagoCfdi[$borrador->metodo_pago] ?? '' }}</div>
                        </div>
                        <div class="box">
                            <h2>Exportacion</h2>
                            <div>01</div>
                        </div>
                        <div class="box">
                            <h2>Moneda</h2>
                            <div>MXN</div>
                        </div>
                    </div>
                    <div class="amount-words">
                        <div class="field-label">Importe en letra</div>
                        <div class="field-value">Pendiente de generar al timbrar - Total: ${{ number_format((float) $borrador->total, 2) }} MXN</div>
                    </div>
                </div>

                <div class="totals">
                    <div class="total-row"><span>Subtotal</span><span>${{ number_format((float) $borrador->subtotal, 2) }}</span></div>
                    <div class="total-row"><span>{{ $ivaLabel }}</span><span>${{ number_format((float) $borrador->iva, 2) }}</span></div>
                    <div class="total-row"><span>Retenciones - {{ $retencionLabel }}</span><span>${{ number_format((float) $borrador->retenciones, 2) }}</span></div>
                    <div class="total-row"><span>Descuentos</span><span>${{ number_format((float) $borrador->descuentos, 2) }}</span></div>
                    <div class="total-row grand"><span>Total</span><span>${{ number_format((float) $borrador->total, 2) }}</span></div>
                </div>
            </section>

            @if($borrador->complemento_construccion_activo)
                @php($cc = $borrador->complemento_construccion ?: [])
                <section class="box construction">
                    <h2>Complemento Servicios Parciales de Construccion</h2>
                    <div class="construction-grid">
                        <div><div class="field-label">Permiso / licencia</div><div class="field-value">{{ data_get($cc, 'num_per_lico_aut') ?: '-' }}</div></div>
                        <div><div class="field-label">Calle</div><div class="field-value">{{ data_get($cc, 'calle') ?: '-' }}</div></div>
                        <div><div class="field-label">Codigo postal</div><div class="field-value">{{ data_get($cc, 'codigo_postal') ?: '-' }}</div></div>
                        <div><div class="field-label">No. exterior</div><div class="field-value">{{ data_get($cc, 'no_exterior') ?: '-' }}</div></div>
                        <div><div class="field-label">No. interior</div><div class="field-value">{{ data_get($cc, 'no_interior') ?: '-' }}</div></div>
                        <div><div class="field-label">Colonia</div><div class="field-value">{{ data_get($cc, 'colonia') ?: '-' }}</div></div>
                        <div><div class="field-label">Localidad</div><div class="field-value">{{ data_get($cc, 'localidad') ?: '-' }}</div></div>
                        <div><div class="field-label">Municipio</div><div class="field-value">{{ data_get($cc, 'municipio') ?: '-' }}</div></div>
                        <div><div class="field-label">Estado</div><div class="field-value">{{ \App\Models\ObraFacturaBorrador::estadosSatMexico()[data_get($cc, 'estado')] ?? data_get($cc, 'estado', '-') }}</div></div>
                        <div style="grid-column: 1 / -1;"><div class="field-label">Referencia</div><div class="field-value">{{ data_get($cc, 'referencia') ?: '-' }}</div></div>
                    </div>
                </section>
            @endif

            <section class="seal-section">
                <div class="seal-title">Sello digital del CFDI</div>
                <div class="seal-placeholder">Pendiente al timbrar. Este borrador no contiene sello digital.</div>

                <div class="seal-title">Sello del SAT</div>
                <div class="seal-placeholder">Pendiente al timbrar. Este borrador no contiene certificacion SAT.</div>

                <div class="seal-title">Cadena original del complemento de certificacion digital del SAT</div>
                <div class="seal-placeholder">Pendiente al timbrar.</div>

                <div class="seal-title">Serie del CSD del SAT</div>
                <div class="seal-placeholder">Pendiente al timbrar.</div>
            </section>

            <footer class="footer">
                <span>Este documento es una previsualizacion interna de factura. No es una representacion impresa de un CFDI timbrado.</span>
                <span>Pagina 1 de 1</span>
            </footer>
        </div>
    </main>
</body>
</html>