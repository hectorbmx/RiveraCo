@extends('layouts.admin')

@section('title', 'CFDIs descargados')

@section('content')
<!-- <div x-data="cfdiModal()" x-cloak> -->
    <div x-data="{ ...cfdiModal(), ...pagoModal() }" x-cloak>
<div class="max-w-8xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Visor SAT</h1>
        <p class="text-sm text-gray-600 mt-1">
            CFDIs descargados y almacenados en la tabla <span class="font-medium">sat_cfdis</span>.
        </p>
    </div>

    {{-- Selector de empresa --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <form method="GET" action="{{ route('sat.cfdis.index') }}" id="empresaFilterForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Empresa SAT</label>
                <select name="sat_empresa_id"
                        id="sat_empresa_id"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        onchange="document.getElementById('empresaFilterForm').submit()">
                    <option value="">Selecciona una empresa para cargar CFDIs</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" @selected(request('sat_empresa_id') == $empresa->id)>
                            {{ $empresa->nombre }} — {{ $empresa->rfc }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($empresaSeleccionada)
                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                    <span class="font-medium text-gray-900">Empresa seleccionada:</span>
                    <span>{{ $empresaSeleccionada->nombre }}</span>
                    <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                        {{ $empresaSeleccionada->rfc }}
                    </span>
                </div>
            @endif
        </form>
    </div>

    @if(!$empresaSeleccionada)
        <div class="bg-white rounded-2xl border border-dashed border-gray-300 shadow-sm p-10 text-center">
            <div class="text-lg font-semibold text-gray-900">Selecciona una empresa SAT</div>
            <p class="text-sm text-gray-500 mt-2">
                Primero elige una empresa para cargar el resumen y el detalle de CFDIs.
            </p>
        </div>
    @else

        {{-- Filtros adicionales --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
            <form method="GET" action="{{ route('sat.cfdis.index') }}" class="flex flex-col gap-4">
                <input type="hidden" name="sat_empresa_id" value="{{ $empresaSeleccionada->id }}">

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">UUID</label>
                        <input type="text"
                               name="uuid"
                               value="{{ request('uuid') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Buscar UUID">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">RFC emisor</label>
                        <input type="text"
                               name="rfc_emisor"
                               value="{{ request('rfc_emisor') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="RFC emisor">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Razón social emisor</label>
                        <input type="text"
                               name="emisor_nombre"
                               value="{{ request('emisor_nombre') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Nombre del emisor">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">RFC receptor</label>
                        <input type="text"
                               name="rfc_receptor"
                               value="{{ request('rfc_receptor') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="RFC receptor">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Razón social receptor</label>
                        <input type="text"
                               name="receptor_nombre"
                               value="{{ request('receptor_nombre') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Nombre del receptor">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
                        <select name="tipo_comprobante"
                                class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="I" @selected(request('tipo_comprobante') === 'I')>Ingresos</option>
                            <option value="E" @selected(request('tipo_comprobante') === 'E')>Egresos</option>
                            <option value="P" @selected(request('tipo_comprobante') === 'P')>Pagos</option>
                            <option value="N" @selected(request('tipo_comprobante') === 'N')>Nóminas</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Fecha inicio</label>
                        <input type="date"
                               name="fecha_inicio"
                               value="{{ request('fecha_inicio') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Fecha fin</label>
                        <input type="date"
                               name="fecha_fin"
                               value="{{ request('fecha_fin') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Filtrar
                        </button>

                        <a href="{{ route('sat.cfdis.index', ['sat_empresa_id' => $empresaSeleccionada->id]) }}"
                           class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Limpiar
                        </a>
                    </div>

                    <div class="text-sm text-gray-500">
                        Total de la empresa:
                        <span class="font-semibold text-gray-900">{{ number_format($totalGeneral) }}</span>
                    </div>
                </div>
            </form>
        </div>

        {{-- Resúmenes --}}
        <!-- <div class="grid grid-cols-1 lg:grid-cols-3 gap-4"> -->
            <div x-data="{ openResumenes: true }">
                <div x-show="openResumenes" x-transition>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            <div class="rounded-2xl border border-indigo-300 bg-indigo-50 shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Ingresos</div>
                        <div class="text-sm text-gray-500 mt-1">CFDIs tipo I</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600 text-lg">
                        ⬈
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Registros</span>
                        <span class="font-semibold text-gray-900">{{ number_format($resumenIngresos) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-900">${{ number_format((float) $subtotalIngresos, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Egresos</div>
                        <div class="text-sm text-gray-500 mt-1">CFDIs tipo E</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-600 text-lg">
                        ⬋
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Registros</span>
                        <span class="font-semibold text-gray-900">{{ number_format($resumenEgresos) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-900">${{ number_format((float) $subtotalEgresos, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Pagos</div>
                        <div class="text-sm text-gray-500 mt-1">CFDIs tipo P</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-600 text-lg">
                        💳
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Registros</span>
                        <span class="font-semibold text-gray-900">{{ number_format($resumenPagos) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-900">${{ number_format((float) $subtotalPagos, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-amber-300 bg-amber-50 shadow-sm p-5">
    <div class="flex items-start justify-between">
        <div>
            <div class="text-lg font-semibold text-gray-900">Nóminas</div>
            <div class="text-sm text-gray-500 mt-1">CFDIs tipo N</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 text-lg">
            👥
        </div>
    </div>

    <div class="mt-5 space-y-3">
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Registros</span>
            <span class="font-semibold text-gray-900">{{ number_format($resumenNominas) }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Subtotal</span>
            <span class="font-semibold text-gray-900">${{ number_format((float) $subtotalNominas, 2) }}</span>
        </div>
    </div>
</div>
        </div>
</div><div class="flex justify-between items-center mb-3">
    <h3 class="text-lg font-semibold text-gray-800">Resúmenes</h3>

    <button 
        @click="openResumenes = !openResumenes"
        class="text-sm text-blue-600 hover:text-blue-800 font-medium"
    >
        <span x-show="openResumenes">Ocultar</span>
        <span x-show="!openResumenes">Mostrar</span>
    </button>
</div>  
</div>
<!-- terminan resumenes  -->
 

        {{-- Encabezado de detalle --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">Detalle de CFDIs descargados</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Total de registros de la empresa antes de filtros:
                    <span class="font-semibold text-gray-900">{{ number_format($totalGeneral) }}</span>
                </p>
            </div>

            <div class="flex flex-col items-start gap-2 text-sm text-gray-600 lg:items-end">
                <div>
                    Resultado actual:
                    <span class="font-semibold text-gray-900">{{ number_format($totalFiltrado) }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" action="{{ route('sat.cfdis.index') }}" class="flex items-center gap-2">
                        @foreach(request()->except('per_page', 'page') as $key => $value)
                            @if(is_array($value))
                                @foreach($value as $item)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        <label for="per_page" class="text-xs font-medium text-gray-500">Mostrar</label>
                        <select id="per_page" name="per_page"
                                class="rounded-lg border-gray-300 py-1.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                onchange="this.form.submit()">
                            @foreach($perPageOpciones as $opcion)
                                <option value="{{ $opcion }}" @selected((int) $perPage === (int) $opcion)>{{ $opcion }}</option>
                            @endforeach
                        </select>
                    </form>

                    <a href="{{ route('sat.cfdis.export', request()->query()) }}"
                       class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                        Exportar Excel
                    </a>
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">UUID</th>
                            <th class="px-4 py-3 text-left font-medium">Fecha de expedición</th>
                            <th class="px-4 py-3 text-left font-medium">Tipo</th>
                            <th class="px-4 py-3 text-left font-medium">RFC emisor</th>
                            <th class="px-4 py-3 text-left font-medium">RFC receptor</th>
                            <th class="px-4 py-3 text-left font-medium">Moneda</th>
                            <th class="px-4 py-3 text-left font-medium">Total MXN</th>
                            <th class="px-4 py-3 text-left font-medium">Obra</th>
                            <th class="px-4 py-3 text-left font-medium">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($cfdis as $cfdi)
                                                        @php
                                $estadoPago = $cfdi->estadoPago();

                                $rowPagoClass = match($estadoPago) {
                                    'pagada' => 'bg-emerald-50',
                                    'parcial' => 'bg-amber-50',
                                    default => 'bg-white',
                                };
                            @endphp

                            <tr class="{{ $rowPagoClass }}">
                                
                                <td class="px-4 py-3">
                                    <span class="inline-flex max-w-[260px] truncate rounded-lg border border-indigo-300 bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700"
                                          title="{{ $cfdi->uuid }}">
                                        {{ $cfdi->uuid }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ optional($cfdi->fecha_emision)->format('d/m/Y H:i') }}
                                </td>

                                <td class="px-4 py-3">
                                    @if($cfdi->tipo_comprobante === 'I')
                                        <span class="inline-flex rounded-lg bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 border border-green-200">
                                            Ingreso
                                        </span>
                                    @elseif($cfdi->tipo_comprobante === 'E')
                                        <span class="inline-flex rounded-lg bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 border border-red-200">
                                            Egreso
                                        </span>
                                    @elseif($cfdi->tipo_comprobante === 'P')
                                        <span class="inline-flex rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 border border-blue-200">
                                            Pago
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-lg bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                                            {{ $cfdi->tipo_comprobante }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    @if($cfdi->rfc_emisor)
                                        <a href="{{ route('sat.cfdis.emisor', ['rfc' => $cfdi->rfc_emisor, 'sat_empresa_id' => $empresaSeleccionada?->id]) }}"
                                           class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                                            {{ $cfdi->rfc_emisor }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $cfdi->rfc_receptor }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $cfdi->moneda }}
                                </td>

                                <td class="px-4 py-3 font-medium text-gray-900">
                                    ${{ number_format((float) $cfdi->total, 2) }}
                                </td>
                               <td class="px-4 py-3">
                                    @if($cfdi->obra)
                                        <span class="inline-flex max-w-[220px] truncate rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 border border-emerald-200"
                                            title="{{ $cfdi->obra->nombre ?? $cfdi->obra->Nombre ?? 'Obra #' . $cfdi->obra->id }}">
                                            Obra: {{ $cfdi->obra->nombre ?? $cfdi->obra->Nombre ?? 'Obra #' . $cfdi->obra->id }}
                                        </span>

                                    @elseif($cfdi->ordenCompra)
                                        <span class="inline-flex max-w-[220px] truncate rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 border border-blue-200"
                                            title="{{ $cfdi->ordenCompra->folio ?? 'OC #' . $cfdi->ordenCompra->id }}">
                                            OC: {{ $cfdi->ordenCompra->folio ?? 'OC #' . $cfdi->ordenCompra->id }}
                                        </span>

                                    @else
                                        @if($cfdi->rfc_emisor === 'RCO820921T66')
                                            <span class="inline-flex rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 border border-amber-200">
                                                Sin obra
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 border border-amber-200">
                                                Sin OC
                                            </span>
                                        @endif
                                    @endif
                                </td>
                              <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">

                                        {{-- Ver detalle --}}
                                        <button type="button"
                                            class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 border border-blue-200 hover:bg-blue-100"
                                            @click="openCfdiModal('{{ route('sat.cfdis.detalle', $cfdi->id) }}')">
                                            Ver
                                        </button>
                                        {{-- Pagar --}}
                                            @if($cfdi->tipo_comprobante === 'I' && !$cfdi->estaPagada())
                                                <button type="button"
                                                    class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 border border-indigo-200 hover:bg-indigo-100"
                                                    @click="openPagoModal({
                                                        action: '{{ route('sat.cfdis.pagos.store', $cfdi->id) }}',
                                                        id: {{ $cfdi->id }},
                                                        uuid: '{{ $cfdi->uuid }}',
                                                        total: {{ (float) $cfdi->total }},
                                                        pagado: {{ (float) $cfdi->totalPagado() }},
                                                        saldo: {{ (float) $cfdi->saldoPendiente() }},
                                                        metodo: '{{ $cfdi->metodo_pago }}'
                                                    })">
                                                    Pagar
                                                </button>
                                            @endif
@if($cfdi->estadoPago() === 'pagada')
    <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 border border-emerald-200">
        Pagada
    </span>
@elseif($cfdi->estadoPago() === 'parcial')
    <span class="inline-flex items-center rounded-lg bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 border border-amber-200">
        Parcial
    </span>
@endif
@if($cfdi->totalPagado() > 0)
    <button type="button"
        class="inline-flex items-center rounded-lg bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 border border-slate-200 hover:bg-slate-100"
        @click="console.log('click ver pagos'); openVerPagosModal({

            uuid: '{{ $cfdi->uuid }}',
            total: {{ (float) $cfdi->total }},
            pagado: {{ (float) $cfdi->totalPagado() }},
            saldo: {{ (float) $cfdi->saldoPendiente() }},
            pagos: @js($cfdi->pagos()->where('estatus', 'activo')->latest()->get())
        })">
        Ver pagos
    </button>
@endif
                                        {{-- Relacionar / Cambiar obra --}}
                                        <!-- <button type="button"
                                            class="inline-flex items-center rounded-lg px-3 py-1 text-xs font-medium border
                                                {{ $cfdi->obra_id 
                                                    ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' 
                                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' }}"
                                            @click="openRelacionarModal(
                                                '{{ route('sat.cfdis.relacionar', $cfdi->id) }}',
                                                '{{ $cfdi->uuid }}',
                                                '{{ $cfdi->obra_id ?? '' }}'
                                            )">
                                            {{ $cfdi->obra_id ? 'Cambiar obra' : 'Relacionar' }}
                                        </button> -->
                                        <button type="button"
                                            class="inline-flex items-center rounded-lg px-3 py-1 text-xs font-medium border
                                                {{ $cfdi->obra_id || $cfdi->orden_compra_id
                                                    ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' }}"
                                            @click="openRelacionarModal(
                                                '{{ route('sat.cfdis.relacionar', $cfdi->id) }}',
                                                '{{ $cfdi->uuid }}',
                                                '{{ $cfdi->rfc_emisor }}',
                                                '{{ $cfdi->obra_id ?? '' }}',
                                                '{{ $cfdi->orden_compra_id ?? '' }}'
                                            )">
                                            {{ ($cfdi->obra_id || $cfdi->orden_compra_id) ? 'Cambiar relación' : 'Relacionar' }}
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                    No hay CFDIs registrados para esta empresa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($cfdis instanceof \Illuminate\Contracts\Pagination\Paginator)
                <div class="px-4 py-4 border-t border-gray-200">
                    {{ $cfdis->links() }}
                </div>
            @endif
        </div>

    @endif

</div>

<!-- Modal CFDI -->
<div x-show="open"
     x-transition
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">

    <div class="bg-gray-50 w-full max-w-4xl rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 bg-blue-600 text-white">
            <h2 class="text-lg font-semibold text-white">
                Detalle CFDI
            </h2>

            <button @click="close()"
                    class="text-white/80 hover:text-white">
                ✕
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 max-h-[70vh] overflow-y-auto bg-white">

            <!-- Loader -->
            <div x-show="loading" class="text-center text-gray-500 py-10">
                Cargando...
            </div>

            <!-- Contenido -->
            <div x-show="!loading && data" class="space-y-5">

                <!-- Datos generales -->
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Datos generales
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="md:col-span-2">
                            <span class="text-gray-500">UUID</span>
                            <div class="font-medium break-all" x-text="data?.uuid || '-'"></div>
                        </div>

                        <div>
                            <span class="text-gray-500">Fecha</span>
                            <div class="font-medium" x-text="data?.fecha_emision || '-'"></div>
                        </div>

                        <div>
                            <span class="text-gray-500">Serie / Folio</span>
                            <div class="font-medium">
                                <span x-text="data?.serie || '-'"></span>
                                <span x-text="data?.folio || ''"></span>
                            </div>
                        </div>

                        <div>
                            <span class="text-gray-500">Tipo CFDI</span>
                            <div class="font-medium" x-text="data?.tipo_comprobante || '-'"></div>
                        </div>

                        <div>
                            <span class="text-gray-500">Total</span>
                            <div class="font-semibold text-green-700" x-text="data?.total || '-'"></div>
                        </div>
                    </div>
                </div>

                <!-- Pago -->
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Pago y expedición
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Forma de pago</span>
                            <div class="font-medium" x-text="data?.forma_pago || '-'"></div>
                        </div>

                        <div>
                            <span class="text-gray-500">Método de pago</span>
                            <div class="font-medium" x-text="data?.metodo_pago || '-'"></div>
                        </div>

                        <div>
                            <span class="text-gray-500">Lugar expedición</span>
                            <div class="font-medium" x-text="data?.lugar_expedicion || '-'"></div>
                        </div>
                    </div>
                </div>

                <!-- Emisor / Receptor -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Emisor -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">
                            Emisor
                        </h3>

                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-gray-500">RFC</span>
                                <div class="font-medium" x-text="data?.rfc_emisor || data?.emisor_rfc || '-'"></div>
                            </div>

                            <div>
                                <span class="text-gray-500">Nombre</span>
                                <div class="font-medium" x-text="data?.emisor_nombre || '-'"></div>
                            </div>

                            <div>
                                <span class="text-gray-500">Régimen fiscal</span>
                                <div class="font-medium" x-text="data?.emisor_regimen_fiscal || '-'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Receptor -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">
                            Receptor
                        </h3>

                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-gray-500">RFC</span>
                                <div class="font-medium" x-text="data?.rfc_receptor || data?.receptor_rfc || '-'"></div>
                            </div>

                            <div>
                                <span class="text-gray-500">Nombre</span>
                                <div class="font-medium" x-text="data?.receptor_nombre || '-'"></div>
                            </div>

                            <div>
                                <span class="text-gray-500">Uso CFDI</span>
                                <div class="font-medium" x-text="data?.receptor_uso_cfdi || '-'"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Conceptos -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Conceptos
                    </h3>

                    <div class="overflow-x-auto border rounded-xl">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left">Clave</th>
                                    <th class="px-3 py-2 text-left">Descripción</th>
                                    <th class="px-3 py-2 text-left">Cantidad</th>
                                    <th class="px-3 py-2 text-left">Unidad</th>
                                    <th class="px-3 py-2 text-left">Precio</th>
                                    <th class="px-3 py-2 text-left">Importe</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-for="c in (data?.conceptos || [])" :key="c.id">
                                    <tr class="border-t">
                                        <td class="px-3 py-2" x-text="c.clave_prod_serv"></td>
                                        <td class="px-3 py-2" x-text="c.descripcion"></td>
                                        <td class="px-3 py-2" x-text="c.cantidad"></td>
                                        <td class="px-3 py-2" x-text="c.unidad"></td>
                                        <td class="px-3 py-2" x-text="c.valor_unitario"></td>
                                        <td class="px-3 py-2" x-text="c.importe"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
      <!-- Footer -->
<div class="px-6 py-3 border-t flex justify-between">

    <div class="space-x-2">

        <a
            :href="`/sat/cfdis/${data?.id}/xml`"
            target="_blank"
            class="px-4 py-2 text-sm rounded-lg border border-blue-300 text-blue-700 hover:bg-blue-50">
            XML
        </a>

        <a
            :href="`/sat/cfdis/${data?.id}/pdf`"
            target="_blank"
            class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700">
            Generar PDF
        </a>

    </div>

    <button @click="close()"
            class="px-4 py-2 text-sm rounded-lg border hover:bg-gray-50">
        Cerrar
    </button>

</div>

    </div>
    
</div>
<!-- {{-- Modal relacionar CFDI con obra --}}
<div x-show="showRelacionarModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">

    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">

        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    Relacionar CFDI a obra
                </h3>
                <p class="mt-1 text-xs text-gray-500">
                    UUID:
                    <span class="font-mono" x-text="relacionCfdiUuid"></span>
                </p>
            </div>

            <button type="button"
                    class="text-gray-400 hover:text-gray-600"
                    @click="closeRelacionarModal()">
                ✕
            </button>
        </div>

        <form :action="relacionFormAction" method="POST" class="px-6 py-5">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    Obra
                </label>

                <select name="obra_id"
                        x-model="relacionObraId"
                        required
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Selecciona una obra</option>

                    @foreach ($obras as $obra)
                        <option value="{{ $obra->id }}">
                            {{ $obra->nombre ?? $obra->Nombre ?? 'Obra #' . $obra->id }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        @click="closeRelacionarModal()">
                    Cancelar
                </button>

                <button type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Relacionar
                </button>
            </div>
        </form>
    </div>


</div> -->

{{-- Modal relacionar CFDI --}}
<div x-show="showRelacionarModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">

    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">

        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900"
                    x-text="relacionTipo === 'obra' ? 'Relacionar CFDI a obra' : 'Relacionar CFDI a orden de compra'">
                </h3>

                <p class="mt-1 text-xs text-gray-500">
                    UUID:
                    <span class="font-mono" x-text="relacionCfdiUuid"></span>
                </p>
            </div>

            <button type="button"
                    class="text-gray-400 hover:text-gray-600"
                    @click="closeRelacionarModal()">
                ✕
            </button>
        </div>

        <form :action="relacionFormAction" method="POST" class="px-6 py-5">
            @csrf

            {{-- SELECT OBRA --}}
            <div x-show="relacionTipo === 'obra'">
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    Obra
                </label>

                <select name="obra_id"
                        x-model="relacionObraId"
                        :required="relacionTipo === 'obra'"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Selecciona una obra</option>

                    @foreach ($obras as $obra)
                        <option value="{{ $obra->id }}">
                            {{ $obra->nombre ?? $obra->Nombre ?? 'Obra #' . $obra->id }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- SELECT ORDEN COMPRA --}}
            <div x-show="relacionTipo === 'orden_compra'">
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    Orden de compra
                </label>

                <select name="orden_compra_id"
                        x-model="relacionOrdenCompraId"
                        :required="relacionTipo === 'orden_compra'"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Selecciona una orden de compra</option>

                    @foreach ($ordenesCompra as $oc)
                        <option value="{{ $oc->id }}">
                            {{ $oc->folio ?? 'OC #' . $oc->id }}
                            — {{ $oc->proveedor->nombre ?? 'Sin proveedor' }}
                            — {{ ucfirst($oc->estado ?? 'sin estado') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        @click="closeRelacionarModal()">
                    Cancelar
                </button>

                <button type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Relacionar
                </button>
            </div>
        </form>
    </div>
</div>
    {{-- MODAL PAGO --}}
<div x-show="pagoModalOpen"
     x-transition 
     class="fixed inset-0  z-[9999] flex items-center justify-center bg-black/50">

    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">

        <h2 class="text-lg font-semibold mb-4">
    <span x-text="pagoForm.tipo === 'cobro' ? 'Registrar Cobro' : 'Registrar Pago'"></span>
</h2>

        {{-- Info CFDI --}}
        <div class="text-sm mb-4 space-y-1">
            <div><strong>UUID:</strong> <span x-text="pagoForm.uuid"></span></div>
            <div><strong>Total:</strong> $<span x-text="pagoForm.total.toFixed(2)"></span></div>
            <div><strong>Pagado:</strong> $<span x-text="pagoForm.pagado.toFixed(2)"></span></div>
            <div><strong>Saldo:</strong> $<span x-text="pagoForm.saldo.toFixed(2)"></span></div>
            <div><strong>Método:</strong> <span x-text="pagoForm.metodo"></span></div>
        </div>

        <!-- <form method="POST" 

              :action="`/sat/cfdis/${pagoForm.cfdi_id}/pagos`"
              enctype="multipart/form-data"> -->
              <form method="POST"
      :action="pagoForm.action"
      enctype="multipart/form-data">

            @csrf

            <div class="space-y-3">

                <input type="date" name="fecha_pago" x-model="pagoForm.fecha_pago"
                    class="w-full border rounded-lg p-2 text-sm" required>

                <input type="number" step="0.01" name="monto" x-model="pagoForm.monto"
                    class="w-full border rounded-lg p-2 text-sm"
                    :max="pagoForm.saldo"
                    placeholder="Monto" required>

                <select name="metodo_pago" x-model="pagoForm.metodo_pago"
                    class="w-full border rounded-lg p-2 text-sm">
                    <option value="">Método</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="cheque">Cheque</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>

                <input type="text" name="referencia" x-model="pagoForm.referencia"
                    class="w-full border rounded-lg p-2 text-sm"
                    placeholder="Referencia / folio">

                <input type="file" name="comprobante"
                    class="w-full text-sm">

            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button type="button"
                    @click="pagoModalOpen = false"
                    class="px-4 py-2 text-sm bg-gray-100 rounded-lg">
                    Cancelar
                </button>

                <button type="submit"
                    class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>
{{-- MODAL VER PAGOS --}}
<div x-show="verPagosModalOpen"
     x-transition
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     ">

    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6">

        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Pagos registrados</h2>
                <p class="text-xs text-gray-500 mt-1">
                    UUID: <span x-text="verPagosData.uuid"></span>
                </p>
            </div>

            <button type="button"
                @click="closeVerPagosModal()"
                class="text-gray-400 hover:text-gray-700">
                ✕
            </button>
        </div>

        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="rounded-lg bg-gray-50 border p-3">
                <div class="text-xs text-gray-500">Total</div>
                <div class="font-semibold">$<span x-text="money(verPagosData.total)"></span></div>
            </div>

            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3">
                <div class="text-xs text-emerald-600">Pagado</div>
                <div class="font-semibold text-emerald-700">$<span x-text="money(verPagosData.pagado)"></span></div>
            </div>

            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3">
                <div class="text-xs text-amber-600">Saldo</div>
                <div class="font-semibold text-amber-700">$<span x-text="money(verPagosData.saldo)"></span></div>
            </div>
        </div>

        <div class="border rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">Fecha</th>
                        <th class="px-3 py-2 text-left">Método</th>
                        <th class="px-3 py-2 text-left">Referencia</th>
                        <th class="px-3 py-2 text-right">Monto</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    <template x-for="pago in verPagosData.pagos" :key="pago.id">
                        <tr>
                            <td class="px-3 py-2" x-text="pago.fecha_pago"></td>
                            <td class="px-3 py-2" x-text="pago.metodo_pago ?? '-'"></td>
                            <td class="px-3 py-2" x-text="pago.referencia ?? pago.folio_transferencia ?? pago.numero_cheque ?? '-'"></td>
                            <td class="px-3 py-2 text-right font-semibold">
                                $<span x-text="money(pago.monto)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-5">
            <button type="button"
                @click="closeVerPagosModal()"
                class="px-4 py-2 text-sm bg-gray-100 rounded-lg hover:bg-gray-200">
                Cerrar
            </button>
        </div>
    </div>
</div>
    <script>
function cfdiModal() {
    return {
        open: false,
        loading: false,
        data: null,

        showRelacionarModal: false,
        relacionFormAction: '',
        relacionCfdiUuid: '',
        relacionRfcEmisor: '',
        relacionTipo: 'obra',
        relacionObraId: '',
        relacionOrdenCompraId: '',

        async openCfdiModal(url) {
            this.open = true;
            this.loading = true;
            this.data = null;

            try {
                const res = await fetch(url);
                const json = await res.json();
                this.data = json.cfdi;
            } catch (e) {
                console.error(e);
            }

            this.loading = false;
        },

        close() {
            this.open = false;
            this.data = null;
        },

        openRelacionarModal(action, uuid, rfcEmisor, obraId = '', ordenCompraId = '') {
            const miRfc = 'RCO820921T66';

            this.relacionFormAction = action;
            this.relacionCfdiUuid = uuid;
            this.relacionRfcEmisor = rfcEmisor || '';
            this.relacionTipo = this.relacionRfcEmisor === miRfc ? 'obra' : 'orden_compra';

            this.relacionObraId = obraId || '';
            this.relacionOrdenCompraId = ordenCompraId || '';

            this.showRelacionarModal = true;
        },

        closeRelacionarModal() {
            this.showRelacionarModal = false;
            this.relacionFormAction = '';
            this.relacionCfdiUuid = '';
            this.relacionRfcEmisor = '';
            this.relacionTipo = 'obra';
            this.relacionObraId = '';
            this.relacionOrdenCompraId = '';
        }
    }
}


    function pagoModal() {
        return {
            pagoModalOpen: false,
            verPagosModalOpen: false,

            pagoForm: {
                action: '',
                cfdi_id: null,
                uuid: '',
                total: 0,
                pagado: 0,
                saldo: 0,
                metodo: '',
                fecha_pago: '',
                monto: '',
                metodo_pago: '',
                referencia: '',
            },

            verPagosData: {
                uuid: '',
                total: 0,
                pagado: 0,
                saldo: 0,
                pagos: [],
            },

            openPagoModal(data) {
                    console.log('openPagoModal llamado', data);
                    const miRfc = 'RCO820921T66'; // 🔥 luego lo pasamos dinámico

                    const esIngreso = data.rfc_emisor === miRfc;
                            this.verPagosModalOpen = false;

                this.pagoForm = {
                    action: data.action,
                    cfdi_id: data.id,
                    uuid: data.uuid,
                    total: Number(data.total || 0),
                    pagado: Number(data.pagado || 0),
                    saldo: Number(data.saldo || 0),
                    metodo: data.metodo || '',
                    fecha_pago: '',
                    monto: '',
                    metodo_pago: '',
                    referencia: '',
                };
                console.log('pagoModalOpen antes:', this.pagoModalOpen);
        this.pagoModalOpen = true;
        console.log('pagoModalOpen después:', this.pagoModalOpen);

                
            },

            openVerPagosModal(data) {
                    console.log('openVerPagosModal llamado', data);

                this.pagoModalOpen = false;

                this.verPagosData = {
                    uuid: data.uuid,
                    total: Number(data.total || 0),
                    pagado: Number(data.pagado || 0),
                    saldo: Number(data.saldo || 0),
                    pagos: data.pagos || [],
                };

                this.verPagosModalOpen = true;
            },

            closePagoModal() {
                this.pagoModalOpen = false;
            },

            closeVerPagosModal() {
                this.verPagosModalOpen = false;
            },

            money(value) {
                return Number(value || 0).toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }
    }
    </script>
</div>

@endsection
