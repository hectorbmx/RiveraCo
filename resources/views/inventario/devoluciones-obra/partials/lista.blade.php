@php($condiciones = ['bueno' => 'Buen estado', 'danado' => 'Dañado', 'desgastado' => 'Desgastado'])
<div class="space-y-3">
    @forelse($solicitudes as $solicitud)
        <details class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <summary class="cursor-pointer px-4 py-4">
                <span class="font-semibold text-[#0B265A]">{{ $solicitud->folio ?: 'Solicitud #'.$solicitud->id }}</span>
                <span class="ml-2 text-sm text-slate-700">{{ $solicitud->obra?->nombre ?? 'Obra #'.$solicitud->obra_id.' no disponible' }}</span>
                <span class="ml-2 rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-800">{{ $solicitud->estado === 'recepcion_parcial' ? ($solicitud->detalles->every(fn ($p) => $p->consulta_pendiente !== null && $p->consulta_pendiente <= 0) ? 'Recibida físicamente' : 'Recepción parcial') : 'Solicitada' }}</span>
                <span class="ml-2 text-xs text-slate-500">Presentada: {{ $solicitud->presentado_at?->format('d/m/Y H:i') ?? 'Sin fecha registrada' }} · {{ $solicitud->presentadoPor?->name ?? 'Sin usuario registrado' }}</span>
                @if($solicitud->resumen_diferencias['partidas_pendientes'])<span class="ml-2 text-xs font-semibold text-amber-800">{{ $solicitud->resumen_diferencias['partidas_pendientes'] }} partida(s) pendientes de recibir</span>@endif
                @if($solicitud->resumen_diferencias['condiciones_diferentes'])<span class="ml-2 text-xs font-semibold text-amber-800">Condiciones por aclarar: {{ $solicitud->resumen_diferencias['condiciones_diferentes'] }}</span>@endif
                @if($solicitud->resumen_diferencias['partidas_revision'] || $solicitud->resumen_diferencias['errores'])<span class="ml-2 text-xs font-semibold text-red-800">Datos por revisar</span>@endif
            </summary>
            @if($solicitud->observaciones)<p class="px-4 pb-3 text-sm text-slate-600"><strong>Observaciones:</strong> {{ $solicitud->observaciones }}</p>@endif
            <div class="overflow-x-auto border-t border-slate-200">
                <table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr>
                    <th class="px-4 py-3">Material / origen</th><th class="px-4 py-3">Almacén de origen</th><th class="px-4 py-3">Condición declarada</th>
                    <th class="px-4 py-3 text-right">Solicitado</th><th class="px-4 py-3 text-right">Recibido físicamente</th><th class="px-4 py-3 text-right">Pendiente de recibir</th>
                </tr></thead><tbody>
                @forelse($solicitud->detalles as $partida)
                    <tr class="border-t border-slate-200 align-top">
                        <td class="px-4 py-3"><div class="font-semibold">{{ $partida->descripcion }}</div><div class="text-xs text-slate-500">Origen: {{ $partida->consulta_origen }}</div>
                            @if($partida->consulta_errores)<div class="mt-1 text-xs font-semibold text-amber-800">Requiere revisión</div><ul class="text-xs text-amber-800">@foreach($partida->consulta_errores as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
                        </td>
                        <td class="px-4 py-3">{{ $partida->consulta_almacen_origen }}</td>
                        <td class="px-4 py-3">{{ $condiciones[$partida->condicion_declarada] ?? 'Sin definir' }}@if($partida->observaciones)<div class="mt-1 text-xs text-slate-500">{{ $partida->observaciones }}</div>@endif</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $partida->cantidad_solicitada, 3) }} {{ $partida->unidad }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $partida->consulta_recibido, 3) }} {{ $partida->unidad }}
                            @if(! $partida->resumen_devolucion['errores'])<div class="text-xs text-slate-500">Aplicado: {{ number_format($partida->resumen_devolucion['aplicado'], 3) }} · Pendiente de aplicar: {{ number_format($partida->resumen_devolucion['por_aplicar'], 3) }}</div>
                            @if($partida->resumen_devolucion['evaluacion'] > 0)<div class="text-xs text-amber-800">De lo recibido, {{ number_format($partida->resumen_devolucion['evaluacion'], 3) }} pendiente de evaluación</div>@endif @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold">{{ $partida->consulta_pendiente === null ? 'Revisar' : number_format((float) $partida->consulta_pendiente, 3).' '.$partida->unidad }}</td>
                    </tr>
                @empty <tr><td colspan="6" class="px-4 py-4 text-amber-800">Solicitud sin partidas; requiere revisión.</td></tr> @endforelse
                </tbody></table>
            </div>
            @if($solicitud->resumen_diferencias['errores'])<div class="px-4 py-2 text-sm text-red-800">{{ implode(' ', $solicitud->resumen_diferencias['errores']) }}</div>@endif
            <div class="space-y-2 border-t border-slate-200 px-4 py-3 text-sm">
                @foreach($solicitud->recepciones->where('estado', 'pendiente_aplicacion') as $recepcion)
                    <p><strong>{{ $recepcion->folio }}</strong> · {{ $recepcion->fecha_recepcion?->format('d/m/Y') }} · {{ $recepcion->almacen?->nombre ?? 'Almacén no disponible' }} · <span class="font-semibold text-amber-800">Con partidas pendientes de aplicación a inventario y costos</span></p>
                    @foreach($recepcion->detalles as $recibido)
                        @php($solicitada = $solicitud->detalles->firstWhere('id', $recibido->devolucion_detalle_id))
                        <p class="pl-4 text-slate-600">{{ $solicitada?->descripcion ?? 'Partida no disponible' }}: {{ number_format((float) $recibido->cantidad_recibida, 3) }} {{ $solicitada?->unidad }} · {{ $condiciones[$recibido->condicion_recibida] ?? 'Sin definir' }} @if(\App\Services\Obras\ObraDevolucionEstadoPartida::aplicada($recepcion->estado, $recibido)) <strong class="text-green-800">· Aplicada a inventario y costo</strong> @endif @if($recibido->estado_evaluacion === 'pendiente_evaluacion') <strong class="text-amber-800">· Herramienta recibida, pendiente de evaluación; no disponible</strong>@endif @if($solicitada && ! isset($condiciones[$solicitada->condicion_declarada])) <strong class="text-amber-800">· Condición declarada sin definir</strong>@elseif($solicitada && $solicitada->condicion_declarada !== $recibido->condicion_recibida) <strong class="text-amber-800">· Condición diferente a la declarada; pendiente de aclarar</strong>@endif @if($recibido->observaciones) · {{ $recibido->observaciones }} @endif</p>
                    @endforeach
                    @if($recepcion->detalles->contains(fn ($d) => in_array($d->condicion_recibida, ['danado', 'desgastado'], true) && ! $d->estado_evaluacion && $solicitud->detalles->firstWhere('id', $d->devolucion_detalle_id)?->producto?->tipo_inventario === 'herramienta'))
                        <form method="POST" action="{{ route('inventario.devoluciones-obra.evaluacion', $recepcion) }}" class="pl-4">@csrf<button class="rounded-lg border border-amber-400 bg-amber-50 px-3 py-2 font-semibold text-amber-900">Registrar herramientas pendientes de evaluación</button></form>
                    @endif
                    @if($recepcion->entrada_requiere_revision)
                        <p class="pl-4 font-semibold text-amber-800">La preparación de la entrada requiere revisión.</p>
                    @elseif($recepcion->entrada_preparada)
                        <p class="pl-4 text-slate-600">Preparación de entrada: {{ count($recepcion->entrada_preparada['entradas']) }} partida(s) listas para aplicar; {{ count($recepcion->entrada_preparada['ya_aplicadas']) }} aplicada(s); {{ count($recepcion->entrada_preparada['pendientes']) }} pendiente(s) de aclaración o evaluación.</p>
                        @if(count($recepcion->entrada_preparada['entradas']))
                            <form method="POST" action="{{ route('inventario.devoluciones-obra.aplicar', $recepcion) }}" class="pl-4">@csrf<button class="rounded-lg bg-emerald-700 px-3 py-2 font-semibold text-white">Aplicar entrada y costo de obra</button></form>
                        @endif
                    @endif
                @endforeach
                @if($solicitud->detalles->contains(fn ($p) => $p->consulta_pendiente !== null && $p->consulta_pendiente > 0))
                    <a class="inline-block rounded-lg bg-[#0B265A] px-4 py-2 font-semibold text-white" href="{{ route('inventario.devoluciones-obra.recibir', $solicitud) }}">Registrar recepción física</a>
                @endif
            </div>
        </details>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No hay solicitudes pendientes de devolución.</div>
    @endforelse
</div>
