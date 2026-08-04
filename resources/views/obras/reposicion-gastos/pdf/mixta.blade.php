<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reposicion de gastos por tipo</title>
    @include('obras.reposicion-gastos.pdf._styles')
    <style>
        .pdf-section { page-break-after: always; }
        .pdf-section:last-of-type { page-break-after: auto; }
    </style>
</head>
<body>
    @foreach($seccionesPdf as $seccion)
        <div class="pdf-section">
            @php
                $tituloPdf = $seccion['titulo'];
                $totalEncabezado = $seccion['total'];
                $detallesSeccion = $seccion['detalles'];
            @endphp

            @include('obras.reposicion-gastos.pdf._header')

            @if($seccion['tipo'] === 'caja_chica')
                <table class="concept-table">
                    <thead>
                        <tr>
                            <th width="12%">Fecha</th>
                            <th width="22%">Partida</th>
                            <th width="20%">Factura</th>
                            <th width="34%">Concepto</th>
                            <th width="12%">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($detallesSeccion as $detalle)
                            <tr>
                                <td class="text-center">{{ optional($detalle->fecha)->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <div class="bold">{{ $detalle->partida->partida ?? 'SIN PARTIDA' }}</div>
                                    <div class="small">{{ $detalle->partida->concepto ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="bold">{{ $detalle->rfc ?? '-' }}</div>
                                    <div class="small">{{ $detalle->uuid ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="bold">{{ $detalle->proveedor ?? '-' }}</div>
                                    <div class="small">{{ $detalle->descripcion ?? '-' }}</div>
                                </td>
                                <td class="text-right bold">${{ number_format($detalle->monto, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No hay conceptos registrados.</td></tr>
                        @endforelse
                        <tr class="total-row">
                            <td colspan="4" class="text-right">TOTAL</td>
                            <td class="text-right">${{ number_format($seccion['total'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            @elseif($seccion['tipo'] === 'gastos_varios')
                <table class="concept-table">
                    <thead>
                        <tr>
                            <th width="12%">Fecha</th>
                            <th width="22%">Partida</th>
                            <th width="18%">Nota</th>
                            <th width="36%">Concepto</th>
                            <th width="12%">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($detallesSeccion as $detalle)
                            <tr>
                                <td class="text-center">{{ optional($detalle->fecha)->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <div class="bold">{{ $detalle->partida->partida ?? 'SIN PARTIDA' }}</div>
                                    <div class="small">{{ $detalle->partida->concepto ?? '-' }}</div>
                                </td>
                                <td>
                                    @if($detalle->comprobante_tipo === 'cfdi')
                                        <div class="bold">FACTURA</div>
                                        <div class="small">{{ $detalle->uuid ?? '-' }}</div>
                                    @else
                                        <div class="bold">NOTA</div>
                                        <div class="small">{{ $detalle->numero_nota ?? '-' }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="bold">{{ $detalle->proveedor ?? '-' }}</div>
                                    <div class="small">{{ $detalle->descripcion ?? '-' }}</div>
                                </td>
                                <td class="text-right bold">${{ number_format($detalle->monto, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No hay conceptos registrados.</td></tr>
                        @endforelse
                        <tr class="total-row">
                            <td colspan="4" class="text-right">TOTAL</td>
                            <td class="text-right">${{ number_format($seccion['total'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <table class="concept-table">
                    <thead>
                        <tr>
                            <th width="16%">Fecha</th>
                            <th width="24%">Partida</th>
                            <th width="46%">Concepto</th>
                            <th width="14%">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($detallesSeccion as $detalle)
                            @php
                                $fechaInicio = optional($detalle->fecha_inicio)->format('d/m/Y');
                                $fechaFin = optional($detalle->fecha_fin)->format('d/m/Y');
                                $fechaViatico = $fechaInicio && $fechaFin ? $fechaInicio . ' - ' . $fechaFin : optional($detalle->fecha)->format('d/m/Y');
                            @endphp
                            <tr>
                                <td class="text-center">{{ $fechaViatico ?? '-' }}</td>
                                <td>
                                    <div class="bold">{{ $detalle->partida->partida ?? 'SIN PARTIDA' }}</div>
                                    <div class="small">{{ $detalle->partida->concepto ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="bold">{{ $detalle->descripcion ?? '-' }}</div>
                                    <div class="small">{{ $detalle->dias ?? 0 }} dia(s) x ${{ number_format((float) $detalle->importe_unitario, 2) }} por dia</div>
                                </td>
                                <td class="text-right bold">${{ number_format($detalle->monto, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">No hay viaticos registrados.</td></tr>
                        @endforelse
                        <tr class="total-row">
                            <td colspan="3" class="text-right">TOTAL</td>
                            <td class="text-right">${{ number_format($seccion['total'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

    @php($totalEncabezado = $reposicion->total)
    @include('obras.reposicion-gastos.pdf._observaciones-firmas')
</body>
</html>