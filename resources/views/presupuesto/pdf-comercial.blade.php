<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Presupuesto {{ $cabecera['folio'] }}</title>
    <style>
        @page { margin: 24pt 36pt 36pt; }
        body { margin: 0; font: 7.8pt/1.18 "DejaVu Sans", sans-serif; color: #1b2134; }
        table { width: 100%; border-collapse: collapse; }
        .membrete td, .ficha td, .cierre td { vertical-align: top; }
        .membrete { width: 612pt; margin: -24pt -36pt 0; background: #1c2781; color: #fff; }
        .membrete td { height: 69pt; vertical-align: middle; padding: 0; }
        .membrete .marca { width: 34pt; padding: 0 8pt 0 36pt; }
        .marca img { display: block; }
        .membrete .empresa { padding-right: 36pt; font-size: 6.8pt; line-height: 1.25; }
        .membrete .tipo-presupuesto { width: 57.2pt; padding: 0 36pt 0 6pt; text-align: right; line-height: 0; }
        .tipo-presupuesto img { max-width: 57.2pt; max-height: 57.2pt; vertical-align: middle; }
        .empresa strong { display: block; font-size: 13pt; line-height: 1.15; margin-bottom: 2pt; }
        .ficha { margin-top: 6pt; table-layout: fixed; font-size: 7pt; line-height: 1.16; color: #505a70; }
        .ficha .ficha-cliente { width: 68%; vertical-align: top; }
        .ficha .derecha { width: 32%; vertical-align: top; }
        .ficha .fecha { margin-bottom: 2pt; }
        .ficha .datos-cliente { padding: 2pt 10pt 0 0; font-size: 6.8pt; overflow-wrap: break-word; }
        .derecha { text-align: right; }
        .rotulo { color: #1c2781; font-size: 7.5pt; font-weight: bold; letter-spacing: 1.3pt; }
        .cliente, .identificador { line-height: 1.2; color: #1b2134; font-weight: bold; overflow-wrap: break-word; }
        .cliente { font-size: 14pt; margin-bottom: 2pt; }
        .identificador { font-size: 12pt; }
        .conceptos { margin-top: 8pt; table-layout: fixed; }
        .conceptos thead { display: table-header-group; }
        .conceptos thead th { background: #1c2781; color: #fff; padding: 4pt; font-size: 7.3pt; letter-spacing: .6pt; }
        .conceptos td { padding: 2.5pt 4pt; vertical-align: top; border-bottom: .5pt solid #dce1ef; }
        .conceptos td:first-child { overflow-wrap: break-word; }
        .conceptos tr, .notas tr { page-break-inside: avoid; }
        .conceptos tbody tr:nth-child(even) { background: #eff2fa; }
        .conceptos .encabezado-perforacion td { color: #1c2781; font-weight: bold; }
        .conceptos .encabezado-partida { page-break-after: avoid; }
        .conceptos .subtotal-partida { page-break-before: avoid; }
        .conceptos .subtotal-partida td { background: #e8edf8; color: #1c2781; font-weight: bold; }
        .partida { color: #1c2781; font-weight: bold; padding-right: 4pt; }
        .cantidad { text-align: right; white-space: nowrap; }
        .centro { text-align: center; }
        .conceptos .subrenglon { padding-left: 17pt; }
        .importe { font-weight: bold; }
        .cierre { margin-top: 6pt; page-break-inside: avoid; }
        .datos-ejecucion { padding-right: 10pt; }
        .cierre p { margin: 0 8pt 3pt 0; }
        .anticipo { color: #a41111; font-weight: bold; }
        .totales td { padding: 2pt 8pt; color: #505a70; }
        .totales .total td { padding: 4pt 8pt; font-size: 9pt; font-weight: bold; color: #fff; background: #1c2781; }
        .nota-ejecucion { margin: 3pt 0 0; font-size: 6.7pt; line-height: 1.15; overflow-wrap: break-word; }
        .nota-ejecucion p, .nota-ejecucion ol, .nota-ejecucion ul { margin: 0 0 1pt; }
        .nota-ejecucion ol, .nota-ejecucion ul { padding-left: 11pt; }
        .nota-ejecucion h2 { font-size: 7pt; margin: 0 0 2pt; page-break-after: avoid; }
        .notas h2 { color: #1c2781; border-bottom: 1pt solid #1c2781; margin: 0 0 4pt; padding-bottom: 3pt; }
        .notas table { margin-top: 8pt; table-layout: fixed; font-size: 6.4pt; line-height: 1.17; color: #333d54; }
        .notas thead { display: table-header-group; }
        .notas th { text-align: left; padding: 0; }
        .notas td { width: 50%; vertical-align: top; padding: .5pt 7pt .5pt 11pt; text-indent: -8pt; }
        .notas .nota-fija { width: 100%; }
        @if($compacto ?? false)
            @page { margin: 24pt 30pt 30pt; }
            .membrete { margin-left: -30pt; margin-right: -30pt; }
            .membrete td { height: 60pt; }
            .membrete .marca { padding-left: 30pt; }
            .membrete .empresa { padding-right: 30pt; }
            .membrete .tipo-presupuesto { padding-right: 30pt; }
            .ficha { margin-top: 5pt; }
            .conceptos { margin-top: 6pt; font-size: 7.2pt; line-height: 1.12; }
            .conceptos td { padding-top: 1.6pt; padding-bottom: 1.6pt; }
            .cierre { margin-top: 4pt; }
            .nota-ejecucion { font-size: 6.4pt; }
            .notas table { margin-top: 6pt; font-size: 6.2pt; line-height: 1.12; }
        @endif
    </style>
</head>
<body>
@php
    $moneda = fn ($valor) => '$' . number_format($valor, 2);
    $decimal = fn ($valor) => rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    $presentacion += array_fill_keys(['domicilio', 'correo', 'telefono', 'atencion', 'ubicacion', 'anticipo', 'condiciones_pago', 'plazo_semanas', 'tasa_iva', 'notas'], null);
    $datosCliente = collect([
        $presentacion['atencion'] ? 'Atención ' . $presentacion['atencion'] : null,
        ...preg_split('/\R/u', (string) $presentacion['domicilio']),
        $presentacion['correo'],
        $presentacion['telefono'] ? 'Tel. ' . $presentacion['telefono'] : null,
        $cabecera['referencia'] ? 'Referencia ' . $cabecera['referencia'] : null,
        $presentacion['ubicacion'] ? 'Ubicación ' . $presentacion['ubicacion'] : null,
        !isset($presentacion['tasa_iva']) && $cabecera['equipo'] ? 'Equipo propuesto ' . $cabecera['equipo'] : null,
    ])->map(fn ($dato) => trim((string) $dato))->filter()->implode(' · ');
    $operacionesNota = $presentacion['nota_ejecucion'] ?? [];
    $textoNota = implode('', array_column($operacionesNota, 'insert'));
    $porPartidas = isset($presentacion['partidas']);
    $notaEnCierre = !$porPartidas && mb_strlen($textoNota) <= 1500 && substr_count($textoNota, "\n") <= 35;
@endphp
<table class="membrete">
    <tr>
        <td class="marca"><img src="{{ public_path('images/logo.png') }}" width="44" alt="Rivera Construcciones"></td>
        <td class="empresa">
            <strong>RIVERA CONSTRUCCIONES, S.A. de C.V.</strong>
            Pilas de cimentación y pozos de infiltración<br>
            Justo Sierra No. 2469 · C.P. 44600 · Guadalajara, Jalisco · pilas@riveraco.com.mx<br>
            Tel. 33 3615 0741 · 33 3630 1056 · 33 3616 8364
        </td>
        @if(!empty($cabecera['imagen_tipo']))
            <td class="tipo-presupuesto"><img src="{{ $cabecera['imagen_tipo'] }}" alt=""></td>
        @endif
    </tr>
</table>
<table class="ficha">
    <tr>
        <td class="ficha-cliente">
            <div class="cliente">{{ $cabecera['cliente'] }}</div>
            @if($datosCliente !== '')
                <div class="datos-cliente">{{ $datosCliente }}</div>
            @endif
        </td>
        <td class="derecha">
            <div class="fecha">{{ $cabecera['fecha'] }}</div>
            <div class="identificador">{{ $cabecera['folio'] }}</div>
            @if($cabecera['obra'])<div class="identificador">{{ $cabecera['obra'] }}</div>@endif
        </td>
    </tr>
</table>

<table class="conceptos">
    <thead>
        <tr>
            <th scope="col" style="width:58%;text-align:left;">CONCEPTO</th>
            <th scope="col" style="width:8%;">UNIDAD</th>
            <th scope="col" class="cantidad" style="width:10%;">CANT.</th>
            <th scope="col" class="cantidad" style="width:10%;">P.U.</th>
            <th scope="col" class="cantidad" style="width:14%;">IMPORTE</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($presentacion['partidas']))
        @foreach($presentacion['partidas'] as $partida)
            <tr class="encabezado-perforacion encabezado-partida"><td colspan="5"><span class="partida">{{ $partida['clave'] }}.</span>{{ $partida['nombre'] }}</td></tr>
            @foreach($partida['subpartidas'] as $fila)
                <tr>
                    <td class="subrenglon"><span class="partida">{{ $fila['numero'] }}.</span>{!! nl2br(e($fila['concepto'])) !!}</td>
                    <td class="centro">{{ $fila['unidad'] }}</td>
                    <td class="cantidad">{{ number_format($fila['cantidad'], 2) }}</td>
                    <td class="cantidad">{{ $moneda($fila['precio_unitario']) }}</td>
                    <td class="cantidad importe">{{ $moneda($fila['importe']) }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-partida"><td colspan="4">Subtotal {{ $partida['nombre'] }}</td><td class="cantidad">{{ $moneda($partida['subtotal']) }}</td></tr>
        @endforeach
        @else
        @forelse($presentacion['filas'] as $fila)
            @if($fila['numero_encabezado'] !== null)
                <tr class="encabezado-perforacion"><td colspan="5"><span class="partida">{{ $fila['numero_encabezado'] }}</span>{!! nl2br(e($presentacion['descripcion_perforacion'])) !!}</td></tr>
            @endif
            <tr>
                <td @class(['subrenglon' => $fila['partida'] === 'perforacion'])><span class="partida">{{ $fila['numero'] }}{{ $fila['partida'] === 'perforacion' ? ')' : '' }}</span>{!! nl2br(e($fila['concepto'])) !!}</td>
                <td class="centro">{{ $fila['unidad'] }}</td>
                <td class="cantidad">{{ number_format($fila['cantidad'], 2) }}</td>
                <td class="cantidad">{{ $moneda($fila['precio_unitario']) }}</td>
                <td class="cantidad importe">{{ $moneda($fila['importe']) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Sin partidas cobrables</td></tr>
        @endforelse
        @endif
    </tbody>
</table>
<table class="cierre">
    <tr>
        <td class="datos-ejecucion">
            @if($presentacion['anticipo'] !== null && empty($presentacion['anticipo_y_plazo_en_notas']))
                <p class="anticipo">Anticipo del {{ $decimal($presentacion['anticipo']) }} %.</p>
            @endif
            @if($presentacion['condiciones_pago'])<p>{!! nl2br(e($presentacion['condiciones_pago'])) !!}</p>@endif
            @if($presentacion['plazo_semanas'] !== null && empty($presentacion['anticipo_y_plazo_en_notas']))
                <p><strong>Periodo de ejecución</strong> {{ $decimal($presentacion['plazo_semanas']) }} semanas aproximadamente.</p>
            @endif
            @if($operacionesNota && $notaEnCierre)
                <section class="nota-ejecucion">
                    <h2>Notas de ejecución</h2>
                    {!! \App\Support\PresentacionPresupuesto::htmlNotaEjecucion($operacionesNota) !!}
                </section>
            @endif
        </td>
        <td style="width: 186pt;">
            <table class="totales">
                @if(isset($presentacion['tasa_iva']))
                    <tr><td>Subtotal</td><td class="cantidad">{{ $moneda($presentacion['subtotal']) }}</td></tr>
                    <tr><td>IVA {{ $decimal($presentacion['tasa_iva']) }} %</td><td class="cantidad">{{ $moneda($presentacion['iva']) }}</td></tr>
                @endif
                <tr class="total"><td>{{ isset($presentacion['tasa_iva']) ? 'Total' : 'Total de venta' }}</td><td class="cantidad">{{ $moneda($presentacion['total']) }}</td></tr>
            </table>
        </td>
    </tr>
</table>
@if($operacionesNota && !$notaEnCierre)
    <section class="nota-ejecucion">
        <h2>{{ $porPartidas ? 'Notas adicionales' : 'Notas de ejecución' }}</h2>
        {!! \App\Support\PresentacionPresupuesto::htmlNotaEjecucion($operacionesNota) !!}
    </section>
@endif
@if($presentacion['notas'])
    <div class="notas">
        @php
            $notas = array_values($presentacion['notas']);
            $notaFija = !empty($presentacion['nota_fija']) ? array_shift($notas) : null;
            $inicio = $notaFija === null ? 1 : 2;
            $mitad = (int) ceil(count($notas) / 2);
        @endphp
        <table>
            <thead><tr><th colspan="2"><h2 class="rotulo">NOTAS IMPORTANTES</h2></th></tr></thead>
            <tbody>
            @if($notaFija !== null)
                <tr><td colspan="2" class="nota-fija">1. {!! nl2br(e($notaFija)) !!}</td></tr>
            @endif
            @foreach(array_slice($notas, 0, $mitad) as $indice => $nota)
                <tr>
                    <td>{{ $indice + $inicio }}. {!! nl2br(e($nota)) !!}</td>
                    <td>@isset($notas[$indice + $mitad]){{ $indice + $mitad + $inicio }}. {!! nl2br(e($notas[$indice + $mitad])) !!}@endisset</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
</body>
</html>
