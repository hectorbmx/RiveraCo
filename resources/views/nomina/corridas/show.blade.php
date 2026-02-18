@extends('layouts.admin')

@section('content')
<div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <div class="text-xs text-slate-500">Nómina / Corrida</div>
            <h1 class="text-2xl font-bold text-slate-900">
                {{ $corrida->periodo_label ?? ('Corrida #'.$corrida->id) }}
            </h1>

            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                <span class="px-2 py-1 rounded-full bg-slate-100 border border-slate-200">
                    Tipo: <span class="font-semibold">{{ ucfirst($corrida->tipo_pago) }}</span>
                </span>

                <span class="px-2 py-1 rounded-full bg-slate-100 border border-slate-200">
                    Periodo: <span class="font-semibold">{{ optional($corrida->fecha_inicio)->format('d/m/Y') }} – {{ optional($corrida->fecha_fin)->format('d/m/Y') }}</span>
                </span>

                @if($corrida->fecha_pago)
                    <span class="px-2 py-1 rounded-full bg-slate-100 border border-slate-200">
                        Pago: <span class="font-semibold">{{ optional($corrida->fecha_pago)->format('d/m/Y') }}</span>
                    </span>
                @endif

                {{-- Status badge --}}
                @php
                    $status = $corrida->status ?? 'abierta';
                    $badge = match ($status) {
                        'abierta' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'cerrada' => 'bg-slate-100 text-slate-700 border-slate-200',
                        'pagada'  => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                        'cancelada' => 'bg-red-50 text-red-700 border-red-200',
                        default   => 'bg-slate-100 text-slate-700 border-slate-200',
                    };
                @endphp

                <span class="px-2 py-1 rounded-full border {{ $badge }}">
                    Status: <span class="font-semibold">{{ strtoupper($status) }}</span>
                </span>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex flex-wrap gap-2 justify-end">
            <a href="{{ route('nomina.generador.index')}}"
               class="px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm rounded-xl shadow-sm hover:bg-slate-50">
                Volver al generador
            </a>

            {{-- Placeholder acciones futuras --}}
            <!-- <button type="button"
                    class="px-4 py-2 bg-slate-800 text-white text-sm rounded-xl shadow hover:bg-slate-900">
                Cargar comisiones
            </button> -->
            <form method="POST"
                action="{{ route('nomina.corridas.destroy', $corrida) }}"
                onsubmit="return confirm('¿Eliminar esta corrida COMPLETA? Se borrarán también todos sus recibos.');">
                @csrf
                @method('DELETE')

                <button type="submit"
                        @disabled(($corrida->status ?? '') !== 'abierta')
                        class="px-4 py-2 text-white text-sm rounded-xl shadow
                            {{ ($corrida->status ?? '') === 'abierta'
                                    ? 'bg-red-600 hover:bg-red-800'
                                    : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                    Eliminar corrida
                </button>
            </form>

          <form method="POST"
                action="{{ route('nomina.corridas.recibos.destroy', $corrida) }}"
                onsubmit="return confirm('¿Seguro que quieres BORRAR TODOS los recibos de esta corrida? Esta acción no se puede deshacer.')">
                @csrf
                @method('DELETE')

                <button type="submit"
                        @disabled(($corrida->status ?? '') !== 'abierta')
                        class="px-4 py-2 text-white text-sm rounded-xl shadow
                            {{ ($corrida->status ?? '') === 'abierta'
                                    ? 'bg-red-600 hover:bg-red-700'
                                    : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                    Borrar recibos
                </button>
            </form>


          <form method="POST"
                action="{{ route('nomina.corridas.recibos.generar', $corrida) }}"
                onsubmit="return confirm('¿Generar recibos base para esta corrida?')">
                @csrf

                <button type="submit"
                        @disabled(($corrida->status ?? '') !== 'abierta')
                        class="px-4 py-2 bg-slate-800 text-white text-sm rounded-xl shadow
                            {{ ($corrida->status ?? '') === 'abierta'
                                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                                    : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                    Generar recibos
                </button>
            </form>


        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Total bruto</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">
                $<span id="kpi-bruto">{{ number_format($totalBruto ?? 0, 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Deducciones</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">
                $<span id="kpi-deducciones">{{ number_format($totalDeducciones ?? 0, 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-4 border border-slate-100">
            <div class="text-xs font-semibold text-slate-500">Total neto</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">
                $<span id="kpi-neto">{{ number_format($totalNeto ?? 0, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Tabla Recibos --}}
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-900">Recibos</div>
                <div class="text-xs text-slate-500">Listado de recibos generados para esta corrida</div>
            </div>

            <div class="text-xs text-slate-500">
                Total: <span class="font-semibold">{{ $corrida->recibos->count() }}</span>
            </div>
        </div>

        @if($corrida->recibos->isEmpty())
            <div class="p-6 text-center">
                <div class="text-sm font-semibold text-slate-800">Aún no hay recibos</div>
                <div class="text-xs text-slate-500 mt-1">
                    Usa “Generar recibos” para crear los recibos base y después “Cargar comisiones”.
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <form method="POST" action="{{ route('nomina.corridas.recibos.guardar', $corrida) }}">
  @csrf
                <table class="min-w-full text-xs border-collapse">
    <thead class="bg-slate-50 text-slate-600">
        <tr>
            <th class="px-3 py-2 text-left font-semibold">Empleado</th>

            {{-- 🟢 Informativo --}}
            <th class="px-3 py-2 text-right font-semibold bg-green-50">IMSS</th>
            <th class="px-3 py-2 text-right font-semibold bg-green-50">Complemento</th>
            <th class="px-3 py-2 text-right font-semibold bg-green-100">Sueldo real</th>

            {{-- 🔴 Deducciones --}}
            <th class="px-3 py-2 text-center font-semibold bg-red-50">Infonavit</th>
            <th class="px-3 py-2 text-center font-semibold bg-red-50">Faltas</th>
            <th class="px-3 py-2 text-center font-semibold bg-red-50">Descuentos</th>

            {{-- 🔵 Operativo --}}
            <th class="px-3 py-2 text-center font-semibold bg-blue-50">Horas extra</th>
            <th class="px-3 py-2 text-center font-semibold bg-blue-50">M. lineales</th>
            <th class="px-3 py-2 text-center font-semibold bg-blue-50">Comisiones</th>
            <th class="px-3 py-2 text-center font-semibold bg-blue-50">Notas</th>

            <th class="px-3 py-2 text-center font-semibold">Obra</th>

            {{-- Totales --}}
            <th class="px-3 py-2 text-right font-semibold">Bruto</th>
            <th class="px-3 py-2 text-right font-semibold">Deducciones</th>
            <th class="px-3 py-2 text-right font-semibold">Neto</th>
        </tr>
    </thead>
<tbody class="divide-y divide-slate-100 bg-white">
@foreach($corrida->recibos as $r)
  @php
    // Base real (SUELDO_REAL). Regla: no recalcular, ya viene.
    $sueldoReal = (float)($r->total_percepciones ?? 0);

    $sueldoImss  = (float)($r->sueldo_imss_snapshot ?? 0);
    $complemento = (float)($r->complemento_snapshot ?? 0);

    $infonavit   = (float)($r->infonavit_legacy ?? 0);
    $faltas      = (float)($r->faltas ?? 0);
    $descuentos  = (float)($r->descuentos ?? 0);

    $horasExtra  = (float)($r->horas_extra ?? 0);
    $metrosMonto = (float)($r->metros_lin_monto ?? 0);
    $comisiones  = (float)($r->comisiones_monto ?? 0);

    // Extras (si todavía no existen en BD, van en 0 igual)
    $aguinaldo   = (float)($r->aguinaldo_monto ?? 0);
    $primaVac    = (float)($r->prima_vacacional_monto ?? 0);

    $bruto = $sueldoReal + $horasExtra + $metrosMonto + $comisiones + $aguinaldo + $primaVac;
    $deds  = $infonavit + $faltas + $descuentos;
    $neto  = max(0, $bruto - $deds);
  @endphp

  <tr class="hover:bg-slate-50 fila-recibo"
      data-recibo-id="{{ $r->id }}"
      data-sueldo-real="{{ $sueldoReal }}">

    {{-- EMPLEADO --}}
    <td class="px-3 py-2">
      <div class="font-semibold text-slate-900">
        {{ $r->empleado?->Nombre ?? '' }} {{ $r->empleado?->Apellidos ?? '' }}
      </div>
      <div class="text-xs text-slate-500">ID: {{ $r->empleado_id }}</div>
    </td>

    {{-- 🟢 INFORMATIVO --}}
    <td class="px-3 py-2 text-right bg-green-50">
      ${{ number_format($sueldoImss, 2) }}
    </td>

    <td class="px-3 py-2 text-right bg-green-50">
      ${{ number_format($complemento, 2) }}
    </td>

    {{-- Sueldo real (base) --}}
    <td class="px-3 py-2 text-right font-semibold bg-green-100">
      ${{ number_format($sueldoReal, 2) }}
    </td>

    {{-- 🔴 DEDUCCIONES --}}
    <td class="px-3 py-2 text-center bg-red-50">
      <input type="number" step="0.01"
             name="recibos[{{ $r->id }}][infonavit]"
             value="{{ number_format($infonavit, 2, '.', '') }}"
             class="w-20 text-center rounded-lg border-slate-200 text-xs campo-calculo">
    </td>

    <td class="px-3 py-2 text-center bg-red-50">
      <input type="number" step="0.01"
             name="recibos[{{ $r->id }}][faltas]"
             value="{{ number_format($faltas, 2, '.', '') }}"
             class="w-20 text-center rounded-lg border-slate-200 text-xs campo-calculo">
    </td>

    <td class="px-3 py-2 text-center bg-red-50">
      <input type="number" step="0.01"
             name="recibos[{{ $r->id }}][descuentos]"
             value="{{ number_format($descuentos, 2, '.', '') }}"
             class="w-20 text-center rounded-lg border-slate-200 text-xs campo-calculo">
    </td>

    {{-- 🔵 OPERATIVO --}}
    <td class="px-3 py-2 text-center bg-blue-50">
      <input type="number" step="0.01"
             name="recibos[{{ $r->id }}][horas_extra]"
             value="{{ number_format($horasExtra, 2, '.', '') }}"
             class="w-20 text-center rounded-lg border-slate-200 text-xs campo-calculo">
    </td>

    <td class="px-3 py-2 text-center bg-blue-50">
      <input type="number" step="0.01"
             name="recibos[{{ $r->id }}][metros_lin_monto]"
             value="{{ number_format($metrosMonto, 2, '.', '') }}"
             class="w-20 text-center rounded-lg border-slate-200 text-xs campo-calculo">
    </td>

    <td class="px-3 py-2 text-center bg-blue-50">
      <input type="number" step="0.01"
             name="recibos[{{ $r->id }}][comisiones_monto]"
             value="{{ number_format($comisiones, 2, '.', '') }}"
             class="w-24 text-center rounded-lg border-slate-200 text-xs campo-calculo">
    </td>

    <td class="px-3 py-2 bg-blue-50">
     <div class="flex gap-2 items-center">
    <input type="text"
           name="recibos[{{ $r->id }}][notas]"
           value="{{ $r->notas_legacy ?? '' }}"
           class="w-full rounded-lg border-slate-200 text-xs">
    <button type="button"
            class="px-2 py-1 rounded-lg border text-xs bg-white hover:bg-slate-50"
            data-toggle-extras="{{ $r->id }}">
      + Extras
    </button>
  </div>
    </td>

    {{-- OBRA --}}
    <td class="px-3 py-2 text-center">
      <select name="recibos[{{ $r->id }}][obra_id]" data-obra-select="{{ $r->id }}" 
              class="rounded-lg border-slate-200 text-xs w-36">
        <option value="">Sin obra</option>
        @foreach($obras as $o)
          <option value="{{ $o->id }}" @selected($r->obra_id == $o->id)>
            {{ $o->folio ?? $o->nombre_obra ?? ('Obra #'.$o->id) }}
          </option>
        @endforeach
      </select>
    </td>

    {{-- TOTALES (dinámicos) --}}
    <td class="px-3 py-2 text-right font-semibold">
      $<span class="js-bruto">{{ number_format($bruto, 2, '.', '') }}</span>
    </td>

    <td class="px-3 py-2 text-right font-semibold">
      $<span class="js-deducciones">{{ number_format($deds, 2, '.', '') }}</span>
    </td>

    <td class="px-3 py-2 text-right font-bold text-slate-900">
      $<span class="js-neto">{{ number_format($neto, 2, '.', '') }}</span>
    </td>

  </tr>
 <tr class="hidden bg-slate-50" data-extras-row="{{ $r->id }}">
  <td colspan="15" class="px-3 py-3">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">

      {{-- 1) Tipo --}}
      <div>
        <label class="block text-[11px] text-slate-600 mb-1">Tipo de extra</label>
        <select
          name="recibos[{{ $r->id }}][extra][tipo]"
          class="w-full rounded-lg border-slate-200 text-xs px-2 py-2"
        >
          @php $tipo = $r->extra_tipo ?? null; @endphp
          <option value="" {{ $tipo==='' || $tipo===null ? 'selected' : '' }}>— Selecciona —</option>
          <option value="aguinaldo" {{ $tipo==='aguinaldo' ? 'selected' : '' }}>Aguinaldo</option>
          <option value="prima_vacacional" {{ $tipo==='prima_vacacional' ? 'selected' : '' }}>Prima vacacional</option>
          <option value="bono_especial" {{ $tipo==='bono_especial' ? 'selected' : '' }}>Bono especial</option>
          <option value="otro" {{ $tipo==='otro' ? 'selected' : '' }}>Otro</option>
        </select>
      </div>

      {{-- 2) Monto --}}
      <div>
        <label class="block text-[11px] text-slate-600 mb-1">Monto</label>
        <input
          type="number" step="0.01" min="0"
          name="recibos[{{ $r->id }}][extra][monto]"
          value="{{ number_format((float)($r->extra_monto ?? 0), 2, '.', '') }}"
          class="w-full rounded-lg border-slate-200 text-xs px-2 py-2 campo-calculo"
        >
      </div>

      {{-- 3) Notas --}}
      <div>
        <label class="block text-[11px] text-slate-600 mb-1">Notas</label>
        <input
          type="text"
          name="recibos[{{ $r->id }}][extra][notas]"
          value="{{ $r->extra_notas ?? '' }}"
          class="w-full rounded-lg border-slate-200 text-xs px-2 py-2"
          placeholder="Opcional"
        >
      </div>
    </div>

    {{-- Hidden: amarre fuerte al pago --}}
    <input type="hidden" name="recibos[{{ $r->id }}][extra][recibo_id]" value="{{ $r->id }}">
    <input type="hidden" name="recibos[{{ $r->id }}][extra][empleado_id]" value="{{ $r->empleado_id }}">
    <input type="hidden" name="recibos[{{ $r->id }}][extra][anio]" value="{{ $corrida->anio ?? \Carbon\Carbon::parse($corrida->fecha_pago)->year }}">
    <input type="hidden" name="recibos[{{ $r->id }}][extra][fecha_pago]" value="{{ \Carbon\Carbon::parse($corrida->fecha_pago)->format('Y-m-d') }}">

    {{-- obra_id: lo sincronizamos con el select de Obra del row principal --}}
    <input
      type="hidden"
      name="recibos[{{ $r->id }}][extra][obra_id]"
      data-extra-obra="{{ $r->id }}"
      value="{{ $r->obra_id ?? '' }}"
    >
  </td>
</tr>


@endforeach
</tbody>

</table>
  <div class="p-4 border-t flex justify-end">
    <button type="submit"
      @disabled(($corrida->status ?? '') !== 'abierta')
      class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm hover:bg-emerald-700">
      Guardar cambios
    </button>
  </div>
</form>
            </div>
        @endif
    </div>

    {{-- Metadata --}}
    <div class="mt-4 text-xs text-slate-400">
        Creada: {{ optional($corrida->created_at)->format('d/m/Y H:i') }}
        @if($corrida->created_by)
            · Usuario: #{{ $corrida->created_by }}
        @endif
    </div>

</div>
@endsection
<script>

 document.addEventListener('DOMContentLoaded', () => {
  // Toggle child row
  document.querySelectorAll('[data-toggle-extras]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-toggle-extras');
      const row = document.querySelector(`[data-extras-row="${id}"]`);
      if (!row) return;

      row.classList.toggle('hidden');
      btn.textContent = row.classList.contains('hidden') ? '+ Extras' : '− Extras';
    });
  });

  // Sincronizar obra_id del extra con el select Obra del row principal
  document.querySelectorAll('[data-obra-select]').forEach(sel => {
    const id = sel.getAttribute('data-obra-select');

    const sync = () => {
      const hidden = document.querySelector(`[data-extra-obra="${id}"]`);
      if (hidden) hidden.value = sel.value || '';
    };

    sel.addEventListener('change', sync);
    sync(); // inicial
  });
});

document.addEventListener('DOMContentLoaded', () => {

  const money = (n) => (Math.round((n + Number.EPSILON) * 100) / 100).toFixed(2);

  const num = (el) => {
    if (!el) return 0;
    const v = parseFloat(el.value);
    return isNaN(v) ? 0 : v;
  };

  const kpiBruto = document.getElementById('kpi-bruto');
  const kpiDeds  = document.getElementById('kpi-deducciones');
  const kpiNeto  = document.getElementById('kpi-neto');

  const filas = Array.from(document.querySelectorAll('tr.fila-recibo'));

  const recalcularKpis = () => {
    let sumBruto = 0, sumDeds = 0, sumNeto = 0;

    filas.forEach(f => {
      const b = parseFloat(f.querySelector('.js-bruto')?.textContent || '0') || 0;
      const d = parseFloat(f.querySelector('.js-deducciones')?.textContent || '0') || 0;
      const n = parseFloat(f.querySelector('.js-neto')?.textContent || '0') || 0;
      sumBruto += b; sumDeds += d; sumNeto += n;
    });

    if (kpiBruto) kpiBruto.textContent = money(sumBruto);
    if (kpiDeds)  kpiDeds.textContent  = money(sumDeds);
    if (kpiNeto)  kpiNeto.textContent  = money(sumNeto);
  };

  filas.forEach((fila) => {
    const reciboId  = fila.dataset.reciboId;
    const sueldoReal = parseFloat(fila.dataset.sueldoReal || '0') || 0;

    // helper: busca inputs por key del name (como ya hacías)
    const q = (needle) => fila.querySelector(`input[name*="[${needle}]"]`);

    // deducciones
    const inInfonavit  = q('infonavit');
    const inFaltas     = q('faltas');
    const inDescuentos = q('descuentos');

    // operativos (monto)
    const inHorasExtra  = q('horas_extra');
    const inMetrosMonto = q('metros_lin_monto'); // en tu blade es monto
    const inComisiones  = q('comisiones_monto');

    // extra monto (vive en la child row)
    const extraMontoInput = document.querySelector(
      `tr[data-extras-row="${reciboId}"] input[name="recibos[${reciboId}][extra][monto]"]`
    );

    // outputs row
    const outBruto = fila.querySelector('.js-bruto');
    const outDeds  = fila.querySelector('.js-deducciones');
    const outNeto  = fila.querySelector('.js-neto');

    const recalcularFila = () => {
      const infonavit  = num(inInfonavit);
      const faltas     = num(inFaltas);
      const descuentos = num(inDescuentos);

      const horasExtra = num(inHorasExtra);
      const metros     = num(inMetrosMonto);
      const comisiones = num(inComisiones);

      const extraMonto = num(extraMontoInput);

      const bruto = sueldoReal + horasExtra + metros + comisiones + extraMonto;
      const deds  = infonavit + faltas + descuentos;

      let neto = bruto - deds;
      if (neto < 0) neto = 0;

      if (outBruto) outBruto.textContent = money(bruto);
      if (outDeds)  outDeds.textContent  = money(deds);
      if (outNeto)  outNeto.textContent  = money(neto);

      recalcularKpis();
    };

    // escuchas en fila
    fila.querySelectorAll('.campo-calculo').forEach((input) => {
      input.addEventListener('input', recalcularFila);
      input.addEventListener('change', recalcularFila);
    });

    // escucha el extra monto (en child row)
    if (extraMontoInput) {
      extraMontoInput.addEventListener('input', recalcularFila);
      extraMontoInput.addEventListener('change', recalcularFila);
    }

    // primer cálculo
    recalcularFila();
  });

  // KPIs inicial
  recalcularKpis();
});
</script>
