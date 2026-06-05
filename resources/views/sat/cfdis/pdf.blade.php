<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .box { border: 1px solid #d1d5db; padding: 10px; margin-bottom: 10px; }
        .label { font-weight: bold; color: #374151; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 5px; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>

<div class="title">Representación impresa CFDI</div>

<div class="box">
    <div><span class="label">UUID:</span> {{ $cfdi->uuid }}</div>
    <div><span class="label">Fecha:</span> {{ (string) $xml['Fecha'] }}</div>
    <div><span class="label">Serie/Folio:</span> {{ (string) $xml['Serie'] }} {{ (string) $xml['Folio'] }}</div>
    <div><span class="label">Tipo:</span> {{ (string) $xml['TipoDeComprobante'] }}</div>
</div>

<div class="box">
    <div class="label">Emisor</div>
    <div>{{ (string) $emisor['Nombre'] }}</div>
    <div>RFC: {{ (string) $emisor['Rfc'] }}</div>
    <div>Régimen: {{ (string) $emisor['RegimenFiscal'] }}</div>
</div>

<div class="box">
    <div class="label">Receptor</div>
    <div>{{ (string) $receptor['Nombre'] }}</div>
    <div>RFC: {{ (string) $receptor['Rfc'] }}</div>
    <div>Uso CFDI: {{ (string) $receptor['UsoCFDI'] }}</div>
</div>

<table>
    <thead>
        <tr>
            <th>Cantidad</th>
            <th>Clave</th>
            <th>Descripción</th>
            <th class="right">Importe</th>
        </tr>
    </thead>
    <tbody>
        @foreach($conceptos as $concepto)
            <tr>
                <td>{{ (string) $concepto['Cantidad'] }}</td>
                <td>{{ (string) $concepto['ClaveProdServ'] }}</td>
                <td>{{ (string) $concepto['Descripcion'] }}</td>
                <td class="right">${{ number_format((float) $concepto['Importe'], 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<br>

<div class="box">
    <div class="right">Subtotal: ${{ number_format((float) $xml['SubTotal'], 2) }}</div>
    <div class="right">Total: ${{ number_format((float) $xml['Total'], 2) }}</div>
    <div class="right">Moneda: {{ (string) $xml['Moneda'] }}</div>
</div>

</body>
</html>