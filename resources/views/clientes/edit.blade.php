@extends('layouts.admin')

@section('title', 'Expediente del cliente')

@php
  $tabs = [
    'general'   => 'General',
    'obras'     => 'Obras',
    'facturas'  => 'Facturas',
    'pagos'     => 'Pagos',
    'contactos' => 'Contactos',
    'docs'      => 'Documentos',
    'notas'     => 'Notas',
  ];

  $currentTab = $tab ?? request()->query('tab', 'general');
@endphp

@section('content')
<div class="max-w-6xl mx-auto">

  {{-- Header --}}
  <div class="flex items-start justify-between gap-4 mb-4">
    <div>
      <h1 class="text-2xl font-bold text-[#0B265A]">
        Cliente: {{ $cliente->nombre_comercial }}
      </h1>
      <p class="text-sm text-slate-500">
        ID: {{ $cliente->id }}
        @if($cliente->rfc) · RFC: {{ $cliente->rfc }} @endif
        · Estatus:
        @if((int)$cliente->activo === 1)
          <span class="text-green-600 font-semibold">Activo</span>
        @else
          <span class="text-red-600 font-semibold">Inactivo</span>
        @endif
      </p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('clientes.index') }}"
         class="px-4 py-2 rounded bg-slate-100 hover:bg-slate-200">
        Volver
      </a>

      <form action="{{ route('clientes.destroy', $cliente) }}" method="POST"
            onsubmit="return confirm('¿Eliminar cliente? Esta acción no se puede deshacer.')">
        @csrf
        @method('DELETE')
        <button class="px-4 py-2 rounded bg-red-600 text-white hover:opacity-90">
          Eliminar
        </button>
      </form>
    </div>
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="mb-4 p-3 rounded bg-green-50 text-green-700">
      {{ session('success') }}
    </div>
  @endif

  @if($errors->any())
    <div class="mb-4 p-3 rounded bg-red-50 text-red-700">
      <div class="font-semibold mb-1">Revisa los campos marcados.</div>
      <ul class="list-disc ml-5 text-sm">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Tabs --}}
  <div class="bg-white rounded shadow">
    <div class="border-b px-4">
      <div class="flex gap-2 overflow-x-auto">
        @foreach($tabs as $key => $label)
          <a href="{{ route('clientes.edit', $cliente) }}?tab={{ $key }}"
             class="px-4 py-3 -mb-px border-b-2 whitespace-nowrap
                    {{ $currentTab === $key
                        ? 'border-[#0B265A] text-[#0B265A] font-semibold'
                        : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            {{ $label }}
          </a>
        @endforeach
      </div>
    </div>

    {{-- Tab body --}}
    <div class="p-6">
      @if($currentTab === 'general')
        <form action="{{ route('clientes.update', $cliente) }}" method="POST">
          @csrf
          @method('PUT')
          @include('clientes._form', ['cliente' => $cliente])
        </form>

      @elseif($currentTab === 'obras')
        @if(isset($obras) && $obras && $obras->count())
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-slate-800">Obras del cliente</h2>
            {{-- Si ya tienes ruta para crear obra, la conectamos después --}}
            {{-- <a href="{{ route('obras.create', ['cliente_id' => $cliente->id]) }}" class="px-3 py-2 rounded bg-[#0B265A] text-white">+ Nueva obra</a> --}}
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-slate-600">
                <tr>
                  <th class="text-left px-4 py-3">Obra</th>
                  <th class="text-left px-4 py-3">Estatus</th>
                  <th class="text-right px-4 py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($obras as $o)
               <tr class="border-t hover:bg-slate-50 cursor-pointer"
    onclick="window.location='{{ route('obras.edit', $o) }}'">
                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('obras.edit', $o->id ?? $o->id_Obra) }}"
                            class="text-[#0B265A] hover:underline">
                            {{ $o->nombre ?? $o->Nombre ?? ('Obra #' . ($o->id ?? '')) }}
                        </a>
                        </td>
                    <td class="px-4 py-3">
                      <span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-700">
                        {{ $o->estatus ?? $o->status ?? '—' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                      {{-- Ajusta a tu ruta real --}}
                      {{-- <a href="{{ route('obras.edit', $o) }}" class="px-3 py-1 rounded bg-slate-100 hover:bg-slate-200">Ver</a> --}}
                      <span class="text-slate-400 text-xs">pendiente</span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="mt-4">
            {{ $obras->links() }}
          </div>
        @else
          @include('clientes.partials.tab-placeholder', [
            'title' => 'Obras',
            'msg'   => 'Este cliente aún no tiene obras registradas (o falta conectar la relación/listado).'
          ])
        @endif
<!-- TAB DE FACTURAS -->
      @elseif($currentTab === 'facturas')

  <div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold text-slate-800">
      Facturas del cliente
    </h2>
    @if(!$cliente->rfc)
      <span class="text-xs text-red-600">
        El cliente no tiene RFC asignado.
      </span>
    @endif
  </div>

  @if(!$cliente->rfc)
      <div class="p-4 bg-yellow-50 border rounded text-yellow-700">
        No se pueden buscar facturas porque el cliente no tiene RFC.
      </div>

  @elseif($facturas && $facturas->count())

      {{-- KPIs rápidos --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-slate-50 p-4 rounded border">
          <div class="text-xs text-slate-500">Total facturado</div>
          <div class="text-xl font-bold">
            ${{ number_format($facturas->sum('total'), 2) }}
          </div>
        </div>

        <div class="bg-slate-50 p-4 rounded border">
          <div class="text-xs text-slate-500">Facturas emitidas</div>
          <div class="text-xl font-bold">
            {{ $facturas->total() }}
          </div>
        </div>

        <div class="bg-slate-50 p-4 rounded border">
          <div class="text-xs text-slate-500">Canceladas</div>
          <div class="text-xl font-bold text-red-600">
            {{ $facturas->where('status','cancelada')->count() }}
          </div>
        </div>
      </div>

      {{-- Tabla --}}
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="text-left px-4 py-3">Fecha</th>
              <th class="text-left px-4 py-3">Serie/Folio</th>
              <th class="text-left px-4 py-3">UUID</th>
              <th class="text-left px-4 py-3">Moneda</th>
              <th class="text-right px-4 py-3">Total</th>
              <th class="text-left px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($facturas as $f)
              <tr class="border-t">
                <td class="px-4 py-3">
                  {{ optional($f->fecha_emision)->format('Y-m-d') }}
                </td>

                <td class="px-4 py-3 font-medium">
                  {{ $f->serie }} {{ $f->folio }}
                </td>

                <td class="px-4 py-3 text-xs text-slate-500">
                  {{ Str::limit($f->uuid, 25) }}
                </td>

                <td class="px-4 py-3">
                  {{ $f->moneda }}
                </td>

                <td class="px-4 py-3 text-right font-semibold">
                  ${{ number_format($f->total, 2) }}
                </td>

                <td class="px-4 py-3">
                  @if($f->status === 'cancelada')
                    <span class="inline-flex px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">
                      Cancelada
                    </span>
                  @else
                    <span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                      Activa
                    </span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $facturas->links() }}
      </div>

  @else

      <div class="p-4 bg-slate-50 border rounded text-slate-600">
        No se encontraron facturas para este RFC.
      </div>

  @endif
        <!-- TERMINA TAB FACTURAS -->
      @elseif($currentTab === 'pagos')
        @include('clientes.partials.tab-placeholder', [
          'title' => 'Pagos',
          'msg'   => 'No tenemos captura de pagos todavía. Esta sección queda lista para cuando se implemente.'
        ])

      @elseif($currentTab === 'contactos')
        @include('clientes.partials.tab-placeholder', [
          'title' => 'Contactos',
          'msg'   => 'Pendiente: catálogo de contactos del cliente.'
        ])

      @elseif($currentTab === 'docs')
        @include('clientes.partials.tab-placeholder', [
          'title' => 'Documentos',
          'msg'   => 'Pendiente: documentos (RFC/contratos/anexos) del cliente.'
        ])

      @elseif($currentTab === 'notas')
        @include('clientes.partials.tab-placeholder', [
          'title' => 'Notas',
          'msg'   => 'Pendiente: bitácora/notas internas del cliente.'
        ])

      @else
        @include('clientes.partials.tab-placeholder', [
          'title' => 'Sección',
          'msg'   => 'Tab no reconocido.'
        ])
      @endif
    </div>
  </div>
</div>
@endsection