@extends('layouts.admin')

@section('title', 'Detalle factura')

@section('content')
{{-- Todo el contenido dentro de un solo x-data para que compartan el estado --}}
<div x-data="detalleFactura()" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- TOASTS --}}
    <div class="fixed top-5 right-5 z-[100] flex flex-col gap-3 w-80">
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="flex items-center p-4 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-lg text-emerald-700">
                <div class="flex-shrink-0 mr-3">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </div>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)"
                 class="flex items-center p-4 rounded-2xl bg-red-50 border border-red-200 shadow-lg text-red-700">
                <div class="flex-shrink-0 mr-3">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </div>
                <div class="text-sm font-medium">{{ session('error') ?? 'Ocurrió un error en la solicitud.' }}</div>
            </div>
        @endif
    </div>

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Factura {{ $factura->serie }}-{{ $factura->folio }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                UUID: {{ $factura->uuid ?? 'Sin UUID' }}
            </p>
        </div>
        <a href="{{ route('sat.facturacion.index') }}"
           class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Volver
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- DATOS GENERALES --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 text-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4 uppercase tracking-wider">Datos generales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><span class="text-slate-500 block">Emisor</span><p class="font-medium">{{ $factura->empresa->nombre ?? '—' }}</p></div>
                    <div><span class="text-slate-500 block">Receptor</span><p class="font-medium">{{ $factura->receptor_nombre }}</p></div>
                    <div><span class="text-slate-500 block">RFC receptor</span><p class="font-medium text-indigo-600">{{ $factura->receptor_rfc }}</p></div>
<div>
    <span class="text-slate-500 block">Uso CFDI</span>
    <p class="font-medium">
        {{ $factura->uso_cfdi }}
    </p>
</div>

<div>
    <span class="text-slate-500 block">Método de pago</span>
    <p class="font-medium">
        {{ $factura->metodo_pago }}
        <span class="text-slate-500 text-sm">
            @if($factura->metodo_pago === 'PUE')
                (Pago en una sola exhibición)
            @elseif($factura->metodo_pago === 'PPD')
                (Pago en parcialidades o diferido)
            @endif
        </span>
    </p>
</div>

<div>
    <span class="text-slate-500 block">Forma de pago</span>
    <p class="font-medium">
        {{ $factura->forma_pago }}
    </p>
</div>                    <div><span class="text-slate-500 block">Estado</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $factura->estado == 'timbrada' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($factura->estado) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- TABLA CONCEPTOS --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden text-sm">
                <div class="px-6 py-4 border-b border-slate-200"><h2 class="text-lg font-semibold text-slate-900">Conceptos</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-xs uppercase text-slate-500">
                                <th class="px-5 py-3 text-left">Descripción</th>
                                <th class="px-5 py-3 text-right">Cantidad</th>
                                <th class="px-5 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($factura->conceptos as $concepto)
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-slate-900">{{ $concepto->descripcion }}</div>
                                        <div class="text-xs text-slate-500">SAT: {{ $concepto->clave_producto_servicio }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-right">{{ number_format($concepto->cantidad, 2) }}</td>
                                    <td class="px-5 py-4 text-right font-semibold">${{ number_format($concepto->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        {{-- RESUMEN Y ACCIONES --}}
        <div class="space-y-6">
            @php
    $totalPagado = $factura->pagos()
        ->where('estado', 'timbrado')
        ->sum('monto');

    $saldoPendiente = max($factura->total - $totalPagado, 0);
@endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 text-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4 font-bold">Resumen</h2>
                <div class="space-y-3">
                    <div class="flex justify-between font-bold text-lg text-slate-900 border-t pt-3">
                        <span>Total</span><span>${{ number_format($factura->total, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Archivos y Acciones</h2>
                <div class="space-y-3">
                    @if($factura->xml_path)
                        <a href="{{ route('sat.facturacion.xml', $factura) }}"
                        class="group relative flex items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700
                                transition-all duration-300 ease-out
                                hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-[0_10px_20px_-10px_rgba(59,130,246,0.3)]
                                active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500/20">

                            <!-- Icono de Código / XML con animación de expansión -->
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 text-blue-500 transition-transform duration-300 group-hover:scale-110"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>

                            <span class="tracking-tight">Descargar XML</span>

                            <!-- Brillo alucín azulado en la esquina -->
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-tr from-transparent via-transparent to-blue-400/10 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        </a>
                    @endif
                   @if($factura->pdf_path)
                        <a href="{{ route('sat.facturacion.pdf', $factura) }}"
                        class="group relative flex items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700
                                transition-all duration-300 ease-out
                                hover:border-red-200 hover:bg-red-50 hover:text-red-700 hover:shadow-[0_10px_20px_-10px_rgba(239,68,68,0.3)]
                                active:scale-95 focus:outline-none focus:ring-2 focus:ring-red-500/20">

                            <!-- Icono de PDF con animación de rebote sutil al pasar el mouse -->
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 text-red-500 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-3"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9h1.5m1.5 0H13m-4 4h1.5m1.5 0H13m-4 4h1.5m1.5 0H13" />
                            </svg>

                            <span class="tracking-tight">Descargar PDF</span>

                            <!-- Brillo alucín en la esquina superior al hacer hover -->
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-tr from-transparent via-transparent to-red-400/10 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        </a>
                    @endif

                    <button type="button"
                            @click="envioOpen = true"
                            class="group relative w-full flex items-center justify-center gap-3 rounded-xl border border-indigo-200 bg-white px-4 py-3 text-sm font-bold text-indigo-700
                                transition-all duration-300 ease-out
                                hover:border-indigo-300 hover:bg-indigo-50 hover:shadow-[0_10px_20px_-10px_rgba(79,70,229,0.4)]
                                active:scale-95 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">

                        <!-- Icono de Avión de Papel con animación de traslación -->
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-indigo-600 transition-all duration-300 group-hover:translate-x-1 group-hover:-translate-y-1 group-hover:scale-110"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l19-9-9 19-2-9-8-2z" />
                        </svg>

                        <span class="tracking-tight">
                            {{ $factura->email_enviado_at ? 'Reenviar factura' : 'Enviar factura' }}
                        </span>

                        <!-- Destello alucín índigo -->
                        <div class="absolute inset-0 rounded-xl bg-gradient-to-tr from-transparent via-transparent to-indigo-400/15 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                    </button>
                    <a href="{{ $whatsappUrl }}"
        target="_blank"
        rel="noopener"
        class="group relative w-full flex items-center justify-center gap-3 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm font-bold text-emerald-700
            transition-all duration-300 ease-out
            hover:border-emerald-300 hover:bg-emerald-50 hover:shadow-[0_10px_20px_-10px_rgba(16,185,129,0.4)]
            active:scale-95 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">

    <!-- Icono de WhatsApp con animación -->
    <svg xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5 text-emerald-600 transition-all duration-300 group-hover:scale-110 group-hover:rotate-6"
        viewBox="0 0 24 24" fill="currentColor">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
        <path d="M12.004 2c-5.522 0-10 4.477-10 10 0 1.766.464 3.484 1.343 5.006L2 22l5.13-1.345A9.955 9.955 0 0012.004 22c5.523 0 10-4.477 10-10s-4.477-10-10-10zm0 18.176a8.152 8.152 0 01-4.155-1.14l-.298-.177-3.045.799.813-2.97-.194-.306a8.156 8.156 0 01-1.253-4.382c0-4.507 3.667-8.176 8.177-8.176 4.51 0 8.177 3.669 8.177 8.176 0 4.508-3.667 8.176-8.177 8.176z"/>
    </svg>

    <span class="tracking-tight">
        {{ $factura->whatsapp_enviado_at ? 'Reenviar por WhatsApp' : 'Enviar por WhatsApp' }}
    </span>

    <div class="absolute inset-0 rounded-xl bg-gradient-to-tr from-transparent via-transparent to-emerald-400/15 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
</a>
                    @if($factura->metodo_pago === 'PPD' && $factura->estado !== 'cancelada' && $saldoPendiente > 0)
                        <button type="button"
                                @click="pagoOpen = true"
                                class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                            Registrar pago
                        </button>
                    @endif
                    @if($factura->estado === 'cancelada')
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('sat.facturacion.acuse', [$factura, 'pdf']) }}"
                            class="flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 hover:bg-red-100">
                                Acuse PDF
                            </a>
                            <a href="{{ route('sat.facturacion.acuse', [$factura, 'xml']) }}"
                            class="flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Acuse XML
                            </a>
                        </div>
                    @endif
                    @if($factura->estado === 'cancelacion_solicitada')
                        <form method="POST" action="{{ route('sat.facturacion.sincronizar-cancelacion', $factura) }}" @submit="loading = true">
                            @csrf
                            <button type="submit"
                                    class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700 hover:bg-amber-100">
                                Actualizar estatus SAT
                            </button>
                        </form>
                    @endif
                    @if($factura->estado === 'timbrada')
                        <button type="button"
                            @click="cancelacionOpen = true"
                            class="group relative w-full flex items-center justify-center gap-3 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-bold text-red-700
                                transition-all duration-300 ease-out
                                hover:border-red-300 hover:bg-red-50 hover:text-red-800 hover:shadow-[0_10px_20px_-10px_rgba(220,38,38,0.4)]
                                active:scale-95 focus:outline-none focus:ring-2 focus:ring-red-500/20">

                        <!-- Icono de Advertencia / Cancelar con animación de rotación -->
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-red-600 transition-transform duration-300 group-hover:rotate-12 group-hover:scale-110"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>

                        <span class="tracking-tight">Cancelar CFDI</span>

                        <!-- Destello alucín de peligro en rojo -->
                        <div class="absolute inset-0 rounded-xl bg-gradient-to-tr from-transparent via-transparent to-red-500/10 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
{{-- TABLA PAGOS --}}
@if($factura->pagos->count())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden text-sm mt-6">

        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">
                    Complementos de Pago
                </h2>

                <span class="text-xs text-slate-500">
                    {{ $factura->pagos->count() }} pago(s)
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">

                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-xs uppercase text-slate-500">
                        <th class="px-5 py-3 text-left">UUID</th>
                        <th class="px-5 py-3 text-center">Parcialidad</th>
                        <th class="px-5 py-3 text-center">Fecha</th>
                        <th class="px-5 py-3 text-center">Forma Pago</th>
                        <th class="px-5 py-3 text-right">Monto</th>
                        <th class="px-5 py-3 text-right">Saldo Insoluto</th>
                        <th class="px-5 py-3 text-center">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @foreach($factura->pagos as $pago)

                        <tr class="hover:bg-slate-50">

                            {{-- UUID --}}
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-900 text-xs">
                                    {{ $pago->uuid ?? 'Sin UUID' }}
                                </div>
                            </td>

                            {{-- PARCIALIDAD --}}
                            <td class="px-5 py-4 text-center">
                                {{ $pago->numero_parcialidad }}
                            </td>

                            {{-- FECHA --}}
                            <td class="px-5 py-4 text-center">
                                {{ $pago->fecha_pago?->format('d/m/Y H:i') }}
                            </td>

                            {{-- FORMA PAGO --}}
                            <td class="px-5 py-4 text-center">
                                {{ $pago->forma_pago }}
                            </td>

                            {{-- MONTO --}}
                            <td class="px-5 py-4 text-right font-semibold text-emerald-700">
                                ${{ number_format($pago->monto, 2) }}
                            </td>

                            {{-- SALDO --}}
                            <td class="px-5 py-4 text-right">
                                ${{ number_format($pago->saldo_insoluto, 2) }}
                            </td>

                            {{-- ESTADO --}}
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                                    @class([
                                        'bg-emerald-50 text-emerald-700 border border-emerald-200' =>
                                            $pago->estado === 'timbrado',

                                        'bg-red-50 text-red-700 border border-red-200' =>
                                            $pago->estado === 'cancelado',

                                        'bg-amber-50 text-amber-700 border border-amber-200' =>
                                            !in_array($pago->estado, ['timbrado', 'cancelado']),
                                    ])">
                                    {{ ucfirst($pago->estado) }}
                                </span>
                            </td>

                            {{-- ACCIONES --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">

                                    @if($pago->xml_path)
                                        <a href="{{ route('sat.facturacion.pagos.xml', $pago) }}"
                                           class="text-xs font-medium text-slate-600 hover:text-slate-900">
                                            XML
                                        </a>
                                    @endif

                                    @if($pago->pdf_path)
                                        <a href="{{ route('sat.facturacion.pagos.pdf', $pago) }}"
                                           class="text-xs font-medium text-slate-600 hover:text-slate-900">
                                            PDF
                                        </a>
                                    @endif

                                </div>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>
        </div>
    </div>
@endif
    {{-- MODAL DE CANCELACIÓN (Dentro del x-data) --}}
    <div x-show="cancelacionOpen"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-[150] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">

        <div @click.away="cancelacionOpen = false"
             class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Cancelar CFDI</h2>
                <button @click="cancelacionOpen = false" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
            </div>

            <form method="POST" action="{{ route('sat.facturacion.cancelar', $factura) }}" @submit="loading = true; cancelacionOpen = false" class="p-6 space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Motivo de cancelación SAT</label>
                    <select name="motivo_cancelacion" x-model="motivoCancelacion"
                            class="w-full rounded-xl border-slate-200 focus:border-red-300 focus:ring-red-200">
                        <option value="01">01 - Comprobante emitido con errores con relación</option>
                        <option value="02">02 - Comprobante emitido con errores sin relación</option>
                        <option value="03">03 - No se llevó a cabo la operación</option>
                        <option value="04">04 - Operación nominativa relacionada en factura global</option>
                    </select>
                </div>

                <div x-show="motivoCancelacion === '01'" x-transition>
                    <label class="block text-sm font-medium text-slate-700 mb-2">UUID CFDI sustituto</label>
                    <input type="text" name="sustitucion_uuid" placeholder="UUID de la nueva factura"
                           class="w-full rounded-xl border-slate-200 focus:border-red-300 focus:ring-red-200">
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 italic">
                    Esta acción solicitará la cancelación fiscal ante el SAT.
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="cancelacionOpen = false" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cerrar</button>
                    <button type="submit" class="rounded-xl bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700 shadow-md">
                        Confirmar cancelación
                    </button>
                </div>
            </form>
        </div>
    </div>
    {{--  MODAL AGREGAR PAGO A FACTURA --}}
    <div x-show="pagoOpen"
     x-transition.opacity
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     style="display:none;">

    <div @click.away="pagoOpen = false"
         class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-900">
                Registrar pago
            </h2>

            <button type="button"
                    @click="pagoOpen = false"
                    class="text-slate-400 hover:text-slate-600">
                ✕
            </button>
        </div>

        <form method="POST"
              action="{{ route('sat.facturacion.pagos.store', $factura) }}"
              class="p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Fecha de pago
                </label>
                <input type="datetime-local"
                       name="fecha_pago"
                       value="{{ now()->format('Y-m-d\TH:i') }}"
                       class="w-full rounded-xl border-slate-200 focus:border-emerald-300 focus:ring-emerald-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Forma de pago
                </label>
                <select name="forma_pago"
                        class="w-full rounded-xl border-slate-200 focus:border-emerald-300 focus:ring-emerald-200">
                    <option value="03">03 - Transferencia electrónica de fondos</option>
                    <option value="01">01 - Efectivo</option>
                    <option value="02">02 - Cheque nominativo</option>
                    <option value="04">04 - Tarjeta de crédito</option>
                    <option value="28">28 - Tarjeta de débito</option>
                    <option value="99">99 - Por definir</option>
                </select>
            </div>

            <div>
                @php
                    $totalPagado = $factura->pagos()
                        ->where('estado', 'timbrado')
                        ->sum('monto');

                    $saldoPendiente = max($factura->total - $totalPagado, 0);
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs text-slate-500">Monto factura</div>
                        <div class="text-sm font-bold text-slate-900">
                            ${{ number_format($factura->total, 2) }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <div class="text-xs text-emerald-600">Pagos registrados</div>
                        <div class="text-sm font-bold text-emerald-700">
                            ${{ number_format($totalPagado, 2) }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <div class="text-xs text-amber-600">Saldo pendiente</div>
                        <div class="text-sm font-bold text-amber-700">
                            ${{ number_format($saldoPendiente, 2) }}
                        </div>
                    </div>
                </div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Monto pagado
                </label>
               <input type="number"
                        name="monto"
                        step="0.01"
                        min="0.01"
                        max="{{ $saldoPendiente }}"
                        value="{{ $saldoPendiente }}"
                        class="w-full rounded-xl border-slate-200 focus:border-emerald-300 focus:ring-emerald-200">
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                Este pago generará un CFDI tipo P con complemento de pago.
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button"
                        @click="pagoOpen = false"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cerrar
                </button>

                <button type="submit"
                        class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Generar complemento
                </button>
            </div>
        </form>
    </div>
</div>

    {{-- MODAL ENVIO FACTURA --}}
    <div x-show="envioOpen"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-[150] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">
        <div @click.away="envioOpen = false"
             class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Enviar factura</h2>
                    <p class="text-sm text-slate-500">XML y PDF se enviaran adjuntos.</p>
                </div>
                <button type="button" @click="envioOpen = false" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
            </div>

            <form method="POST"
                  action="{{ route('sat.facturacion.enviar', $factura) }}"
                  @submit="loading = true; envioOpen = false"
                  class="p-6 space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Correo destino</label>
                    <input type="email"
                           name="email_destino"
                           x-model="envioEmail"
                           required
                           class="w-full rounded-xl border-slate-200 focus:border-indigo-300 focus:ring-indigo-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Correo adicional</label>
                    <input type="email"
                           name="email_adicional"
                           x-model="envioEmailAdicional"
                           class="w-full rounded-xl border-slate-200 focus:border-indigo-300 focus:ring-indigo-200"
                           placeholder="Opcional">
                </div>

                <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                    Se enviara a <strong x-text="envioEmail || 'sin correo'"></strong><template x-if="envioEmailAdicional"><span> y <strong x-text="envioEmailAdicional"></strong></span></template>.
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button"
                            @click="envioOpen = false"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cerrar
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-md">
                        Enviar factura
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DE CARGA (VELO) --}}
    <div x-show="loading" x-cloak x-transition.opacity
         class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-8 shadow-2xl flex flex-col items-center">
            <svg class="animate-spin h-10 w-10 text-indigo-600 mb-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <p class="text-slate-900 font-bold">Procesando solicitud...</p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function detalleFactura() {
        return {
            cancelacionOpen: false,
            motivoCancelacion: '02',
            envioOpen: false,
            envioEmail: @json(old('email_destino', $factura->email_destino ?? $factura->cliente?->email ?? '')),
            envioEmailAdicional: @json(old('email_adicional', '')),
            loading: false,
            pagoOpen: false,
        }
    }
</script>
@endpush
