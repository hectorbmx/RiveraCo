@php
    $zonaAsistencia = 'America/Mexico_City';
    $hoyAsistencia = \Carbon\Carbon::now($zonaAsistencia);
    $inicioSemanaActual = $hoyAsistencia->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $finSemanaActual = $hoyAsistencia->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

    $inicioSeleccionado = $asist_desde
        ? \Carbon\Carbon::parse($asist_desde, $zonaAsistencia)->startOfWeek(\Carbon\Carbon::MONDAY)
        : $inicioSemanaActual->copy();
    $finSeleccionado = $asist_hasta
        ? \Carbon\Carbon::parse($asist_hasta, $zonaAsistencia)->endOfWeek(\Carbon\Carbon::SUNDAY)
        : $inicioSeleccionado->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

    $inicioSemanaAnterior = $inicioSeleccionado->copy()->subWeek();
    $finSemanaAnterior = $inicioSemanaAnterior->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $inicioSemanaSiguiente = $inicioSeleccionado->copy()->addWeek();
    $finSemanaSiguiente = $inicioSemanaSiguiente->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

    $rutaSemana = fn ($inicio, $fin) => route('obras.edit', [
        'obra' => $obra->id,
        'tab' => 'asistencias',
        'asist_desde' => $inicio->toDateString(),
        'asist_hasta' => $fin->toDateString(),
    ]);

    $esSemanaActual = $inicioSeleccionado->isSameDay($inicioSemanaActual);
@endphp

<form method="GET" action="{{ route('obras.edit', $obra) }}" class="mb-4">
    <input type="hidden" name="tab" value="asistencias">

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Semana de asistencia</p>
                <p class="mt-1 text-lg font-bold text-slate-900">
                    {{ $inicioSeleccionado->format('d/m/Y') }} - {{ $finSeleccionado->format('d/m/Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $rutaSemana($inicioSemanaAnterior, $finSemanaAnterior) }}"
                   class="inline-flex h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    Semana anterior
                </a>

                <a href="{{ $rutaSemana($inicioSemanaActual, $finSemanaActual) }}"
                   class="inline-flex h-10 items-center rounded-xl border px-3 text-sm font-semibold shadow-sm {{ $esSemanaActual ? 'border-[#0B265A] bg-[#0B265A] text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                    Semana actual
                </a>

                <a href="{{ $rutaSemana($inicioSemanaSiguiente, $finSemanaSiguiente) }}"
                   class="inline-flex h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    Semana siguiente
                </a>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-end gap-4 border-t border-slate-100 pt-4">
            <div class="flex flex-col">
                <label class="mb-1 text-xs font-semibold text-slate-500">Desde</label>
                <input type="date" name="asist_desde" value="{{ old('asist_desde', $asist_desde ?? '') }}" class="w-44 rounded-xl border-slate-300 text-sm focus:border-yellow-400 focus:ring-yellow-400">
            </div>

            <div class="flex flex-col">
                <label class="mb-1 text-xs font-semibold text-slate-500">Hasta</label>
                <input type="date" name="asist_hasta" value="{{ old('asist_hasta', $asist_hasta ?? '') }}" class="w-44 rounded-xl border-slate-300 text-sm focus:border-yellow-400 focus:ring-yellow-400">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-yellow-400 px-4 text-sm font-semibold text-gray-900 transition hover:bg-yellow-500">
                    Filtrar
                </button>

                <a href="{{ route('obras.edit', [$obra, 'tab' => 'asistencias']) }}" class="inline-flex h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Limpiar
                </a>
            </div>
        </div>
    </div>
</form>
