@extends('layouts.admin')

@section('title', 'Nueva comisión')

@section('content')

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">
                Nueva comisión – {{ $obra->nombre_obra ?? $obra->clave_obra ?? 'Obra' }}
            </h1>
            <p class="text-sm text-slate-500">
                Cliente: {{ $obra->cliente->nombre_comercial ?? 'N/D' }}
            </p>
        </div>

        <a href="{{ route('obras.edit', ['obra' => $obra, 'tab' => 'comisiones']) }}"
           class="text-sm text-slate-600 hover:text-slate-800">
            ← Volver a comisiones de la obra
        </a>
    </div>
    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            <p class="font-semibold mb-1">Hay errores en el formulario:</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li class="text-xs">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('obras.comisiones.store', $obra) }}" class="mt-4 space-y-6">

        @csrf
        {{-- CARD: Datos generales de la comisión --}}
        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-1">
                Datos generales de la comisión
            </h2>
            <p class="text-xs text-slate-500 mb-4">
                Define la fecha, la pila y la información general del formato.
            </p>

            <!-- @php
                // Intentamos detectar residente de la obra a partir de las asignaciones
                $residentes = $asignacionesEmpleados->filter(function ($asig) {
                    $emp = $asig->empleado;
                    if (!$emp) return false;
                    return strtoupper($emp->puesto_base ?? '') === 'RESIDENTE';
                });
                $residentePorDefecto = $residentes->first();
            @endphp -->
                @php
                        
        $rolResidenteId = \App\Models\CatalogoRol::where('rol_key','RESIDENTE')->value('id');
    


                    $personalAsignado = $asignacionesEmpleados->filter(function ($asignacion) use ($rolResidenteId) {
                        if (!$rolResidenteId) return true; // si no existe rol residente, no filtramos
                        return (int)$asignacion->rol_id !== (int)$rolResidenteId;
                    });
                @endphp

            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                {{-- Fecha --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Fecha
                    </label>
                    <input type="date"
                           name="fecha"
                           value="{{ old('fecha', now()->toDateString()) }}"
                           class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                    @error('fecha')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

              {{-- Máquina perforadora --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Máquina perforadora
                        </label>

                        @if($maquinaPerforadora && $maquinaPerforadora->maquina)
                            @php $maq = $maquinaPerforadora->maquina; @endphp

                            {{-- Guardamos el id de la asignación obra-maquina --}}
                            <input type="hidden"
                                name="obra_maquina_id"
                                value="{{ $maquinaPerforadora->id }}">

                            <input type="text"
                                class="w-full rounded-xl border-slate-200 text-sm px-3 py-2 bg-slate-50"
                                value="{{ $maq->nombre ?? $maq->descripcion ?? ('Máquina #'.$maq->id) }}"
                                readonly>
                        @else
                            <p class="text-xs text-red-500">
                                No hay una máquina activa asignada a esta obra. Asigna una en la pestaña de Maquinaria.
                            </p>
                        @endif
                    </div>

                {{-- Residente --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Residente
                    </label>

                    @if($residenteAsignado && $residenteAsignado->empleado)
                        @php $emp = $residenteAsignado->empleado; @endphp

                        {{-- Mandamos el id del empleado como residente_id, pero sin que el usuario lo elija --}}
                        <input type="hidden"
                            name="residente_id"
                            value="{{ $emp->id_Empleado }}">

                        <input type="text"
                            class="w-full rounded-xl border-slate-200 text-sm px-3 py-2 bg-slate-50"
                            value="{{ $emp->Nombre }} {{ $emp->Apellidos }} ({{ $emp->Puesto ?? $emp->puesto_base }})"
                            readonly>
                    @else
                        <p class="text-xs text-red-500">
                            No hay un residente asignado a esta obra. Asigna uno en la pestaña de Empleados.
                        </p>
                    @endif
                </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                {{-- Número de formato --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Número de formato
                    </label>
                    <input type="text"
                           name="numero_formato"
                           value="{{ old('numero_formato') }}"
                           class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                    @error('numero_formato')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Cliente nombre (puede venir por defecto de la obra) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Nombre del cliente (en formato)
                    </label>
                    <input type="text"
                           name="cliente_nombre"
                           value="{{ old('cliente_nombre', $obra->cliente->nombre_comercial ?? '') }}"
                           class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                    @error('cliente_nombre')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Observaciones --}}
            <div class="mt-4">
                <label class="block text-xs font-semibold text-slate-600 mb-1">
                    Observaciones
                </label>
                <textarea name="observaciones"
                          rows="3"
                          class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">{{ old('observaciones') }}</textarea>
                @error('observaciones')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- CARD: Datos generales del formato --}}
               {{-- CARD: Registro de personal (HORARIO / COMIDA / HORAS / EXTRA) --}}
        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-1">
                Personal en la obra
            </h2>
            <p class="text-xs text-slate-500 mb-4">
                Captura el horario trabajado por cada elemento. El residente no se incluye en esta tabla.
            </p>

            @php
                // Excluir al residente: filtramos por puesto_base != RESIDENTE (si existe)
                $personalAsignado = $asignacionesEmpleados->filter(function ($asignacion) {
                    $emp = $asignacion->empleado;
                    if (!$emp) return false;

                    // Si tienes campo puesto_base, lo usamos para excluir residente
                    return strtoupper($emp->puesto_base ?? '') !== 'RESIDENTE';
                });
            @endphp

            @if($personalAsignado->isEmpty())
                <p class="text-sm text-slate-500">
                    No hay personal asignado a esta obra para registrar comisiones.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs md:text-sm">
                        <thead class="bg-slate-50 border-b text-slate-500">
                            <tr>
                                <th class="py-2 px-2 text-left">Inicio</th>
                                <th class="py-2 px-2 text-left">Fin</th>
                                <th class="py-2 px-2 text-left">Comida (hrs)</th>
                                <th class="py-2 px-2 text-left">Horas laboradas</th>
                                <th class="py-2 px-2 text-left">Tiempo extra</th>
                                <th class="py-2 px-2 text-left">Personal</th>
                                <th class="py-2 px-2 text-left text-xs font-semibold text-slate-500">Actividades</th>
                                                               
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($personalAsignado as $index => $asignacion)
                                @php $emp = $asignacion->empleado; @endphp
                                @if($emp)
                                    <tr class="border-b">
                                        {{-- ID de la asignación de empleado (clave para ComisionPersonal) --}}
                                        <input type="hidden"
                                               name="personales[{{ $index }}][asignacion_empleado_id]"
                                               value="{{ $asignacion->id }}">

                                        {{-- Inicio --}}
                                        <td class="py-2 px-2">
                                            <input type="time"
                                                   name="personales[{{ $index }}][hora_inicio]"
                                                   value="{{ old('personales.'.$index.'.hora_inicio', '08:00') }}"
                                                   class="w-32 border rounded-lg px-2 py-1 text-xs personal-inicio"
                                                   data-row="{{ $index }}">
                                        </td>

                                        {{-- Fin --}}
                                        <td class="py-2 px-2">
                                            <input type="time"
                                                   name="personales[{{ $index }}][hora_fin]"
                                                   value="{{ old('personales.'.$index.'.hora_fin', '17:00') }}"
                                                   class="w-32 border rounded-lg px-2 py-1 text-xs personal-fin"
                                                   data-row="{{ $index }}">
                                        </td>

                                        {{-- Comida (horas) --}}
                                        <td class="py-2 px-2">
                                            <input type="number"
                                                   step="0.25"
                                                   min="0"
                                                   name="personales[{{ $index }}][tiempo_comida]"
                                                   value="{{ old('personales.'.$index.'.tiempo_comida', 1) }}"
                                                   class="w-32 border rounded-lg px-2 py-1 text-xs personal-comida"
                                                   data-row="{{ $index }}">
                                        </td>

                                        {{-- Horas laboradas (calculado) --}}
                                        <td class="py-2 px-2">
                                            <input type="number"
                                                   step="0.25"
                                                   min="0"
                                                   name="personales[{{ $index }}][horas_laboradas]"
                                                   value="{{ old('personales.'.$index.'.horas_laboradas') }}"
                                                   class="w-32 border rounded-lg px-2 py-1 text-xs personal-horas"
                                                   data-row="{{ $index }}"
                                                   readonly>
                                        </td>

                                        {{-- Tiempo extra (calculado) --}}
                                        <td class="py-2 px-2">
                                            <input type="number"
                                                   step="0.25"
                                                   min="0"
                                                   name="personales[{{ $index }}][tiempo_extra]"
                                                   value="{{ old('personales.'.$index.'.tiempo_extra') }}"
                                                   class="w-32 border rounded-lg px-2 py-1 text-xs personal-extra"
                                                   data-row="{{ $index }}"
                                                   readonly>
                                        </td>
                                        {{-- Actividades comisionables --}}
                                        <td class="py-2 px-2">
                                            <div class="flex flex-wrap gap-3">
                                                @foreach($actividades as $act)
                                                    <label class="inline-flex items-center gap-2 text-[11px] text-slate-700">
                                                        <input type="checkbox"
                                                            name="personales[{{ $index }}][actividades][]"
                                                            value="{{ $act->id }}"
                                                            class="rounded border-slate-300">
                                                        <span>{{ $act->nombre }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>


                                        {{-- Personal --}}
                                        <td class="py-2 px-2">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-slate-800">
                                                    {{ $emp->Nombre }} {{ $emp->Apellidos }}
                                                </span>
                                                <span class="text-[11px] text-slate-500">
                                                    <!-- {{ $emp->Puesto ?? $emp->puesto_base }} -->
                                                    {{ $asignacion->rol?->nombre ?? ($emp->Puesto ?? $emp->puesto_base) }}

                                                    
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    
            {{-- CARD: Detalle de perforación (diámetros / volúmenes) --}}
        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-1">
                Detalle de perforación (diámetros y volúmenes)
            </h2>
            <p class="text-xs text-slate-500 mb-4">
                Captura los diámetros, profundidades y volúmenes correspondientes a esta pila.
            </p>
            {{-- Selector de tipo de pila para agregar filas --}}
            <div class="mb-4 flex flex-col md:flex-row md:items-end gap-3">
                <div class="md:w-1/3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Tipo de pila a agregar
                    </label>
                    <select id="select-tipo-pila"
                            class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                        <option value="">Selecciona un tipo de pila</option>
                        @foreach($pilas as $pila)
                            <option
                                value="{{ $pila->id }}"
                                data-diametro="{{ $pila->diametro_proyecto ?? ($pila->catalogo->diametro_cm ?? '') }}"
                                data-cantidad="{{ $pila->cantidad_programada ?? 1 }}"
                                data-profundidad="{{ $pila->profundidad_proyecto ?? '' }}"
                            >
                                {{ $pila->tipo }} (Pila {{ $pila->numero_pila }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <button type="button"
                            id="btn-agregar-detalle"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        + Agregar fila
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs md:text-sm" id="tabla-detalles-diametro">
                    <thead class="bg-slate-50 border-b text-slate-500">
                        <tr>
                            <th class="py-2 px-2 text-left">Diámetro</th>
                            <th class="py-2 px-2 text-left">Cant</th>
                            <th class="py-2 px-2 text-left">Profundidad</th>
                            <th class="py-2 px-2 text-left">Metros a comisión</th>
                            <th class="py-2 px-2 text-left">Kg/acero</th>
                            <th class="py-2 px-2 text-left">Vol. bentonita</th>
                            <th class="py-2 px-2 text-left">Vol. concreto</th>
                            <th class="py-2 px-2 text-left">ML Ademe Bauer</th>
                            <th class="py-2 px-2 text-left">Campana (pzas)</th>
                            <th class="py-2 px-2 text-left">Adicional</th>
                            <th class="py-2 px-2 text-left">Inicio perf.</th>
                            <th class="py-2 px-2 text-left">Término perf.</th>
                        </tr>
                    </thead>

                <tbody id="detalles-diametro-body">
    @php
        $oldDetalles = old('detalles');
    @endphp

    @if($oldDetalles)
        {{-- Caso: hubo error de validación, reconstruimos las filas anteriores --}}
        @foreach($oldDetalles as $index => $detalle)
            <tr data-index="{{ $index }}">
                {{-- Pila asociada (opcional, por si la usas en backend) --}}
                @if(isset($detalle['pila_id']))
                    <input type="hidden"
                           name="detalles[{{ $index }}][pila_id]"
                           value="{{ $detalle['pila_id'] }}">
                @endif

                <td class="py-2 px-2">
                    <input type="text"
                           name="detalles[{{ $index }}][diametro]"
                           value="{{ $detalle['diametro'] ?? '' }}"
                           class="w-full border rounded-lg px-2 py-1 text-xs">
                </td>
                <td class="py-2 px-2">
                    <input type="number"
                           min="0"
                           step="1"
                           name="detalles[{{ $index }}][cantidad]"
                           value="{{ $detalle['cantidad'] ?? 0 }}"
                           class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-cantidad">
                </td>
                <td class="py-2 px-2">
                    <input type="number"
                           min="0"
                           step="0.01"
                           name="detalles[{{ $index }}][profundidad]"
                           value="{{ $detalle['profundidad'] ?? '' }}"
                           class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-profundidad">
                </td>
                <td class="py-2 px-2">
                    <input type="number"
                           min="0"
                           step="0.01"
                           name="detalles[{{ $index }}][metros_comision]"
                           value="{{ $detalle['metros_comision'] ?? '' }}"
                           class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-metros">
                </td>
                <td class="py-2 px-2">
                    <input type="number"
                           min="0"
                           step="0.01"
                           name="detalles[{{ $index }}][kg_acero]"
                           value="{{ $detalle['kg_acero'] ?? '' }}"
                           class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-kg">
                </td>
                <td class="py-2 px-2">
                    <input type="number"
                           min="0"
                           step="0.01"
                           name="detalles[{{ $index }}][vol_bentonita]"
                           value="{{ $detalle['vol_bentonita'] ?? '' }}"
                           class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-bentonita">
                </td>
                <td class="py-2 px-2">
                    <input type="number"
                           min="0"
                           step="0.01"
                           name="detalles[{{ $index }}][vol_concreto]"
                           value="{{ $detalle['vol_concreto'] ?? '' }}"
                           class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-concreto">
                </td>
                <td class="py-2 px-2">
                    <input type="number"
                            min="0"
                            step="0.01"
                            name="detalles[{{ $index }}][ml_ademe_bauer]"
                            value="{{ $detalle['ml_ademe_bauer'] ?? 0 }}"
                            class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-ademe">
                    </td>
                <td class="py-2 px-2">
                <input type="number"
                        min="0"
                        step="1"
                        name="detalles[{{ $index }}][campana_pzas]"
                        value="{{ $detalle['campana_pzas'] ?? 0 }}"
                        class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-campana">
                </td>

                <td class="py-2 px-2">
                    <input type="number"
                           min="0"
                           step="0.01"
                           name="detalles[{{ $index }}][adicional]"
                           value="{{ $detalle['adicional'] ?? '' }}"
                           class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-adicional">
                </td>
                <td class="py-2 px-2">
                    <input type="time"
                           name="detalles[{{ $index }}][hora_inicio]"
                           value="{{ $detalle['hora_inicio'] ?? '' }}"
                           class="w-full border rounded-lg px-2 py-1 text-xs">
                </td>
                <td class="py-2 px-2">
                    <input type="time"
                           name="detalles[{{ $index }}][hora_fin]"
                           value="{{ $detalle['hora_fin'] ?? '' }}"
                           class="w-full border rounded-lg px-2 py-1 text-xs">
                </td>
            </tr>
        @endforeach
    @else
        {{-- Caso normal: primera carga - sin filas, se agregan desde el select + botón --}}
    @endif
</tbody>


                    <tfoot>
                        <tr class="border-t bg-slate-50">
                            <td class="py-2 px-2 text-right font-semibold text-xs md:text-sm">
                                TOTAL:
                            </td>
                            <td></td>
                            <td class="py-2 px-2">
                                <input type="number" readonly
                                       id="total_profundidad"
                                       class="w-full border rounded-lg px-2 py-1 text-xs bg-slate-100">
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" readonly
                                       id="total_metros"
                                       class="w-full border rounded-lg px-2 py-1 text-xs bg-slate-100">
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" readonly
                                       id="total_kg"
                                       class="w-full border rounded-lg px-2 py-1 text-xs bg-slate-100">
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" readonly
                                       id="total_bentonita"
                                       class="w-full border rounded-lg px-2 py-1 text-xs bg-slate-100">
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" readonly
                                       id="total_concreto"
                                       class="w-full border rounded-lg px-2 py-1 text-xs bg-slate-100">
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" readonly
                                        id="total_ademe"
                                        class="w-full border rounded-lg px-2 py-1 text-xs bg-slate-100">
                                </td>
                                <td class="py-2 px-2">
                                <input type="number" readonly
                                        id="total_campana"
                                        class="w-full border rounded-lg px-2 py-1 text-xs bg-slate-100">
                                </td>

                            <td class="py-2 px-2">
                                <input type="number" readonly
                                       id="total_adicional"
                                       class="w-full border rounded-lg px-2 py-1 text-xs bg-slate-100">
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
       
          {{-- BOTÓN DE GUARDADO --}}
        <div class="flex justify-end">
            <button type="submit"
                    class="px-6 py-2 rounded-xl bg-teal-600 text-white text-sm font-medium hover:bg-teal-700">
                Guardar comisión
            </button>
        </div>
</form>
    <script>
        function calcularFilaPersonal(row) {
            const inicioInput = document.querySelector('input.personal-inicio[data-row="' + row + '"]');
            const finInput    = document.querySelector('input.personal-fin[data-row="' + row + '"]');
            const comidaInput = document.querySelector('input.personal-comida[data-row="' + row + '"]');
            const horasInput  = document.querySelector('input.personal-horas[data-row="' + row + '"]');
            const extraInput  = document.querySelector('input.personal-extra[data-row="' + row + '"]');

            if (!inicioInput || !finInput || !comidaInput || !horasInput || !extraInput) return;

            const inicio = inicioInput.value;
            const fin    = finInput.value;
            const comida = parseFloat(comidaInput.value || 0);

            if (!inicio || !fin) {
                horasInput.value = '';
                extraInput.value = '';
                return;
            }

            // Convertimos HH:MM a horas decimales
            function aHorasDecimales(hhmm) {
                const [h, m] = hhmm.split(':').map(Number);
                return h + (m / 60);
            }

            let horasTotales = aHorasDecimales(fin) - aHorasDecimales(inicio);

            // si el turno pasó la medianoche, ajustamos
            if (horasTotales < 0) {
                horasTotales += 24;
            }

            const horasLaboradas = Math.max(horasTotales - comida, 0);
            const tiempoExtra    = Math.max(horasLaboradas - 8, 0);

            horasInput.value = horasLaboradas.toFixed(2);
            extraInput.value = tiempoExtra.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.personal-inicio, .personal-fin, .personal-comida');

            inputs.forEach(function (input) {
                const row = input.getAttribute('data-row');
                input.addEventListener('change', function () {
                    calcularFilaPersonal(row);
                });

                // cálculo inicial con los valores por defecto
                calcularFilaPersonal(row);
            });
        });
       function recalcularTotalesDiametro() {
        const body = document.getElementById('detalles-diametro-body');
        if (!body) return;

        let tProf = 0, tMetros = 0, tKg = 0, tBent = 0, tConc = 0, tAdi = 0 ,tAdem = 0, tCamp =0;

        body.querySelectorAll('tr').forEach(function (tr) {
            const prof = parseFloat(tr.querySelector('.detalle-profundidad')?.value || 0);
            const met  = parseFloat(tr.querySelector('.detalle-metros')?.value || 0);
            const kg   = parseFloat(tr.querySelector('.detalle-kg')?.value || 0);
            const bent = parseFloat(tr.querySelector('.detalle-bentonita')?.value || 0);
            const conc = parseFloat(tr.querySelector('.detalle-concreto')?.value || 0);
            const adem = parseFloat(tr.querySelector('.detalle-ademe')?.value || 0);
            const camp = parseFloat(tr.querySelector('.detalle-campana')?.value || 0);
            const adi  = parseFloat(tr.querySelector('.detalle-adicional')?.value || 0);

            tProf += prof;
            tMetros += met;
            tKg += kg;
            tBent += bent;
            tConc += conc;
            tAdem += adem;
            tCamp += camp;
            tAdi += adi;
        });

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val ? val.toFixed(2) : '';
        };

        setVal('total_profundidad', tProf);
        setVal('total_metros', tMetros);
        setVal('total_kg', tKg);
        setVal('total_bentonita', tBent);
        setVal('total_concreto', tConc);
        setVal('total_ademe', tAdem);
        setVal('total_campana', tCamp);
        setVal('total_adicional', tAdi);
    }

    function agregarFilaDetalleDiametro() {
        const body = document.getElementById('detalles-diametro-body');
        const select = document.getElementById('select-tipo-pila');
        if (!body || !select) return;

        const selected = select.options[select.selectedIndex];
        const pilaId   = select.value;

        if (!pilaId) {
            alert('Selecciona un tipo de pila antes de agregar una fila.');
            return;
        }

        const diametro    = selected.dataset.diametro || '';
        const cantidad    = selected.dataset.cantidad || '';
        const profundidad = selected.dataset.profundidad || '';

        const filas = body.querySelectorAll('tr');
        let nextIndex = 0;
        if (filas.length > 0) {
            const last = filas[filas.length - 1];
            nextIndex = parseInt(last.getAttribute('data-index') || '0', 10) + 1;
        }

        const tr = document.createElement('tr');
        tr.setAttribute('data-index', nextIndex);
        tr.innerHTML = `
            <input type="hidden"
                   name="detalles[${nextIndex}][pila_id]"
                   value="${pilaId}">

            <td class="py-2 px-2">
                <input type="text"
                       name="detalles[${nextIndex}][diametro]"
                       value="${diametro}"
                       class="w-full border rounded-lg px-2 py-1 text-xs detalle-diametro">
            </td>
            <td class="py-2 px-2">
                <input type="number"
                       min="0"
                       step="1"
                       name="detalles[${nextIndex}][cantidad]"
                       value="1"
                       class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-cantidad">
            </td>
            <td class="py-2 px-2">
                <input type="number"
                       min="0"
                       step="0.01"
                       name="detalles[${nextIndex}][profundidad]"
                       value="${profundidad}"
                       class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-profundidad">
                       

            </td>
            <td class="py-2 px-2">
                <input type="number"
                       min="0"
                       step="1"
                       name="detalles[${nextIndex}][metros_comision]"
                       class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-metros">
            </td>
            <td class="py-2 px-2">
                <input type="number"
                       min="0"
                       step="0.01"
                       name="detalles[${nextIndex}][kg_acero]"
                       class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-kg">
            </td>
            <td class="py-2 px-2">
                <input type="number"
                       min="0"
                       step="0.01"
                       name="detalles[${nextIndex}][vol_bentonita]"
                       class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-bentonita">
            </td>
            <td class="py-2 px-2">
                <input type="number"
                       min="0"
                       step="0.01"
                       name="detalles[${nextIndex}][vol_concreto]"
                       class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-concreto">
            </td>
             <td class="py-2 px-2">
                <input type="number"
                       min="0"
                       step="1"
                       value="0"
                       name="detalles[${nextIndex}][ml_ademe_bauer]"
                       class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-ademe">
            </td>
             <td class="py-2 px-2">
                <input type="number"
                       min="0"
                       step="1"
                       name="detalles[${nextIndex}][campana_pzas]"
                       class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-campana">
            </td>
            <td class="py-2 px-2">
                <input type="number"
                       min="0"
                       step="0.01"
                       name="detalles[${nextIndex}][adicional]"
                       class="w-full border rounded-lg px-2 py-1 text-xs detalle-valor detalle-adicional">
            </td>
            <td class="py-2 px-2">
                <input type="time"
                       name="detalles[${nextIndex}][hora_inicio]"
                       class="w-full border rounded-lg px-2 py-1 text-xs">
            </td>
            <td class="py-2 px-2">
                <input type="time"
                       name="detalles[${nextIndex}][hora_fin]"
                       class="w-full border rounded-lg px-2 py-1 text-xs">
            </td>
        `;
        body.appendChild(tr);

        // ---- Cálculo automático de metros a comisión por fila ----
        const inputDiametro = tr.querySelector('.detalle-diametro');
        const inputProf     = tr.querySelector('.detalle-profundidad');
        const inputMetros   = tr.querySelector('.detalle-metros');

        function actualizarMetrosComision() {
            const metros = calcularMetrosComision(inputDiametro.value, inputProf.value);
            inputMetros.value = metros ? metros.toFixed(2) : '';
            // si quieres que también actualice el TOTAL:
            recalcularTotalesDiametro();
        }

        // Calcula al crear la fila
        actualizarMetrosComision();

        // Recalcula cuando cambie diámetro o profundidad
        inputDiametro.addEventListener('input', actualizarMetrosComision);
        inputProf.addEventListener('input', actualizarMetrosComision);

        // Listeners para recalcular totales con la nueva fila
        tr.querySelectorAll('.detalle-valor').forEach(function (input) {
            input.addEventListener('change', recalcularTotalesDiametro);
        });

        recalcularTotalesDiametro();
    }
function calcularMetrosComision(diametroValor, profundidadValor) {
    const profundidad = parseFloat(profundidadValor) || 0;
    let diametro = parseFloat(diametroValor) || 0;

    // Si parece estar en cm (ej. 120, 150), lo pasamos a metros
    if (diametro > 10) {
        diametro = diametro / 100;
    }

    if (diametro <= 1) {
        // Diámetro <= 1 m: se toma solo la profundidad
        return profundidad;
    }

    // Diámetro > 1 m: profundidad × diámetro (en metros)
    return profundidad * diametro;
}

    document.addEventListener('DOMContentLoaded', function () {
        // --- PERSONAL (se queda igual) ---
        const inputsPersonal = document.querySelectorAll('.personal-inicio, .personal-fin, .personal-comida');

        inputsPersonal.forEach(function (input) {
            const row = input.getAttribute('data-row');
            input.addEventListener('change', function () {
                calcularFilaPersonal(row);
            });
            calcularFilaPersonal(row);
        });

        // --- DETALLES DIÁMETRO ---
        const bodyDetalles   = document.getElementById('detalles-diametro-body');
        const btnAgregarFila = document.getElementById('btn-agregar-detalle');

        if (bodyDetalles) {
            bodyDetalles.querySelectorAll('.detalle-valor').forEach(function (input) {
                input.addEventListener('change', recalcularTotalesDiametro);
            });
            recalcularTotalesDiametro();
        }

        if (btnAgregarFila) {
            btnAgregarFila.addEventListener('click', function () {
                agregarFilaDetalleDiametro();
            });
        }
    });
    </script>
@endsection


