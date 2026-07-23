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
    'seguimiento' => 'Seguimiento',
    'portales' => 'Portales',
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
        @if($cliente->rfc) Â· RFC: {{ $cliente->rfc }} @endif
        Â· Estatus:
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
            onsubmit="return confirm('Â¿Eliminar cliente? Esta acciÃ³n no se puede deshacer.')">
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

  @if(session('error'))
    <div class="mb-4 p-3 rounded bg-red-50 text-red-700">
      {{ session('error') }}
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
            {{-- Si ya tienes ruta para crear obra, la conectamos despuÃ©s --}}
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
                        {{ $o->estatus ?? $o->status ?? 'â€”' }}
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
            'msg'   => 'Este cliente aÃºn no tiene obras registradas (o falta conectar la relaciÃ³n/listado).'
          ])
        @endif
      <!-- TAB DE FACTURAS -->
      @elseif($currentTab === 'facturas')

  <div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold text-slate-800">
      Facturas del cliente
    </h2>
    @if(!$cliente->rfc)
      <span class="text-xs text-amber-600 font-medium">
        âš  El cliente no tiene RFC asignado â€” agrega el RFC en la pestaÃ±a General para ver facturas.
      </span>
    @endif
  </div>

  @if(!$cliente->rfc)
      <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-sm">
        <strong>Sin RFC:</strong> No se pueden buscar facturas porque el cliente no tiene RFC registrado.
        <a href="{{ route('clientes.edit', $cliente) }}?tab=general" class="underline ml-1">Editar cliente</a>
      </div>

  @elseif($facturas && $facturas->count())

      {{-- KPIs --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-slate-50 p-4 rounded-xl border">
          <div class="text-xs text-slate-500 uppercase tracking-wide">Total facturado</div>
          <div class="text-xl font-bold text-slate-800 mt-1">
            ${{ number_format($facturas->sum('total'), 2) }}
          </div>
        </div>
        <div class="bg-slate-50 p-4 rounded-xl border">
          <div class="text-xs text-slate-500 uppercase tracking-wide">Facturas emitidas</div>
          <div class="text-xl font-bold text-slate-800 mt-1">{{ $facturas->count() }}</div>
        </div>
        <div class="bg-slate-50 p-4 rounded-xl border">
          <div class="text-xs text-slate-500 uppercase tracking-wide">Canceladas</div>
          <div class="text-xl font-bold text-red-600 mt-1">
            {{ $facturas->where('estado', 'cancelled')->count() }}
          </div>
        </div>
      </div>

      {{-- Tabla --}}
      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wide">
            <tr>
              <th class="text-left px-4 py-3">Origen</th>
              <th class="text-left px-4 py-3">Fecha</th>
              <th class="text-left px-4 py-3">Serie/Folio</th>
              <th class="text-left px-4 py-3">UUID</th>
              <th class="text-left px-4 py-3">Moneda</th>
              <th class="text-right px-4 py-3">Total</th>
              <th class="text-center px-4 py-3">Obra</th>
              <th class="text-center px-4 py-3">Estado</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($facturas as $f)
              <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold
                    {{ $f['origen'] === 'FacturAPI' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }}">
                    {{ $f['origen'] }}
                  </span>
                </td>
                <td class="px-4 py-3 text-slate-600">{{ $f['fecha'] }}</td>
                <td class="px-4 py-3 font-medium">{{ $f['serie_folio'] ?: 'â€”' }}</td>
                <td class="px-4 py-3 font-mono text-[11px] text-slate-500">
                  {{ Str::limit($f['uuid'], 28) }}
                </td>
                <td class="px-4 py-3">{{ $f['moneda'] ?? 'MXN' }}</td>
                <td class="px-4 py-3 text-right font-semibold">
                  ${{ number_format($f['total'], 2) }}
                </td>
                <td class="px-4 py-3 text-center">
                  @if($f['obra_id'])
                    <a href="{{ route('obras.edit', $f['obra_id']) }}"
                       class="text-[11px] text-[#0B265A] hover:underline font-medium">
                      Ver obra
                    </a>
                  @else
                    <span class="text-[11px] text-slate-400">Sin asignar</span>
                  @endif
                </td>
                <td class="px-4 py-3 text-center">
                  @if($f['estado'] === 'cancelled')
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] bg-red-100 text-red-700">Cancelada</span>
                  @elseif($f['estado'])
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] bg-emerald-100 text-emerald-700">{{ ucfirst($f['estado']) }}</span>
                  @else
                    <span class="text-slate-400 text-[11px]">â€”</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

  @else
      <div class="p-4 bg-slate-50 border rounded-lg text-slate-600 text-sm">
        No se encontraron facturas emitidas por Rivera Construcciones a este cliente.
      </div>
  @endif
        <!-- TERMINA TAB FACTURAS -->
      @elseif($currentTab === 'pagos')
        @include('clientes.partials.tab-placeholder', [
          'title' => 'Pagos',
          'msg'   => 'No tenemos captura de pagos todavÃ­a. Esta secciÃ³n queda lista para cuando se implemente.'
        ])

      @elseif($currentTab === 'contactos')
        @include('clientes.partials.tab-placeholder', [
          'title' => 'Contactos',
          'msg'   => 'Pendiente: catÃ¡logo de contactos del cliente.'
        ])

      @elseif($currentTab === 'docs')
        @include('clientes.partials.tab-placeholder', [
          'title' => 'Documentos',
          'msg'   => 'Pendiente: documentos (RFC/contratos/anexos) del cliente.'
        ])


      @elseif($currentTab === 'seguimiento')
        <div class="flex flex-col gap-4 mb-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-800">Llamadas relacionadas</h2>
            <p class="text-sm text-slate-500">Historial identificado por los telefonos registrados del cliente.</p>
            @if(isset($extensionTelefoniaActual) && $extensionTelefoniaActual)
              <p class="mt-1 text-xs text-slate-500">Tu extension para llamar: <span class="font-semibold text-slate-700">{{ $extensionTelefoniaActual->extension }}</span></p>
            @else
              <p class="mt-1 text-xs text-amber-700">Tu usuario no tiene extension asignada para click-to-call.</p>
            @endif
          </div>

          <div class="w-full lg:w-auto">
            @if(isset($telefonosSeguimiento) && $telefonosSeguimiento->count())
              <div class="flex flex-wrap gap-2 lg:justify-end">
                @foreach($telefonosSeguimiento as $telefonoSeguimiento)
                  <form method="POST" action="{{ route('clientes.telephony.call', ['cliente' => $cliente, 'phoneNumber' => $telefonoSeguimiento]) }}" onsubmit="return confirm('Se llamara desde tu extension al numero {{ $telefonoSeguimiento->raw_number }}. Deseas continuar?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60" {{ isset($extensionTelefoniaActual) && $extensionTelefoniaActual ? '' : 'disabled' }}>
                      <span aria-hidden="true">&#9742;</span>
                      Llamar {{ $telefonoSeguimiento->raw_number }}
                    </button>
                  </form>
                @endforeach
              </div>
            @else
              <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                No hay telefonos indexados para llamar. Ejecuta el indexador de telefonos si acabas de capturar el numero.
              </div>
            @endif
          </div>
        </div>

        @if(isset($llamadasSeguimiento) && $llamadasSeguimiento && $llamadasSeguimiento->count())
          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wide">
                <tr>
                  <th class="text-left px-4 py-3">Fecha</th>
                  <th class="text-left px-4 py-3">Empleado</th>
                  <th class="text-left px-4 py-3">Extension</th>
                  <th class="text-left px-4 py-3">Numero</th>
                  <th class="text-left px-4 py-3">Direccion</th>
                  <th class="text-left px-4 py-3">Estado</th>
                  <th class="text-right px-4 py-3">Duracion</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @foreach($llamadasSeguimiento as $llamada)
                  <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-700">
                      {{ optional($llamada->started_at)->format('Y-m-d H:i') ?: '-' }}
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                      {{ $llamada->user_name_snapshot ?: optional($llamada->user)->name ?: '-' }}
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                      {{ $llamada->extension_snapshot ?: optional($llamada->extension)->extension ?: '-' }}
                      @if($llamada->extension_name_snapshot)
                        <div class="text-xs text-slate-400">{{ $llamada->extension_name_snapshot }}</div>
                      @endif
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-700">
                      {{ $llamada->matched_number ?: $llamada->destination_number ?: $llamada->source_number ?: '-' }}
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $llamada->direction ?: '-' }}</td>
                    <td class="px-4 py-3">
                      <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $llamada->status === 'answered' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $llamada->status ?: '-' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right text-slate-700">
                      {{ gmdate('H:i:s', (int) $llamada->duration_seconds) }}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="mt-4">
            {{ $llamadasSeguimiento->links() }}
          </div>
        @else
          <div class="p-4 bg-slate-50 border rounded-lg text-slate-600 text-sm">
            No hay llamadas relacionadas todavia. Importa CDR y valida que el telefono del cliente este normalizado en SIRICO.
          </div>
        @endif
      @elseif($currentTab === 'portales')
        @include('clientes.partials._portales', ['cliente' => $cliente, 'portales' => $portales])
      @elseif($currentTab === 'notas')
        @include('clientes.partials.tab-placeholder', [
          'title' => 'Notas',
          'msg'   => 'Pendiente: bitÃ¡cora/notas internas del cliente.'
        ])

      @else
        @include('clientes.partials.tab-placeholder', [
          'title' => 'SecciÃ³n',
          'msg'   => 'Tab no reconocido.'
        ])
      @endif
    </div>
  </div>
</div>
@endsection
