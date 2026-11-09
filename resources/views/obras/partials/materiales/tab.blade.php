@php
    $entregasMaterial = $materialesObra['entregas'];
    $origenesAmbiguos = $materialesObra['origenes_ambiguos'];
    $devolucionesSinOrigen = $materialesObra['devoluciones_sin_origen'];
    $inconsistenciasMaterial = $materialesObra['inconsistencias'];
    $etiquetasOrigen = ['recepcion_directa' => 'Entrega directa', 'salida_general' => 'Salida de almacén', 'salida_huentitan' => 'Salida Huentitán'];
    $condicionesDevolucion = ['bueno' => 'Buen estado', 'danado' => 'Dañado', 'desgastado' => 'Desgastado'];
@endphp
<section class="space-y-5">
    @if(session('success'))<div class="rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if(isset($errors) && $errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
        <h2 class="text-lg font-semibold text-slate-800">Materiales entregados a la obra</h2>
        <p class="text-sm text-slate-500">Consulta las entregas aplicadas y sus saldos para devolución. Las cantidades disponibles son informativas y se volverán a validar al solicitar.</p>
        </div>
        @isset($obra)<a href="{{ route('obras.devoluciones.finiquito', $obra->id) }}" class="rounded-lg border border-blue-800 px-4 py-2 text-sm font-semibold text-blue-800">Consultar finiquito de materiales</a>@endisset
    </div>
    @if($origenesAmbiguos->isNotEmpty() || $devolucionesSinOrigen->isNotEmpty() || count($inconsistenciasMaterial))
        <div role="alert" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Hay movimientos o saldos que requieren revisión</p>
            <p class="mt-1">No se asigna automáticamente saldo disponible cuando no puede identificarse su origen con certeza.</p>
            @if($origenesAmbiguos->isNotEmpty())<p class="mt-2">Salidas con documento ambiguo: {{ $origenesAmbiguos->count() }}.</p>@endif
            @if($devolucionesSinOrigen->isNotEmpty())<p>Devoluciones anteriores sin origen identificable: {{ $devolucionesSinOrigen->count() }}.</p>@endif
            @if(count($inconsistenciasMaterial))<p>Entregas o solicitudes con inconsistencias: {{ count($inconsistenciasMaterial) }}.</p>@endif
            <details class="mt-2"><summary class="cursor-pointer font-semibold">Ver referencias para revisión</summary>
                <ul class="mt-2 list-inside list-disc text-xs">
                    @foreach($origenesAmbiguos as $ambiguo)<li>Movimiento #{{ $ambiguo->movimiento_id }}, documento #{{ $ambiguo->documento_id }}, producto #{{ $ambiguo->producto_id }}: origen de salida ambiguo.</li>@endforeach
                    @foreach($devolucionesSinOrigen as $devolucion)<li>Devolución: movimiento #{{ $devolucion->id }}, documento #{{ $devolucion->documento_id }}, producto #{{ $devolucion->producto_id }}: sin entrega de origen identificable.</li>@endforeach
                    @foreach($inconsistenciasMaterial as $inconsistencia)<li>{{ $inconsistencia['origen'] }}: {{ implode(' ', $inconsistencia['motivos']) }}</li>@endforeach
                </ul>
            </details>
        </div>
    @endif
    @if(auth()->user()?->can('obra.devoluciones.create.access'))
    <form method="POST" action="{{ route('obras.devoluciones.store', $obra->id) }}" class="space-y-4">
        @csrf <input type="hidden" name="operacion_uuid" value="{{ old('operacion_uuid', (string) \Illuminate\Support\Str::uuid()) }}">
        <p class="text-sm text-slate-600">Selecciona las entregas e indica cantidad, condición declarada y observaciones. Almacén verificará la condición al recibir.</p>
    @endif
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-[#0B265A] text-white"><tr>
                <th class="px-3 py-3">Material / origen</th><th class="px-3 py-3">Antecedentes</th>
                <th class="px-3 py-3 text-right">Entregado</th><th class="px-3 py-3 text-right">Devuelto</th>
                <th class="px-3 py-3 text-right">Comprometido</th><th class="px-3 py-3 text-right">Disponible para solicitar</th>
                <th class="px-3 py-3 text-right">Pendiente de evaluación</th><th class="px-3 py-3 text-right">Costo original MXN</th><th class="px-3 py-3 text-right">Devuelto MXN</th><th class="px-3 py-3 text-right">Costo neto MXN</th>
                @if(auth()->user()?->can('obra.devoluciones.create.access'))<th class="px-3 py-3">Solicitar devolución</th>@endif
            </tr></thead><tbody>
            @forelse($entregasMaterial as $entrega)
                <tr class="border-t border-slate-200 align-top">
                    <td class="px-3 py-3"><div class="font-semibold text-slate-800">{{ $entrega->descripcion }}</div>
                        <div class="text-xs text-slate-500">{{ $etiquetasOrigen[$entrega->origen_tipo] ?? $entrega->origen_tipo }} · {{ $entrega->fecha ? \Carbon\Carbon::parse($entrega->fecha)->format('d/m/Y') : 'Sin fecha' }}</div>
                        @if($entrega->unidad_fuente === 'catalogo_actual')<div class="text-xs text-slate-500">Unidad del catálogo actual</div>@endif
                        @if($entrega->requiere_revision)<div class="mt-1 text-xs font-semibold text-amber-800">Requiere revisión</div><ul class="mt-1 text-xs text-amber-800">@foreach($entrega->inconsistencias as $motivo)<li>{{ $motivo }}</li>@endforeach</ul>@endif
                    </td>
                    <td class="px-3 py-3 text-xs text-slate-700">
                        @if($entrega->recepcion)<div>Recepción: {{ $entrega->recepcion['folio'] }}</div>@endif
                        <div>Salida: {{ $entrega->salida['folio'] ?: '#'.$entrega->salida['id'] }}</div>
                        @forelse($entrega->ordenes_compra as $oc)<div>OC: {{ $oc['folio'] }}@if($oc['alcance'] === 'documento') <span class="text-slate-500">(vínculo del documento)</span>@elseif($oc['alcance'] === 'partida_compra') <span class="text-slate-500">(partida de compra)</span>@endif</div>@empty<div class="text-slate-500">Sin OC vinculada</div>@endforelse
                        @foreach($entrega->requisiciones as $requisicion)<div>Requisición: {{ $requisicion['folio'] ?: '#'.$requisicion['id'] }}@if($requisicion['alcance'] === 'partida_compra') <span class="text-slate-500">(partida de compra)</span>@endif</div>@endforeach
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-right">{{ number_format((float) $entrega->cantidad, 3) }} {{ $entrega->unidad }}</td>
                    <td class="whitespace-nowrap px-3 py-3 text-right">{{ number_format($entrega->cantidad_devuelta_confirmada, 3) }} {{ $entrega->unidad }}</td>
                    <td class="whitespace-nowrap px-3 py-3 text-right">{{ number_format($entrega->cantidad_comprometida, 3) }} {{ $entrega->unidad }}</td>
                    <td class="whitespace-nowrap px-3 py-3 text-right font-semibold">{{ $entrega->cantidad_disponible_solicitar === null ? 'Revisar' : number_format($entrega->cantidad_disponible_solicitar, 3).' '.$entrega->unidad }}</td>
                    <td class="whitespace-nowrap px-3 py-3 text-right">{{ number_format($entrega->cantidad_pendiente_evaluacion, 3) }} {{ $entrega->unidad }}</td>
                    <td class="whitespace-nowrap px-3 py-3 text-right">{{ $entrega->importe_mxn === null ? 'Sin costo registrado' : '$'.number_format((float) $entrega->importe_mxn, 2) }}</td>
                    <td class="whitespace-nowrap px-3 py-3 text-right">${{ number_format($entrega->importe_devuelto_mxn, 2) }}</td>
                    <td class="whitespace-nowrap px-3 py-3 text-right">{{ $entrega->importe_neto_mxn === null ? 'Sin costo registrado' : '$'.number_format($entrega->importe_neto_mxn, 2) }}</td>
                    @if(auth()->user()?->can('obra.devoluciones.create.access'))
                        @php($disponible = $entrega->cantidad_disponible_solicitar !== null && $entrega->cantidad_disponible_solicitar > 0 && $entrega->costo_unitario_mxn !== null && $entrega->importe_mxn !== null && $entrega->unidad)
                        @php($clavePartida = $entrega->origen_tipo.'_'.$entrega->origen_id)
                        <td class="px-3 py-3">
                            <input type="hidden" name="partidas[{{ $clavePartida }}][origen_tipo]" value="{{ $entrega->origen_tipo }}">
                            <input type="hidden" name="partidas[{{ $clavePartida }}][origen_id]" value="{{ $entrega->origen_id }}">
                            <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="partidas[{{ $clavePartida }}][seleccionada]" value="1" @checked(old('partidas.'.$clavePartida.'.seleccionada') === '1') @disabled(!$disponible)> Seleccionar</label>
                            <input class="mt-1 w-28 rounded border-slate-300 text-right text-sm" type="number" name="partidas[{{ $clavePartida }}][cantidad]" step="0.001" min="0.001" max="{{ $entrega->cantidad_disponible_solicitar ?? 0 }}" value="{{ old('partidas.'.$clavePartida.'.cantidad') }}" aria-label="Cantidad a devolver de {{ $entrega->descripcion }}" @disabled(!$disponible)>
                            <label class="mt-1 block text-xs">Condición declarada
                                <select name="partidas[{{ $clavePartida }}][condicion]" class="mt-1 block w-40 rounded border-slate-300 text-sm" @disabled(!$disponible)>
                                    <option value="">Selecciona condición</option>
                                    @foreach($condicionesDevolucion as $valor => $etiqueta)<option value="{{ $valor }}" @selected(old('partidas.'.$clavePartida.'.condicion') === $valor)>{{ $etiqueta }}</option>@endforeach
                                </select>
                            </label>
                            <label class="mt-1 block text-xs">Observaciones de partida
                                <textarea name="partidas[{{ $clavePartida }}][observaciones]" maxlength="2000" rows="2" class="mt-1 block w-40 rounded border-slate-300 text-sm" @disabled(!$disponible)>{{ old('partidas.'.$clavePartida.'.observaciones') }}</textarea>
                            </label>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="9" class="px-3 py-8 text-center text-slate-500">No hay entregas aplicadas identificadas para esta obra.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if(auth()->user()?->can('obra.devoluciones.create.access'))
        <label class="block text-sm font-semibold">Observaciones de la solicitud<textarea name="observaciones" maxlength="2000" class="mt-1 block w-full rounded border-slate-300">{{ old('observaciones') }}</textarea></label>
        <div class="flex flex-wrap gap-3"><button type="submit" name="accion" value="borrador" class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold">Guardar borrador</button><button type="submit" name="accion" value="solicitar" class="rounded bg-blue-800 px-4 py-2 text-sm font-semibold text-white">Presentar solicitud</button></div>
    </form>
    @endif
    @if(($solicitudesDevolucion ?? collect())->isNotEmpty())
        <section class="rounded-xl border border-slate-200 p-4"><h3 class="font-semibold">Solicitudes de devolución</h3>
        @foreach($solicitudesDevolucion as $solicitud)
            <div class="mt-3 rounded border border-slate-200 p-3 text-sm"><strong>{{ $solicitud->folio }}</strong> · {{ ucfirst(str_replace('_', ' ', $solicitud->estado)) }}
                <a class="ml-2 font-semibold text-blue-700 underline" href="{{ route('obras.devoluciones.imprimir', [$obra->id, $solicitud->id]) }}" target="_blank" rel="noopener">Imprimir solicitud</a>
                @if($solicitud->estado === 'borrador' && auth()->user()?->can('obra.devoluciones.create.access'))
                    <form method="POST" action="{{ route('obras.devoluciones.update', [$obra->id, $solicitud->id]) }}" class="mt-2 space-y-2">@csrf @method('PATCH')
                        @foreach($solicitud->detalles as $partida)
                            <div class="rounded border border-slate-100 p-2 text-slate-600">
                                <div>{{ $partida->descripcion }} <span class="text-xs">({{ $partida->recepcionDirectaDetalle?->recepcion?->folio ?? $partida->movimientoOrigen?->documento?->folio ?? $partida->salidaHuentitanDetalle?->salida?->folio ?? 'Origen #'.$partida->id }})</span></div>
                                <label class="inline-block text-xs">Cantidad <input type="number" name="cantidades[{{ $partida->id }}]" min="0.001" step="0.001" required value="{{ old('cantidades.'.$partida->id, $partida->cantidad_solicitada) }}" class="w-28 rounded border-slate-300 text-right text-sm"> {{ $partida->unidad }}</label>
                                <label class="inline-block text-xs">Condición declarada <select name="condiciones[{{ $partida->id }}]" required class="rounded border-slate-300 text-sm"><option value="">Selecciona condición</option>@foreach($condicionesDevolucion as $valor => $etiqueta)<option value="{{ $valor }}" @selected(old('condiciones.'.$partida->id, $partida->condicion_declarada) === $valor)>{{ $etiqueta }}</option>@endforeach</select></label>
                                <label class="block text-xs">Observaciones de partida <textarea name="observaciones_partidas[{{ $partida->id }}]" maxlength="2000" rows="2" class="mt-1 block w-full rounded border-slate-300 text-sm">{{ old('observaciones_partidas.'.$partida->id, $partida->observaciones) }}</textarea></label>
                            </div>
                        @endforeach
                        <label class="block text-xs">Observaciones <textarea name="observaciones" maxlength="2000" class="mt-1 block w-full rounded border-slate-300 text-sm">{{ $solicitud->observaciones }}</textarea></label>
                        <button class="rounded border border-slate-300 px-3 py-1">Guardar borrador</button>
                    </form>
                    <form method="POST" action="{{ route('obras.devoluciones.presentar', [$obra->id, $solicitud->id]) }}" class="mt-2">@csrf<button class="rounded bg-blue-800 px-3 py-1 text-white">Presentar borrador</button></form>
                @else
                    <ul class="mt-1 list-inside list-disc text-slate-600">@foreach($solicitud->detalles as $partida)<li>{{ $partida->descripcion }} · {{ number_format((float) $partida->cantidad_solicitada, 3) }} {{ $partida->unidad }} · {{ $partida->recepcionDirectaDetalle?->recepcion?->folio ?? $partida->movimientoOrigen?->documento?->folio ?? $partida->salidaHuentitanDetalle?->salida?->folio ?? 'Origen #'.$partida->id }} · Condición declarada: {{ $condicionesDevolucion[$partida->condicion_declarada] ?? 'Sin definir' }} @if($partida->observaciones) · {{ $partida->observaciones }} @endif</li>@endforeach</ul>
                    @if($solicitud->observaciones)<p class="mt-1 text-xs text-slate-500">{{ $solicitud->observaciones }}</p>@endif
                @endif
                @include('obras.devoluciones.partials.seguimiento')
                @if(in_array($solicitud->estado, ['borrador', 'solicitada'], true) && auth()->user()?->can('obra.devoluciones.create.access'))
                    <details class="mt-2"><summary class="cursor-pointer text-xs font-semibold text-red-700">Cancelar solicitud</summary>
                        <form method="POST" action="{{ route('obras.devoluciones.cancelar', [$obra->id, $solicitud->id]) }}" class="mt-2 flex flex-wrap gap-2">@csrf
                            <label class="text-xs">Motivo <input name="motivo" required maxlength="2000" class="rounded border-slate-300 text-sm"></label>
                            <button class="rounded border border-red-300 px-3 py-1 text-red-700">Confirmar cancelación</button>
                        </form>
                    </details>
                @elseif($solicitud->estado === 'cancelada')
                    <p class="mt-1 text-xs text-slate-500">Motivo: {{ $solicitud->motivo_cancelacion }}</p>
                @endif
            </div>
        @endforeach</section>
    @endif
    <p class="text-xs text-slate-500">El costo neto corresponde a lo entregado menos las devoluciones aplicadas; no representa necesariamente el consumo final. Las herramientas recibidas pendientes de evaluación se muestran por separado y conservan su importe sin ajuste definitivo.</p>
</section>
