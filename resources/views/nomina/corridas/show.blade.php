@extends('layouts.admin')

@section('content')
<div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <div class="text-xs text-slate-500">Nomina / Corrida</div>
            <h1 class="text-2xl font-bold text-slate-900">
                {{ $corrida->periodo_label ?? ('Corrida #'.$corrida->id) }}
            </h1>

            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                <span class="px-2 py-1 rounded-full bg-slate-100 border border-slate-200">
                    Tipo: <span class="font-semibold">{{ ucfirst($corrida->tipo_pago) }}</span>
                </span>

                <span class="px-2 py-1 rounded-full bg-slate-100 border border-slate-200">
                    Periodo: <span class="font-semibold">{{ optional($corrida->fecha_inicio)->format('d/m/Y') }} - {{ optional($corrida->fecha_fin)->format('d/m/Y') }}</span>
                </span>

                @if($corrida->fecha_pago)
                    <span class="px-2 py-1 rounded-full bg-slate-100 border border-slate-200">
                        Pago: <span class="font-semibold">{{ optional($corrida->fecha_pago)->format('d/m/Y') }}</span>
                    </span>
                @endif

                {{-- Status badge --}}
                @php
                    $status = $corrida->status ?? 'abierta';
                    $isEditable = $status === 'abierta';
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

                @if($corrida->closed_at)
                    <span class="px-2 py-1 rounded-full bg-slate-100 border border-slate-200">
                        Cerrada: <span class="font-semibold">{{ optional($corrida->closed_at)->format('d/m/Y H:i') }}</span>
                        @if($corrida->cerrador)
                            <span class="text-slate-400">por</span> <span class="font-semibold">{{ $corrida->cerrador->name }}</span>
                        @elseif($corrida->closed_by)
                            <span class="text-slate-400">por usuario #{{ $corrida->closed_by }}</span>
                        @endif
                    </span>
                @endif

                @if($corrida->paid_at)
                    <span class="px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                        Pagada: <span class="font-semibold">{{ optional($corrida->paid_at)->format('d/m/Y H:i') }}</span>
                        @if($corrida->pagadoPor)
                            <span class="text-indigo-400">por</span> <span class="font-semibold">{{ $corrida->pagadoPor->name }}</span>
                        @elseif($corrida->paid_by)
                            <span class="text-indigo-400">por usuario #{{ $corrida->paid_by }}</span>
                        @endif
                    </span>
                @endif
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex flex-wrap gap-2 justify-end">
            <a href="{{ route('nomina.generador.index')}}"
               class="px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm rounded-xl shadow-sm hover:bg-slate-50">
                Volver al generador
            </a>

            @can('nomina.corridas.close.access')
            @if($isEditable)
                <form method="POST" action="{{ route('nomina.corridas.cerrar', $corrida) }}"
                      onsubmit="return confirm('Cerrar la corrida? Ya no se podra editar.');">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-slate-700 text-white text-sm rounded-xl shadow hover:bg-slate-800">
                        Cerrar corrida
                    </button>
                </form>
            @endif
            @endcan

            @can('nomina.corridas.pay.access')
            @if($status === 'cerrada')
                <form method="POST" action="{{ route('nomina.corridas.pagar', $corrida) }}"
                      onsubmit="return confirm('Marcar esta corrida como pagada?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-xl shadow hover:bg-indigo-700">
                        Marcar pagada
                    </button>
                </form>
            @endif
            @endcan

            @can('nomina.corridas.reopen.access')
            @if($status === 'cerrada')
                <form method="POST" action="{{ route('nomina.corridas.reabrir', $corrida) }}"
                      onsubmit="return confirm('Reabrir la corrida para editar?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm rounded-xl shadow-sm hover:bg-slate-50">
                        Reabrir
                    </button>
                </form>
            @endif
            @endcan
            @can('nomina.corridas.delete.access')
            <form method="POST"
                action="{{ route('nomina.corridas.destroy', $corrida) }}"
                onsubmit="return confirm('Eliminar esta corrida COMPLETA? Se borraran tambien todos sus recibos.');">
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
            @endcan

            @can('nomina.corridas.delete.access')
          <form method="POST"
                action="{{ route('nomina.corridas.recibos.destroy', $corrida) }}"
                onsubmit="return confirm('Seguro que quieres BORRAR TODOS los recibos de esta corrida? Esta accion no se puede deshacer.')">
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
            @endcan


          <form method="POST"
                action="{{ route('nomina.corridas.recibos.generar', $corrida) }}"
                onsubmit="return confirm('Generar recibos base para esta corrida?')">
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

    @unless($isEditable)
        <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            Esta corrida esta {{ $status }}. La captura y el autosave quedan bloqueados para proteger la nomina.
        </div>
    @endunless

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
                <div class="text-sm font-semibold text-slate-800">Aun no hay recibos</div>
                <div class="text-xs text-slate-500 mt-1">
                    Usa "Generar recibos" para crear los recibos base y despues "Cargar comisiones".
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

            {{-- Informativo --}}
            <th class="px-3 py-2 text-right font-semibold bg-green-50">IMSS</th>
            <th class="px-3 py-2 text-right font-semibold bg-green-50">Complemento</th>
            <th class="px-3 py-2 text-right font-semibold bg-green-100">Sueldo real</th>

            {{-- Deducciones --}}
            <th class="px-3 py-2 text-center font-semibold bg-red-50">Infonavit</th>
            <th class="px-3 py-2 text-center font-semibold bg-red-50">Faltas</th>
            <th class="px-3 py-2 text-center font-semibold bg-red-50">Descuentos</th>

            {{-- Operativo --}}
            <th class="px-3 py-2 text-center font-semibold bg-blue-50">Horas extra</th>
            <th class="px-3 py-2 text-center font-semibold bg-blue-50">M. lineales</th>
            <th class="px-3 py-2 text-center font-semibold bg-blue-50">Comisiones</th>
            <th class="px-3 py-2 text-center font-semibold bg-blue-50">Notas</th>
            <th class="px-3 py-2 text-center font-semibold bg-blue-50">Extras</th>

            <th class="px-3 py-2 text-center font-semibold">Obra</th>

            {{-- Totales --}}
            <th class="px-3 py-2 text-right font-semibold">Bruto</th>
            <th class="px-3 py-2 text-right font-semibold">Deducciones</th>
            <th class="px-3 py-2 text-right font-semibold">Neto</th>
        </tr>
    </thead>
