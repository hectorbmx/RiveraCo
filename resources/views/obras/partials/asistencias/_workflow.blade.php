@php
    $estatusActual = $asistenciaSemanalReporte?->estatus ?? 'borrador';
    $flujo = [
        'generado' => ['label' => 'Generado', 'area' => 'Residente'],
        'revisado' => ['label' => 'Revisado', 'area' => 'Aux. pilas'],
        'autorizado' => ['label' => 'Autorizado', 'area' => 'Gerente construccion'],
        'pagado' => ['label' => 'Pagado', 'area' => 'Administracion'],
    ];
    $orden = array_keys($flujo);
    $actualIndex = array_search($estatusActual, $orden, true);
@endphp

<div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
    <div class="flex flex-wrap items-center gap-2">
        @foreach($flujo as $key => $item)
            @php
                $idx = array_search($key, $orden, true);
                $done = $actualIndex !== false && $idx <= $actualIndex;
            @endphp
            <div class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs {{ $done ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-500' }}">
                <span class="h-2 w-2 rounded-full {{ $done ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                <span class="font-semibold">{{ $item['label'] }}</span>
                <span class="text-[11px]">{{ $item['area'] }}</span>
            </div>
        @endforeach
    </div>
</div>
