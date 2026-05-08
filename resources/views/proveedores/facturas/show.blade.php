@extends('layouts.admin')

@section('title', 'Factura proveedor')

@section('content')
<!-- <div class="max-w-7xl mx-auto"> -->
<!-- <div class="max-w-7xl mx-auto" x-data="{ relacionarOpen: false, tipoRelacion: '{{ $cfdi->orden_compra_id ? 'orden_compra' : ($cfdi->obra_id ? 'obra' : 'orden_compra') }}' }"> -->
<div class="max-w-7xl mx-auto"
     x-data="{
        relacionarOpen: false,
        programarPagoOpen: false,
        tipoRelacion: '{{ $cfdi->orden_compra_id ? 'orden_compra' : ($cfdi->obra_id ? 'obra' : 'orden_compra') }}'
     }">    
{{-- Header --}}
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('proveedores.show', ['proveedor' => $proveedor->id, 'tab' => 'facturas']) }}"
               class="text-sm text-slate-500 hover:text-slate-800">
                ← Volver a facturas del proveedor
            </a>

            <h1 class="text-2xl font-bold text-[#0B265A] mt-3">
                Factura proveedor
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                Proveedor: {{ $proveedor->nombre }} · RFC: {{ $proveedor->rfc }}
            </p>
        </div>

        {{-- Acciones --}}
      
<div class="flex items-center gap-3">
    {{-- Botón Relacionar (Estilo Neutro/Elegante) --}}
    <button type="button"  @click="relacionarOpen = true"
            class="group relative inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 
                   transition-all duration-300 ease-out
                   hover:border-slate-300 hover:bg-slate-50 hover:shadow-[0_10px_20px_-10px_rgba(148,163,184,0.3)]
                   active:scale-95 focus:outline-none">
        
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500 transition-transform group-hover:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
        </svg>

        <span>Relacionar</span>

        <div class="absolute inset-0 rounded-xl bg-gradient-to-tr from-transparent via-transparent to-slate-400/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
    </button>

    {{-- Botón Programar Pago (Estilo Alerta/Premium) --}}
    <button type="button"  @click="programarPagoOpen = true"
            class="group relative inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-5 py-2.5 text-sm font-bold text-amber-700 
                   transition-all duration-300 ease-out
                   hover:border-amber-300 hover:bg-amber-100 hover:shadow-[0_10px_20px_-10px_rgba(245,158,11,0.4)]
                   active:scale-95 focus:outline-none">
        
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-600 transition-all group-hover:scale-110 group-hover:rotate-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>

        <span>Programar pago</span>

        <div class="absolute inset-0 rounded-xl bg-gradient-to-tr from-transparent via-transparent to-amber-400/10 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
    </button>
