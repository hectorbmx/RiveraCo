@if(in_array($solicitud->estado, ['solicitada', 'recepcion_parcial', 'recibida'], true) && $solicitud->resumen_diferencias)
    <div class="mt-2 space-y-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-700">
        <p class="font-semibold">Seguimiento de recepción</p>
        @if($solicitud->resumen_diferencias['errores'])<p class="font-semibold text-red-800">{{ implode(' ', $solicitud->resumen_diferencias['errores']) }}</p>@endif
        @foreach($solicitud->detalles as $partida)
            @php($resumen = $partida->resumen_devolucion)
            <div class="rounded border border-slate-200 bg-white p-2">
                <p class="font-semibold">{{ $partida->descripcion }}</p>
                @if($resumen['errores'])
                    <p class="text-red-800">{{ implode(' ', $resumen['errores']) }}</p>
                @else
                    <p>Solicitado: {{ number_format((float) $partida->cantidad_solicitada, 3) }} {{ $partida->unidad }} · Recibido físicamente: {{ number_format($resumen['recibido'], 3) }} · Pendiente de recibir: {{ number_format($resumen['pendiente'], 3) }}</p>
                    <p>Aplicado a inventario y costo: {{ number_format($resumen['aplicado'], 3) }} · Pendiente de aplicar: {{ number_format($resumen['por_aplicar'], 3) }}@if($resumen['evaluacion'] > 0) · De lo recibido, pendiente de evaluación: {{ number_format($resumen['evaluacion'], 3) }} @endif</p>
                @endif
                @foreach($resumen['historial'] as $evento)
                    <p class="mt-1 border-t border-slate-100 pt-1">{{ $evento['folio'] }}@if($evento['fecha']) · {{ $evento['fecha']->format('d/m/Y') }}@endif @if($evento['almacen']) · {{ $evento['almacen'] }} @endif · {{ number_format($evento['cantidad'], 3) }} {{ $partida->unidad }} · Recibido: {{ $condicionesDevolucion[$evento['condicion']] ?? 'Sin definir' }} · {{ $evento['aplicada'] ? 'Aplicada' : 'Pendiente de aplicación' }}
                        @if($evento['diferente']) <strong class="text-amber-800">· Condición diferente a la declarada; pendiente de aclarar</strong>@elseif($evento['sin_referencia']) <strong class="text-amber-800">· Condición declarada sin definir</strong>@endif
                        @if($evento['evaluacion']) <strong class="text-amber-800">· Pendiente de evaluación</strong>@endif @if($evento['observaciones']) · {{ $evento['observaciones'] }} @endif
                    </p>
                @endforeach
            </div>
        @endforeach
        <p class="text-slate-500">Una cantidad pendiente no implica que exista una pérdida. Las diferencias de condición siguen pendientes de aclaración.</p>
    </div>
@endif
