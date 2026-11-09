<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Solicitud {{ $devolucion->folio }}</title>
<style>
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { background: #e5e7eb; font: 10.5px Arial, sans-serif; color: #111827; margin: 24px auto; }
    .sheet { background: white; box-shadow: 0 8px 24px rgba(15,23,42,.16); margin: 0 auto 24px; min-height: 11in; padding: .45in; width: 8.5in; }
    .no-print { position: fixed; bottom: 22px; right: 22px; }
    .btn { border: 1px solid #cbd5e1; background: white; border-radius: 5px; padding: 7px 11px; font-weight: 700; cursor: pointer; }
    @include('impresion.cabecera-estilos')
    table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
    tr { page-break-inside: avoid; } thead { display: table-header-group; }
    th, td { border: 1px solid #1f2937; padding: 5px 6px; vertical-align: top; overflow-wrap: anywhere; }
    th { background: #c8d8f2 !important; text-transform: uppercase; font-size: 10px; }
    .label { background: #e5e7eb; font-weight: bold; width: 17%; text-transform: uppercase; }
    .right { text-align: right; } .notice { margin: 10px 0; padding: 8px; border: 1px solid #64748b; font-weight: bold; }
    .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 55px; margin-top: 55px; break-inside: avoid; }
    .signature { text-align: center; border-top: 1px solid #111827; padding-top: 6px; }
    @media print { @page { size: letter portrait; margin: 6mm; } body { background: white; margin: 0; } .sheet { box-shadow: none; margin: 0; min-height: auto; padding: .45in; width: 100%; } .no-print { display: none; } }
</style></head><body>
@php
    $estados = ['borrador' => 'Borrador', 'solicitada' => 'Solicitada', 'recepcion_parcial' => 'Recepción parcial', 'recibida' => 'Recibida', 'cancelada' => 'Cancelada'];
    $condiciones = ['bueno' => 'Buen estado', 'danado' => 'Dañado', 'desgastado' => 'Desgastado'];
@endphp
<div class="no-print"><button class="btn" type="button" onclick="window.print()">Imprimir</button></div>
<main class="sheet">
    @include('impresion.cabecera', ['tituloDocumento' => 'Solicitud de devolución de materiales'])
    <table><tbody>
        <tr><td class="label">Obra</td><td colspan="3">{{ $obra->nombre }}@if($obra->clave_obra) · {{ $obra->clave_obra }}@endif</td></tr>
        <tr><td class="label">Folio</td><td>{{ $devolucion->folio }}</td><td class="label">Estado</td><td>{{ $estados[$devolucion->estado] ?? $devolucion->estado }}</td></tr>
        <tr><td class="label">Creada</td><td>{{ $devolucion->created_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td><td class="label">Creada por</td><td>{{ $devolucion->creadoPor?->name ?? 'Sin registro' }}</td></tr>
        <tr><td class="label">Presentada</td><td>{{ $devolucion->presentado_at?->format('d/m/Y H:i') ?? 'Sin presentar' }}</td><td class="label">Presentada por</td><td>{{ $devolucion->presentadoPor?->name ?? 'Sin presentar' }}</td></tr>
    </tbody></table>
    @if($devolucion->estado === 'borrador')<div class="notice">BORRADOR: esta solicitud todavía no ha sido presentada a almacén.</div>
    @elseif($devolucion->estado === 'cancelada')<div class="notice">SOLICITUD CANCELADA: {{ $devolucion->motivo_cancelacion ?: 'Sin motivo registrado' }}</div>
    @else<div class="notice">Las cantidades y condiciones siguientes son declaradas por obra. La recepción física y la condición serán verificadas por almacén.</div>@endif
    <table><thead><tr><th>#</th><th>Material / origen</th><th>Cantidad solicitada</th><th>Condición declarada</th><th>Observaciones</th></tr></thead><tbody>
    @forelse($devolucion->detalles as $partida)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $partida->descripcion }}<br><small>Origen: {{ $partida->recepcionDirectaDetalle?->recepcion?->folio ?? $partida->movimientoOrigen?->documento?->folio ?? $partida->salidaHuentitanDetalle?->salida?->folio ?? 'No disponible' }}</small></td><td class="right">{{ number_format((float) $partida->cantidad_solicitada, 3) }} {{ $partida->unidad }}</td><td>{{ $condiciones[$partida->condicion_declarada] ?? 'Sin definir' }}</td><td>{{ $partida->observaciones ?: '—' }}</td></tr>
    @empty <tr><td colspan="5">Sin partidas registradas.</td></tr> @endforelse
    </tbody></table>
    <p><strong>Observaciones de la solicitud:</strong> {{ $devolucion->observaciones ?: 'Sin observaciones' }}</p>
    <div class="signatures"><div class="signature">Entrega en obra<br>Nombre y firma</div><div class="signature">Recibe en almacén<br>Nombre y firma</div></div>
    <p>La firma física no sustituye la confirmación de recepción en el sistema.</p>
</main></body></html>
