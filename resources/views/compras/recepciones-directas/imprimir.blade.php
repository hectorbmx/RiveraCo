<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Recepción {{ $recepcion->folio }}</title>
<style>body{font:12px Arial;color:#17233c;margin:20px}h1{text-align:center;color:#0b2f6b;font-size:22px}table{border-collapse:collapse;width:100%;margin-top:12px}th,td{border:1px solid #45536a;padding:6px}th{background:#cbdcf2;text-align:left}.acciones{text-align:right}@media print{.acciones{display:none}body{margin:8mm}tr{break-inside:avoid}}</style></head><body>
<div class="acciones"><button onclick="window.print()">Imprimir</button></div>
<h1>COMPROBANTE DE RECEPCIÓN DIRECTA EN OBRA</h1>
@if($recepcion->estado !== 'aplicada')<p><strong>ESTADO: {{ strtoupper($recepcion->estado) }} — IMPORTE REGISTRADO, NO VIGENTE</strong></p>@endif
<table><tr><th>Folio</th><td>{{ $recepcion->folio }}</td><th>Fecha</th><td>{{ $recepcion->fecha_recepcion?->format('d/m/Y') }}</td></tr>
<tr><th>Obra</th><td>{{ $recepcion->obra?->nombre }}</td><th>Orden de compra</th><td>{{ $recepcion->ordenCompra?->folio }}</td></tr>
<tr><th>Recibió en obra</th><td>{{ $recepcion->receptor_nombre }}</td><th>Confirmó Compras</th><td>{{ $recepcion->confirmadoPor?->name }}</td></tr>
<tr><th>Remisión</th><td>{{ $recepcion->remision ?: 'Sin número' }}</td><th>Almacén responsable</th><td>{{ $recepcion->almacen?->nombre }}</td></tr>
<tr><th>Entrada documental</th><td>{{ $recepcion->entrada?->folio ?? 'No disponible' }}</td><th>Salida a obra</th><td>{{ $recepcion->salida?->folio ?? 'No disponible' }}</td></tr></table>
<table><thead><tr><th>Material / requisición</th><th>Cantidad OC</th><th>Cantidad obra</th><th>Costo unitario MXN</th><th>Importe MXN</th></tr></thead><tbody>
@foreach($recepcion->detalles as $partida)<tr><td>{{ $partida->descripcion }}<br>{{ $partida->requisicionDetalle?->requisicion?->folio ?? 'Sin requisición vinculada' }}</td><td>{{ number_format((float)$partida->cantidad_recibida, 3) }} {{ $partida->unidad }}</td><td>{{ number_format((float)$partida->cantidad_inventario, 3) }} {{ $partida->unidad_inventario }}</td><td>${{ number_format((float)$partida->costo_unitario_mxn, 8) }}</td><td>${{ number_format((float)$partida->importe_mxn, 2) }}</td></tr>@endforeach
</tbody></table><p><strong>Total registrado: ${{ number_format((float)$recepcion->total_mxn, 2) }} MXN</strong> · Compra: {{ number_format((float)$recepcion->total_oc, 2) }} {{ $recepcion->moneda }} · Tipo de cambio: {{ $recepcion->tipo_cambio }}</p>
<p><strong>Observaciones:</strong> {{ $recepcion->observaciones ?: 'Sin observaciones' }}</p></body></html>
