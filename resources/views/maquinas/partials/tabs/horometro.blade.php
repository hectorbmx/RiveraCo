@php
    $lecturas = collect($lecturasHorometro ?? []);
    $conflictos = (int) ($resumenHorometro['duplicadas'] ?? 0) + (int) ($resumenHorometro['menores'] ?? 0);
    $anioHorometro = now()->year;
    $nombresMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $horasPorMes = collect(range(1, 12))->map(function (int $mes) use ($lecturas, $anioHorometro, $nombresMeses) {
        return [
            'mes' => $nombresMeses[$mes - 1],
            'horas' => round((float) $lecturas
                ->filter(fn (array $lectura) =>
                    ($lectura['estado'] ?? null) === 'valida'
                    && $lectura['fecha']?->year === $anioHorometro
                    && $lectura['fecha']?->month === $mes
                )
                ->sum('avance_valido'), 2),
        ];
    })->values();
    $maxHorasGrafica = max(1, (float) $horasPorMes->max('horas'));
    $totalHorasGrafica = (float) $horasPorMes->sum('horas');
@endphp

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-[#0B265A]">Historial de horómetro</h2>
                <p class="text-sm text-slate-500">Lecturas consolidadas de la app, web y ajustes del mecánico.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-blue-50 px-3 py-1 font-medium text-blue-700">
                    {{ $resumenHorometro['validas'] ?? 0 }} válidas
                </span>
                <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">
                    {{ number_format((float) ($resumenHorometro['avance_valido'] ?? 0), 2) }} h acumuladas
                </span>
                @if($conflictos > 0)
                    <span class="rounded-full bg-amber-50 px-3 py-1 font-medium text-amber-700">
                        {{ $conflictos }} conflictos
                    </span>
                @endif
                <button type="button" data-open-horometro-chart title="Ver horas trabajadas por mes" aria-label="Ver horas trabajadas por mes"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-300 bg-white text-[#0B265A] hover:border-[#0B265A] hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 19V5m0 14h16" stroke-linecap="round" />
                        <path d="M7 16v-4h3v4H7Zm5 0V8h3v8h-3Zm5 0v-6h3v6h-3Z" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="horometro-chart-modal" class="hidden fixed inset-0 z-50 items-center justify-center overflow-y-auto bg-slate-950/60 px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="horometro-chart-title">
        <div class="max-h-[85vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <h2 id="horometro-chart-title" class="text-lg font-semibold text-slate-900">Horas trabajadas por mes</h2>
                    <p class="text-sm text-slate-500">Horas válidas del horómetro consolidado durante {{ $anioHorometro }}.</p>
                </div>
                <button type="button" data-close-horometro-chart title="Cerrar gráfica" aria-label="Cerrar gráfica" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800">&times;</button>
            </div>
            <div class="mt-5 flex items-center gap-4 text-xs text-slate-600">
                <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-sky-400"></span>Horas válidas</span>
                <span class="border-l border-slate-300 pl-4 font-semibold text-slate-700">
                    Total {{ $anioHorometro }}: {{ number_format($totalHorasGrafica, 2) }} h
                </span>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($horasPorMes as $resumenMes)
                    <div class="grid grid-cols-[5.5rem_1fr_7rem] items-center gap-3 text-xs">
                        <span class="font-medium text-slate-600">{{ $resumenMes['mes'] }}</span>
                        <div class="h-3 overflow-hidden rounded-full bg-slate-200" title="{{ number_format($resumenMes['horas'], 2) }} horas válidas">
                            <div class="h-full rounded-full bg-sky-400" style="width: {{ round(($resumenMes['horas'] / $maxHorasGrafica) * 100, 2) }}%"></div>
                        </div>
                        <span class="text-right text-slate-500">{{ number_format($resumenMes['horas'], 2) }} h</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('horometro-chart-modal');
            if (!modal) return;
            document.addEventListener('click', (event) => {
                if (event.target.closest('[data-open-horometro-chart]')) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
                if (event.target.closest('[data-close-horometro-chart]') || event.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        })();
    </script>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-[#0B265A] text-white">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Fecha captura</th>
                        <th class="px-4 py-3 text-left font-semibold">Capturado por</th>
                        <th class="px-4 py-3 text-left font-semibold">Origen</th>
                        <th class="px-4 py-3 text-left font-semibold">Obra</th>
                        <th class="px-4 py-3 text-right font-semibold">Horómetro</th>
                        <th class="px-4 py-3 text-right font-semibold">Horas</th>
                        <th class="px-4 py-3 text-left font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($lecturas as $lectura)
                        @php
                            $fuente = match ($lectura['fuente'] ?? '') {
                                'app' => 'App',
                                'web_obras' => 'Residente',
                                'web' => 'Mecánico (supervisor)',
                                'web_maquinas' => 'Mecánico (supervisor)',
                                default => ucfirst(str_replace('_', ' ', (string) ($lectura['fuente'] ?? '—'))),
                            };
                            $estado = $lectura['estado'] ?? '—';
                            $estadoClase = match ($estado) {
                                'valida' => 'bg-emerald-50 text-emerald-700',
                                'duplicada' => 'bg-slate-100 text-slate-600',
                                'menor' => 'bg-amber-50 text-amber-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                {{ $lectura['fecha']?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $lectura['usuario'] ?? 'Sin usuario' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                    {{ $fuente }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $lectura['obra'] ?? 'Patio / Sin obra' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-slate-700">
                                {{ number_format((float) ($lectura['horometro_anterior'] ?? 0), 2) }}
                                <span class="px-1 text-slate-400">→</span>
                                {{ number_format((float) ($lectura['horometro_nuevo'] ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                {{ number_format((float) ($lectura['avance_valido'] ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $estadoClase }}">
                                    {{ ucfirst($estado) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Sin lecturas de horómetro registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
