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
            <input type="date" name="desde" value="{{ $desde }}"
                   class="w-full rounded-xl border-slate-200 text-sm">
        </div>

        {{-- Hasta --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ $hasta }}"
                   class="w-full rounded-xl border-slate-200 text-sm">
        </div>

        {{-- Tipo de pago --}}
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo de pago</label>
            <select name="tipo"id="tipoSelect" class="w-full rounded-xl border-slate-200 text-sm">
                <option value="semanal"   @selected($tipo === 'semanal')>Semanal</option>
                <option value="quincenal" @selected($tipo === 'quincenal')>Quincenal</option>
                <option value="mensual"   @selected($tipo === 'mensual')>Mensual</option>
            </select>
        </div>

       

        {{-- Botones --}}
        <div class="flex gap-2 justify-end">
            <button type="submit"
                    class="px-4 py-2 bg-slate-800 text-white text-sm rounded-xl shadow hover:bg-slate-900">
                Aplicar filtros
            </button>

            <!-- <button type="button"
                    onclick="confirmarGeneracion()"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow">
                Generar corrida
            </button> -->
            <button type="submit"
                    form="formGenerarCorrida"
                    onclick="
                        const sel = document.getElementById('tipoSelect');
                        const hidden = document.getElementById('tipoHidden');
                        if (!sel) { alert('No se encontró el selector de tipo'); return false; }
                        hidden.value = sel.value;
                        if (!hidden.value) { alert('Selecciona un tipo de pago'); return false; }
                        return confirm('¿Estás seguro de generar la corrida con los filtros actuales?');
                    "
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow">
                Generar corrida
            </button>



        </div>
    </div>
</form>
                
<form id="formGenerarCorrida"
      method="POST"
      action="{{ route('nomina.corridas.store') }}">
    @csrf

    <input type="hidden" name="desde" value="{{ $desde }}">
    <input type="hidden" name="hasta" value="{{ $hasta }}">
    <!-- <input type="hidden" name="tipo" value="{{ $tipo }}"> -->
      <input type="hidden" name="tipo" id="tipoHidden">
    
</form>

{{-- RESUMEN --}}
<div class="flex justify-between items-center mb-3 text-sm text-slate-600">
   
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
  {{-- RESUMEN --}}
<div class="flex justify-between items-center mb-3 text-sm text-slate-600">
  <div>
    Corridas:
    <span class="font-semibold">{{ $corridas->total() }}</span>
  </div>
  <div class="text-xs">
    Rango:
    <span class="font-semibold">
      {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
    </span>
    @if($tipo)
      · Tipo: <span class="font-semibold capitalize">{{ $tipo }}</span>
    @endif
    @if($status)
      · Status: <span class="font-semibold uppercase">{{ $status }}</span>
    @endif
  </div>
</div>

{{-- TABLA CORRIDAS --}}
<div class="bg-white rounded-2xl shadow overflow-x-auto">
  <table class="min-w-full text-xs md:text-sm">
    <thead class="bg-slate-50">
      <tr class="text-left text-slate-500 border-b">
        <th class="py-2 px-3">Corrida</th>
        <th class="py-2 px-3">Periodo</th>
        <th class="py-2 px-3">Tipo</th>
        <th class="py-2 px-3">Pago</th>
        <th class="py-2 px-3 text-center">Recibos</th>
        <th class="py-2 px-3 text-center">Status</th>
        <th class="py-2 px-3 text-right">Acciones</th>
      </tr>
    </thead>

    <tbody class="divide-y divide-slate-100">
      @forelse($corridas as $c)
        @php
          $st = $c->status ?? 'abierta';
          $badge = match ($st) {
            'abierta' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cerrada' => 'bg-slate-100 text-slate-700 border-slate-200',
            'pagada'  => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'cancelada' => 'bg-red-50 text-red-700 border-red-200',
            default   => 'bg-slate-100 text-slate-700 border-slate-200',
          };
        @endphp

        <tr class="hover:bg-slate-50">
          <td class="py-2 px-3">
            <div class="font-semibold text-slate-800">
              {{ $c->periodo_label ?? ('Corrida #'.$c->id) }}
            </div>
            <div class="text-[11px] text-slate-400">ID: {{ $c->id }}</div>
          </td>

          <td class="py-2 px-3 text-[12px]">
            {{ optional($c->fecha_inicio)->format('d/m/Y') }} – {{ optional($c->fecha_fin)->format('d/m/Y') }}
          </td>

          <td class="py-2 px-3 capitalize">{{ $c->tipo_pago }}</td>

          <td class="py-2 px-3 text-[12px]">
            {{ $c->fecha_pago ? \Carbon\Carbon::parse($c->fecha_pago)->format('d/m/Y') : '—' }}
          </td>

          <td class="py-2 px-3 text-center">
            <span class="inline-flex items-center px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-[11px]">
              {{ $c->recibos_count ?? 0 }}
            </span>
          </td>

          <td class="py-2 px-3 text-center">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border text-[11px] {{ $badge }}">
              <span class="inline-block w-2 h-2 rounded-full
                {{ $st==='abierta' ? 'bg-emerald-500' : ($st==='pagada' ? 'bg-indigo-500' : ($st==='cancelada' ? 'bg-red-500' : 'bg-slate-400')) }}">
              </span>
              {{ strtoupper($st) }}
            </span>
          </td>

          <td class="py-2 px-3">
            <div class="flex gap-2 justify-end">

              <a href="{{ route('nomina.corridas.show', $c) }}"
                 class="px-3 py-1 rounded-xl bg-slate-800 text-white text-xs hover:bg-slate-900">
                Ver
              </a>

           <div class="flex gap-2 justify-end items-center flex-wrap">


  @if(($c->status ?? '') === 'abierta')
    <form method="POST" action="{{ route('nomina.corridas.cerrar', $c) }}"
          onsubmit="return confirm('¿Cerrar la corrida? Ya no se podrá editar.')">
      @csrf
      <button type="submit"
              class="px-3 py-1 rounded-xl text-xs bg-slate-700 hover:bg-slate-800 text-white">
        Cerrar
      </button>
    </form>
  @endif

  @if(($c->status ?? '') === 'cerrada')
    <form method="POST" action="{{ route('nomina.corridas.pagar', $c) }}"
          onsubmit="return confirm('¿Marcar como PAGADA?')">
      @csrf
      <button type="submit"
              class="px-3 py-1 rounded-xl text-xs bg-indigo-600 hover:bg-indigo-700 text-white">
        Marcar pagada
      </button>
    </form>

    <form method="POST" action="{{ route('nomina.corridas.reabrir', $c) }}"
          onsubmit="return confirm('¿Reabrir para editar?')">
      @csrf
      <button type="submit"
              class="px-3 py-1 rounded-xl text-xs bg-slate-200 hover:bg-slate-300 text-slate-800">
        Reabrir
      </button>
    </form>
  @endif

  <form method="POST" action="{{ route('nomina.corridas.destroy', $c) }}"
        onsubmit="return confirm('¿Eliminar corrida completa?')">
    @csrf
    @method('DELETE')
    <button type="submit"
            @disabled(($c->status ?? '') !== 'abierta')
            class="px-3 py-1 rounded-xl text-xs {{ ($c->status ?? '') === 'abierta' ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
      Eliminar
    </button>
  </form>
</div>



            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="py-6 px-3 text-center text-slate-500 text-sm">
            No hay corridas en este rango.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">
  {{ $corridas->links() }}
</div>

</div>
@endsection
