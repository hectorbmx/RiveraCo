<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Vista previa de factura</title>
    <style>
        @page { margin: 30pt 32pt 34pt; }
        body { margin: 0; color: #172639; font-family: "DejaVu Sans", sans-serif; font-size: 10pt; line-height: 12pt; }
        h1 { margin: 0 0 10pt; font-size: 21pt; line-height: 25pt; }
        h2 { margin: 12pt 0 4pt; font-size: 10pt; color: #173a70; }
        p { margin: 0 0 3pt; }
        table { border-collapse: collapse; width: 100%; }
        th, td { vertical-align: top; }
        .heading td { width: 50%; }
        .status { color: #835419; text-align: right; font-size: 9pt; font-weight: bold; }
        .receptor { padding: 7pt 9pt; background: #f0f4f9; }
        .items { margin-top: 14pt; }
        .items thead { display: table-header-group; }
        .items th { background: #173a70; color: white; text-align: left; font-size: 8pt; }
        .items th, .items td { padding: 6pt 4pt; }
        .items td { border-bottom: 1px solid #dce4ef; }
        .num, .items th.num { text-align: right; white-space: nowrap; }
        .items td.num { font-size: 9pt; }
        .descripcion { width: 270pt; overflow-wrap: break-word; }
        .meta { color: #445b76; font-size: 8pt; }
        .items .meta { margin-top: 4pt; }
        .totales { width: 44%; margin: 12pt 0 0 auto; page-break-inside: avoid; }
        .totales th, .totales td { padding: 4pt; }
        .totales th { text-align: left; }
        .total { background: #e8eff8; }
        .bloque { page-break-inside: avoid; }
    </style>
</head>
<body>
@php
    $receptor = $payload['customer'];
    $moneda = $payload['currency'] ?? 'MXN';
    $dinero = fn ($valor) => '$' . number_format($valor, 2);
    $cantidad = fn ($valor) => rtrim(rtrim(number_format($valor, 6, '.', ','), '0'), '.');
@endphp
<table class="heading">
    <tr>
        <td><h1>Vista previa de factura</h1></td>
        <td class="status">BORRADOR · SIN TIMBRAR</td>
    </tr>
    <tr>
        <td>
            <h2>Emisor</h2>
            <p><strong>{{ $empresa->nombre }}</strong></p>
            <p>RFC {{ $empresa->rfc }}</p>
        </td>
        <td>
            <h2>Datos del comprobante</h2>
            <p>CFDI 4.0 · Ingreso</p>
            <p>Método de pago {{ $payload['payment_method'] }} · Forma de pago {{ $payload['payment_form'] }}</p>
            <p>Moneda {{ $moneda }}</p>
        </td>
    </tr>
</table>
<h2>Receptor</h2>
<div class="receptor">
    <p><strong>{{ $receptor['legal_name'] }}</strong></p>
    <p>RFC {{ $receptor['tax_id'] }} · Régimen fiscal {{ $receptor['tax_system'] }}</p>
    <p>C.P. {{ $receptor['address']['zip'] }} · Uso del CFDI {{ $payload['use'] }}</p>
</div>
<table class="items">
    <thead>
        <tr>
            <th style="width: 61pt; white-space: nowrap">Clave ProdServ</th>
            <th style="width: 270pt">Concepto</th>
            <th class="num" style="width: 60pt">Cant.</th>
            <th style="width: 32pt">Unidad</th>
            <th class="num" style="width: 82pt">Importe</th>
        </tr>
    </thead>
    <tbody>
        @foreach($conceptos as $concepto)
            <tr>
                <td>{{ $concepto['product']['product_key'] }}</td>
                <td>
                    <div class="descripcion">{!! nl2br(e($concepto['product']['description'])) !!}</div>
                    @if($concepto['discount'] > 0)
                        <p class="meta">Descuento {{ $dinero($concepto['discount']) }}</p>
                    @endif
                    @foreach($concepto['impuestos'] as $impuesto)
                        <p class="meta">
                            @if(($impuesto['factor'] ?? '') === 'Exento')
                                {{ $impuesto['type'] }} exento
                            @else
                                {{ $impuesto['type'] }} {{ $cantidad(100 * $impuesto['rate']) }} % sobre {{ $dinero($concepto['base']) }} · {{ $dinero($impuesto['importe']) }}
                            @endif
                        </p>
                    @endforeach
                </td>
                <td class="num">{{ $cantidad($concepto['quantity']) }}</td>
                <td>{{ $concepto['product']['unit_key'] }}</td>
                <td class="num">{{ $dinero($concepto['importe']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<table class="totales">
    <tr><th>Subtotal</th><td class="num">{{ $dinero($subtotal) }}</td></tr>
    @if($descuento > 0)
        <tr><th>Descuento</th><td class="num">{{ $dinero($descuento) }}</td></tr>
    @endif
    <tr><th>IVA</th><td class="num">{{ $dinero($iva) }}</td></tr>
    <tr class="total"><th>Total estimado {{ $moneda }}</th><td class="num">{{ $dinero($subtotal - $descuento + $iva) }}</td></tr>
</table>
@foreach($payload['related_documents'] ?? [] as $relacion)
    <div class="bloque">
        <h2>CFDI relacionados · Tipo {{ $relacion['relationship'] }}</h2>
        @foreach($relacion['documents'] as $uuid)
            <p>{{ $uuid }}</p>
        @endforeach
    </div>
@endforeach
@if($complemento)
    <div class="bloque">
        <h2>Complemento de Servicios Parciales de Construcción</h2>
        <p>Permiso, licencia o autorización {{ $complemento['num_per_lico_aut'] }}</p>
        <p>{{ implode(', ', array_filter(\Illuminate\Support\Arr::only($complemento, ['calle', 'no_exterior', 'no_interior', 'colonia', 'localidad', 'municipio', 'estado', 'codigo_postal', 'referencia']))) }}</p>
    </div>
@endif
</body>
</html>
