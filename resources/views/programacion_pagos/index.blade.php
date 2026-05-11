@extends('layouts.admin')

@section('title', 'Programación de pagos')

@section('content')

<div class="max-w-8xl mx-auto px-6 py-8" x-data="programacionPagos()">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#0B1F3A]">
                Programación de pagos
            </h1>
            <p class="text-sm text-slate-500">
                CFDIs recibidos pendientes de programar para pago.
            </p>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-3">

    <input
        type="date"
        name="fecha_inicio"
        value="{{ request('fecha_inicio', $fechaInicio->format('Y-m-d')) }}"
        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
    >

    <input
        type="date"
        name="fecha_fin"
        value="{{ request('fecha_fin', $fechaFin->format('Y-m-d')) }}"
        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
    >

    <input
        type="text"
        name="rfc_emisor"
        value="{{ request('rfc_emisor') }}"
        placeholder="RFC emisor"
        class="w-44 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
    >

    <select
        name="metodo_pago"
        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
    >
        <option value="">Método</option>
        <option value="PUE" @selected(request('metodo_pago') == 'PUE')>PUE</option>
        <option value="PPD" @selected(request('metodo_pago') == 'PPD')>PPD</option>
    </select>

    <button
        type="submit"
        class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700"
    >
        Buscar
    </button>

