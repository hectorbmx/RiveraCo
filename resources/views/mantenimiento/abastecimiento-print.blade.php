<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @php
        $tituloSolicitud = $abastecimiento->tipo === 'almacen'
            ? 'Surtido de almacén'
            : 'Solicitud de compras';
        $esSurtidoAlmacen = $abastecimiento->tipo === 'almacen';
    @endphp
    <title>{{ $abastecimiento->folio }} - {{ $tituloSolicitud }}</title>
    <style>
        @page { size: Letter; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { background: #e5e7eb; color: #172033; font-family: Arial, sans-serif; font-size: 12px; margin: 0; }
        .page { background: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, .16); margin: 24px auto; min-height: 11in; padding: .55in; width: 8.5in; }
        h1 { color: #0B265A; font-size: 22px; margin: 0; }
        h2 { color: #0B265A; font-size: 15px; margin: 22px 0 8px; }
        .muted { color: #667085; }
        .brand { display: flex; align-items: center; gap: 10px; color: #0B265A; }
        .brand img { display: block; width: 78px; max-height: 48px; object-fit: contain; }
        .brand-text { display: flex; flex-direction: column; line-height: .9; letter-spacing: 1px; }
        .brand-text .rivera { font-size: 23px; font-weight: 900; }
        .brand-text .construcciones { margin-top: 4px; font-size: 10px; font-weight: 900; letter-spacing: 1.2px; }
        .header { border-bottom: 3px solid #0B265A; display: flex; justify-content: space-between; align-items: end; padding-bottom: 12px; }
        .folio { color: #0B265A; font-size: 15px; font-weight: bold; text-align: right; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 28px; margin-top: 18px; }
        .item { border-bottom: 1px solid #e5e7eb; padding: 6px 0; }
        .label { color: #667085; font-size: 10px; text-transform: uppercase; }
        table { border-collapse: collapse; margin-top: 8px; width: 100%; }
        th { background-color: #0B265A !important; border: 0.5px solid #0B265A; color: #ffffff !important; font-size: 10px; padding: 6px 7px; text-align: left; text-transform: uppercase; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        td { border: 0.5px solid #cbd5e1; padding: 7px; }
        .product-col { width: {{ $esSurtidoAlmacen ? '43%' : '36%' }}; }
        .sku-col { width: {{ $esSurtidoAlmacen ? '17%' : '16%' }}; }
        .unit-col { width: 8%; }
        .quantity-col { width: {{ $esSurtidoAlmacen ? '16%' : '13.333%' }}; }
        .right { text-align: right; }
        .footer { border-top: 1px solid #d0d5dd; margin-top: 40px; padding-top: 10px; }
        @media screen and (max-width: 900px) { .page { margin: 12px; max-width: calc(100vw - 24px); min-height: auto; padding: 28px; width: auto; } }
        @media print { body { background: #fff; } .no-print { display: none; } .page { box-shadow: none; margin: 0; min-height: 11in; padding: .55in; width: 8.5in; } }
    </style>
</head>
<body>
    <div class="no-print" style="position: fixed; bottom: 22px; right: max(22px, calc((100vw - 8.5in) / 2 + 22px)); z-index: 20;">
        <button onclick="window.print()" style="display:inline-flex;align-items:center;gap:7px;background:#0B265A;color:white;border:0;border-radius:6px;padding:8px 14px;font-weight:700;cursor:pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v7H6z" /></svg>
            Imprimir
        </button>
    </div>

    <div class="page">
    <div class="header">
        <div>
            <div class="brand">
                <img src="{{ asset('images/logoAzul.png') }}" alt="Rivera Construcciones" onerror="this.style.display='none';">
                <div class="brand-text">
                    <span class="rivera">RIVERA</span>
                    <span class="construcciones">CONSTRUCCIONES</span>
                </div>
            </div>
            <h1>{{ $tituloSolicitud }}</h1>
            <div class="muted">Materiales requeridos para servicio de mantenimiento</div>
        </div>
        <div class="folio">{{ $abastecimiento->folio }}<br><span class="muted">{{ $abastecimiento->created_at?->format('d/m/Y H:i') }}</span></div>
    </div>

    <div class="grid">
        <div class="item"><div class="label">Máquina</div>{{ $abastecimiento->mantenimiento->maquina ? trim(($abastecimiento->mantenimiento->maquina->nombre ?? '—') . ($abastecimiento->mantenimiento->maquina->codigo ? ' · ' . $abastecimiento->mantenimiento->maquina->codigo : '')) : '—' }}</div>
        <div class="item"><div class="label">Tipo de solicitud</div>{{ $tituloSolicitud }}</div>
        <div class="item"><div class="label">Servicio</div>{{ $abastecimiento->mantenimiento->servicioPreventivoTipo?->nombre ?? '—' }}</div>
        <div class="item"><div class="label">Estado</div>{{ ucfirst(str_replace('_', ' ', $abastecimiento->estado)) }}</div>
    </div>

    <h2>Materiales solicitados</h2>
    <table>
        <thead>
            <tr>
                <th class="product-col">Producto</th>
                <th class="sku-col">SKU</th>
                <th class="unit-col">Unidad</th>
                <th class="quantity-col right">Cantidad requerida</th>
                @if($esSurtidoAlmacen)
                    <th class="quantity-col right">Cantidad surtida</th>
                @else
                    <th class="quantity-col right">Cantidad disponible</th>
                    <th class="quantity-col right">Mínimo a comprar</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($abastecimiento->detalles as $detalle)
                @php($existencia = $existencias->get($detalle->id, ['disponible' => 0, 'minimo_compra' => (float) $detalle->cantidad_solicitada]))
                <tr>
                    <td>{{ $detalle->producto_nombre_snapshot }}</td>
                    <td>{{ $detalle->producto_sku_snapshot ?: '—' }}</td>
                    <td>{{ $detalle->unidad ?: '—' }}</td>
                    <td class="right">{{ number_format((float) $detalle->cantidad_solicitada, 3) }}</td>
                    @if($esSurtidoAlmacen)
                        <td class="right" style="height: 34px;">&nbsp;</td>
                    @else
                        <td class="right">{{ number_format((float) $existencia['disponible'], 3) }}</td>
                        <td class="right">{{ number_format((float) $existencia['minimo_compra'], 3) }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $esSurtidoAlmacen ? 5 : 6 }}">No hay partidas registradas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer muted">
        @if($esSurtidoAlmacen)
            Documento recuperable por folio. La cantidad surtida se captura como evidencia física del almacén.
        @else
            Documento recuperable por folio. La impresión no descuenta inventario ni confirma la compra.
        @endif
    </div>
    </div>
</body>
</html>
