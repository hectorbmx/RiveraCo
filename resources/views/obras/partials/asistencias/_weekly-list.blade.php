@php
    $estadoCampoLabels = [
        'confirmado' => 'Confirmado',
        'confirmado_parcial' => 'Parcial',
        'sin_evidencia' => 'Sin evidencia',
        'excepcion' => 'Excepcion',
        'no_planeado' => 'No planeado',
        'hoy_capturable' => 'Hoy',
        'pendiente_vencido' => 'Pendiente',
        'futuro' => 'Futuro',
    ];
    $estadoCampoClasses = [
        'confirmado' => 'bg-emerald-100 text-emerald-800',
        'confirmado_parcial' => 'bg-blue-100 text-blue-800',
        'sin_evidencia' => 'bg-amber-100 text-amber-800',
        'excepcion' => 'bg-violet-100 text-violet-800',
        'no_planeado' => 'bg-slate-100 text-slate-600',
        'hoy_capturable' => 'bg-emerald-200 text-emerald-900',
        'pendiente_vencido' => 'bg-amber-200 text-amber-900',
        'futuro' => 'bg-slate-200 text-slate-700',
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
            <thead class="bg-[#0B265A] text-white">
                <tr>
                    <th class="text-left px-4 py-3 sticky left-0 bg-[#0B265A] text-white z-10 min-w-[260px]">Empleado</th>
                    @foreach($weekDays as $wd)
                        <th class="text-center px-3 py-3 border-l border-white/20 bg-[#0B265A] text-white min-w-[150px]">
                            <div class="text-[11px] font-semibold leading-none text-white">{{ $wd['dow'] }}</div>
                            <div class="font-semibold text-white leading-none mt-1">{{ $wd['label'] }}</div>
                        </th>
                    @endforeach
                    <th class="text-center px-3 py-3 border-l border-white/20 bg-[#0B265A] text-white min-w-[110px]">Resumen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($asistenciasSemana as $row)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-4 py-3 sticky left-0 bg-[#0B265A] text-white z-10 min-w-[260px]">
                            <div class="font-semibold text-white">
                                {{ $row->empleado->Nombre }} {{ $row->empleado->Apellidos }}
                            </div>
                            <div class="text-xs text-blue-100">
                                {{ $row->asignacion->puesto_en_obra ?: ($row->empleado->Puesto ?? $row->empleado->puesto_base ?? '') }}
                            </div>
                        </td>

                        @foreach($weekDays as $wd)
                            @php
                                $cell = $row->dias[$wd['date']];
                                $estado = $cell['estado_visual'] ?? ($cell['estado_campo'] ?? 'sin_evidencia');
                                $estadoClase = $estadoCampoClasses[$estado] ?? 'bg-slate-100 text-slate-600';
                                $estadoLabel = $estadoCampoLabels[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
                                $bloqueada = (bool) ($cell['bloqueada'] ?? false);
                                $capturable = (bool) ($cell['capturable'] ?? false);
                            @endphp
                            <td class="align-top px-3 py-3 border-l border-gray-200 {{ $bloqueada ? 'bg-slate-50' : ($capturable ? 'bg-emerald-50/60' : 'bg-white') }}">
                                <label class="inline-flex items-center gap-2 font-semibold text-slate-800 {{ $bloqueada ? 'opacity-60' : '' }}">
                                    <input type="checkbox" class="asistencia-semanal-check rounded border-gray-300 text-blue-600 focus:ring-blue-500" name="asistencia[{{ $row->empleado->id_Empleado }}][{{ $wd['date'] }}][planeado]" value="1" @checked($cell['planeado']) @disabled($bloqueada)>
                                    Asistencia
                                </label>

                                <div class="mt-2 flex items-center justify-center gap-1 text-[11px]">
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5">Ent {{ $cell['entrada'] ?? '--' }}</span>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5">Sal {{ $cell['salida'] ?? '--' }}</span>
                                </div>

                                <div class="mt-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $estadoClase }}">
                                        {{ $estadoLabel }}
                                    </span>
                                </div>

                                @if(!empty($cell['modo_celda']))
                                    <div class="mt-2 text-[10px] text-slate-500">
                                        {{ ucfirst($cell['modo_celda']) }}
                                    </div>
                                @endif

                                @if($capturable)
                                    <button type="button"
                                            data-open-manual-modal
                                            data-empleado-id="{{ $row->empleado->id_Empleado }}"
                                            data-fecha="{{ $wd['date'] }}"
                                            data-empleado-label="{{ trim(($row->empleado->Nombre ?? '') . ' ' . ($row->empleado->Apellidos ?? '')) }}"
                                            class="mt-2 inline-flex w-full items-center justify-center rounded-md bg-emerald-600 px-2 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-700">
                                        Capturar
                                    </button>
                                @elseif($bloqueada)
                                    <div class="mt-2 text-[10px] font-medium text-slate-500">
                                        Bloqueado
                                    </div>
                                @endif

                                <div class="mt-2 inline-flex rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                    {{ $cell['origen_label'] ?? 'Sin evidencia' }}
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

                                <select name="asistencia[{{ $row->empleado->id_Empleado }}][{{ $wd['date'] }}][excepcion_tipo]" class="mt-2 w-full rounded-md border-gray-300 text-[11px]" @disabled($bloqueada)>
                                    <option value="">Sin excepcion</option>
                                    <option value="sin_senal" @selected($cell['excepcion_tipo'] === 'sin_senal')>Sin señal</option>
                                    <option value="sin_camara" @selected($cell['excepcion_tipo'] === 'sin_camara')>Sin camara</option>
                                    <option value="ocupacion_obra" @selected($cell['excepcion_tipo'] === 'ocupacion_obra')>Ocupacion de obra</option>
                                    <option value="autorizada" @selected($cell['excepcion_tipo'] === 'autorizada')>Autorizada</option>
                                </select>

                                <input type="text" name="asistencia[{{ $row->empleado->id_Empleado }}][{{ $wd['date'] }}][excepcion_motivo]" value="{{ $cell['excepcion_motivo'] }}" placeholder="Nota" class="mt-2 w-full rounded-md border-gray-300 text-[11px]" @disabled($bloqueada)>
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

@if($asistenciasSemana->isNotEmpty())
    <div class="mt-6 overflow-hidden rounded-xl border border-[#0B265A] shadow-sm">
        <div class="mb-0 flex items-center justify-between bg-[#0B265A] px-4 py-3">
            <h3 class="text-sm font-semibold text-white">Resumen por empleado</h3>
            <span class="text-[11px] font-medium text-blue-100">Totales de la semana</span>
        </div>

        <!-- <div class="grid grid-cols-1 gap-3 bg-white p-3 xl:grid-cols-3"> -->
  

        <!-- Ajuste clave aquí: md:grid-cols-2 y xl:grid-cols-3 -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 bg-white p-4">
            @foreach($asistenciasSemana as $row)
                @php
                    $resumenEmpleado = [
                        'planeadas' => 0,
                        'confirmadas' => 0,
                        'manuales' => 0,
                        'app' => 0,
                        'parciales' => 0,
                        'sin_evidencia' => 0,
                        'excepciones' => 0,
                    ];

                    foreach (($row->dias ?? []) as $cell) {
                        $estado = $cell['estado_visual'] ?? 'sin_evidencia';
                        $origen = $cell['origen_label'] ?? 'Sin evidencia';

                        if (!empty($cell['planeado'])) {
                            $resumenEmpleado['planeadas']++;
                        }

                        if (in_array($estado, ['confirmado', 'confirmado_parcial'], true)) {
                            $resumenEmpleado['confirmadas']++;
                        }

                        if ($estado === 'confirmado_parcial') {
                            $resumenEmpleado['parciales']++;
                        }

                        if ($origen === 'Manual') {
                            $resumenEmpleado['manuales']++;
                        }

                        if ($origen === 'App') {
                            $resumenEmpleado['app']++;
                        }

                        if (in_array($estado, ['sin_evidencia', 'hoy_capturable', 'pendiente_vencido', 'futuro'], true)) {
                            $resumenEmpleado['sin_evidencia']++;
                        }

                        if ($estado === 'excepcion') {
                            $resumenEmpleado['excepciones']++;
                        }
                    }
                @endphp

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-3 bg-[#0B265A] px-3 py-2 text-white">
                        <div>
                            <div class="font-semibold text-white text-xs">
                                {{ trim(($row->empleado->Nombre ?? '') . ' ' . ($row->empleado->Apellidos ?? '')) }}
                            </div>
                            <div class="text-[10px] text-blue-100">
                                {{ $row->asignacion->puesto_en_obra ?: ($row->empleado->Puesto ?? $row->empleado->puesto_base ?? '') }}
                            </div>
                        </div>
                        <span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold text-white shrink-0">
                            {{ $resumenEmpleado['planeadas'] }}/7 planeadas
                        </span>
                    </div>

                    <div class="p-3">
                        <div class="grid grid-cols-3 gap-2 text-[11px]">
                            <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5">
                                <div class="text-slate-500 text-[10px]">Planeadas</div>
                                <div class="mt-0.5 font-bold text-slate-800">{{ $resumenEmpleado['planeadas'] }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5">
                                <div class="text-slate-500 text-[10px]">Campo</div>
                                <div class="mt-0.5 font-bold text-emerald-700">{{ $resumenEmpleado['confirmadas'] }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5">
                                <div class="text-slate-500 text-[10px]">Manual</div>
                                <div class="mt-0.5 font-bold text-blue-700">{{ $resumenEmpleado['manuales'] }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5">
                                <div class="text-slate-500 text-[10px]">App</div>
                                <div class="mt-0.5 font-bold text-violet-700">{{ $resumenEmpleado['app'] }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5">
                                <div class="text-slate-500 text-[10px]">Parciales</div>
                                <div class="mt-0.5 font-bold text-amber-700">{{ $resumenEmpleado['parciales'] }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5">
                                <div class="text-slate-500 text-[10px]">Sin ev.</div>
                                <div class="mt-0.5 font-bold text-amber-800">{{ $resumenEmpleado['sin_evidencia'] }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 col-span-3 flex items-center justify-between">
                                <span class="text-slate-500 text-[10px]">Excepciones</span>
                                <span class="font-bold text-violet-800">{{ $resumenEmpleado['excepciones'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div id="manualAsistenciaModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4">
    <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Captura manual</h3>
                <p id="manualAsistenciaEmpleado" class="mt-1 text-xs text-slate-500">Empleado</p>
            </div>
            <button type="button" data-close-manual-modal class="rounded-md border border-slate-200 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Cerrar</button>
        </div>

        <form method="POST" action="{{ route('obras.asistencias.manual.guardar', $obra) }}" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" name="empleado_id" id="manualEmpleadoId">
            <input type="hidden" name="fecha" id="manualFecha">
            <input type="hidden" name="semana_desde" id="manualSemanaDesde" value="{{ request('asist_desde') ?? now('America/Mexico_City')->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString() }}">
            <input type="hidden" name="semana_hasta" id="manualSemanaHasta" value="{{ request('asist_hasta') ?? now('America/Mexico_City')->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString() }}">

            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600">Fecha</label>
                <div id="manualFechaLabel" class="mt-1 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"></div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="manualEntradaHora" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600">Entrada</label>
                    <input id="manualEntradaHora" name="entrada_hora" type="time" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="manualSalidaHora" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600">Salida</label>
                    <input id="manualSalidaHora" name="salida_hora" type="time" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label for="manualMotivo" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-600">Motivo / nota</label>
                <textarea id="manualMotivo" name="motivo" rows="3" required class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ej. Captura manual por falta de señal..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" data-close-manual-modal class="rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                <button type="submit" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleAsistenciaSemanal(checked) {
        document.querySelectorAll('.asistencia-semanal-check').forEach((input) => {
            input.checked = checked;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('manualAsistenciaModal');
        const empleadoLabel = document.getElementById('manualAsistenciaEmpleado');
        const empleadoIdInput = document.getElementById('manualEmpleadoId');
        const fechaInput = document.getElementById('manualFecha');
        const semanaDesdeInput = document.getElementById('manualSemanaDesde');
        const semanaHastaInput = document.getElementById('manualSemanaHasta');
        const fechaLabel = document.getElementById('manualFechaLabel');
        const entradaInput = document.getElementById('manualEntradaHora');
        const salidaInput = document.getElementById('manualSalidaHora');
        const motivoInput = document.getElementById('manualMotivo');

        const url = new URL(window.location.href);
        if (semanaDesdeInput && !semanaDesdeInput.value) {
            semanaDesdeInput.value = url.searchParams.get('asist_desde') || '';
        }
        if (semanaHastaInput && !semanaHastaInput.value) {
            semanaHastaInput.value = url.searchParams.get('asist_hasta') || '';
        }

        function closeManualModal() {
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            empleadoIdInput.value = '';
            fechaInput.value = '';
            fechaLabel.textContent = '';
            empleadoLabel.textContent = 'Empleado';
            entradaInput.value = '';
            salidaInput.value = '';
            motivoInput.value = '';
        }

        document.querySelectorAll('[data-open-manual-modal]').forEach((button) => {
            button.addEventListener('click', function () {
                const empleadoId = this.dataset.empleadoId;
                const fecha = this.dataset.fecha;
                const empleado = this.dataset.empleadoLabel || 'Empleado';

                empleadoLabel.textContent = empleado;
                empleadoIdInput.value = empleadoId;
                fechaInput.value = fecha;
                fechaLabel.textContent = fecha;

                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            });
        });

        document.querySelectorAll('[data-close-manual-modal]').forEach((button) => {
            button.addEventListener('click', closeManualModal);
        });

        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeManualModal();
                }
            });
        }
    });
</script>

