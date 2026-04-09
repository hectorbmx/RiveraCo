{{-- pdf_template.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 1cm; }
        body { font-family: sans-serif; font-size: 10px; line-height: 1.2; color: #1a202c; }
        .header-table { width: 100%; border-bottom: 2px solid #1e3a8a; margin-bottom: 15px; }
        .logo-placeholder { width: 120px; height: 80px; background: #f3f4f6; text-align: center; line-height: 80px; color: #9ca3af; border: 1px dashed #d1d5db; }
        .company-data { text-align: right; font-size: 8px; }
        .info-box { width: 100%; margin-bottom: 15px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th { background: #f8fafc; border: 1px solid #e2e8f0; padding: 5px; font-weight: bold; text-transform: uppercase; }
        .data-table td { border: 1px solid #e2e8f0; padding: 5px; }
        .notes-title { background: #e2e8f0; padding: 3px 8px; font-weight: bold; font-size: 9px; margin-top: 20px; }
        .notes-list { font-size: 8.5px; padding-left: 20px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                {{-- Espacio para el logo [cite: 1] --}}
                <div class="logo-placeholder">TU LOGO AQUÍ</div>
            </td>
            <td class="company-data">
                <strong>RIVERA CONSTRUCCIONES, S.A. de C.V.</strong> [cite: 2]<br>
                JUSTO SIERRA No 2469, Guadalajara, Jalisco [cite: 3, 4]<br>
                Tels: (0133) 3615-0741 [cite: 3]<br>
                Email: pilas@riveraco.com.mx [cite: 5]
            </td>
        </tr>
    </table>

    <table class="info-box">
        <tr>
            <td>
                <strong>CLIENTE:</strong> {{ $presupuesto->nombre_cliente }} [cite: 6]<br>
                <strong>OBRA:</strong> Be Grand Ocean Pozos [cite: 13]<br>
                <strong>UBICACIÓN:</strong> Flamingos, Bahia de Banderas, Nayarit [cite: 14]
            </td>
            <td style="text-align: right; vertical-align: top;">
                <strong>FOLIO:</strong> {{ $presupuesto->codigo_proyecto }} [cite: 12]<br>
                <strong>FECHA:</strong> {{ date('d/m/Y') }} [cite: 11]
            </td>
        </tr>
    </table>

    <p>De acuerdo a su atenta solicitud, le presento presupuesto de las pilas de cimentación para su proyecto en cuestión: [cite: 1]</p>

    <table class="data-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Unidad</th>
                <th>Cant.</th>
                <th>P.U.</th>
                <th>Importe</th>
            </tr>
        </thead>
      <tbody>
    @foreach($tablaConsolidada as $fila)
        {{-- Aplicamos la regla: solo imprimir si cantidad y precio son mayores a 0 --}}
        @if($fila['cantidad'] > 0 && $fila['precio'] > 0)
            <tr>
                <td>{{ $fila['concepto'] }}</td>
                <td align="center">{{ $fila['unidad'] }}</td>
                <td align="center">{{ number_format($fila['cantidad'], 2) }}</td>
                <td align="right">${{ number_format($fila['precio'], 2) }}</td>
                <td align="right">${{ number_format($fila['importe'], 2) }}</td>
            </tr>
        @endif
    @endforeach
</tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #f1f5f9;">
                <td colspan="4" align="right">TOTAL:</td>
                <td align="right">${{ number_format($totalGeneral, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="notes-title">NOTAS IMPORTANTES</div>
  <ol>
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
</body>
</html>