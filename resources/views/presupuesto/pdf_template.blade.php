<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Presupuesto {{ $cabecera['folio'] }}</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; line-height: 1.3; color: #1a202c; }
        .header-table { width: 100%; border-bottom: 2px solid #1e3a8a; margin-bottom: 15px; }
        .header-table td { padding-bottom: 8px; vertical-align: middle; }
        .company-data { text-align: right; font-size: 8pt; }
        h1 { margin: 0 0 10px; color: #0b265a; font-size: 16pt; }
        .info-box { width: 100%; margin-bottom: 15px; }
        .info-box td { vertical-align: top; padding: 0; }
        .info-box .folio { text-align: right; width: 33%; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { border: 1px solid #e2e8f0; padding: 5px; }
        .data-table th { background: #0b265a; color: #fff; }
        .data-table thead { display: table-header-group; }
        .data-table tr { page-break-inside: avoid; }
        .numero { text-align: right; white-space: nowrap; }
        .centro { text-align: center; }
        .total { font-weight: bold; background: #f1f5f9; }
        .notes-title { background: #e2e8f0; padding: 3px 8px; font-weight: bold; font-size: 9px; margin-top: 20px; }
        .notes-list { font-size: 8.5px; padding-left: 20px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <img src="{{ public_path('images/logoAzul.png') }}" width="105" alt="Rivera Construcciones">
            </td>
            <td class="company-data">
                <strong>RIVERA CONSTRUCCIONES, S.A. de C.V.</strong><br>
                Justo Sierra No. 2469, Guadalajara, Jalisco<br>
                Tels: (0133) 3615-0741<br>
                Email: pilas@riveraco.com.mx
            </td>
        </tr>
    </table>

    <h1>Presupuesto de pilas de cimentación</h1>
    <table class="info-box">
        <tr>
            <td>
                <strong>Cliente</strong> {{ $cabecera['cliente'] }}<br>
                @if($cabecera['obra'])
                    <strong>Obra</strong> {{ $cabecera['obra'] }}<br>
                @endif
                @if($cabecera['equipo'])
                    <strong>Equipo propuesto</strong> {{ $cabecera['equipo'] }}
                @endif
            </td>
            <td class="folio">
                <strong>Folio</strong> {{ $cabecera['folio'] }}<br>
                <strong>Fecha</strong> {{ $cabecera['fecha'] }}
                @if($cabecera['referencia'] ?? null)
                    <br><strong>Referencia</strong> {{ $cabecera['referencia'] }}
                @endif
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th scope="col" style="text-align: left;">Concepto</th>
                <th scope="col" class="centro">Unidad</th>
                <th scope="col" class="numero">Cant.</th>
                <th scope="col" class="numero">P.U.</th>
                <th scope="col" class="numero">Importe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tablaConsolidada as $fila)
                <tr>
                    <td>{{ $fila['concepto'] }}</td>
                    <td class="centro">{{ $fila['unidad'] }}</td>
                    <td class="numero">{{ number_format($fila['cantidad'], 2) }}</td>
                    <td class="numero">${{ number_format($fila['precio_unitario'], 2) }}</td>
                    <td class="numero">${{ number_format($fila['importe'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="centro">Sin partidas de venta activas</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="4" class="numero">Total de venta</td>
                <td class="numero">${{ number_format($totalGeneral, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @unless($esVersion)
        <div class="notes-title">NOTAS IMPORTANTES</div>
        <ol class="notes-list">
                <li>Se requiere de la mecánica de suelos para validar el presupuesto. </li>
                <li>Incluye EPP, seguro social y personal calificado. </li>
                <li>No incluye segurista, suministro de acero ni concreto. </li>
                <li>Se cobrará la cantidad real medida en obra. </li>
                <li>No contempla perforación en roca ni escombro. </li>
                <li>El cliente debe acercar el armado a la perforación. </li>
                <li>Equipo sujeto a disponibilidad. </li>
                <li>Mandar orden de compra firmada para autorizar. </li>
                <li>Si el material no corresponde, habrá ajuste de precios. </li>
                <li>El terreno debe soportar aprox. 30 ton. </li>
                <li>No nos hacemos responsables por daños a instalaciones subterráneas. </li>
                <li>No debe haber obstáculos debajo de los puntos a perforar. </li>
                <li>Cables eléctricos retirados mínimo a 6.00 mts. </li>
                <li>Ingreso accesible para la máquina. </li>
                <li>Costo por hora extra: $2,800.00/hr perforadora. </li>
                <li>Cliente proporciona trazo, niveles y agua. </li>
                <li>Indicar centro con estaca y nivel de colado. </li>
                <li>Se requiere retroexcavadora para retiro de material. </li>
                <li>Cárcamo de lodos de al menos 20m3 si se requiere. </li>
                <li>Actividades fuera de presupuesto deben autorizarse antes. </li>
                <li>Personal asegurado ante el IMSS (enviar SIROC). </li>
                <li>Se requiere velador para resguardo de maquinaria. </li>
                <li>Pilas > 1.00m generan sobre-perforación de +/- 1.00m3. </li>
                <li>Salida de obra de personal cada tercer semana. </li>
                <li>Vigencia del presupuesto: 30 dias. </li>
        </ol>
    @endunless
</body>
</html>
