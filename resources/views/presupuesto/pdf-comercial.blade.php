<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Presupuesto {{ $cabecera['folio'] }}</title>
    <style>
        @page { margin: 24pt 28pt 32pt; }
        body { margin: 0; font: 7.5pt/1.12 "DejaVu Sans", sans-serif; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        .membrete td, .ficha td, .cierre td { vertical-align: top; }
        .empresa { color: #102b73; text-align: center; font-size: 6.7pt; }
        .empresa strong { display: block; font-weight: normal; font-size: 13pt; margin-bottom: 3pt; }
        .marca { width: 92pt; text-align: right; }
        .perforadora { width: 47pt; }
        .ficha { margin-top: 3pt; font-size: 7.2pt; }
        .ficha td { width: 50%; }
        .derecha { text-align: right; }
        .cliente { font-size: 11pt; font-weight: bold; }
        .conceptos { margin-top: 7pt; table-layout: fixed; }
        .conceptos thead { display: table-header-group; }
        .conceptos thead th { border-top: 1pt solid #111; border-bottom: 1pt solid #111; padding: 2pt 3pt; font-size: 7.4pt; }
        .conceptos td { padding: 2pt 3pt; vertical-align: top; }
        .conceptos tr, .notas li { page-break-inside: avoid; }
        .conceptos td:first-child { text-align: center; font-weight: bold; }
        .cantidad { text-align: right; white-space: nowrap; }
        .centro { text-align: center; }
        .subrenglon { padding-left: 8pt !important; }
        .cierre { margin-top: 7pt; page-break-inside: avoid; }
        .cierre p { margin: 0 8pt 3pt 0; }
        .anticipo { color: #a41111; font-weight: bold; }
        .totales td { padding: 1pt 0 2pt 5pt; }
        .totales .total { font-weight: bold; border-top: .6pt solid #111; }
        .notas h2 { text-align: center; border: 1pt solid #111; font-size: 7.9pt; letter-spacing: 1.8pt; margin: 8pt 0 3pt; page-break-after: avoid; }
        .notas ol { margin: 0; padding-left: 26pt; font-size: 6.7pt; line-height: 1.1; }
        .notas li { padding-left: 3pt; margin-bottom: 0; }
    </style>
</head>
<body>
@php
    $moneda = fn ($valor) => '$' . number_format($valor, 2);
    $decimal = fn ($valor) => rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
@endphp
<table class="membrete">
    <tr>
        <td class="perforadora" rowspan="2"><img src="{{ public_path('images/presupuesto-perforadora.png') }}" height="105" alt=""></td>
        <td class="empresa">
            <strong>RIVERA CONSTRUCCIONES, S.A. de C.V.</strong>
            Justo Sierra No. 2469 · C.P. 44600 · Guadalajara, Jalisco, México<br>
            Tel. 33 3615 0741 · 33 3630 1056 · 33 3616 8364<br>
            pilas@riveraco.com.mx<br>
            PILAS DE CIMENTACIÓN Y POZOS DE INFILTRACIÓN
        </td>
        <td class="marca"><img src="{{ public_path('images/logoAzul.png') }}" width="88" alt="Rivera Construcciones"></td>
    </tr>
    <tr>
        <td colspan="2">
            <table class="ficha">
                <tr>
                    <td>
                        <span class="cliente">{{ $cabecera['cliente'] }}</span>
                        @if($presentacion['domicilio'])
                            <br>{!! nl2br(e($presentacion['domicilio'])) !!}
                        @endif
                        @if($presentacion['correo'])<br>{{ $presentacion['correo'] }}@endif
                        @if($presentacion['telefono'])<br>Tel. {{ $presentacion['telefono'] }}@endif
                    </td>
                    <td class="derecha">
                        {{ $cabecera['fecha'] }}<br>
                        <strong>Presupuesto {{ $cabecera['folio'] }}</strong>
                        @if($cabecera['referencia'])<br>Referencia {{ $cabecera['referencia'] }}@endif
                        @if($presentacion['atencion'])<br>Atención {{ $presentacion['atencion'] }}@endif
                        @if($cabecera['obra'])<br>Obra {{ $cabecera['obra'] }}@endif
                        @if($presentacion['ubicacion'])<br>Ubicación {{ $presentacion['ubicacion'] }}@endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="conceptos">
    <thead>
        <tr>
            <th style="width:4%;" aria-label="Número"></th>
            <th style="width:53%;">C O N C E P T O</th>
            <th style="width:8%;">UNIDAD</th>
            <th class="cantidad" style="width:10%;">CANT.</th>
            <th class="cantidad" style="width:11%;">P.U.</th>
            <th class="cantidad" style="width:14%;">IMPORTE</th>
        </tr>
    </thead>
    <tbody>
        @forelse($presentacion['filas'] as $fila)
            @if($fila['numero_encabezado'] !== null)
                <tr><td>{{ $fila['numero_encabezado'] }})</td><td colspan="5">{!! nl2br(e($presentacion['descripcion_perforacion'])) !!}</td></tr>
            @endif
            <tr>
                <td>{{ $fila['numero'] }})</td>
                <td @class(['subrenglon' => $fila['partida'] === 'perforacion'])>{!! nl2br(e($fila['concepto'])) !!}</td>
                <td class="centro">{{ $fila['unidad'] }}</td>
                <td class="cantidad">{{ number_format($fila['cantidad'], 2) }}</td>
                <td class="cantidad">{{ $moneda($fila['precio_unitario']) }}</td>
                <td class="cantidad">{{ $moneda($fila['importe']) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Sin partidas cobrables</td></tr>
        @endforelse
    </tbody>
</table>
<table class="cierre">
    <tr>
        <td>
            @if($presentacion['anticipo'] !== null)
                <p class="anticipo">Anticipo del {{ $decimal($presentacion['anticipo']) }} %.</p>
            @endif
            @if($presentacion['condiciones_pago'])<p>{!! nl2br(e($presentacion['condiciones_pago'])) !!}</p>@endif
            @if($presentacion['plazo_semanas'] !== null)
                <p><strong>Periodo de ejecución</strong> {{ $decimal($presentacion['plazo_semanas']) }} semanas aproximadamente.</p>
            @endif
        </td>
        <td style="width: 155pt;">
            <table class="totales">
                <tr><td class="derecha">Subtotal</td><td class="cantidad">{{ $moneda($presentacion['subtotal']) }}</td></tr>
                <tr><td class="derecha">IVA {{ $decimal($presentacion['tasa_iva']) }} %</td><td class="cantidad">{{ $moneda($presentacion['iva']) }}</td></tr>
                <tr class="total"><td class="derecha">Total</td><td class="cantidad">{{ $moneda($presentacion['total']) }}</td></tr>
            </table>
        </td>
    </tr>
</table>
@if($presentacion['notas'])
    <div class="notas">
        <h2>NOTAS IMPORTANTES</h2>
        <ol>
            @foreach($presentacion['notas'] as $nota)
                <li>{{ $nota }}</li>
            @endforeach
        </ol>
    </div>
@endif
</body>
</html>
