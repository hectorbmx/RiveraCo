@extends('layouts.admin')

@section('title', 'Generador de Nómina')

@section('content')

<h1 class="text-2xl font-semibold mb-6">Generador de Nómina</h1>

{{-- FILTROS --}}
<form method="GET" action="{{ route('nomina.generador.index') }}" class="mb-4">
    <div class="bg-white rounded-2xl shadow p-4 grid grid-cols-1 md:grid-cols-6 gap-4 items-end">

        {{-- Desde --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Desde</label>
            <input type="date"
                name="desde"
                value="{{ $desde }}"
                class="w-full rounded-xl border-slate-200 text-sm">
        </div>

        {{-- Hasta --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Hasta</label>
            <input type="date"
                name="hasta"
                value="{{ $hasta }}"
                class="w-full rounded-xl border-slate-200 text-sm">
        </div>

        {{-- Tipo de pago --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo de pago</label>
            <select name="tipo" class="w-full rounded-xl border-slate-200 text-sm">
                <option value="semanal"   @selected($tipo === 'semanal')>Semanal</option>
                <option value="quincenal" @selected($tipo === 'quincenal')>Quincenal</option>
                <option value="mensual"   @selected($tipo === 'mensual')>Mensual</option>
            </select>
        </div>
        {{-- Filtro por obra --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Obra</label>
            <select name="obra_id" class="w-full rounded-xl border-slate-200 text-sm">
                <option value="">Todas las obras</option>
                @foreach($obras as $obra)
                    <option value="{{ $obra->id }}"
                        @selected($obraId == $obra->id)>
                        {{ $obra->folio ?? $obra->nombre_obra ?? ('Obra #'.$obra->id) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Buscar empleado --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Buscar empleado</label>
            <input type="text"
                   name="buscar"
                   value="{{ $buscar }}"
                   placeholder="Nombre, apellido o ID"
                   class="w-full rounded-xl border-slate-200 text-sm"
            >
        </div>

        {{-- Botón --}}
        <div class="flex gap-2 justify-end">
            <button class="px-4 py-2 bg-slate-800 text-white text-sm rounded-xl shadow hover:bg-slate-900">
                Aplicar filtros
            </button>
        </div>
    </div>
</form>

{{-- RESUMEN --}}
<div class="flex justify-between items-center mb-3 text-sm text-slate-600">
    <div>
        Empleados a mostrar:
        <span class="font-semibold">{{ $empleados->count() }}</span>
    </div>
    <div class="text-xs">
        Periodo:
        <span class="font-semibold">
            {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}
            –
            {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
        </span>
        · Tipo:
        <span class="font-semibold capitalize">{{ $tipo }}</span>
    </div>
</div>

{{-- TABLA PRINCIPAL --}}
<div class="bg-white rounded-2xl shadow overflow-x-auto">
    <table class="min-w-full text-xs md:text-sm">
        <thead class="bg-slate-50">
            <tr class="text-left text-slate-500 border-b">
                <th class="py-2 px-3">Empleado</th>
                <th class="py-2 px-3 text-right">Sueldo IMSS</th>
                <th class="py-2 px-3 text-right">Complemento</th>
                <th class="py-2 px-3 text-right">Sueldo real</th>

                <th class="py-2 px-3 text-right">Faltas</th>
                <th class="py-2 px-3 text-right">Descuentos</th>
                <th class="py-2 px-3 text-right">INFONAVIT</th>
                <th class="py-2 px-3 text-right">Horas extra</th>
                <th class="py-2 px-3 text-right">M. lineales</th>
                <th class="py-2 px-3 text-right">Comisiones</th>
                <!-- <th class="py-2 px-3 text-right">Prima vac.</th> -->

                <th class="py-2 px-3">Notas</th>
                <th class="py-2 px-3">Obra</th>

                <th class="py-2 px-3 text-right">Total a pagar</th>
                <th class="py-2 px-3 text-center">Acción</th>
                <th class="py-2 px-3 text-center">Estado periodo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($empleados as $empleado)
                @php
                    /** @var \App\Models\NominaRecibo|null $recibo */
                    $recibo = $nominasPorEmpleado[$empleado->id_Empleado] ?? null;

                    $faltas      = $recibo->faltas ?? 0;
                    $descuentos  = $recibo->descuentos_legacy ?? 0;

                    // INFONAVIT: si hay nómina previa usamos ese valor,
                    // si no, tomamos el valor por defecto del empleado
                    $infonavitEmpleado = $empleado->infonavit ?? $empleado->Infonavit ?? 0;
                    $infonavit         = !is_null($recibo?->infonavit_legacy)
                                        ? $recibo->infonavit_legacy
                                        : $infonavitEmpleado;

                    $horas_extra = $recibo->horas_extra ?? 0;
                    $metros_lin  = $recibo->metros_lin_monto ?? 0;
                    $comisiones  = $recibo->comisiones_monto ?? 0;

                    // Total a pagar: si hay recibo uso su neto, si no, sueldo_real como base
                    $total = $recibo
                        ? $recibo->sueldo_neto
                        : ($empleado->Sueldo_real ?? 0);
                @endphp

                <form method="POST"
                action="{{ route('nomina.generador.storeEmpleado', $empleado) }}">
                        @csrf

                        <input type="hidden" name="desde" value="{{ $desde }}">
                        <input type="hidden" name="hasta" value="{{ $hasta }}">
                        <input type="hidden" name="tipo"  value="{{ $tipo }}">

                        <tr class="border-b last:border-b-0 hover:bg-slate-50 align-top fila-nomina"
                            data-empleado-id="{{ $empleado->id_Empleado }}"
                            data-sueldo-real="{{ $empleado->Sueldo_real ?? 0 }}"
                            data-complemento="{{ $empleado->Complemento ?? 0 }}">

                        {{-- Empleado --}}
                        <td class="py-2 px-3">
                            <div class="font-semibold text-slate-800">
                                {{ $empleado->Nombre }} {{ $empleado->Apellidos }}
                            </div>
                            <div class="text-[11px] text-slate-400">
                                ID: {{ $empleado->id_Empleado }}
                            </div>
                        </td>

                        {{-- Sueldo IMSS --}}
                        <td class="py-2 px-3 text-right text-[12px]">
                            ${{ number_format($empleado->Sueldo ?? 0, 2) }}
                        </td>

                        {{-- Complemento --}}
                        <td class="py-2 px-3 text-right text-[12px]">
                            ${{ number_format($empleado->Complemento ?? 0, 2) }}
                        </td>

                        {{-- Sueldo real --}}
                        <td class="py-2 px-3 text-right font-semibold text-slate-800 text-[12px]">
                            ${{ number_format($empleado->Sueldo_real ?? 0, 2) }}
                        </td>

                        {{-- Faltas --}}
                        <td class="py-2 px-1 text-right">
                            <input type="number" step="0.01" name="faltas"
                                   value="{{ $faltas }}"
                                   class="w-16 md:w-20 rounded border-slate-200 text-right text-xs px-1 py-1 campo-calculo">
                        </td>

                        {{-- Descuentos --}}
                        <td class="py-2 px-1 text-right">
                            <input type="number" step="0.01" name="descuentos"
                                   value="{{ $descuentos }}"
                                   class="w-16 md:w-20 rounded border-slate-200 text-right text-xs px-1 py-1 campo-calculo">
                        </td>

                        {{-- INFONAVIT --}}
                        <td class="py-2 px-1 text-right">
                            <input type="number" step="0.01" name="infonavit"
                                   value="{{ $infonavit }}"
                                   class="w-16 md:w-20 rounded border-slate-200 text-right text-xs px-1 py-1 campo-calculo">
                        </td>

                        {{-- Horas extra --}}
                        <td class="py-2 px-1 text-right">
                            <input type="number" step="0.01" name="horas_extra"
                                   value="{{ $horas_extra }}"
                                   class="w-16 md:w-20 rounded border-slate-200 text-right text-xs px-1 py-1 campo-calculo">
                        </td>

                        {{-- Metros lineales --}}
                        <td class="py-2 px-1 text-right">
                            <input type="number" step="0.01" name="metros_lin"
                                   value="{{ $metros_lin }}"
                                   class="w-16 md:w-20 rounded border-slate-200 text-right text-xs px-1 py-1 campo-calculo">
                        </td>

                        {{-- Comisiones --}}
                        <td class="py-2 px-1 text-right">
                            <input type="number" step="0.01" name="comisiones"
                                   value="{{ $comisiones }}"
                                   class="w-16 md:w-20 rounded border-slate-200 text-right text-xs px-1 py-1 campo-calculo">
                        </td>


                        {{-- Notas --}}
                        <td class="py-2 px-1">
                            <input type="text" name="notas"
                                   value="{{ $recibo->notas_legacy ?? '' }}"
                                   class="w-32 md:w-40 rounded border-slate-200 text-xs px-2 py-1">
                        </td>

                       {{-- Obra --}}
<td class="py-2 px-2">
    @php
    
    $obraSeleccionadaId = $recibo?->obra_id;

    
    if (!$obraSeleccionadaId && $empleado->obraActiva && $empleado->obraActiva->count() > 0) {
        $obraSeleccionadaId = $empleado->obraActiva->first()->id;
    }
@endphp

    <select name="obra_id" class="w-32 md:w-40 rounded border-slate-200 text-xs px-1 py-1">
        <option value="">Sin obra</option>
        @foreach($obras as $obra)
            <option value="{{ $obra->id }}"
                @selected($obraSeleccionadaId == $obra->id)>
                {{ $obra->folio ?? $obra->nombre_obra ?? ('Obra #'.$obra->id) }}
            </option>
        @endforeach
    </select>
</td>


                        {{-- Total a pagar --}}
                        <td class="py-2 px-2 text-right">
                            <input type="number" step="0.01" name="suma"
                                value="{{ $total }}"
                                readonly
                                class="w-24 md:w-28 rounded border-slate-200 text-right text-xs px-2 py-1 font-semibold total-pagar">
                        </td>


                        {{-- Botón guardar --}}
                        <td class="py-2 px-2 text-center">
                            <button type="submit"
                                    class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs">
                                Guardar
                            </button>
                        </td>

                        {{-- Estado periodo --}}
                        <td class="py-2 px-3 text-center">
                            @if($recibo)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] bg-green-100 text-green-700">
                                    <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                                    Pagado
                                </span>
                                <div class="text-[11px] text-slate-400 mt-1">
                                    Neto: ${{ number_format($recibo->sueldo_neto, 2) }}
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] bg-slate-100 text-slate-500">
                                    <span class="inline-block w-2 h-2 rounded-full bg-slate-400"></span>
                                    Sin pago
                                </span>
                            @endif
                        </td>
                    </tr>
                </form>
            @empty
                <tr>
                    <td colspan="16" class="py-4 px-3 text-center text-slate-500 text-sm">
                        No hay empleados para este tipo de pago.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filas = document.querySelectorAll('tr.fila-nomina');

    filas.forEach(function (fila) {
        const sueldoReal = parseFloat(fila.dataset.sueldoReal || '0');
        const complemento = parseFloat(fila.dataset.complemento || '0');

        const inputFaltas     = fila.querySelector('input[name="faltas"]');
        const inputDesc       = fila.querySelector('input[name="descuentos"]');
        const inputInfonavit  = fila.querySelector('input[name="infonavit"]');
        const inputHorasExtra = fila.querySelector('input[name="horas_extra"]');
        const inputMetros     = fila.querySelector('input[name="metros_lin"]');
        const inputComisiones = fila.querySelector('input[name="comisiones"]');
        const inputTotal      = fila.querySelector('input.total-pagar');

        if (!inputTotal) return;

        function numero(input) {
            if (!input) return 0;
            const v = parseFloat(input.value);
            return isNaN(v) ? 0 : v;
        }

        function recalcular() {
            const faltas     = numero(inputFaltas);
            const desc       = numero(inputDesc);
            const infonavit  = numero(inputInfonavit);
            const horasExtra = numero(inputHorasExtra);
            const metros     = numero(inputMetros);
            const comisiones = numero(inputComisiones);

            // Fórmula base (ajustable):
            // sueldo_real + complemento
            // + horas extra + metros + comisiones
            // - faltas - descuentos - infonavit
            let total =
                sueldoReal +
                complemento +
                horasExtra +
                metros +
                comisiones -
                faltas -
                desc -
                infonavit;

            if (total < 0) total = 0;

            inputTotal.value = total.toFixed(2);
        }

        // Recalcular cuando cualquier campo que afecta el total cambie o pierda foco
        const campos = fila.querySelectorAll('.campo-calculo');
        campos.forEach(function (input) {
            ['blur', 'change'].forEach(function (ev) {
                input.addEventListener(ev, recalcular);
            });
        });

        // Cálculo inicial
        recalcular();
    });
});
</script>


@endsection
