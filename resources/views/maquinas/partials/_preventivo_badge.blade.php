@php
    $preventivo = $preventivo ?? null;
    $color = $preventivo['color'] ?? 'slate';
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
    $porcentaje = $preventivo ? min(100, max(0, (float)($preventivo['porcentaje'] ?? 0))) : 0;
@endphp

@if(!$preventivo)
    <span class="inline-flex px-2 py-1 rounded-lg border text-xs bg-slate-50 text-slate-600 border-slate-200">
        Sin datos
    </span>
@else
    <div class="min-w-[190px] space-y-1.5">
        <div class="flex items-center justify-between gap-2">
            <span class="inline-flex px-2 py-0.5 rounded-lg border text-xs font-medium {{ $badgeClass }}">
                {{ $preventivo['label'] }}
            </span>
            @if($preventivo['horometro_actual'] !== null)
                <span class="text-[11px] text-slate-500 whitespace-nowrap">
                    {{ number_format($preventivo['horometro_actual'], 1) }} h
                </span>
            @endif
        </div>

        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full rounded-full {{ $barClass }}" style="width: {{ $porcentaje }}%"></div>
        </div>

        @if($preventivo['horas_usadas'] !== null)
            <div class="flex items-center justify-between text-[11px] text-slate-500">
                <span>{{ number_format($preventivo['horas_usadas'], 1) }} / {{ number_format($preventivo['intervalo_horas'], 0) }} h</span>
                @if($preventivo['proximo_horometro'] !== null)
                    <span>Meta {{ number_format($preventivo['proximo_horometro'], 1) }} h</span>
                @endif
            </div>
        @endif
    </div>
@endif
