@php
    $etiquetasOrigen = ['recepcion_directa' => 'Entrega directa', 'salida_general' => 'Salida de almacén', 'salida_huentitan' => 'Salida Huentitán'];
    $condicionesDevolucion = ['bueno' => 'Buen estado', 'danado' => 'Dañado', 'desgastado' => 'Desgastado'];
    $avisos = $finiquito['materiales'];
@endphp
<div class="mx-auto max-w-7xl space-y-5 px-4 py-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><h1 class="text-2xl font-semibold text-slate-900">Finiquito de materiales</h1>
            <p class="text-sm text-slate-600">{{ $obra->nombre }} @if($obra->clave_obra) · {{ $obra->clave_obra }} @endif</p></div>
        <div class="flex flex-wrap gap-2"><a href="{{ route('obras.devoluciones.finiquito.imprimir', $obra->id) }}" target="_blank" rel="noopener" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white">Imprimir resumen preliminar</a>
        <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'materiales-obra']) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Volver a materiales</a></div>
    </div>
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
        <strong>Consulta preliminar.</strong> El saldo sin devolución física registrada puede incluir material consumido o pendiente de devolver. Esta consulta no confirma consumo ni cierra la obra. Las solicitudes, diferencias y evaluaciones pendientes siguen abiertas.
    </div>
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs text-slate-500">Entregas identificadas</div><div class="text-xl font-semibold">{{ $finiquito['entregas']->count() }}</div></div>
        <div class="rounded-xl border bg-white p-4"><div class="text-xs text-slate-500">Solicitudes en curso o borrador</div><div class="text-xl font-semibold">{{ $finiquito['solicitudes_pendientes'] }}</div></div>
        <div class="rounded-xl border bg-white p-4"><div class="text-xs text-slate-500">Condiciones por aclarar</div><div class="text-xl font-semibold">{{ $finiquito['condiciones_diferentes'] }}</div></div>
    </div>
    @if($avisos['origenes_ambiguos']->isNotEmpty() || $avisos['devoluciones_sin_origen']->isNotEmpty() || count($avisos['inconsistencias']) || count($finiquito['origenes_faltantes']))
        <div role="alert" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Hay información que requiere revisión.</strong> Las cantidades con origen o saldo inconsistente no se presentan como definitivas.
            <ul class="mt-2 list-inside list-disc">
                @foreach($avisos['origenes_ambiguos'] as $item)<li>Salida ambigua: movimiento #{{ $item->movimiento_id }}, producto #{{ $item->producto_id }}.</li>@endforeach
                @foreach($avisos['devoluciones_sin_origen'] as $item)<li>Devolución sin origen: movimiento #{{ $item->id }}, producto #{{ $item->producto_id }}.</li>@endforeach
                @foreach($avisos['inconsistencias'] as $item)<li>{{ $item['origen'] }}: {{ implode(' ', $item['motivos']) }}</li>@endforeach
                @foreach($finiquito['origenes_faltantes'] as $item)<li>{{ $item['folio'] }} · {{ $item['partida'] }}: entrega de origen no disponible.</li>@endforeach
            </ul>
        </div>
    @endif
    <section class="space-y-2"><h2 class="text-lg font-semibold">Saldos por entrega</h2>
        <p class="text-sm text-slate-500">Cada entrega aparece una vez. Las OC y requisiciones son referencias de su origen.</p>
        <div class="overflow-x-auto rounded-xl border bg-white"><table class="min-w-full text-left text-sm"><thead class="bg-[#0B265A] text-white"><tr>
            <th class="px-3 py-3">Material y origen</th><th class="px-3 py-3">Solicitudes</th><th class="px-3 py-3 text-right">Entregado</th><th class="px-3 py-3 text-right">Devolución aplicada</th><th class="px-3 py-3 text-right">Recibido, sin aplicar</th><th class="px-3 py-3 text-right">Pendiente de recibir</th><th class="px-3 py-3 text-right">Sin devolución física registrada</th><th class="px-3 py-3 text-right">Pendiente de evaluación</th><th class="px-3 py-3 text-right">Costo neto provisional MXN</th>
        </tr></thead><tbody>
        @forelse($finiquito['entregas'] as $fila)
            @php($entrega = $fila['entrega'])
            <tr class="border-t align-top"><td class="px-3 py-3"><strong>{{ $entrega->descripcion }}</strong><div class="text-xs text-slate-500">{{ $etiquetasOrigen[$entrega->origen_tipo] ?? $entrega->origen_tipo }} · {{ $entrega->recepcion['folio'] ?? $entrega->salida['folio'] ?? 'Sin folio' }}</div>
                @foreach($entrega->ordenes_compra as $oc)<div class="text-xs text-slate-500">OC: {{ $oc['folio'] }} @if($oc['alcance'] === 'documento')(vínculo del documento)@endif</div>@endforeach
                @foreach($entrega->requisiciones as $requisicion)<div class="text-xs text-slate-500">Requisición: {{ $requisicion['folio'] ?: '#'.$requisicion['id'] }}</div>@endforeach</td>
                <td class="px-3 py-3 text-xs">{{ $fila['solicitudes'] ? implode(', ', $fila['solicitudes']) : 'Sin solicitud presentada' }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right">{{ number_format((float) $entrega->cantidad, 3) }} {{ $entrega->unidad }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($entrega->cantidad_devuelta_confirmada, 3) }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($fila['fisico_pendiente_aplicar'], 3) }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($fila['pendiente_recibir'], 3) }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right font-semibold">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($fila['sin_devolucion_fisica'], 3) }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($entrega->cantidad_pendiente_evaluacion, 3) }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right">{{ $fila['requiere_revision'] || $entrega->importe_neto_mxn === null ? 'Revisar' : '$'.number_format($entrega->importe_neto_mxn, 2) }}</td></tr>
        @empty <tr><td colspan="9" class="px-3 py-6 text-center text-slate-500">No hay entregas aplicadas identificadas para esta obra.</td></tr> @endforelse
        </tbody></table></div>
        <p class="text-xs text-slate-500">El pendiente de recibir está incluido en el saldo sin devolución física registrada; el pendiente de evaluación forma parte de lo recibido. El costo neto descuenta solo devoluciones aplicadas y no representa necesariamente consumo.</p>
    </section>
    <section class="space-y-3"><h2 class="text-lg font-semibold">Solicitudes y recepciones</h2>
        @forelse($finiquito['solicitudes'] as $solicitud)
            <details class="rounded-xl border bg-white p-4"><summary class="cursor-pointer text-sm"><strong>{{ $solicitud->folio }}</strong> · {{ ucfirst(str_replace('_', ' ', $solicitud->estado)) }}</summary>
                @if($solicitud->estado === 'borrador')<p class="mt-2 text-xs text-slate-600">Borrador: todavía no compromete saldo.</p>@endif
                @if($solicitud->estado === 'cancelada')<p class="mt-2 text-xs text-slate-600">Cancelada: {{ $solicitud->motivo_cancelacion }}</p>@endif
                @if(in_array($solicitud->estado, ['borrador', 'cancelada'], true))
                    <ul class="mt-2 list-inside list-disc text-xs text-slate-600">@foreach($solicitud->detalles as $partida)<li>{{ $partida->descripcion }} · {{ number_format((float) $partida->cantidad_solicitada, 3) }} {{ $partida->unidad }} · {{ $condicionesDevolucion[$partida->condicion_declarada] ?? 'Sin definir' }}</li>@endforeach</ul>
                @endif
                @include('obras.devoluciones.partials.seguimiento')
                <a class="mt-2 inline-block text-sm font-semibold text-blue-700" href="{{ route('obras.devoluciones.imprimir', [$obra->id, $solicitud->id]) }}" target="_blank" rel="noopener">Ver solicitud imprimible</a>
            </details>
        @empty <p class="rounded-xl border border-dashed p-4 text-sm text-slate-500">No hay solicitudes de devolución para esta obra.</p> @endforelse
    </section>
</div>
