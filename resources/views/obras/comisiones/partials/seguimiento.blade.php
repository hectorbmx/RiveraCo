@if(($seguimiento['avance']['total'] ?? 0) > 0)
    @php
        $avance = $seguimiento['avance'];
        $porcentaje = $avance['porcentaje'];
        $siguiente = $seguimiento['siguiente_etapa'];
        $cancelada = ($seguimiento['estado'] ?? '') === 'cancelada';
        $nombresEtapa = [
            'perforacion' => 'Perforación',
            'bentonita' => 'Bentonita',
            'ademe' => 'Ademe',
            'acero' => 'Colocación de acero',
            'colado' => 'Colado',
        ];
    @endphp

    <section class="bg-white border rounded-xl shadow-sm p-6 mb-6" aria-label="Seguimiento de la comisión">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-700">Avance del trabajo</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $avance['completadas'] }} de {{ $avance['total'] - $avance['no_aplica'] }} etapas aplicables completadas
                </p>
            </div>
            <span class="text-2xl font-bold text-[#0B265A]">{{ $porcentaje !== null ? $porcentaje . '%' : 'Sin etapas aplicables' }}</span>
        </div>

        @if($porcentaje !== null)
            <div class="h-3 overflow-hidden rounded-full bg-slate-100" role="progressbar"
                 aria-label="Avance del trabajo" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $porcentaje }}">
                <div class="h-full rounded-full" style="width: {{ $porcentaje }}%; background-color: #0B265A;"></div>
            </div>
        @endif

        @if($cancelada)
            <p class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-600">Comisión cancelada.</p>
        @elseif($siguiente)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                <span class="text-xs font-bold uppercase tracking-wide text-amber-800">Siguiente paso</span>
                <span class="text-sm font-semibold text-[#0B265A]">
                    {{ $nombresEtapa[$siguiente['etapa']] ?? ucfirst($siguiente['etapa']) }}
                    @if($siguiente['estado'] === 'en_proceso')
                        <span class="font-normal text-slate-500">· En proceso</span>
                    @endif
                </span>
            </div>
        @elseif($porcentaje === 100)
            <p class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">Todas las etapas aplicables están completadas.</p>
        @endif
    </section>
@endif