</form>
    </div>
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">

    <div class="text-sm text-slate-500">
        Semana mostrada:
        <span class="font-semibold text-[#0B1F3A]">
            {{ $fechaInicio->format('d/m/Y') }}
        </span>
        al
        <span class="font-semibold text-[#0B1F3A]">
            {{ $fechaFin->format('d/m/Y') }}
        </span>
    </div>

    <div class="flex gap-2">

        <a
            href="{{ route('programacion-pagos.index', [
                'fecha_inicio' => $semanaAnteriorInicio,
                'fecha_fin' => $semanaAnteriorFin,
                'rfc_emisor' => request('rfc_emisor'),
                'metodo_pago' => request('metodo_pago'),
            ]) }}"
            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-[#0B1F3A] hover:bg-slate-50"
        >
            ← Semana anterior
        </a>

        <a
            href="{{ route('programacion-pagos.index') }}"
            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-[#0B1F3A] hover:bg-slate-50"
        >
            Semana actual
        </a>

        <a
            href="{{ route('programacion-pagos.index', [
                'fecha_inicio' => $semanaSiguienteInicio,
                'fecha_fin' => $semanaSiguienteFin,
                'rfc_emisor' => request('rfc_emisor'),
                'metodo_pago' => request('metodo_pago'),
            ]) }}"
            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-[#0B1F3A] hover:bg-slate-50"
        >
            Semana siguiente →
        </a>

    </div>

</div>
    {{-- CHIPS / KPIS --}}
    <div class="flex flex-wrap gap-2 mb-5">
        <span class="rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
            CFDIs: {{ number_format($cfdis->total()) }}
        </span>

        <span class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-[#0B1F3A]">
            Total: ${{ number_format($cfdis->sum('total'), 2) }}
        </span>

        <span class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-[#0B1F3A]">
            PPD: {{ $cfdis->where('metodo_pago', 'PPD')->count() }}
        </span>

        <span class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-[#0B1F3A]">
            PUE: {{ $cfdis->where('metodo_pago', 'PUE')->count() }}
        </span>
    </div>

    {{-- TABLA --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Proveedor</th>
                        <th class="px-4 py-3">RFC</th>
                        <th class="px-4 py-3">UUID</th>
                        <th class="px-4 py-3 text-center">Método</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-center">Obra</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($cfdis as $cfdi)

                        @php
                            $programacion = $cfdi->programaciones->first();
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-semibold text-[#0B1F3A]">
                                    {{ \Carbon\Carbon::parse($cfdi->fecha_emision)->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-slate-400">
                                    {{ \Carbon\Carbon::parse($cfdi->fecha_emision)->format('H:i') }}
                                </div>
                            </td>

                            <td class="px-4 py-3 min-w-[240px]">
                                <div class="font-semibold text-[#0B1F3A]">
                                    {{ $cfdi->emisor_nombre }}
                                </div>
                                <div class="text-xs text-slate-400">
                                    {{ $cfdi->receptor_nombre }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <span class="rounded-md bg-slate-50 px-2 py-1 text-xs text-[#0B1F3A] border border-slate-200">
                                    {{ $cfdi->rfc_emisor }}
                                </span>
                            </td>

                            <td class="px-4 py-3 max-w-[220px]">
                                <div class="truncate text-xs text-slate-500" title="{{ $cfdi->uuid }}">
                                    {{ $cfdi->uuid }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if($cfdi->metodo_pago == 'PPD')
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                        PPD
                                    </span>
                                @elseif($cfdi->metodo_pago == 'PUE')
                                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                        PUE
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                        N/A
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right font-semibold text-[#0B1F3A] whitespace-nowrap">
                                ${{ number_format($cfdi->total, 2) }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if($cfdi->obra)
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                        {{ $cfdi->obra->nombre ?? 'Obra relacionada' }}
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                                        Sin obra
                                    </span>
                                @endif
                            </td>

                         <td class="px-4 py-3 text-center">

    @if($programacion)

        @switch($programacion->estatus)

            @case('pendiente_revision_admin')

                <div class="flex flex-col items-center gap-1">
                    <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">
                        Pendiente revisión
                    </span>

                    <span class="text-xs text-slate-500">
                        {{ $programacion->fecha_programada?->format('d/m/Y') }}
                    </span>
                </div>

            @break

            @case('pendiente_aprobacion_ceo')

                <div class="flex flex-col items-center gap-1">
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                        Pendiente CEO
                    </span>

                    <span class="text-xs text-slate-500">
                        {{ $programacion->fecha_programada?->format('d/m/Y') }}
                    </span>
                </div>

            @break

            @case('aprobada')

                <div class="flex flex-col items-center gap-1">
                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                        Aprobada
                    </span>

                    <span class="text-xs text-slate-500">
                        {{ $programacion->fecha_programada?->format('d/m/Y') }}
                    </span>
                </div>

            @break

            @case('pagada')

                <div class="flex flex-col items-center gap-1">
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                        Pagada
                    </span>

                    <span class="text-xs text-slate-500">
                        {{ $programacion->fecha_programada?->format('d/m/Y') }}
                    </span>
                </div>

            @break

            @case('cancelada')

                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                    Cancelada
                </span>

            @break

            @default

                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ $programacion->estatus }}
                </span>

        @endswitch

    @else

        <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">
            Sin programar
        </span>

    @endif

</td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                              <button
    type="button"
    @click="openVerFactura({
        id: {{ $cfdi->id }},
        uuid: @js($cfdi->uuid),
        fecha: @js(\Carbon\Carbon::parse($cfdi->fecha_emision)->format('d/m/Y H:i')),
        serie: @js($cfdi->serie),
        folio: @js($cfdi->folio),

        emisor_nombre: @js($cfdi->emisor_nombre),
        rfc_emisor: @js($cfdi->rfc_emisor),
        emisor_regimen: @js($cfdi->emisor_regimen),

        receptor_nombre: @js($cfdi->receptor_nombre),
        rfc_receptor: @js($cfdi->rfc_receptor),
        receptor_regimen: @js($cfdi->receptor_regimen),
        uso_cfdi: @js($cfdi->uso_cfdi),

        forma_pago: @js($cfdi->forma_pago),
        metodo_pago: @js($cfdi->metodo_pago),
        moneda: @js($cfdi->moneda),
        subtotal: {{ (float) $cfdi->subtotal }},
        descuento: {{ (float) $cfdi->descuento }},
        total: {{ (float) $cfdi->total }},

        conceptos: @js($cfdi->conceptos->map(function($concepto) {
            return [
                'cantidad' => $concepto->cantidad,
                'clave_prod_serv' => $concepto->clave_prod_serv,
                'clave_unidad' => $concepto->clave_unidad,
                'descripcion' => $concepto->descripcion,
                'valor_unitario' => $concepto->valor_unitario,
                'importe' => $concepto->importe,
                'descuento' => $concepto->descuento,
            ];
        })->values())
    })"
    class="text-xs font-semibold text-blue-600 hover:text-blue-800"
>
    Ver
</button>

                                <span class="mx-2 text-slate-300">|</span>

                                @if($programacion)
                                    <button
                                        type="button"
                                       @click="openProgramar({
                                            id: {{ $cfdi->id }},
                                            uuid: @js($cfdi->uuid),
                                            proveedor: @js($cfdi->emisor_nombre),
                                            total: {{ (float) $cfdi->total }},
                                            fecha_programada: @js(optional($programacion->fecha_programada)->format('Y-m-d')),
                                            estatus: @js($programacion->estatus),
                                            solicitado_at: @js(optional($programacion->solicitado_at)->format('d/m/Y H:i')),
                                            revisado_at: @js(optional($programacion->revisado_at)->format('d/m/Y H:i')),
                                            aprobado_at: @js(optional($programacion->aprobado_at)->format('d/m/Y H:i')),
                                            observaciones: @js($programacion->observaciones),
                                            comentario_revision: @js($programacion->comentario_revision),
                                            comentario_aprobacion: @js($programacion->comentario_aprobacion),
                                            programado: true
                                        })"
                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-800"
                                    >
                                        Ver programación
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        @click="openProgramar({
                                            id: {{ $cfdi->id }},
                                            uuid: @js($cfdi->uuid),
                                            proveedor: @js($cfdi->emisor_nombre),
                                            total: {{ (float) $cfdi->total }},
                                            fecha_programada: null,
                                            programado: false
                                        })"
                                        class="text-xs font-semibold text-[#0B1F3A] hover:text-blue-700"
                                    >
                                        Programar
                                    </button>
                                @endif
                                 {{-- REVISAR --}}
                                    @if(
                                        $programacion &&
                                        $programacion->estatus === 'pendiente_revision_admin' &&
                                        auth()->user()->hasAnyRole(['admin-rivera', 'super-admin'])
                                    )

                                        <span class="mx-2 text-slate-300">|</span>

                                        <form
                                            action="{{ route('programacion-pagos.revisar', $programacion) }}"
                                            method="POST"
                                            class="inline"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="text-xs font-semibold text-amber-600 hover:text-amber-800"
                                            >
                                                Revisar
                                            </button>
                                        </form>

                                    @endif

                                    {{-- AUTORIZAR --}}
                                    @if(
                                        $programacion &&
                                        $programacion->estatus === 'pendiente_aprobacion_ceo' &&
                                        auth()->user()->hasAnyRole(['admin-rivera', 'super-admin'])
                                    )

                                        <span class="mx-2 text-slate-300">|</span>

                                        <form
                                            action="{{ route('programacion-pagos.autorizar', $programacion) }}"
                                            method="POST"
                                            class="inline"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="text-xs font-semibold text-green-600 hover:text-green-800"
                                            >
                                                Autorizar
                                            </button>
                                        </form>

                                    @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">
                                No hay CFDIs disponibles para programación de pagos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cfdis->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $cfdis->links() }}
            </div>
        @endif
    </div>
{{-- MODAL VER FACTURA --}}
<div
    x-show="verFacturaModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4"
    style="display: none;"
>
    <div
        @click.away="closeVerFactura()"
        class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden"
    >
        {{-- HEADER MODAL --}}
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-[#0B1F3A]">
                    Representación de factura
                </h3>

                <p class="text-xs text-slate-500">
                    UUID:
                    <span x-text="factura.uuid"></span>
                </p>
            </div>

            <button
                type="button"
                @click="closeVerFactura()"
                class="h-9 w-9 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:bg-slate-100"
            >
                ✕
            </button>
        </div>

        {{-- CUERPO --}}
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)] bg-slate-100">

            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">

                {{-- CABECERA FACTURA --}}
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 border-b border-slate-200 pb-5 mb-5">
                    <div>
                        <h2 class="text-2xl font-bold text-[#0B1F3A]">
                            FACTURA CFDI
                        </h2>

                        <p class="text-sm text-slate-500 mt-1">
                            Fecha de emisión:
                            <span class="font-semibold text-slate-700" x-text="factura.fecha"></span>
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-xs text-slate-500 uppercase font-semibold">
                            Serie / Folio
                        </p>

                        <p class="text-xl font-bold text-[#0B1F3A]">
                            <span x-text="factura.serie || '-'"></span>
                            -
                            <span x-text="factura.folio || '-'"></span>
                        </p>
                    </div>
                </div>

                {{-- EMISOR / RECEPTOR --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">

                    <div class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-xs font-bold uppercase text-slate-400 mb-3">
                            Emisor
                        </h4>

                        <p class="font-bold text-[#0B1F3A]" x-text="factura.emisor_nombre"></p>

                        <p class="text-sm text-slate-600 mt-1">
                            RFC:
                            <span class="font-semibold" x-text="factura.rfc_emisor"></span>
                        </p>

                        <p class="text-sm text-slate-600 mt-1">
                            Régimen:
                            <span class="font-semibold" x-text="factura.emisor_regimen || 'N/A'"></span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-xs font-bold uppercase text-slate-400 mb-3">
                            Receptor
                        </h4>

                        <p class="font-bold text-[#0B1F3A]" x-text="factura.receptor_nombre"></p>

                        <p class="text-sm text-slate-600 mt-1">
                            RFC:
                            <span class="font-semibold" x-text="factura.rfc_receptor"></span>
                        </p>

                        <p class="text-sm text-slate-600 mt-1">
                            Régimen:
                            <span class="font-semibold" x-text="factura.receptor_regimen || 'N/A'"></span>
                        </p>
                    </div>

                </div>

                {{-- DATOS FISCALES --}}
                <div class="rounded-xl border border-slate-200 p-4 mb-5">
                    <h4 class="text-xs font-bold uppercase text-slate-400 mb-3">
                        Datos fiscales
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-slate-400 text-xs uppercase font-semibold">Forma de pago</p>
                            <p class="font-semibold text-[#0B1F3A]" x-text="factura.forma_pago || 'N/A'"></p>
                        </div>

                        <div>
                            <p class="text-slate-400 text-xs uppercase font-semibold">Método de pago</p>
                            <p class="font-semibold text-[#0B1F3A]" x-text="factura.metodo_pago || 'N/A'"></p>
                        </div>

                        <div>
                            <p class="text-slate-400 text-xs uppercase font-semibold">Uso CFDI</p>
                            <p class="font-semibold text-[#0B1F3A]" x-text="factura.uso_cfdi || 'N/A'"></p>
                        </div>

                        <div>
                            <p class="text-slate-400 text-xs uppercase font-semibold">Moneda</p>
                            <p class="font-semibold text-[#0B1F3A]" x-text="factura.moneda || 'MXN'"></p>
                        </div>
                    </div>
                </div>

                {{-- CONCEPTOS --}}
                <div class="rounded-xl border border-slate-200 overflow-hidden mb-5">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-xs uppercase text-slate-500">
                                <th class="px-4 py-3 text-left">Cant.</th>
                                <th class="px-4 py-3 text-left">Clave</th>
                                <th class="px-4 py-3 text-left">Unidad</th>
                                <th class="px-4 py-3 text-left">Descripción</th>
                                <th class="px-4 py-3 text-right">Valor unit.</th>
                                <th class="px-4 py-3 text-right">Importe</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200">
                            <template x-for="concepto in factura.conceptos" :key="concepto.descripcion">
                                <tr>
                                    <td class="px-4 py-3" x-text="concepto.cantidad"></td>

                                    <td class="px-4 py-3" x-text="concepto.clave_prod_serv || '-'"></td>

                                    <td class="px-4 py-3" x-text="concepto.clave_unidad || '-'"></td>

                                    <td class="px-4 py-3">
                                        <span class="font-medium text-[#0B1F3A]" x-text="concepto.descripcion"></span>
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        $<span x-text="Number(concepto.valor_unitario || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></span>
                                    </td>

                                    <td class="px-4 py-3 text-right font-semibold">
                                        $<span x-text="Number(concepto.importe || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></span>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="!factura.conceptos || factura.conceptos.length === 0">
                                <td colspan="6" class="px-4 py-6 text-center text-slate-400">
                                    No hay conceptos registrados para este CFDI.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- TOTALES --}}
                <div class="flex justify-end">
                    <div class="w-full md:w-80 rounded-xl border border-slate-200 overflow-hidden">
                        <div class="flex justify-between px-4 py-3 border-b border-slate-100">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="font-semibold">
                                $<span x-text="Number(factura.subtotal || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></span>
                            </span>
                        </div>

                        <div class="flex justify-between px-4 py-3 border-b border-slate-100">
                            <span class="text-slate-500">Descuento</span>
                            <span class="font-semibold">
                                $<span x-text="Number(factura.descuento || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></span>
                            </span>
                        </div>

                        <div class="flex justify-between px-4 py-4 bg-[#0B1F3A] text-white">
                            <span class="font-bold">Total</span>
                            <span class="font-bold text-lg">
                                $<span x-text="Number(factura.total || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></span>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
    {{-- MODAL PROGRAMAR / VER PROGRAMACIÓN --}}
<div
    x-show="programarModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm"
    style="display: none;"
>
    <div
        @click.away="closeModal()"
        class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden"
    >
        <form action="{{ route('programacion-pagos.store') }}" method="POST">
            @csrf

            <input type="hidden" name="sat_cfdi_id" :value="selectedCfdi.id">

            {{-- Cabecera --}}
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg">
                    <span x-show="!selectedCfdi.programado">Programar pago</span>
                    <span x-show="selectedCfdi.programado">Programación registrada</span>
                </h3>

                <button
                    type="button"
                    @click="closeModal()"
                    class="text-slate-400 hover:text-slate-600"
                >
                    ✕
                </button>
            </div>

            {{-- Cuerpo --}}
            <div class="p-6 space-y-4">
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100 text-sm">
                    <p class="text-blue-800 font-medium" x-text="selectedCfdi.proveedor"></p>

                    <p class="text-blue-600 text-xs mt-1">
                        UUID:
                        <span x-text="selectedCfdi.uuid ? selectedCfdi.uuid.substring(0, 18) + '...' : 'Sin UUID'"></span>
                    </p>

                    <p class="text-blue-900 font-bold mt-2 text-base">
                        Total:
                        $<span x-text="Number(selectedCfdi.total || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                    </p>
                </div>

                {{-- FORMULARIO NUEVA PROGRAMACIÓN --}}
                <div x-show="!selectedCfdi.programado" class="space-y-4">

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            📅 Fecha sugerida de pago
                        </label>

                        <input
                            type="date"
                            name="fecha_programada"
                            x-model="fechaSugerida"
                            :required="!selectedCfdi.programado"
                            class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                        <p class="text-[10px] text-slate-400 mt-2 italic">
                            * Esta fecha será revisada por gerencia administrativa antes de aprobación.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Observaciones
                        </label>

                        <textarea
                            name="observaciones"
                            rows="3"
                            class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Comentario opcional para administración..."
                        ></textarea>
                    </div>

                </div>

                {{-- HISTORIAL PROGRAMACIÓN --}}
                <div x-show="selectedCfdi.programado" class="space-y-4">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-400 font-bold uppercase">Fecha sugerida de pago</p>
                            <p class="text-lg font-bold text-[#0B1F3A]" x-text="selectedCfdi.fecha_programada || 'N/A'"></p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-400 font-bold uppercase">Estatus actual</p>
                            <p class="text-lg font-bold text-[#0B1F3A]" x-text="(selectedCfdi.estatus || '').replaceAll('_', ' ')"></p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-xs uppercase text-slate-500">
                                    <th class="px-4 py-3 text-left">Paso</th>
                                    <th class="px-4 py-3 text-left">Fecha</th>
                                    <th class="px-4 py-3 text-left">Comentario</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200">
    {{-- PASO 1 --}}
    <tr>
        <td class="px-4 py-3 font-semibold text-slate-700 flex items-center gap-2">
            <span x-show="selectedCfdi.solicitado_at" class="text-emerald-500">✅</span>
            Solicitud gerente de área
        </td>
        <td class="px-4 py-3 text-slate-600" x-text="selectedCfdi.solicitado_at || 'Pendiente'"></td>
        <td class="px-4 py-3 text-slate-500" x-text="selectedCfdi.observaciones || '-'"></td>
    </tr>

    {{-- PASO 2 --}}
    <tr>
        <td class="px-4 py-3 font-semibold text-slate-700 flex items-center gap-2">
            <span x-show="selectedCfdi.revisado_at" class="text-emerald-500">✅</span>
            Revisión administrativa
        </td>
        <td class="px-4 py-3 text-slate-600" x-text="selectedCfdi.revisado_at || 'Pendiente'"></td>
        <td class="px-4 py-3 text-slate-500" x-text="selectedCfdi.comentario_revision || '-'"></td>
    </tr>

    {{-- PASO 3 --}}
    <tr>
        <td class="px-4 py-3 font-semibold text-slate-700 flex items-center gap-2">
            <span x-show="selectedCfdi.aprobado_at" class="text-emerald-500">✅</span>
            Autorización CEO
        </td>
        <td class="px-4 py-3 text-slate-600" x-text="selectedCfdi.aprobado_at || 'Pendiente'"></td>
        <td class="px-4 py-3 text-slate-500" x-text="selectedCfdi.comentario_aprobacion || '-'"></td>
    </tr>
</tbody>
                        </table>
                    </div>

                </div>
            </div>

            {{-- Acciones --}}
            <div class="px-6 py-4 bg-slate-50 flex gap-3 justify-end text-sm">
                <button
                    type="button"
                    @click="closeModal()"
                    class="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-200 rounded-lg transition"
                >
                    Cerrar
                </button>

                <button
                    x-show="!selectedCfdi.programado"
                    type="submit"
                    class="px-5 py-2 font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-md shadow-blue-200 transition"
                >
                    Confirmar programación
                </button>
            </div>
        </form>
    </div>
</div>

</div>

<script>
    function programacionPagos() {
        return {
            programarModalOpen: false,
            verFacturaModalOpen: false,
            
            factura: {
                conceptos: []
            },

          selectedCfdi: {
                id: '',
                uuid: '',
                proveedor: '',
                total: 0,
                fecha_programada: null,
                estatus: '',
                solicitado_at: '',
                revisado_at: '',
                aprobado_at: '',
                observaciones: '',
                comentario_revision: '',
                comentario_aprobacion: '',
                programado: false
            },

            fechaSugerida: '',

            openVerFactura(cfdi) {
                this.factura = cfdi;
                this.verFacturaModalOpen = true;
            },
            closeVerFactura() {
                this.verFacturaModalOpen = false;
            },
            openProgramar(cfdi) {
                this.selectedCfdi = {
                        id: cfdi.id || '',
                        uuid: cfdi.uuid || '',
                        proveedor: cfdi.proveedor || '',
                        total: cfdi.total || 0,
                        fecha_programada: cfdi.fecha_programada || null,
                        estatus: cfdi.estatus || '',
                        solicitado_at: cfdi.solicitado_at || '',
                        revisado_at: cfdi.revisado_at || '',
                        aprobado_at: cfdi.aprobado_at || '',
                        observaciones: cfdi.observaciones || '',
                        comentario_revision: cfdi.comentario_revision || '',
                        comentario_aprobacion: cfdi.comentario_aprobacion || '',
                        programado: cfdi.programado || false
                    };

                this.fechaSugerida = cfdi.fecha_programada || new Date().toISOString().split('T')[0];

                this.programarModalOpen = true;
            },

            closeModal() {
                this.programarModalOpen = false;
            }
        }
    }
</script>

@endsection