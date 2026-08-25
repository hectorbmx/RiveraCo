@php
    $estadoCampoLabels = [
        'confirmado' => 'Confirmado',
        'confirmado_parcial' => 'Parcial',
        'sin_evidencia' => 'Sin evidencia',
        'excepcion' => 'Excepcion',
        'no_planeado' => 'No planeado',
    ];
    $estadoCampoClasses = [
        'confirmado' => 'bg-emerald-100 text-emerald-800',
        'confirmado_parcial' => 'bg-blue-100 text-blue-800',
        'sin_evidencia' => 'bg-amber-100 text-amber-800',
        'excepcion' => 'bg-violet-100 text-violet-800',
        'no_planeado' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<form method="POST" action="{{ route('obras.asistencias.semanal.guardar', $obra) }}" class="mb-8" id="asistenciaSemanalForm">
    @csrf
    <input type="hidden" name="semana_inicio" value="{{ $asist_desde }}">
    <input type="hidden" name="semana_fin" value="{{ $asist_hasta }}">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div>
            <div class="text-sm font-semibold text-gray-800">
                Semana {{ $weekDays->first()['label'] }} - {{ $weekDays->last()['label'] }}
            </div>
            <div class="text-xs text-gray-500">
                Base administrativa para nomina, validada debajo con la asistencia tomada en campo.
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="toggleAsistenciaSemanal(true)" class="px-3 py-2 rounded-md border text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Marcar todos
            </button>
            <button type="button" onclick="toggleAsistenciaSemanal(false)" class="px-3 py-2 rounded-md border text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Desmarcar todos
            </button>
            <button type="submit" name="accion" value="guardar" class="px-4 py-2 rounded-md bg-slate-800 text-sm font-semibold text-white hover:bg-slate-900">
                Guardar lista
            </button>
            <button type="submit" name="accion" value="generar" formtarget="_blank" class="px-4 py-2 rounded-md bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">
                Generar PDF
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-3 sticky left-0 bg-gray-50 z-10 min-w-[260px]">Empleado</th>
                    @foreach($weekDays as $wd)
                        <th class="text-center px-3 py-3 border-l border-gray-200 min-w-[150px]">
                            <div class="text-[11px] text-gray-500 leading-none">{{ $wd['dow'] }}</div>
                            <div class="font-semibold text-gray-800 leading-none mt-1">{{ $wd['label'] }}</div>
                        </th>
                    @endforeach
                    <th class="text-center px-3 py-3 border-l border-gray-200 min-w-[110px]">Resumen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($asistenciasSemana as $row)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-4 py-3 sticky left-0 bg-white z-10 min-w-[260px]">
                            <div class="font-semibold text-gray-900">
                                {{ $row->empleado->Nombre }} {{ $row->empleado->Apellidos }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $row->asignacion->puesto_en_obra ?: ($row->empleado->Puesto ?? $row->empleado->puesto_base ?? '') }}
                            </div>
                        </td>

                        @foreach($weekDays as $wd)
                            @php
                                $cell = $row->dias[$wd['date']];
                                $estado = $cell['estado_campo'];
                            @endphp
                            <td class="align-top px-3 py-3 border-l border-gray-200">
                                <label class="inline-flex items-center gap-2 font-semibold text-slate-800">
                                    <input type="checkbox" class="asistencia-semanal-check rounded border-gray-300 text-blue-600 focus:ring-blue-500" name="asistencia[{{ $row->empleado->id_Empleado }}][{{ $wd['date'] }}][planeado]" value="1" @checked($cell['planeado'])>
                                    Asistencia
                                </label>

                                <div class="mt-2 flex items-center justify-center gap-1 text-[11px]">
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5">Ent {{ $cell['entrada'] ?? '--' }}</span>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5">Sal {{ $cell['salida'] ?? '--' }}</span>
                                </div>

                                <div class="mt-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $estadoCampoClasses[$estado] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $estadoCampoLabels[$estado] ?? $estado }}
                                    </span>
                                </div>

                                <input type="hidden" name="asistencia[{{ $row->empleado->id_Empleado }}][{{ $wd['date'] }}][estado_admin]" value="{{ $cell['estado_admin'] }}">
                                {{-- Estado administrativo para una etapa posterior.
                                <select name="asistencia[{{ $row->empleado->id_Empleado }}][{{ $wd['date'] }}][estado_admin]" class="mt-2 w-full rounded-md border-gray-300 text-[11px]">
                                    <option value="planeado" @selected($cell['estado_admin'] === 'planeado')>Planeado</option>
                                    <option value="falta_reportada" @selected($cell['estado_admin'] === 'falta_reportada')>Falta</option>
                                    <option value="ajuste_pendiente" @selected($cell['estado_admin'] === 'ajuste_pendiente')>Ajuste pendiente</option>
                                    <option value="ajustado" @selected($cell['estado_admin'] === 'ajustado')>Ajustado</option>
                                </select>
                                --}}

                                <select name="asistencia[{{ $row->empleado->id_Empleado }}][{{ $wd['date'] }}][excepcion_tipo]" class="mt-2 w-full rounded-md border-gray-300 text-[11px]">
                                    <option value="">Sin excepcion</option>
                                    <option value="sin_senal" @selected($cell['excepcion_tipo'] === 'sin_senal')>Sin señal</option>
                                    <option value="sin_camara" @selected($cell['excepcion_tipo'] === 'sin_camara')>Sin camara</option>
                                    <option value="ocupacion_obra" @selected($cell['excepcion_tipo'] === 'ocupacion_obra')>Ocupacion de obra</option>
                                    <option value="autorizada" @selected($cell['excepcion_tipo'] === 'autorizada')>Autorizada</option>
                                </select>

                                <input type="text" name="asistencia[{{ $row->empleado->id_Empleado }}][{{ $wd['date'] }}][excepcion_motivo]" value="{{ $cell['excepcion_motivo'] }}" placeholder="Nota" class="mt-2 w-full rounded-md border-gray-300 text-[11px]">
                            </td>
                        @endforeach

                        <td class="px-3 py-3 border-l border-gray-200 text-center">
                            <div class="font-semibold text-slate-900">{{ $row->totales['planeados'] }} asistencia</div>
                            <div class="text-emerald-700">{{ $row->totales['confirmados'] }} campo</div>
                            <div class="text-amber-700">{{ $row->totales['sin_evidencia'] }} sin evidencia</div>
                            <div class="text-violet-700">{{ $row->totales['excepciones'] }} excepcion</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 2 + $weekDays->count() }}" class="px-4 py-6 text-center text-gray-500">
                            No hay empleados activos asignados a esta obra.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>

<script>
    function toggleAsistenciaSemanal(checked) {
        document.querySelectorAll('.asistencia-semanal-check').forEach((input) => {
            input.checked = checked;
        });
    }
</script>

