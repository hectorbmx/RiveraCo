<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reposicion caja chica</title>
    @include('obras.reposicion-gastos.pdf._styles')
</head>
<body>
    @php($tituloPdf = 'REPOSICION CAJA CHICA')
    @include('obras.reposicion-gastos.pdf._header')

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
            @forelse($reposicion->detalles as $detalle)
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
                <td class="text-right">${{ number_format($reposicion->total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @include('obras.reposicion-gastos.pdf._observaciones-firmas')
</body>
</html>