</div>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <p class="text-xs text-slate-500">UUID</p>
            <p class="mt-1 text-sm font-mono text-slate-800 break-all">
                {{ $cfdi->uuid ?? '-' }}
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <p class="text-xs text-slate-500">Fecha emisión</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">
                {{ $cfdi->fecha_emision ? \Carbon\Carbon::parse($cfdi->fecha_emision)->format('d/m/Y H:i') : '-' }}
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <p class="text-xs text-slate-500">Total</p>
            <p class="mt-1 text-xl font-bold text-green-700">
                ${{ number_format((float) $cfdi->total, 2) }}
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <p class="text-xs text-slate-500">Estatus pago</p>

            {{-- Temporal: mientras no exista estatus_pago --}}
            <span class="mt-2 inline-flex rounded-lg bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-700 border border-yellow-200">
                Pendiente
            </span>
        </div>
    </div>

    {{-- Datos fiscales --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

        {{-- Emisor --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-[#0B265A] mb-4">
                Emisor / Proveedor
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-slate-500">RFC</p>
                    <p class="font-semibold text-slate-900">{{ $cfdi->rfc_emisor ?? '-' }}</p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Régimen fiscal</p>
                    <p class="font-semibold text-slate-900">{{ $cfdi->emisor_regimen ?? '-' }}</p>
                </div>

                <div class="md:col-span-2">
                    <p class="text-xs text-slate-500">Nombre</p>
                    <p class="font-semibold text-slate-900">{{ $cfdi->emisor_nombre ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Receptor --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-[#0B265A] mb-4">
                Receptor / Empresa
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-slate-500">RFC</p>
                    <p class="font-semibold text-slate-900">{{ $cfdi->rfc_receptor ?? '-' }}</p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Régimen fiscal</p>
                    <p class="font-semibold text-slate-900">{{ $cfdi->receptor_regimen ?? '-' }}</p>
                </div>

                <div class="md:col-span-2">
                    <p class="text-xs text-slate-500">Nombre</p>
                    <p class="font-semibold text-slate-900">{{ $cfdi->receptor_nombre ?? '-' }}</p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Uso CFDI</p>
                    <p class="font-semibold text-slate-900">{{ $cfdi->uso_cfdi ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Pago y CFDI --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-5">
        <h2 class="text-lg font-semibold text-[#0B265A] mb-4">
            Datos del comprobante
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs text-slate-500">Tipo CFDI</p>
                <p class="font-semibold text-slate-900">{{ $cfdi->tipo_comprobante ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Serie / Folio</p>
                <p class="font-semibold text-slate-900">
                    {{ trim(($cfdi->serie ?? '') . ' ' . ($cfdi->folio ?? '')) ?: '-' }}
                </p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Forma pago</p>
                <p class="font-semibold text-slate-900">{{ $cfdi->forma_pago ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Método pago</p>
                <p class="font-semibold text-slate-900">{{ $cfdi->metodo_pago ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Moneda</p>
                <p class="font-semibold text-slate-900">{{ $cfdi->moneda ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Tipo cambio</p>
                <p class="font-semibold text-slate-900">{{ $cfdi->tipo_cambio ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Lugar expedición</p>
                <p class="font-semibold text-slate-900">{{ $cfdi->lugar_expedicion ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs text-slate-500">No. certificado</p>
                <p class="font-semibold text-slate-900">{{ $cfdi->no_certificado ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Conceptos --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-[#0B265A]">
                Conceptos
            </h2>

            <span class="text-xs text-slate-500">
                {{ $cfdi->conceptos->count() ?? 0 }} concepto(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Clave</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Descripción</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">Cantidad</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Unidad</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">Precio</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">Importe</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($cfdi->conceptos as $concepto)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $concepto->clave_prod_serv ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">
                                    {{ $concepto->descripcion ?? '-' }}
                                </div>

                                @if(!empty($concepto->clave_unidad))
                                    <div class="text-xs text-slate-500">
                                        Clave unidad: {{ $concepto->clave_unidad }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{ number_format((float) ($concepto->cantidad ?? 0), 6) }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $concepto->unidad ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                ${{ number_format((float) ($concepto->valor_unitario ?? 0), 2) }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold">
                                ${{ number_format((float) ($concepto->importe ?? 0), 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                Esta factura no tiene conceptos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot class="bg-slate-50 border-t border-slate-200">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right font-semibold text-slate-700">
                            Subtotal
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">
                            ${{ number_format((float) ($cfdi->subtotal ?? 0), 2) }}
                        </td>
                    </tr>

                    @if((float)($cfdi->descuento ?? 0) > 0)
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right font-semibold text-slate-700">
                                Descuento
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                ${{ number_format((float) ($cfdi->descuento ?? 0), 2) }}
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right font-bold text-slate-900">
                            Total
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-green-700">
                            ${{ number_format((float) ($cfdi->total ?? 0), 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    {{-- Modal Programar Pago --}}
<div x-show="programarPagoOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
     style="display: none;">

    {{-- Overlay con desenfoque --}}
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
         @click="programarPagoOpen = false"></div>

    <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
        
        {{-- Header --}}
        <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-white">
            <div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-6 bg-amber-500 rounded-full"></div>
                    <h3 class="text-xl font-bold text-[#0B265A] tracking-tight">
                        Programar pago
                    </h3>
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    Define la fecha y el método para liquidar este documento.
                </p>
            </div>

            <button type="button"
                    @click="programarPagoOpen = false"
                    class="group flex items-center justify-center w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>

        <form method="POST"
              action="{{ route('proveedores.facturas.programar-pago', [$proveedor->id, $cfdi->id]) }}">
            @csrf

            {{-- Body con fondo gris claro --}}
            <div class="p-8 bg-slate-50/50 space-y-6">

                {{-- Resumen de Factura en Tarjeta Blanca --}}
                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                        <div class="space-y-1">
                            <p class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Proveedor</p>
                            <p class="font-bold text-slate-800 leading-tight">{{ $proveedor->nombre }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Folio Fiscal (UUID)</p>
                            <p class="font-mono text-[11px] text-slate-500 truncate" title="{{ $cfdi->uuid }}">
                                ...{{ substr($cfdi->uuid, -12) }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Total a Pagar</p>
                            <p class="text-lg font-black text-green-600">
                                ${{ number_format((float) $cfdi->total, 2) }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Inputs Principales --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="block text-xs uppercase tracking-widest font-bold text-slate-500 ml-1">
                            Fecha programada
                        </label>
                        <input type="date"
                               name="fecha_pago"
                               required
                               class="w-full rounded-2xl border-slate-200 bg-white shadow-sm focus:border-amber-500 focus:ring-amber-500/20 py-3 transition-all">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs uppercase tracking-widest font-bold text-slate-500 ml-1">
                            Monto a programar
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">$</span>
                            <input type="number"
                                   name="monto"
                                   step="0.01"
                                   min="0.01"
                                   value="{{ number_format((float) $cfdi->total, 2, '.', '') }}"
                                   required
                                   class="w-full pl-8 rounded-2xl border-slate-200 bg-white shadow-sm focus:border-amber-500 focus:ring-amber-500/20 py-3 font-bold text-slate-700 transition-all">
                        </div>
                    </div>
                </div>

                {{-- Método de Pago --}}
                <div class="space-y-2">
                    <label class="block text-xs uppercase tracking-widest font-bold text-slate-500 ml-1">
                        Método de pago
                    </label>
                    <select name="metodo_pago"
                            class="w-full rounded-2xl border-slate-200 bg-white shadow-sm focus:border-amber-500 focus:ring-amber-500/20 py-3 transition-all">
                        <option value="">Selecciona método</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="cheque">Cheque</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                    </select>
                </div>

                {{-- Observaciones --}}
                <div class="space-y-2">
                    <label class="block text-xs uppercase tracking-widest font-bold text-slate-500 ml-1">
                        Referencia / observaciones
                    </label>
                    <textarea name="observaciones"
                              rows="2"
                              class="w-full rounded-2xl border-slate-200 bg-white shadow-sm focus:border-amber-500 focus:ring-amber-500/20 p-4 transition-all"
                              placeholder="Ej. Pago semanal, Banorte..."></textarea>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-8 py-6 bg-white border-t border-slate-100 flex items-center justify-end gap-4">
                <button type="button"
                        @click="programarPagoOpen = false"
                        class="rounded-xl px-6 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-100 transition-colors">
                    Cerrar
                </button>

                <button type="submit"
                        class="group relative inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition-all hover:bg-amber-600 hover:-translate-y-0.5 active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-80 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Guardar programación
                </button>
            </div>
        </form>
    </div>
</div>
{{-- Modal Relacionar --}}
<div x-show="relacionarOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
     style="display: none;">

    {{-- Overlay con desenfoque para separar de la vista trasera --}}
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
         @click="relacionarOpen = false"></div>

    <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
        
        {{-- Header con más aire --}}
        <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-white">
            <div>
                <h3 class="text-xl font-bold text-[#0B265A] tracking-tight">
                    Relacionar factura
                </h3>
                <p class="text-sm text-slate-500 mt-1">
                    Vincula este documento a una operación existente.
                </p>
            </div>

            <button type="button"
                    @click="relacionarOpen = false"
                    class="group flex items-center justify-center w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>

        <form method="POST"
              action="{{ route('proveedores.facturas.relacionar', [$proveedor->id, $cfdi->id]) }}">
            @csrf

            {{-- Body con fondo gris claro para dar profundidad --}}
            <div class="p-8 bg-slate-50/50 space-y-8">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Tipo --}}
                    <div class="space-y-2">
                        <label class="block text-xs uppercase tracking-widest font-bold text-slate-500 ml-1">
                            Tipo de relación
                        </label>
                        <select name="tipo"
                                x-model="tipoRelacion"
                                class="w-full rounded-2xl border-slate-200 bg-white shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] py-3">
                            <option value="orden_compra">Orden de compra</option>
                            <option value="obra">Obra</option>
                        </select>
                    </div>

                    {{-- Selector Dinámico --}}
                    <div class="space-y-2">
                        <label class="block text-xs uppercase tracking-widest font-bold text-slate-500 ml-1">
                            <span x-text="tipoRelacion === 'orden_compra' ? 'Seleccionar Orden' : 'Seleccionar Obra'"></span>
                        </label>

                        {{-- Orden compra --}}
                        <div x-show="tipoRelacion === 'orden_compra'" x-transition>
                            <select name="orden_compra_id"
                                    class="w-full rounded-2xl border-slate-200 bg-white shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] py-3">
                                <option value="">— Seleccionar —</option>
                                @foreach($ordenesCompra as $oc)
                                    <option value="{{ $oc->id }}" @selected($cfdi->orden_compra_id == $oc->id)>
                                        {{ $oc->folio ?? 'OC #'.$oc->id }} ({{ optional($oc->fecha)->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                            @if($ordenesCompra->isEmpty())
                                <p class="text-[11px] font-medium text-amber-600 mt-2 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    Sin órdenes registradas.
                                </p>
                            @endif
                        </div>

                        {{-- Obra --}}
                        <div x-show="tipoRelacion === 'obra'" x-transition>
                            <select name="obra_id"
                                    class="w-full rounded-2xl border-slate-200 bg-white shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] py-3">
                                <option value="">— Seleccionar —</option>
                                @foreach($obras as $obra)
                                    <option value="{{ $obra->id }}" @selected($cfdi->obra_id == $obra->id)>
                                        {{ $obra->nombre ?? $obra->Nombre ?? 'Obra #'.$obra->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Estado actual con diseño de "Card" --}}
                <div class="group relative rounded-2xl bg-white border border-slate-200 p-5 shadow-sm transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 rounded-lg bg-slate-100 text-slate-500 group-hover:bg-[#0B265A] group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-xs uppercase tracking-tighter font-bold text-slate-400">Información de Relación Actual</p>
                    </div>

                    <div class="ml-10">
                        @if($cfdi->ordenCompra)
                            <p class="text-slate-700 font-medium">
                                Vinculada a Orden: <span class="text-[#0B265A] font-bold">{{ $cfdi->ordenCompra->folio ?? 'OC #'.$cfdi->ordenCompra->id }}</span>
                            </p>
                        @elseif($cfdi->obra)
                            <p class="text-slate-700 font-medium">
                                Vinculada a Obra: <span class="text-[#0B265A] font-bold">{{ $cfdi->obra->nombre ?? 'Obra #'.$cfdi->obra->id }}</span>
                            </p>
                        @else
                            <p class="text-slate-400 italic">No existe una relación previa para este CFDI.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Footer con más presencia --}}
            <div class="px-8 py-6 bg-white border-t border-slate-100 flex items-center justify-end gap-4">
                <button type="button"
                        @click="relacionarOpen = false"
                        class="rounded-xl px-6 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-100 transition-colors">
                    Descartar
                </button>

                <button type="submit"
                        class="group relative inline-flex items-center justify-center gap-2 rounded-xl bg-[#0B265A] px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition-all hover:bg-[#12387f] hover:-translate-y-0.5 active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Confirmar Relación
                </button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection 