@extends('layouts.admin')

@section('title', 'Facturas')

@section('content')
<div class="w-full px-6">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-bold text-[#0B265A]">Facturas</h1>
    </div>
  </div>

  {{-- Filtros --}}
  <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-4">
    <form method="GET" action="{{ route('facturas.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">

      <div class="md:col-span-4">
        <label class="text-xs font-semibold text-slate-600">Búsqueda</label>
        <input
          type="text"
          name="q"
          value="{{ request('q') }}"
          placeholder="UUID / RFC / Razón Social / Serie / Folio"
          class="w-full mt-1 rounded-lg border-slate-300 focus:ring-[#0B265A] focus:border-[#0B265A]"
        />
      </div>

      <div class="md:col-span-2">
        <label class="text-xs font-semibold text-slate-600">Estatus</label>
        <select name="status" class="w-full mt-1 rounded-lg border-slate-300">
          <option value="">Todos</option>
          @foreach(($statuses ?? collect()) as $st)
            <option value="{{ $st }}" @selected(request('status') === (string)$st)>{{ $st }}</option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="text-xs font-semibold text-slate-600">Source</label>
        <select name="source_system" class="w-full mt-1 rounded-lg border-slate-300">
          <option value="">Todos</option>
          @foreach(($sources ?? collect()) as $src)
            <option value="{{ $src }}" @selected(request('source_system') === (string)$src)>{{ $src }}</option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="text-xs font-semibold text-slate-600">Desde</label>
        <input type="date" name="from" value="{{ request('from') }}"
          class="w-full mt-1 rounded-lg border-slate-300" />
      </div>

      <div class="md:col-span-2">
        <label class="text-xs font-semibold text-slate-600">Hasta</label>
        <input type="date" name="to" value="{{ request('to') }}"
          class="w-full mt-1 rounded-lg border-slate-300" />
      </div>

      <div class="md:col-span-12 flex items-center gap-2 pt-1">
        <button class="px-4 py-2 rounded-lg bg-[#0B265A] text-white text-sm font-semibold">
          Filtrar
        </button>

        <a href="{{ route('facturas.index') }}"
           class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold border border-slate-200">
          Limpiar
        </a>

        <div class="ml-auto text-sm text-slate-500">
          @if(method_exists($facturas, 'total'))
            Mostrando {{ $facturas->firstItem() ?? 0 }} - {{ $facturas->lastItem() ?? 0 }} de {{ $facturas->total() }}
          @endif
        </div>
      </div>
    </form>
  </div>

  {{-- Tabla --}}
  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left font-semibold px-4 py-3">Emisión</th>
            <th class="text-left font-semibold px-4 py-3">UUID</th>
            <th class="text-left font-semibold px-4 py-3">Serie/Folio</th>
            <th class="text-left font-semibold px-4 py-3">RFC Emisor</th>
            <th class="text-left font-semibold px-4 py-3">RFC Receptor</th>
            <th class="text-left font-semibold px-4 py-3">Razón social</th>
            <th class="text-right font-semibold px-4 py-3">Subtotal</th>
            <th class="text-right font-semibold px-4 py-3">Total</th>
            <th class="text-left font-semibold px-4 py-3">Moneda</th>
            <th class="text-left font-semibold px-4 py-3">Estatus</th>
            <th class="text-right font-semibold px-4 py-3">XML</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          @forelse($facturas as $f)
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-3 whitespace-nowrap">
                {{ optional($f->fecha_emision)->format('Y-m-d') ?? '—' }}
                <div class="text-xs text-slate-500">
                  Timbrado: {{ optional($f->fecha_timbrado)->format('Y-m-d') ?? '—' }}
                </div>
              </td>

              <td class="px-4 py-3">
                <div class="font-mono text-xs break-all max-w-[260px]">
                  {{ $f->uuid ?? '—' }}
                </div>
              </td>

              <td class="px-4 py-3 whitespace-nowrap">
                <span class="font-semibold">{{ $f->serie ?? '—' }}</span>
                <span class="text-slate-500">/</span>
                <span>{{ $f->folio ?? '—' }}</span>
                <div class="text-xs text-slate-500">
                  {{ $f->tipo_comprobante ?? '' }}
                </div>
              </td>

              <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">
                {{ $f->rfc_emisor ?? '—' }}
              </td>

              <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">
                {{ $f->rfc_receptor ?? '—' }}
              </td>

              <td class="px-4 py-3">
                <div class="max-w-[260px] truncate" title="{{ $f->razon_social }}">
                  {{ $f->razon_social ?? '—' }}
                </div>
              </td>

              <td class="px-4 py-3 text-right whitespace-nowrap">
                {{ number_format((float)($f->subtotal ?? 0), 2) }}
              </td>

              <td class="px-4 py-3 text-right whitespace-nowrap font-semibold">
                {{ number_format((float)($f->total ?? 0), 2) }}
              </td>

              <td class="px-4 py-3 whitespace-nowrap">
                {{ $f->moneda ?? '—' }}
                @if(!empty($f->tipo_cambio) && (string)$f->moneda !== 'MXN')
                  <div class="text-xs text-slate-500">TC: {{ $f->tipo_cambio }}</div>
                @endif
              </td>

              <td class="px-4 py-3 whitespace-nowrap">
                @php
                  $st = strtolower((string)($f->status ?? ''));
                @endphp

                @if(str_contains($st, 'cancel'))
                  <span class="inline-flex px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">
                    {{ $f->status }}
                  </span>
                  <div class="text-xs text-slate-500">
                    {{ optional($f->fecha_cancelacion)->format('Y-m-d') ?? '' }}
                  </div>
                @elseif(str_contains($st, 'vig') || str_contains($st, 'act'))
                  <span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                    {{ $f->status }}
                  </span>
                @else
                  <span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-700">
                    {{ $f->status ?? '—' }}
                  </span>
                @endif
              </td>

              <td class="px-4 py-3 text-right whitespace-nowrap">
                @if(!empty($f->xml))
                  <button
                    type="button"
                    class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold"
                    onclick="openXmlModal(@json($f->uuid), @json($f->xml))"
                  >
                    Ver
                  </button>
                @else
                  <span class="text-xs text-slate-400">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="px-4 py-10 text-center text-slate-500">
                No hay facturas con esos filtros.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Paginación --}}
    @if(method_exists($facturas, 'links'))
      <div class="p-3 border-t border-slate-200">
        {{ $facturas->links() }}
      </div>
    @endif
  </div>
</div>

{{-- Modal XML (solo lectura) --}}
<div id="xmlModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 z-50">
  <div class="bg-white w-[95%] max-w-4xl rounded-xl shadow-lg border border-slate-200 overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b border-slate-200">
      <div>
        <div class="text-sm font-bold text-[#0B265A]">XML</div>
        <div id="xmlModalSub" class="text-xs text-slate-500 font-mono"></div>
      </div>
      <button class="px-3 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-sm font-semibold"
              onclick="closeXmlModal()">
        Cerrar
      </button>
    </div>

    <div class="p-4">
      <textarea id="xmlModalBody" class="w-full h-[60vh] font-mono text-xs rounded-lg border-slate-300"
                readonly></textarea>

      <div class="flex items-center justify-end gap-2 mt-3">
        <button class="px-3 py-1.5 rounded-lg bg-[#0B265A] text-white text-sm font-semibold"
                onclick="copyXml()">
          Copiar XML
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  function openXmlModal(uuid, xml) {
    const modal = document.getElementById('xmlModal');
    const sub = document.getElementById('xmlModalSub');
    const body = document.getElementById('xmlModalBody');

    sub.textContent = uuid ? uuid : '';
    body.value = xml ? xml : '';

    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeXmlModal() {
    const modal = document.getElementById('xmlModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  async function copyXml() {
    const body = document.getElementById('xmlModalBody');
    try {
      await navigator.clipboard.writeText(body.value || '');
    } catch (e) {
      // fallback
      body.select();
      document.execCommand('copy');
      body.setSelectionRange(0, 0);
    }
  }

  // cerrar al dar click fuera
  document.addEventListener('click', (e) => {
    const modal = document.getElementById('xmlModal');
    if (modal.classList.contains('hidden')) return;
    if (e.target === modal) closeXmlModal();
  });
</script>
@endsection