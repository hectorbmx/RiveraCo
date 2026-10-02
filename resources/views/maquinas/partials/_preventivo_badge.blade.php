@php
    $preventivo = $preventivo ?? null;
    $preventivoData = is_array($preventivo) ? $preventivo : [];
    $color = $preventivoData['color'] ?? 'slate';
    $barClass = match($color) {
        'rose' => 'bg-rose-500',
        'amber' => 'bg-amber-400',
        'emerald' => 'bg-emerald-500',
        default => 'bg-slate-300',
    };
    $badgeClass = match($color) {
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        default => 'bg-slate-50 text-slate-600 border-slate-200',
    };
    $porcentaje = $preventivoData ? min(100, max(0, (float)($preventivoData['porcentaje'] ?? 0))) : 0;
    $servicioNombre = $preventivoData['servicio_tipo_nombre'] ?? $preventivoData['servicio_nombre'] ?? null;
    $labelPreventivo = $preventivoData['label'] ?? 'Sin datos';
    $badgeText = $servicioNombre ? ($servicioNombre . ': ' . $labelPreventivo) : $labelPreventivo;
    $serviciosTooltip = collect($preventivoData['servicios'] ?? [])
        ->map(function ($servicio) {
            $nombre = $servicio['servicio_tipo_nombre'] ?? $servicio['servicio_nombre'] ?? 'Servicio';
            $label = $servicio['label'] ?? 'Sin datos';
            $meta = isset($servicio['proximo_horometro']) && $servicio['proximo_horometro'] !== null
                ? ' Meta ' . number_format((float) $servicio['proximo_horometro'], 1) . ' h'
                : '';

            return trim($nombre . ': ' . $label . $meta);
        })
        ->filter()
        ->implode("\n");
@endphp

@if(!$preventivo)
    <span class="inline-flex px-2 py-1 rounded-lg border text-xs bg-slate-50 text-slate-600 border-slate-200">
        Sin datos
    </span>
@else
    <div class="min-w-[190px] space-y-1.5">
        <div class="flex items-center justify-between gap-2">
            <span class="inline-flex px-2 py-0.5 rounded-lg border text-xs font-medium {{ $badgeClass }}" title="{{ $serviciosTooltip ?: $badgeText }}">
                {{ $badgeText }}
            </span>
            @if(($preventivoData['horometro_actual'] ?? null) !== null)
                <span class="text-[11px] text-slate-500 whitespace-nowrap">
                    {{ number_format($preventivoData['horometro_actual'], 1) }} h
                </span>
            @endif
        </div>

        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full rounded-full {{ $barClass }}" style="width: {{ $porcentaje }}%"></div>
        </div>

        @if(($preventivoData['horas_usadas'] ?? null) !== null)
            <div class="flex items-center justify-between text-[11px] text-slate-500">
                <span>{{ number_format($preventivoData['horas_usadas'], 1) }} / {{ number_format($preventivoData['intervalo_horas'], 0) }} h</span>
                @if(($preventivoData['proximo_horometro'] ?? null) !== null)
                    <span>Meta {{ number_format($preventivoData['proximo_horometro'], 1) }} h</span>
                @endif
            </div>
        @endif
    </div>
@endif