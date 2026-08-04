<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Viaticos</title>
    @include('obras.reposicion-gastos.pdf._styles')
</head>
<body>
    @php($tituloPdf = 'VIATICOS')
    @include('obras.reposicion-gastos.pdf._header')

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
            @forelse($reposicion->detalles as $detalle)
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
                        <div class="small">
                            {{ $detalle->dias ?? 0 }} dia(s) x ${{ number_format((float) $detalle->importe_unitario, 2) }} por dia
                        </div>
                    </td>
                    <td class="text-right bold">${{ number_format($detalle->monto, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">No hay viaticos registrados.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right">${{ number_format($reposicion->total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @include('obras.reposicion-gastos.pdf._observaciones-firmas')
</body>
</html>