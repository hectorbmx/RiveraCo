@extends('layouts.admin')

@section('title', 'Generador de Nómina')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4" x-data="{ modalOpen: false }">
    <div>
        <h1 class="text-2xl font-bold text-[#0B265A]">Generador de Nómina</h1>
        <p class="text-sm text-slate-500">Consulta corridas y genera nuevos periodos de pago.</p>
    </div>

    <div>
        <button type="button"
                @click="modalOpen = true"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva corrida
        </button>

        {{-- Modal para generar nueva corrida con selección explícita de periodo --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
            <div @click.away="modalOpen = false" class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <div>
                        <h3 class="font-bold text-[#0B265A] text-lg">Nueva Corrida de Nómina</h3>
                        <p class="text-xs text-slate-500">Define el tipo de pago y el rango del periodo a generar.</p>
                    </div>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                </div>

                <form method="POST" action="{{ route('nomina.corridas.store') }}" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo de pago</label>
                        <select name="tipo" required class="w-full rounded-xl border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                            <option value="semanal" selected>Semanal</option>
                            <option value="quincenal">Quincenal</option>
                            <option value="mensual">Mensual</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Desde (Inicio)</label>
                            <input type="date" name="desde" required value="{{ now()->startOfWeek()->toDateString() }}" class="w-full rounded-xl border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Hasta (Fin)</label>
                            <input type="date" name="hasta" required value="{{ now()->endOfWeek()->toDateString() }}" class="w-full rounded-xl border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-sm hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 text-white font-semibold text-sm hover:bg-emerald-700 shadow-sm transition">
                            Generar corrida
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- FILTROS --}}
<x-filters.card action="{{ route('nomina.generador.index') }}" class="mb-4 p-3">
    <x-filters.input
        name="q"
        label="Buscar"
        :value="$q"
        placeholder="Periodo o ID de corrida"
        span="md:col-span-3"
        type="search"
        glow />

    <x-filters.date
        name="desde"
        label="Desde"
        :value="$desde"
        span="md:col-span-2" />

    <x-filters.date
        name="hasta"
        label="Hasta"
        :value="$hasta"
        span="md:col-span-2" />

    <x-filters.select
        name="tipo"
        label="Tipo de pago"
        :value="$tipo"
        :options="['semanal' => 'Semanal', 'quincenal' => 'Quincenal', 'mensual' => 'Mensual']"
        placeholder="Todos"
        span="md:col-span-2" />

    <x-filters.select
        name="status"
        label="Status"
        :value="$status"
        :options="['abierta' => 'Abierta', 'cerrada' => 'Cerrada', 'pagada' => 'Pagada', 'cancelada' => 'Cancelada']"
        placeholder="Todos"
        span="md:col-span-1" />

    <x-filters.actions
        submit-label="Filtrar"
        clear-url="{{ route('nomina.generador.index') }}"
        span="md:col-span-2" />
</x-filters.card>

{{-- RESUMEN --}}
<div class="flex justify-between items-center mb-3 text-sm text-slate-600">
  <div>
    Corridas registradas:
    <span class="font-semibold text-slate-900">{{ $corridas->total() }}</span>
  </div>
  <div class="text-xs">
    @if($desde && $hasta)
      Rango filtrado:
      <span class="font-semibold">
        {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
      </span>
    @else
      <span class="text-slate-400">Mostrando todos los periodos</span>
    @endif
    @if($tipo)
      · Tipo: <span class="font-semibold capitalize">{{ $tipo }}</span>
    @endif
    @if($status)
      · Status: <span class="font-semibold uppercase">{{ $status }}</span>
    @endif
  </div>
</div>

{{-- TABLA CORRIDAS --}}
<div class="bg-white rounded-2xl shadow overflow-x-auto border border-slate-100">
  <table class="min-w-full text-xs md:text-sm">
    <thead class="bg-[#0B265A] text-white">
      <tr class="text-left border-b border-white/20">
        <th class="py-2.5 px-3 font-semibold text-white">Corrida</th>
        <th class="py-2.5 px-3 font-semibold text-white">Periodo</th>
        <th class="py-2.5 px-3 font-semibold text-white">Tipo</th>
        <th class="py-2.5 px-3 font-semibold text-white">Pago</th>
        <th class="py-2.5 px-3 font-semibold text-white text-center">Recibos</th>
        <th class="py-2.5 px-3 font-semibold text-white text-center">Status</th>
        <th class="py-2.5 px-3 font-semibold text-white text-right">Acciones</th>
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

  @can('nomina.corridas.close.access')
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
  @endcan

  @can('nomina.corridas.pay.access')
  @if(($c->status ?? '') === 'cerrada')
    <form method="POST" action="{{ route('nomina.corridas.pagar', $c) }}"
          onsubmit="return confirm('¿Marcar como PAGADA?')">
      @csrf
      <button type="submit"
              class="px-3 py-1 rounded-xl text-xs bg-indigo-600 hover:bg-indigo-700 text-white">
        Marcar pagada
      </button>
    </form>
  @endif
  @endcan

  @can('nomina.corridas.reopen.access')
  @if(($c->status ?? '') === 'cerrada')
    <form method="POST" action="{{ route('nomina.corridas.reabrir', $c) }}"
          onsubmit="return confirm('¿Reabrir para editar?')">
      @csrf
      <button type="submit"
              class="px-3 py-1 rounded-xl text-xs bg-slate-200 hover:bg-slate-300 text-slate-800">
        Reabrir
      </button>
    </form>
  @endif
  @endcan

  @can('nomina.corridas.delete.access')
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
  @endcan
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
@endsection