<tbody class="divide-y divide-slate-100 bg-white">
@php
  $recibosAgrupados = $corrida->recibos
      ->sortBy(fn($recibo) => ($recibo->lista_raya_nombre ?? 'Sin clasificar') . '|' . ($recibo->empleado?->Nombre ?? ''))
      ->groupBy(fn($recibo) => $recibo->lista_raya_nombre ?: 'Sin clasificar');
@endphp
@foreach($recibosAgrupados as $listaRayaNombre => $recibosLista)
  <tr class="bg-slate-100 text-slate-700">
    <td colspan="16" class="px-3 py-2 font-semibold text-xs uppercase tracking-wide">
      Lista de raya: {{ $listaRayaNombre }} <span class="font-normal text-slate-500">({{ $recibosLista->count() }} empleado(s))</span>
    </td>
  </tr>
@foreach($recibosLista as $r)
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

    // Extras (desde relacion pagosExtra - coleccion)
    $extraMonto  = (float)$r->pagosExtra->sum('monto');

    $bruto = $sueldoReal + $horasExtra + $metrosMonto + $comisiones + $extraMonto;
    $deds  = $infonavit + $faltas + $descuentos;
    $neto  = max(0, $bruto - $deds);
  @endphp

  <tr class="hover:bg-slate-50 fila-recibo"
      data-recibo-id="{{ $r->id }}"
      data-sueldo-real="{{ $sueldoReal }}">

    {{-- EMPLEADO --}}
    <td class="px-3 py-2">
      <div class="flex items-center gap-2">
        <span class="font-semibold text-slate-900">
          {{ $r->empleado?->Nombre ?? '' }} {{ $r->empleado?->Apellidos ?? '' }}
        </span>
        <span class="js-status-indicator text-[10px] text-slate-400">
          Guardado
        </span>
      </div>
      <div class="text-xs text-slate-500">ID: {{ $r->empleado_id }}</div>
    </td>

    {{-- INFORMATIVO --}}
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

    {{-- DEDUCCIONES --}}
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

    {{-- OPERATIVO --}}
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

    <td class="px-3 py-2 bg-blue-50 min-w-[220px]">
      <input type="text"
             name="recibos[{{ $r->id }}][notas]"
             value="{{ $r->notas_legacy ?? '' }}"
             class="w-full rounded-lg border-slate-200 text-xs"
             placeholder="Notas del recibo">
    </td>

    <td class="px-3 py-2 text-center bg-blue-50">
      <button type="button"
              class="px-2 py-1 rounded-lg border text-xs bg-white hover:bg-slate-50"
              data-toggle-extras="{{ $r->id }}">
        + Extras
      </button>
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

    {{-- TOTALES (dinamicos) --}}
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
  <td colspan="16" class="px-3 py-3">
    <div class="js-extras-container" data-recibo-id="{{ $r->id }}">
      @forelse($r->pagosExtra as $idx => $extra)
      <div class="js-extra-row grid grid-cols-1 md:grid-cols-4 gap-3 items-end mb-2 p-2 bg-white rounded-lg border border-slate-200" data-extra-id="{{ $extra->id }}">
        <input type="hidden" class="js-extra-db-id" value="{{ $extra->id }}">
        <input type="hidden" class="js-extra-empleado-id" value="{{ $r->empleado_id }}">
        <input type="hidden" class="js-extra-obra-id" value="{{ $r->obra_id ?? '' }}">
        <input type="hidden" class="js-extra-anio" value="{{ $corrida->anio ?? \Carbon\Carbon::parse($corrida->fecha_pago)->year }}">
        <input type="hidden" class="js-extra-fecha-pago" value="{{ \Carbon\Carbon::parse($corrida->fecha_pago)->format('Y-m-d') }}">
        <div>
          <label class="block text-[11px] text-slate-600 mb-1">Tipo de extra</label>
          <select class="js-extra-tipo w-full rounded-lg border-slate-200 text-xs px-2 py-2">
            <option value="">Selecciona</option>
            <option value="aguinaldo" @selected($extra->tipo === 'aguinaldo')>Aguinaldo</option>
            <option value="prima_vacacional" @selected($extra->tipo === 'prima_vacacional')>Prima vacacional</option>
            <option value="bono_especial" @selected($extra->tipo === 'bono_especial')>Bono especial</option>
            <option value="otro" @selected($extra->tipo === 'otro')>Otro</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] text-slate-600 mb-1">Monto</label>
          <input type="number" step="0.01" min="0" value="{{ number_format((float)$extra->monto, 2, '.', '') }}" class="js-extra-monto w-full rounded-lg border-slate-200 text-xs px-2 py-2 campo-calculo">
        </div>
        <div>
          <label class="block text-[11px] text-slate-600 mb-1">Notas</label>
          <input type="text" value="{{ $extra->notas ?? '' }}" class="js-extra-notas w-full rounded-lg border-slate-200 text-xs px-2 py-2" placeholder="Opcional">
        </div>
        <div class="flex items-end">
          <button type="button" class="js-remove-extra px-3 py-2 rounded-lg bg-red-50 text-red-600 text-xs border border-red-200 hover:bg-red-100 transition">
            &times; Quitar
          </button>
        </div>
      </div>
      @empty
      {{-- No extras yet, the user can add them --}}
      @endforelse
    </div>
    <button type="button" class="js-add-extra mt-2 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs border border-indigo-200 hover:bg-indigo-100 transition" data-recibo-id="{{ $r->id }}" data-empleado-id="{{ $r->empleado_id }}" data-obra-id="{{ $r->obra_id ?? '' }}" data-anio="{{ $corrida->anio ?? \Carbon\Carbon::parse($corrida->fecha_pago)->year }}" data-fecha-pago="{{ \Carbon\Carbon::parse($corrida->fecha_pago)->format('Y-m-d') }}">
      + Agregar extra
    </button>
  </td>
