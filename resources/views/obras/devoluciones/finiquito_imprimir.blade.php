<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Finiquito preliminar de materiales — {{ $obra->nombre }}</title>
<style>
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { background: #e5e7eb; color: #111827; font: 10px Arial, sans-serif; margin: 24px auto; }
    .sheet { background: white; box-shadow: 0 8px 24px rgba(15,23,42,.16); margin: 0 auto 24px; min-height: 8.5in; padding: .35in; width: 11in; }
    .no-print { position: fixed; bottom: 22px; right: 22px; }
    .btn { border: 1px solid #cbd5e1; background: white; border-radius: 5px; padding: 7px 11px; font-weight: bold; cursor: pointer; }
    @include('impresion.cabecera-estilos')
    table { width: 100%; border-collapse: collapse; page-break-inside: auto; table-layout: fixed; }
    thead { display: table-header-group; } tr { break-inside: avoid; page-break-inside: avoid; }
    th, td { border: 1px solid #1f2937; padding: 4px; vertical-align: top; overflow-wrap: anywhere; }
    th { background: #c8d8f2 !important; text-transform: uppercase; font-size: 9px; }
    .label { background: #e5e7eb; font-weight: bold; width: 13%; text-transform: uppercase; }
    .right { text-align: right; } .note { margin: 8px 0; padding: 7px; border: 1px solid #64748b; }
    .warning { border-color: #92400e; background: #fffbeb; } .muted { color: #475569; }
    h2 { color: #0B265A; font-size: 13px; margin: 15px 0 5px; break-after: avoid; }
    h3 { color: #0B265A; font-size: 11px; margin: 10px 0 4px; break-after: avoid; }
    p { margin: 5px 0; } ul { margin: 5px 0; padding-left: 18px; }
    @media print { @page { size: letter landscape; margin: 6mm; } body { background: white; margin: 0; } .sheet { box-shadow: none; margin: 0; min-height: auto; padding: .25in; width: 100%; } .no-print { display: none; } }
</style></head><body>
@php
    $origenes = ['recepcion_directa' => 'Entrega directa', 'salida_general' => 'Salida de almacén', 'salida_huentitan' => 'Salida Huentitán'];
    $condiciones = ['bueno' => 'Buen estado', 'danado' => 'Dañado', 'desgastado' => 'Desgastado'];
    $estados = ['borrador' => 'Borrador', 'solicitada' => 'Solicitada', 'recepcion_parcial' => 'Recepción parcial', 'recibida' => 'Recibida', 'cancelada' => 'Cancelada'];
    $avisos = $finiquito['materiales'];
@endphp
<div class="no-print"><button class="btn" type="button" onclick="window.print()">Imprimir</button></div>
<main class="sheet">
    @include('impresion.cabecera', ['tituloDocumento' => 'Finiquito preliminar de materiales'])
    <table><tbody>
        <tr><td class="label">Obra</td><td colspan="3">{{ $obra->nombre }}@if($obra->clave_obra) · {{ $obra->clave_obra }}@endif</td></tr>
        <tr><td class="label">Generado</td><td>{{ $generadoEn->format('d/m/Y H:i') }}</td><td class="label">Alcance</td><td>Consulta preliminar</td></tr>
    </tbody></table>
    <div class="note"><strong>CONSULTA PRELIMINAR.</strong> El saldo sin devolución física registrada puede incluir material consumido o pendiente de devolver. Este resumen no confirma consumo ni cierra la obra. Las diferencias y evaluaciones siguen pendientes.</div>
    @if($avisos['origenes_ambiguos']->isNotEmpty() || $avisos['devoluciones_sin_origen']->isNotEmpty() || count($avisos['inconsistencias']) || count($finiquito['origenes_faltantes']))
        <div class="note warning"><strong>Información que requiere revisión:</strong><ul>
            @foreach($avisos['origenes_ambiguos'] as $item)<li>Salida ambigua: movimiento #{{ $item->movimiento_id }}, producto #{{ $item->producto_id }}.</li>@endforeach
            @foreach($avisos['devoluciones_sin_origen'] as $item)<li>Devolución sin origen: movimiento #{{ $item->id }}, producto #{{ $item->producto_id }}.</li>@endforeach
            @foreach($avisos['inconsistencias'] as $item)<li>{{ $item['origen'] }}: {{ implode(' ', $item['motivos']) }}</li>@endforeach
            @foreach($finiquito['origenes_faltantes'] as $item)<li>{{ $item['folio'] }} · {{ $item['partida'] }}: entrega de origen no disponible.</li>@endforeach
        </ul></div>
    @endif
    <h2>Saldos por entrega</h2>
    <p class="muted">Cada entrega se cuenta una vez; las OC y requisiciones son referencias. Las cantidades están expresadas en la unidad de cada partida.</p>
    <table><colgroup><col style="width:23%"><col style="width:9%"><col style="width:9%"><col style="width:9%"><col style="width:9%"><col style="width:10%"><col style="width:9%"><col style="width:10%"><col style="width:12%"></colgroup>
        <thead><tr><th>Material / origen</th><th>Entregado</th><th>Devuelto aplicado</th><th>Recibido sin aplicar</th><th>Pendiente de recibir</th><th>Sin devolución física</th><th>Pendiente evaluación</th><th>Costo neto provisional MXN</th><th>Solicitudes</th></tr></thead><tbody>
        @forelse($finiquito['entregas'] as $fila)
            @php($entrega = $fila['entrega'])
            <tr><td><strong>{{ $entrega->descripcion }}</strong><br>{{ $origenes[$entrega->origen_tipo] ?? $entrega->origen_tipo }} · {{ $entrega->recepcion['folio'] ?? $entrega->salida['folio'] ?? 'Sin folio' }}
                @foreach($entrega->ordenes_compra as $oc)<br>OC: {{ $oc['folio'] }}@if($oc['alcance'] === 'documento') (vínculo del documento)@endif @endforeach
                @foreach($entrega->requisiciones as $rq)<br>Requisición: {{ $rq['folio'] ?: '#'.$rq['id'] }}@endforeach</td>
                <td class="right">{{ number_format((float) $entrega->cantidad, 3) }} {{ $entrega->unidad }}</td>
                <td class="right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($entrega->cantidad_devuelta_confirmada, 3) }}</td>
                <td class="right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($fila['fisico_pendiente_aplicar'], 3) }}</td>
                <td class="right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($fila['pendiente_recibir'], 3) }}</td>
                <td class="right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($fila['sin_devolucion_fisica'], 3) }}</td>
                <td class="right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($entrega->cantidad_pendiente_evaluacion, 3) }}</td>
                <td class="right">{{ $fila['requiere_revision'] || $entrega->importe_neto_mxn === null ? 'Revisar' : '$'.number_format($entrega->importe_neto_mxn, 2) }}</td>
                <td>{{ $fila['solicitudes'] ? implode(', ', $fila['solicitudes']) : 'Sin solicitud presentada' }}</td></tr>
        @empty <tr><td colspan="9">No hay entregas aplicadas identificadas para esta obra.</td></tr> @endforelse
        </tbody></table>
    <p>El pendiente de recibir está incluido en el saldo sin devolución física; la evaluación forma parte de lo recibido. El costo neto descuenta solo devoluciones aplicadas y no representa necesariamente consumo.</p>
    <h2>Solicitudes y recepciones</h2>
    @forelse($finiquito['solicitudes'] as $solicitud)
        <h3>{{ $solicitud->folio ?: 'Solicitud #'.$solicitud->id }} · {{ $estados[$solicitud->estado] ?? $solicitud->estado }}</h3>
        @if($solicitud->estado === 'borrador')<p>Borrador: todavía no compromete saldo.</p>@endif
        @if($solicitud->estado === 'cancelada')<p>Motivo de cancelación: {{ $solicitud->motivo_cancelacion ?: 'Sin motivo registrado' }}</p>@endif
        @if($solicitud->observaciones)<p>Observaciones de la solicitud: {{ $solicitud->observaciones }}</p>@endif
        @if($solicitud->resumen_diferencias['errores'])<p><strong>Revisar:</strong> {{ implode(' ', $solicitud->resumen_diferencias['errores']) }}</p>@endif
        <table><thead><tr><th style="width:24%">Partida / condición declarada</th><th style="width:10%">Solicitado</th><th style="width:10%">Recibido físico</th><th style="width:10%">Pendiente recibir</th><th style="width:10%">Aplicado</th><th style="width:36%">Recepciones, diferencias y observaciones</th></tr></thead><tbody>
            @forelse($solicitud->detalles as $partida)
                @php($resumen = $partida->resumen_devolucion)
                <tr><td><strong>{{ $partida->descripcion }}</strong><br>Declarada: {{ $condiciones[$partida->condicion_declarada] ?? 'Sin definir' }}@if($partida->observaciones)<br>{{ $partida->observaciones }}@endif</td>
                    <td class="right">{{ number_format((float) $partida->cantidad_solicitada, 3) }} {{ $partida->unidad }}</td>
                    @if(! in_array($solicitud->estado, ['solicitada', 'recepcion_parcial', 'recibida'], true))
                        <td colspan="4">{{ $solicitud->estado === 'borrador' ? 'No presentada.' : 'Sin seguimiento activo.' }}</td>
                    @else
                        @if($resumen['errores'])<td colspan="3"><strong>Revisar:</strong> {{ implode(' ', $resumen['errores']) }}</td>
                        @else<td class="right">{{ number_format($resumen['recibido'], 3) }}</td><td class="right">{{ number_format($resumen['pendiente'], 3) }}</td><td class="right">{{ number_format($resumen['aplicado'], 3) }}</td>@endif
                        <td>@forelse($resumen['historial'] as $evento)
                            <p><strong>{{ $evento['folio'] }}</strong>@if($evento['fecha']) · {{ $evento['fecha']->format('d/m/Y') }}@endif @if($evento['almacen']) · {{ $evento['almacen'] }}@endif · {{ number_format($evento['cantidad'], 3) }} {{ $partida->unidad }} · {{ $condiciones[$evento['condicion']] ?? 'Sin definir' }} · {{ $evento['aplicada'] ? 'Aplicada' : 'Pendiente de aplicación' }}
                            @if($evento['diferente']) · <strong>Condición diferente; pendiente de aclarar</strong>@elseif($evento['sin_referencia']) · <strong>Condición declarada sin definir</strong>@endif @if($evento['evaluacion']) · <strong>Pendiente de evaluación</strong>@endif @if($evento['observaciones']) · {{ $evento['observaciones'] }}@endif</p>
                        @empty Sin recepción registrada. @endforelse</td>
                    @endif
                </tr>
            @empty <tr><td colspan="6">Sin partidas registradas.</td></tr> @endforelse
        </tbody></table>
    @empty <p>No hay solicitudes de devolución para esta obra.</p> @endforelse
    <p class="muted">Información consultada al generar el documento. Las cantidades y estados pueden cambiar con movimientos posteriores.</p>
</main></body></html>
