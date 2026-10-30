{{-- resources/views/inventario/documentos/create.blade.php --}}
@extends('layouts.admin')



@section('content')
@php
  $tipo = $tipo ?? request('tipo','entrada');

  $tipoLabel = [
    'entrada' => 'Entrada',
    'salida' => 'Salida',
    'resguardo' => 'Resguardo',
    'devolucion' => 'Devoluci&oacute;n',
    'ajuste' => 'Ajuste',
  ][$tipo] ?? ucfirst($tipo);

  $needsObra = in_array($tipo, ['resguardo','devolucion'], true);
  $needsMotivo = in_array($tipo, ['entrada','ajuste'], true);
@endphp

<div class="max-w-7xl mx-auto px-6 py-6">

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-semibold">Nuevo documento &mdash; {!! $tipoLabel !!}</h1>
      <p class="text-slate-500 text-sm">Captura cabecera y partidas. Puedes guardar como borrador o aplicar.</p>
    </div>

    <a href="{{ route('inventario.documentos.index', array_merge(request()->query(), ['tipo'=>$tipo])) }}"
       class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm">
      Volver
    </a>
  </div>

  <form method="POST"
        action="{{ route('inventario.documentos.store', array_merge(request()->query(), ['tipo'=>$tipo])) }}"
        class="space-y-6">
    @csrf
    @if(!empty($abastecimiento))
      <input type="hidden" name="mantenimiento_abastecimiento_id" value="{{ $abastecimiento->id }}">
    @endif

    {{-- tipo fijo por tab --}}
    <input type="hidden" name="tipo" value="{{ $tipo }}">

    {{-- CARD: Cabecera --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <h2 class="font-semibold mb-4">Cabecera</h2>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Almac&eacute;n --}}
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Almac&eacute;n *</label>
          @if(!empty($almacenFijo))
            <div class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-semibold text-[#0B265A]">{{ $almacenFijo->nombre }}</div>
            <input type="hidden" name="almacen_id" value="{{ $almacenFijo->id }}">
          @else
          <select name="almacen_id" required
                  class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200">
            <option value="">Selecciona&hellip;</option>
            @foreach(($almacenes ?? []) as $a)
              <option value="{{ $a->id }}" @selected(old('almacen_id')==$a->id)>{{ $a->nombre }}</option>
            @endforeach
          </select>
          @endif
          @error('almacen_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
        </div>

        {{-- Fecha (datetime) --}}
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Fecha y hora *</label>
          <input type="datetime-local" name="fecha" required
                 value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}"
                 class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200">
          @error('fecha')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
        </div>

      
        @if($tipo === 'salida')
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Obra destino (opcional)</label>
            <select name="obra_id"
                    class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200">
              <option value="">Sin obra / consumo general</option>
              @foreach(($obras ?? []) as $o)
                <option value="{{ $o->id }}" @selected(old('obra_id') == $o->id)>
                  {{ $o->clave_obra }}{{ $o->nombre ? ' - '.$o->nombre : '' }}
                </option>
              @endforeach
            </select>
            @error('obra_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
          </div>
        @endif

        @if($tipo === 'entrada')
  <div class="relative" x-data="buscadorProveedor()">
    <label class="block text-sm font-medium text-slate-700 mb-1">Proveedor (opcional)</label>
    
    <div class="relative">
        <input type="text" 
               x-model="search" 
               @input.debounce.300ms="buscar()"
               placeholder="Escribe RFC o nombre (m&iacute;n. 3 letras)..."
               class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200 text-sm">
        
        <div x-show="loading" class="absolute right-3 top-2.5">
            <svg class="animate-spin h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>
    </div>

    <input type="hidden" name="proveedor_id" :value="selectedId">

    <div x-show="results.length > 0" 
         class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto"
         @click.away="results = []">
        <template x-for="p in results" :key="p.id">
            <button type="button" 
                    @click="select(p)"
                    class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 border-b border-slate-50 last:border-0">
                <div class="font-medium text-slate-800" x-text="p.nombre"></div>
                <div class="text-xs text-slate-500" x-text="p.rfc"></div>
            </button>
        </template>
    </div>

    @error('proveedor_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
  </div>
@endif

        {{-- Obra (si aplica) --}}
        @if($needsObra)
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Obra *</label>
            <select name="obra_id" 
                    class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200">
              <option value="">Selecciona&hellip;</option>
              @foreach(($obras ?? []) as $o)
                <option value="{{ $o->id }}" @selected(old('obra_id')==$o->id)>{{ $o->clave_obra }}</option>
              @endforeach
            </select>
            @error('obra_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
          </div>
        @endif

        @if($tipo !== 'salida')
          {{-- Motivo: se conserva para entradas y ajustes. Las salidas usan su vínculo o notas. --}}
          <div class="{{ $needsObra ? 'md:col-span-3' : 'md:col-span-2' }}">
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Motivo{!! $needsMotivo ? ' *' : '' !!}
            </label>
            <input type="text" name="motivo" {{ $needsMotivo ? 'required' : '' }}
                   value="{{ old('motivo') }}"
                   placeholder="{{ $tipo==='entrada' ? 'Ej. Compra directa, recepcion, etc.' : 'Opcional' }}"
                   class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200">
            @error('motivo')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
          </div>
        @elseif(empty($abastecimiento))
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Solicitud de almacén</label>
            <select id="solicitudAlmacenSelect"
                    class="w-full rounded-xl border-slate-200 bg-white focus:border-slate-400 focus:ring-slate-200">
              <option value="">Salida manual / consumo general</option>
              @foreach(($solicitudesAlmacenPendientes ?? []) as $solicitud)
                @php
                  $mantenimiento = $solicitud->mantenimiento;
                  $equipo = $mantenimiento?->maquina?->codigo
                      ?? $mantenimiento?->maquina?->nombre
                      ?? $mantenimiento?->vehiculo?->placas
                      ?? $mantenimiento?->vehiculo?->modelo
                      ?? 'Equipo no especificado';
                  $servicio = $mantenimiento?->servicioPreventivoTipo?->nombre
                      ?? $mantenimiento?->categoria_mantenimiento
                      ?? 'Servicio de mantenimiento';
                @endphp
                <option value="{{ $solicitud->id }}">
                  {{ $solicitud->folio }} - {{ $equipo }} - {{ $servicio }}
                </option>
              @endforeach
            </select>
            <p class="mt-1 text-[11px] text-slate-500">Solo muestra solicitudes pendientes o parciales.</p>
            @error('mantenimiento_abastecimiento_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
          </div>
        @endif

        {{-- Notas --}}
        <div class="md:col-span-3">
          <label class="block text-sm font-medium text-slate-700 mb-1">Notas (opcional)</label>
          <textarea name="notas" rows="2"
                    class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200"
                    placeholder="Observaciones&hellip;">{{ old('notas') }}</textarea>
          @error('notas')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
        </div>

        {{-- Ajuste: tipo incremento/decremento (solo UI; se manda en request) --}}
        @if($tipo === 'ajuste')
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de ajuste *</label>
            <select name="ajuste_tipo" required
                    class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200"
                    id="ajusteTipoSelect">
              <option value="incremento" @selected(old('ajuste_tipo','incremento')==='incremento')>Incremento</option>
              <option value="decremento" @selected(old('ajuste_tipo')==='decremento')>Decremento</option>
            </select>
            @error('ajuste_tipo')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
          </div>
        @endif
      </div>
    </div>

{{-- CARD: Partidas --}}
<div class="bg-white rounded-2xl border border-slate-200 p-5" x-data="documentoPartidas()">
  @if(!empty($abastecimiento))
    <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Solicitud de almacén</p>
          <p class="mt-1 text-lg font-semibold text-[#0B265A]">{{ $abastecimiento->folio }}</p>
          <p class="text-sm text-slate-600">
            Servicio para {{ $abastecimiento->mantenimiento?->maquina?->codigo ?? $abastecimiento->mantenimiento?->maquina?->nombre ?? 'máquina' }}.
            Las partidas se cargaron desde la solicitud.
          </p>
        </div>
        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700">Salida vinculada</span>
      </div>
    </div>
  @endif
  @if($tipo === 'entrada')
    <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2">
      <div class="rounded-xl border border-slate-200 p-4">
        <label class="block text-sm font-medium text-slate-700 mb-1">
            Orden de compra
        </label>

        <div class="relative">
            <input type="text"
                   x-model="ocSearch"
                   @input.debounce.300ms="buscarOrdenesCompra()"
                   placeholder="Busca folio, proveedor o RFC..."
                   class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200 text-sm">

            <div x-show="ocLoading" class="absolute right-3 top-2.5">
                <svg class="animate-spin h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <div x-show="ocResults.length > 0"
             class="relative z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto"
             @click.away="ocResults = []">
            <template x-for="oc in ocResults" :key="oc.id">
                <button type="button"
                        @click="selectOrdenCompra(oc)"
                        class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 border-b border-slate-50 last:border-0">
                    <div class="font-medium text-slate-800" x-text="oc.folio"></div>
                    <div class="text-xs text-slate-500" x-text="oc.proveedor"></div>
                </button>
            </template>
        </div>

        <div x-show="selectedOrders.length > 0" class="mt-3 flex flex-wrap gap-2">
            <template x-for="oc in selectedOrders" :key="oc.id">
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 text-slate-700 px-2.5 py-1 text-xs font-medium">
                    <span x-text="oc.folio"></span>
                    <button type="button" @click="removeOrder(oc.id)" class="text-slate-500 hover:text-red-500" aria-label="Quitar orden">&times;</button>
                </span>
            </template>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 p-4">
        <label class="block text-sm font-medium text-slate-700 mb-1">Caja chica</label>
        <div class="relative flex items-center overflow-hidden rounded-xl border border-slate-200 focus-within:border-slate-400">
          <span class="self-stretch bg-slate-100 px-3 py-2.5 text-sm font-semibold text-slate-600">RCC-G-</span>
          <input type="text"
                 x-model="cajaSearch"
                 @input.debounce.300ms="buscarGastosCajaChica()"
                 inputmode="numeric"
                 pattern="[0-9]*"
                 placeholder="00051"
                 class="w-full border-0 text-sm focus:ring-0">
          <div x-show="cajaLoading" class="absolute right-3 top-2.5">
            <svg class="animate-spin h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
          </div>
        </div>

        <div x-show="cajaResults.length > 0"
             class="relative z-50 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg"
             @click.away="cajaResults = []">
          <template x-for="gasto in cajaResults" :key="gasto.id">
            <button type="button"
                    @click="selectGastoCajaChica(gasto)"
                    class="w-full border-b border-slate-50 px-4 py-2 text-left text-sm hover:bg-slate-50 last:border-0">
              <div class="font-medium text-slate-800" x-text="gasto.folio + ' · ' + gasto.proveedor"></div>
              <div class="text-xs text-slate-500" x-text="gasto.concepto"></div>
            </button>
          </template>
        </div>

        <div x-show="selectedCajaChica" class="mt-3 rounded-lg border border-blue-100 bg-blue-50/50 p-3 text-sm">
          <div class="flex items-center justify-between gap-3">
            <span class="font-semibold text-[#0B265A]" x-text="selectedCajaChica?.folio"></span>
            <button type="button" @click="selectedCajaChica = null" class="text-xs font-semibold text-slate-500 hover:text-red-600">Quitar</button>
          </div>
          <dl class="mt-2 grid grid-cols-1 gap-1 text-xs text-slate-600 sm:grid-cols-2">
            <div><dt class="font-semibold">Proveedor</dt><dd x-text="selectedCajaChica?.proveedor"></dd></div>
            <div><dt class="font-semibold">Fecha</dt><dd x-text="selectedCajaChica?.fecha || '-'" ></dd></div>
            <div><dt class="font-semibold">Categoría</dt><dd x-text="selectedCajaChica?.categoria"></dd></div>
            <div><dt class="font-semibold">Importe autorizado</dt><dd x-text="formatMoney(selectedCajaChica?.importe_autorizado)"></dd></div>
            <div class="sm:col-span-2"><dt class="font-semibold">Concepto</dt><dd x-text="selectedCajaChica?.concepto"></dd></div>
          </dl>
        </div>
      </div>
    </div>
  @endif

  @if(empty($abastecimiento))
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
    <div>
        <h2 class="font-semibold mb-2">Partidas</h2>
        <p class="text-xs text-slate-500">Busca un producto para a&ntilde;adirlo a la lista</p>
    </div>

    <div class="relative flex-1 max-w-xl">
        <div class="flex gap-2">
            <div class="relative flex-1">
                <input type="text" 
                       x-model="search" 
                       @input.debounce.300ms="buscar()"
                       @keydown.enter.prevent="if(selectedProduct) addRow()"
                       placeholder="Buscar producto por nombre o descripci&oacute;n..."
                       class="w-full rounded-xl border-slate-200 text-sm focus:border-[#0B265A] focus:ring-0">
                
                <div x-show="results.length > 0" 
                     class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-64 overflow-auto"
                     @click.away="results = []">
                    <template x-for="p in results" :key="p.id">
                        <button type="button" 
                                @click="preselect(p)"
                                class="w-full text-left px-4 py-3 hover:bg-slate-50 border-b border-slate-50 last:border-0 transition-colors">
                            <div class="font-medium text-slate-800 text-sm" x-text="p.nombre"></div>
                            <div class="text-[10px] text-slate-400 uppercase tracking-wider" x-text="p.descripcion ? p.descripcion.substring(0, 60) + '...' : ''"></div>
                            <div x-show="isCostoReferencia" class="mt-1 text-xs text-slate-500">
                                <span>Stock: </span><span x-text="formatNumber(p.stock_actual)"></span>
                                <span class="mx-1">&middot;</span>
                                <span>Costo prom.: $</span><span x-text="formatMoney(p.costo_promedio)"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
            
            <button type="button" 
                    @click="addRow()"
                    :disabled="!selectedProduct"
                    class="px-4 py-2 rounded-xl bg-[#0B265A] text-white text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <span class="text-lg">+</span> Agregar
            </button>
        </div>
    </div>
  </div>
  @else
    <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
      Agrega únicamente las cantidades que almacén va a surtir en esta aplicación. No se permite agregar productos fuera de la solicitud.
    </div>
  @endif

  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-[#0B265A] text-white">
        <tr class="border-b">
          <th class="text-left py-3 pr-3">Producto</th>
          <th class="text-left py-3 pr-3 w-32">Cantidad</th>
          @if($tipo === 'salida')
            <th class="text-left py-3 pr-3 w-32">Disponible</th>
            <th class="text-left py-3 pr-3 w-72">Equipo destino</th>
          @endif
          @if(in_array($tipo, ['entrada','ajuste'], true))
            <th class="text-left py-3 pr-3 w-40">Costo unitario</th>
          @endif
          <th class="text-left py-3 pr-3">Notas</th>
          <th class="text-right py-3 w-16"></th>
        </tr>
      </thead>
      <tbody>
        <template x-for="(item, index) in partidas" :key="index">
            <tr class="border-b align-top" :class="item.requiere_vinculo_producto ? 'bg-amber-50' : 'bg-white'">
              <td class="py-3 pr-3">
                <div class="font-medium" :class="item.requiere_vinculo_producto ? 'text-amber-900' : 'text-slate-700'" x-text="item.nombre"></div>
                <p x-show="item.requiere_vinculo_producto" class="mt-1 text-xs text-amber-700">Esta partida de la OC no esta vinculada a un producto de inventario.</p>
                <input type="hidden" :name="`detalles[${index}][producto_id]`" :value="item.producto_id">
                <input type="hidden" :name="`detalles[${index}][orden_compra_id]`" :value="item.orden_compra_id || ''">
                <input type="hidden" :name="`detalles[${index}][orden_compra_detalle_id]`" :value="item.orden_compra_detalle_id || ''">

              </td>
              <td class="py-3 pr-3">
                <input type="number" step="1" min="{{ $tipo === 'salida' ? 0 : 1 }}" required
                       :name="`detalles[${index}][cantidad]`"
                       x-model="item.cantidad"
                       class="w-full rounded-lg border border-slate-300 bg-white text-sm focus:border-[#0B265A] focus:outline-none focus:ring-2 focus:ring-blue-100">
                <p x-show="item.cantidad_pendiente && item.cantidad > item.disponible && item.disponible !== null"
                   x-cloak
                   class="mt-1 text-[11px] font-medium text-red-600">
                  Ajusta la cantidad al disponible.
                </p>
              </td>
              @if($tipo === 'salida')
                <td class="py-3 pr-3 align-top">
                  <span x-show="item.disponible !== null"
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                        :class="item.disponible >= (item.cantidad_pendiente || item.cantidad) ? 'bg-emerald-100 text-emerald-700' : (item.disponible > 0 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                        x-text="item.disponible !== null ? formatNumber(item.disponible) : ''"></span>
                  <span x-show="item.disponible === null" class="text-xs text-slate-400">Selecciona almacén</span>
                </td>
                <td class="py-3 pr-3">
                  @if(!empty($abastecimiento))
                    <input type="hidden" :name="`detalles[${index}][equipo_activo]`" :value="item.equipo_activo ? 1 : 0">
                    <input type="hidden" :name="`detalles[${index}][equipo_tipo]`" :value="item.equipo_tipo">
                    <input type="hidden" :name="`detalles[${index}][vehiculo_id]`" :value="item.vehiculo_id || ''">
                    <input type="hidden" :name="`detalles[${index}][maquina_id]`" :value="item.maquina_id || ''">
                  @endif
                  <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                    @if(empty($abastecimiento))
                      <input type="hidden" :name="`detalles[${index}][equipo_activo]`" value="0">
                    @endif
                    <input type="checkbox" :name="`detalles[${index}][equipo_activo]`" value="1"
                           x-model="item.equipo_activo"
                           @disabled(!empty($abastecimiento))
                           class="rounded border-slate-300 text-[#0B265A] focus:ring-[#0B265A]">
                    {{ !empty($abastecimiento) ? 'Equipo de la solicitud' : 'Identificar equipo' }}
                  </label>
                  <div x-show="item.equipo_activo" x-cloak class="mt-2 space-y-2">
                    <select :name="`detalles[${index}][equipo_tipo]`" x-model="item.equipo_tipo" @change="cambiarTipoEquipo(item)"
                            @disabled(!empty($abastecimiento))
                            class="w-full rounded-lg border border-slate-300 bg-white text-xs focus:border-[#0B265A] focus:ring-[#0B265A]">
                      <option value="">Tipo de equipo</option>
                      <option value="vehiculo">Vehículo</option>
                      <option value="maquina">Máquina</option>
                    </select>
                    <select x-show="item.equipo_tipo === 'vehiculo'" :name="`detalles[${index}][vehiculo_id]`"
                            x-model="item.vehiculo_id"
                            @disabled(!empty($abastecimiento))
                            class="w-full rounded-lg border border-slate-300 bg-white text-xs focus:border-[#0B265A] focus:ring-[#0B265A]">
                      <option value="">Selecciona vehículo</option>
                      @foreach(($vehiculos ?? []) as $vehiculo)
                        <option value="{{ $vehiculo->id }}">{{ trim(($vehiculo->marca ?? '').' '.($vehiculo->modelo ?? '').' '.($vehiculo->placas ?? '')) ?: 'Vehículo #'.$vehiculo->id }}</option>
                      @endforeach
                    </select>
                    <select x-show="item.equipo_tipo === 'maquina'" :name="`detalles[${index}][maquina_id]`"
                            x-model="item.maquina_id"
                            @disabled(!empty($abastecimiento))
                            class="w-full rounded-lg border border-slate-300 bg-white text-xs focus:border-[#0B265A] focus:ring-[#0B265A]">
                      <option value="">Selecciona máquina</option>
                      @foreach(($maquinas ?? []) as $maquina)
                        <option value="{{ $maquina->id }}">{{ trim(($maquina->codigo ?? '').' - '.($maquina->nombre ?? '').' '.($maquina->tipo ?? '')) ?: 'Máquina #'.$maquina->id }}</option>
                      @endforeach
                    </select>
                  </div>
                </td>
              @endif
              @if(in_array($tipo, ['entrada','ajuste'], true))
                <td class="py-3 pr-3">
                  <input type="number" step="0.0001" min="0" required
                         :name="`detalles[${index}][costo_unitario]`"
                         x-model="item.costo_unitario"
                         class="w-full rounded-lg border border-slate-300 bg-white text-sm focus:border-[#0B265A] focus:outline-none focus:ring-2 focus:ring-blue-100">
                </td>
              @else
                <input type="hidden" :name="`detalles[${index}][costo_unitario]`" :value="item.costo_unitario || 0">
              @endif
              <td class="py-3 pr-3">
                <input type="text" :name="`detalles[${index}][notas]`"
                       x-model="item.notas"
                       class="w-full rounded-lg border border-slate-300 bg-white text-sm focus:border-[#0B265A] focus:outline-none focus:ring-2 focus:ring-blue-100">
              </td>
              <td class="py-3 text-right">
                <button type="button" @click="removeRow(index)" class="text-red-400 hover:text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
              </td>
            </tr>
        </template>
        <tr x-show="partidas.length === 0">
            <td colspan="{{ $tipo === 'salida' ? 7 : 5 }}" class="py-8 text-center text-slate-400 italic">
                No hay productos agregados. Utiliza el buscador de arriba.
            </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
    {{-- Acciones --}}
    <div class="flex items-center justify-end gap-3">
      <button type="submit" name="action" value="draft"
              class="px-5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm">
        Guardar borrador
      </button>

      <button type="submit" name="action" value="apply"
              class="px-5 py-2 rounded-xl bg-[#0B265A] text-white text-sm hover:opacity-95">
        Guardar y aplicar
      </button>
    </div>

  </form>
</div>

{{-- JS minimo para filas --}}
<script>

/**
 * Logica para el selector de Orden de Compra (Vanilla JS)
 */
document.addEventListener('DOMContentLoaded', () => {
    const ordenCompraSelect = document.getElementById('orden_compra_id');
    if (ordenCompraSelect) {
        ordenCompraSelect.addEventListener('change', function () {
            console.log("Orden seleccionada:", this.value);
        });
    }

    const solicitudAlmacenSelect = document.getElementById('solicitudAlmacenSelect');
    if (solicitudAlmacenSelect) {
        solicitudAlmacenSelect.addEventListener('change', function () {
            if (!this.value) return;

            const url = new URL(@json(route('inventario.documentos.create', ['tipo' => 'salida'])));
            url.searchParams.set('mantenimiento_abastecimiento_id', this.value);
            window.location.href = url.toString();
        });
    }
});

/**
 * Componente para la busqueda de Proveedores
 */
function buscadorProveedor() {
    return {
        search: '',
        selectedId: '',
        results: [],
        loading: false,
        async buscar() {
            if (this.search.length < 3) {
                this.results = [];
                return;
            }
            this.loading = true;
            try {
                const res = await fetch(`{{ route('inventario.documentos.buscar-proveedor') }}?q=${this.search}`);
                this.results = await res.json();
            } catch (e) {
                console.error("Error buscando proveedores");
            } finally {
                this.loading = false;
            }
        },
        select(p) {
            this.selectedId = p.id;
            this.search = p.nombre;
            this.results = [];
        }
    }
}

/**
 * Componente principal de Partidas (Reemplaza toda la logica manual anterior)
 */
function documentoPartidas() {
    return {
        search: '',
        results: [],
        selectedProduct: null,
        partidas: @json($partidasIniciales ?? []), // Este array controla la tabla automaticamente
        tipoDoc: @json($tipo), // 'entrada', 'salida', 'ajuste'
        ajusteTipo: 'incremento', // Vinculado a x-model del select de ajuste
        ocSearch: '',
        ocResults: [],
        selectedOrders: [],
        ocLoading: false,
        cajaSearch: '',
        cajaResults: [],
        selectedCajaChica: null,
        cajaLoading: false,
        stockPorAlmacen: @json($stockPorAlmacen ?? []),

        init() {
            this.actualizarDisponibles(this.getAlmacenId());

            const almacenSelect = document.querySelector('[name="almacen_id"]');
            if (almacenSelect) {
                almacenSelect.addEventListener('change', () => {
                    this.actualizarDisponibles(almacenSelect.value);
                });
            }
        },

        actualizarDisponibles(almacenId) {
            const stock = this.stockPorAlmacen[String(almacenId)] || {};

            this.partidas.forEach((item) => {
                item.disponible = almacenId ? Number(stock[String(item.producto_id)] ?? 0) : null;
            });
        },

        cambiarTipoEquipo(item) {
            if (item.equipo_tipo === 'vehiculo') item.maquina_id = '';
            if (item.equipo_tipo === 'maquina') item.vehiculo_id = '';
            if (!item.equipo_tipo) {
                item.vehiculo_id = '';
                item.maquina_id = '';
            }
        },

        async buscarOrdenesCompra() {
            if (this.ocSearch.length < 2) {
                this.ocResults = [];
                return;
            }

            this.ocLoading = true;
            try {
                const response = await fetch(`{{ route('inventario.documentos.buscar-ordenes-compra') }}?q=${encodeURIComponent(this.ocSearch)}`);
                this.ocResults = await response.json();
            } catch (e) {
                console.error('Error al buscar ordenes de compra', e);
                this.ocResults = [];
            } finally {
                this.ocLoading = false;
            }
        },

        async buscarGastosCajaChica() {
            if (this.cajaSearch.length < 2) {
                this.cajaResults = [];
                return;
            }

            this.cajaLoading = true;
            try {
                const params = new URLSearchParams({ q: this.cajaSearch });
                const almacenId = this.getAlmacenId();
                if (almacenId) params.set('almacen_id', almacenId);

                const response = await fetch(`{{ route('inventario.documentos.buscar-gastos-caja-chica') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                });
                this.cajaResults = response.ok ? await response.json() : [];
            } catch (e) {
                console.error('Error al buscar gastos de caja chica', e);
                this.cajaResults = [];
            } finally {
                this.cajaLoading = false;
            }
        },

        selectGastoCajaChica(gasto) {
            this.selectedCajaChica = gasto;
            this.cajaResults = [];
            this.cajaSearch = '';
        },

        async selectOrdenCompra(order) {
            if (!order || !order.id) return;

            const exists = this.selectedOrders.some(item => item.id === order.id);
            if (exists) {
                this.ocResults = [];
                this.ocSearch = '';
                return;
            }

            this.ocLoading = true;

            try {
                const response = await fetch(`{{ route('inventario.documentos.orden-compra-detalles', ['ordenCompra' => ':id']) }}`.replace(':id', order.id));

                if (!response.ok) {
                    console.error('La orden de compra no esta autorizada o no se pudo consultar.');
                    return;
                }

                const data = await response.json();

                this.selectedOrders.push({
                    id: data.id,
                    folio: data.folio,
                    proveedor: data.proveedor,
                    estado: data.estado,
                });

                this.ocResults = [];
                this.ocSearch = '';

                (data.detalles || []).forEach((detalle) => {
                    const exists = this.partidas.some((item) => item.orden_compra_detalle_id && item.orden_compra_detalle_id === detalle.orden_compra_detalle_id);
                    if (exists) return;

                    const unidad = detalle.unidad ? ` (${detalle.unidad})` : '';

                    this.partidas.push({
                        producto_id: detalle.producto_id,
                        requiere_vinculo_producto: detalle.requiere_vinculo_producto || false,
                        nombre: `${detalle.nombre}${unidad}`,
                        cantidad: detalle.cantidad ?? detalle.cantidad_pendiente ?? 1,
                        orden_compra_id: detalle.orden_compra_id,
                        orden_compra_detalle_id: detalle.orden_compra_detalle_id,
                        costo_unitario: detalle.costo_unitario ?? 0,
                        notas: detalle.notas || '',
                        equipo_activo: false,
                        equipo_tipo: '',
                        vehiculo_id: '',
                        maquina_id: '',
                        disponible: null,
                    });
                });
            } catch (e) {
                console.error('Error al cargar detalles de la orden de compra', e);
            } finally {
                this.ocLoading = false;
            }
        },

        removeOrder(orderId) {
            this.partidas = this.partidas.filter(item => item.orden_compra_id !== orderId);
            this.selectedOrders = this.selectedOrders.filter(item => item.id !== orderId);
        },

        async buscar() {
            if (this.search.length < 3) {
                this.results = [];
                return;
            }
            try {
                const params = new URLSearchParams({ q: this.search });
                const almacenId = this.getAlmacenId();

                if (almacenId) {
                    params.set('almacen_id', almacenId);
                }

                const response = await fetch(`{{ route('inventario.documentos.buscar-producto') }}?${params.toString()}`);
                this.results = await response.json();
            } catch (e) {
                console.error("Error al buscar productos");
            }
        },

        preselect(producto) {
            this.selectedProduct = producto;
            this.search = producto.nombre;
            this.results = [];
            this.addRow(); // Agrega la fila al seleccionar
        },

        addRow() {
            if (!this.selectedProduct) return;

            const tempId = 'qty-' + Date.now();

            // Al anadir al array, Alpine renderiza el <tr> automaticamente
            this.partidas.push({
                producto_id: this.selectedProduct.id,
                nombre: this.selectedProduct.nombre,
                cantidad: 1,
                costo_unitario: this.defaultCostoUnitario(),
                notas: '',
                equipo_activo: false,
                equipo_tipo: '',
                vehiculo_id: '',
                maquina_id: '',
                disponible: null,
                refId: tempId
            });

            // Limpiar buscador
            this.selectedProduct = null;
            this.search = '';

            // Hacer foco en el nuevo input de cantidad
            this.$nextTick(() => {
                const input = document.getElementById(tempId);
                if (input) {
                    input.focus();
                    input.select();
                }
            });
        },

        removeRow(index) {
            // No es necesario buscar el boton ni el .closest('tr')
            this.partidas.splice(index, 1);
        },

        defaultCostoUnitario() {
            if (this.isCostoReferencia) {
                return Number(this.selectedProduct?.costo_promedio ?? 0).toFixed(4);
            }

            return 0;
        },

        getAlmacenId() {
            return document.querySelector('[name="almacen_id"]')?.value || '';
        },

        formatMoney(value) {
            return Number(value ?? 0).toFixed(4);
        },

        formatNumber(value) {
            return Number(value ?? 0).toFixed(3);
        },

        // Determina si el costo es obligatorio (reemplaza applyCostRules)
        get isCostRequired() {
            if (this.tipoDoc === 'entrada') return true;
            if (this.tipoDoc === 'ajuste' && this.ajusteTipo === 'incremento') return true;
            return false;
        },

        get isCostoReferencia() {
            return ['salida', 'resguardo'].includes(this.tipoDoc);
        }
    }
}
</script>
@endsection