</tr>


@endforeach
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
            - Usuario: #{{ $corrida->created_by }}
        @endif
    </div>

</div>
@endsection
<script>
const corridaId = {{ $corrida->id }};
const isEditable = @json($isEditable);

document.addEventListener('DOMContentLoaded', () => {
  if (!isEditable) {
    document.querySelectorAll('.fila-recibo input, .fila-recibo select, .js-extras-container input, .js-extras-container select, .js-add-extra, .js-remove-extra').forEach((control) => {
      control.disabled = true;
      control.classList.add('cursor-not-allowed', 'opacity-70');
    });
  }

  // Toggle child row
  document.querySelectorAll('[data-toggle-extras]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-toggle-extras');
      const row = document.querySelector(`[data-extras-row="${id}"]`);
      if (!row) return;
      row.classList.toggle('hidden');
      btn.textContent = row.classList.contains('hidden') ? '+ Extras' : '- Extras';
    });
  });

  // Sincronizar obra_id de extras con el select Obra del row principal
  document.querySelectorAll('[data-obra-select]').forEach(sel => {
    const id = sel.getAttribute('data-obra-select');
    const sync = () => {
      const container = document.querySelector(`.js-extras-container[data-recibo-id="${id}"]`);
      if (container) {
        container.querySelectorAll('.js-extra-obra-id').forEach(h => h.value = sel.value || '');
      }
    };
    sel.addEventListener('change', sync);
    sync();
  });

  // ========== Agregar extra dinamico ==========
  let extraTempIdx = 0;
  document.querySelectorAll('.js-add-extra').forEach(btn => {
    btn.addEventListener('click', () => {
      if (!isEditable) return;
      const reciboId = btn.dataset.reciboId;
      const empleadoId = btn.dataset.empleadoId;
      const obraId = btn.dataset.obraId;
      const anio = btn.dataset.anio;
      const fechaPago = btn.dataset.fechaPago;
      const container = document.querySelector(`.js-extras-container[data-recibo-id="${reciboId}"]`);
      if (!container) return;

      extraTempIdx++;
      const div = document.createElement('div');
      div.className = 'js-extra-row grid grid-cols-1 md:grid-cols-4 gap-3 items-end mb-2 p-2 bg-white rounded-lg border border-slate-200';
      div.dataset.extraId = '';
      div.innerHTML = `
        <input type="hidden" class="js-extra-db-id" value="">
        <input type="hidden" class="js-extra-empleado-id" value="${empleadoId}">
        <input type="hidden" class="js-extra-obra-id" value="${obraId}">
        <input type="hidden" class="js-extra-anio" value="${anio}">
        <input type="hidden" class="js-extra-fecha-pago" value="${fechaPago}">
        <div>
          <label class="block text-[11px] text-slate-600 mb-1">Tipo de extra</label>
          <select class="js-extra-tipo w-full rounded-lg border-slate-200 text-xs px-2 py-2">
            <option value="">Selecciona</option>
            <option value="aguinaldo">Aguinaldo</option>
            <option value="prima_vacacional">Prima vacacional</option>
            <option value="bono_especial">Bono especial</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] text-slate-600 mb-1">Monto</label>
          <input type="number" step="0.01" min="0" value="0.00" class="js-extra-monto w-full rounded-lg border-slate-200 text-xs px-2 py-2 campo-calculo">
        </div>
        <div>
          <label class="block text-[11px] text-slate-600 mb-1">Notas</label>
          <input type="text" value="" class="js-extra-notas w-full rounded-lg border-slate-200 text-xs px-2 py-2" placeholder="Opcional">
        </div>
        <div class="flex items-end">
          <button type="button" class="js-remove-extra px-3 py-2 rounded-lg bg-red-50 text-red-600 text-xs border border-red-200 hover:bg-red-100 transition">
            &times; Quitar
          </button>
        </div>
      `;
      container.appendChild(div);
      attachExtraListeners(div, reciboId);
      recalcularFila(reciboId);
    });
  });

  // ========== Quitar extra ==========
  const syncExtraChange = (reciboId) => {
    if (!isEditable) return;
    if (typeof window.recalcularFila === 'function') {
      window.recalcularFila(reciboId);
    }

    if (typeof window.queueAutosave === 'function') {
      window.queueAutosave(reciboId);
      return;
    }

    setTimeout(() => {
      if (typeof window.queueAutosave === 'function') {
        window.queueAutosave(reciboId);
      }
    }, 0);
  };

  const attachExtraListeners = (rowEl, reciboId) => {
    const removeBtn = rowEl.querySelector('.js-remove-extra');
    if (removeBtn) {
      removeBtn.addEventListener('click', () => {
        rowEl.remove();
        syncExtraChange(reciboId);
      });
    }
    rowEl.querySelectorAll('.js-extra-monto, .js-extra-tipo, .js-extra-notas').forEach(inp => {
      inp.addEventListener('input', () => syncExtraChange(reciboId));
      inp.addEventListener('change', () => syncExtraChange(reciboId));
    });
  };

  // Init listeners on existing extra rows
  document.querySelectorAll('.js-extras-container').forEach(container => {
    const reciboId = container.dataset.reciboId;
    container.querySelectorAll('.js-extra-row').forEach(row => {
      attachExtraListeners(row, reciboId);
    });
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const money = (n) => (Math.round((n + Number.EPSILON) * 100) / 100).toFixed(2);

  const numVal = (el) => {
    if (!el) return 0;
    const v = parseFloat(el.value);
    return isNaN(v) ? 0 : v;
  };

  const kpiBruto = document.getElementById('kpi-bruto');
  const kpiDeds  = document.getElementById('kpi-deducciones');
  const kpiNeto  = document.getElementById('kpi-neto');

  const filas = Array.from(document.querySelectorAll('tr.fila-recibo'));

  const pendingSaves = new Map();
  const inFlightSaves = new Set();

  const updateStatusIndicator = (reciboId, status, message = '') => {
    const fila = document.querySelector(`tr.fila-recibo[data-recibo-id="${reciboId}"]`);
    if (!fila) return;
    const indicator = fila.querySelector('.js-status-indicator');
    if (!indicator) return;

    if (status === 'saving') {
      indicator.textContent = 'Guardando...';
      indicator.className = 'js-status-indicator text-[10px] text-amber-500 font-semibold';
    } else if (status === 'saved') {
      indicator.textContent = 'Guardado';
      indicator.className = 'js-status-indicator text-[10px] text-slate-400';
    } else if (status === 'error') {
      indicator.textContent = 'Error';
      indicator.title = message;
      indicator.className = 'js-status-indicator text-[10px] text-red-500 font-bold cursor-help';
    }
  };

  // Collect extras from DOM for a given recibo
  const collectExtras = (reciboId) => {
    const container = document.querySelector(`.js-extras-container[data-recibo-id="${reciboId}"]`);
    if (!container) return [];
    const rows = container.querySelectorAll('.js-extra-row');
    const extras = [];
    rows.forEach(row => {
      extras.push({
        id: row.querySelector('.js-extra-db-id')?.value || '',
        tipo: row.querySelector('.js-extra-tipo')?.value || '',
        monto: row.querySelector('.js-extra-monto')?.value || '0',
        notas: row.querySelector('.js-extra-notas')?.value || '',
        empleado_id: row.querySelector('.js-extra-empleado-id')?.value || '',
        obra_id: row.querySelector('.js-extra-obra-id')?.value || '',
        anio: row.querySelector('.js-extra-anio')?.value || '',
        fecha_pago: row.querySelector('.js-extra-fecha-pago')?.value || ''
      });
    });
    return extras;
  };

  // Sum extras monto for a recibo from the DOM
  const sumExtrasMonto = (reciboId) => {
    const container = document.querySelector(`.js-extras-container[data-recibo-id="${reciboId}"]`);
    if (!container) return 0;
    let total = 0;
    container.querySelectorAll('.js-extra-monto').forEach(inp => {
      const v = parseFloat(inp.value);
      if (!isNaN(v)) total += v;
    });
    return total;
  };

  const saveRecibo = async (reciboId) => {
    if (!isEditable) return;
    inFlightSaves.add(reciboId);
    updateStatusIndicator(reciboId, 'saving');

    const fila = document.querySelector(`tr.fila-recibo[data-recibo-id="${reciboId}"]`);
    if (!fila) return;

    const getVal = (namePattern) => {
      const el = fila.querySelector(`[name*="${namePattern}"]`);
      return el ? el.value : '';
    };

    const token = document.querySelector('input[name="_token"]')?.value || '';

    const data = {
      infonavit: getVal('[infonavit]'),
      faltas: getVal('[faltas]'),
      descuentos: getVal('[descuentos]'),
      horas_extra: getVal('[horas_extra]'),
      metros_lin_monto: getVal('[metros_lin_monto]'),
      comisiones_monto: getVal('[comisiones_monto]'),
      notas: getVal('[notas]'),
      obra_id: getVal('[obra_id]'),
      extras: collectExtras(reciboId)
    };

    try {
      const response = await fetch(`/nomina/corridas/${corridaId}/recibos/${reciboId}/autosave`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token
        },
        body: JSON.stringify(data)
      });

      const resData = await response.json();

      if (!response.ok || !resData.success) {
        throw new Error(resData.message || 'Error en el servidor');
      }

      updateStatusIndicator(reciboId, 'saved');

      // Update extras IDs returned from server so future saves update instead of create
      if (resData.recibo?.extras) {
        const container = document.querySelector(`.js-extras-container[data-recibo-id="${reciboId}"]`);
        if (container) {
          const rows = container.querySelectorAll('.js-extra-row');
          resData.recibo.extras.forEach((serverExtra, i) => {
            if (rows[i]) {
              const dbIdInput = rows[i].querySelector('.js-extra-db-id');
              if (dbIdInput) dbIdInput.value = serverExtra.id;
              rows[i].dataset.extraId = serverExtra.id;
            }
          });
        }
      }

      // Update KPIs
      if (resData.kpis) {
        if (kpiBruto) kpiBruto.textContent = money(parseFloat(resData.kpis.total_bruto));
        if (kpiDeds)  kpiDeds.textContent  = money(parseFloat(resData.kpis.total_deducciones));
        if (kpiNeto)  kpiNeto.textContent  = money(parseFloat(resData.kpis.total_neto));
      }

    } catch (err) {
      console.error('Autosave Error:', err);
      updateStatusIndicator(reciboId, 'error', err.message);
    } finally {
      inFlightSaves.delete(reciboId);
    }
  };

  const queueAutosave = (reciboId) => {
    if (!isEditable) return;
    if (pendingSaves.has(reciboId)) {
      clearTimeout(pendingSaves.get(reciboId));
    }
    const timeoutId = setTimeout(() => {
      pendingSaves.delete(reciboId);
      saveRecibo(reciboId);
    }, 1200);
    pendingSaves.set(reciboId, timeoutId);
  };

  // Make queueAutosave and recalcularFila globally accessible for dynamic extras
  window.queueAutosave = queueAutosave;

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

  // Recalcular fila with multi-extras support
  window.recalcularFila = (reciboId) => {
    const fila = document.querySelector(`tr.fila-recibo[data-recibo-id="${reciboId}"]`);
    if (!fila) return;

    const sueldoReal = parseFloat(fila.dataset.sueldoReal || '0') || 0;
    const q = (needle) => fila.querySelector(`[name*="[${needle}]"]`);

    const infonavit  = numVal(q('infonavit'));
    const faltas     = numVal(q('faltas'));
    const descuentos = numVal(q('descuentos'));
    const horasExtra = numVal(q('horas_extra'));
    const metros     = numVal(q('metros_lin_monto'));
    const comisiones = numVal(q('comisiones_monto'));
    const extraTotal = sumExtrasMonto(reciboId);

    const bruto = sueldoReal + horasExtra + metros + comisiones + extraTotal;
    const deds  = infonavit + faltas + descuentos;
    let neto = bruto - deds;
    if (neto < 0) neto = 0;

    const outBruto = fila.querySelector('.js-bruto');
    const outDeds  = fila.querySelector('.js-deducciones');
    const outNeto  = fila.querySelector('.js-neto');

    if (outBruto) outBruto.textContent = money(bruto);
    if (outDeds)  outDeds.textContent  = money(deds);
    if (outNeto)  outNeto.textContent  = money(neto);

    recalcularKpis();
  };

  filas.forEach((fila) => {
    const reciboId = fila.dataset.reciboId;

    const handler = () => {
      window.recalcularFila?.(reciboId);
      window.queueAutosave?.(reciboId);
    };

    fila.querySelectorAll('input, select').forEach((input) => {
      input.addEventListener('input', handler);
      input.addEventListener('change', handler);
    });

    // Initial calculation
    window.recalcularFila?.(reciboId);
  });

  recalcularKpis();

  // Prevent leaving page if saves are pending
  window.addEventListener('beforeunload', (e) => {
    if (inFlightSaves.size > 0 || pendingSaves.size > 0) {
      e.preventDefault();
      e.returnValue = 'Hay cambios de nomina guardandose en segundo plano. Seguro que quieres salir?';
      return e.returnValue;
    }
  });
});
</script>
