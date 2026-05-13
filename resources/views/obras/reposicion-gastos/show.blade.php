@extends('layouts.admin')

@section('title', 'Reposicion Gastos')

@section('content')

<div class="max-w-7xl mx-auto px-6 py-8">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <a
                href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'reposicion-gastos']) }}"
                class="text-sm font-semibold text-blue-600 hover:text-blue-800"
            >
                ← Volver a reposición de gastos
            </a>

            <h1 class="text-2xl font-bold text-[#0B1F3A] mt-2">
                Reposición de gastos
            </h1>

            <p class="text-sm text-slate-500">
                Folio REP-{{ str_pad($reposicion->id, 5, '0', STR_PAD_LEFT) }} · {{ $obra->nombre ?? 'Obra' }} -{{   $obra->clave_obra ?? '' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="rounded-full bg-yellow-100 px-4 py-2 text-xs font-bold text-yellow-700 uppercase">
                {{ str_replace('_', ' ', $reposicion->estatus) }}
            </span>
        </div>
    </div>

    {{-- RESUMEN --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase text-slate-400">Tipo</p>
            <p class="text-lg font-bold text-slate-800 mt-1">
                {{ str_replace('_', ' ', ucfirst($reposicion->tipo_reposicion)) }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase text-slate-400">Semana</p>
            <p class="text-lg font-bold text-slate-800 mt-1">
                {{ $reposicion->semana ?? '-' }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase text-slate-400">Conceptos</p>
            <p class="text-lg font-bold text-slate-800 mt-1">
                {{ $reposicion->detalles->count() }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-green-200 p-5 shadow-sm bg-green-50">
            <p class="text-xs font-bold uppercase text-green-600">Total</p>
            <p class="text-2xl font-bold text-green-700 mt-1">
                ${{ number_format($reposicion->total, 2) }}
            </p>
        </div>

    </div>

    <!-- {{-- PARTIDA --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800">
                Partida relacionada
            </h3>
        </div>

        <div class="p-5">
            <div class="font-bold text-slate-800">
                {{ $reposicion->partida->partida ?? 'SIN PARTIDA' }}
            </div>

            <div class="text-sm text-slate-500 mt-1">
                {{ $reposicion->partida->concepto ?? '-' }}
            </div>

            <div class="text-xs text-slate-400 mt-2">
                Semana planeada: {{ $reposicion->partida->numero_semana ?? '-' }}
            </div>
        </div>
    </div> -->

    {{-- TIMELINE --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800">
                Trazabilidad
            </h3>
            <p class="text-xs text-slate-500">
                Fechas y usuarios del flujo de autorización.
            </p>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <p class="text-xs font-bold uppercase text-blue-600">Solicitado</p>
                    <p class="font-bold text-slate-800 mt-1">
                        {{ optional($reposicion->solicitado_at)->format('d/m/Y H:i') ?? '-' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $reposicion->solicitadoPor->name ?? 'Sin usuario' }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-bold uppercase text-slate-400">Revisado</p>
                    <p class="font-bold text-slate-800 mt-1">
                        {{ optional($reposicion->revisado_at)->format('d/m/Y H:i') ?? 'Pendiente' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $reposicion->revisadoPor->name ?? '-' }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-bold uppercase text-slate-400">Aprobado</p>
                    <p class="font-bold text-slate-800 mt-1">
                        {{ optional($reposicion->aprobado_at)->format('d/m/Y H:i') ?? 'Pendiente' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $reposicion->aprobadoPor->name ?? '-' }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-bold uppercase text-slate-400">Pagado</p>
                    <p class="font-bold text-slate-800 mt-1">
                        {{ optional($reposicion->pagado_at)->format('d/m/Y H:i') ?? 'Pendiente' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $reposicion->pagadoPor->name ?? '-' }}
                    </p>
                </div>

            </div>
        </div>
    </div>

    {{-- DETALLES --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="p-4 bg-slate-50 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800">
                Conceptos de la reposición
            </h3>
            <p class="text-xs text-slate-500">
                Facturas, viáticos o gastos agregados a esta solicitud.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
    <thead class="bg-slate-100">
        <tr>
            <th class="p-3 border text-left">Tipo</th>
            <th class="p-3 border text-left">Partida</th>
            <th class="p-3 border text-left">Proveedor / Descripción</th>
            <th class="p-3 border text-left">RFC</th>
            <th class="p-3 border text-left">UUID</th>
            <th class="p-3 border text-left">Fecha</th>
            <th class="p-3 border text-right">Monto</th>
        </tr>
    </thead>

    <tbody>
        @forelse($reposicion->detalles as $detalle)

            @php
                $partida = $detalle->partida;
            @endphp

            <tr class="hover:bg-slate-50">

                {{-- TIPO --}}
                <td class="p-3 border">
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                        {{ $detalle->tipo ?? '-' }}
                    </span>
                </td>

                {{-- PARTIDA --}}
                <td class="p-3 border">
                    @if($partida)
                        <div class="font-semibold text-slate-800">
                            {{ $partida->partida }}
                        </div>

                        <div class="text-xs text-slate-500">
                            {{ $partida->concepto }}
                        </div>
                    @else
                        <span class="text-slate-400 text-xs">
                            Sin partida
                        </span>
                    @endif
                </td>

                {{-- PROVEEDOR / DESCRIPCIÓN --}}
                <td class="p-3 border">
                    <div class="font-semibold text-slate-800">
                        {{ $detalle->proveedor ?? '-' }}
                    </div>

                    <div class="text-xs text-slate-500">
                        {{ $detalle->descripcion ?? '-' }}
                    </div>
                </td>

                {{-- RFC --}}
                <td class="p-3 border text-xs text-slate-600">
                    {{ $detalle->rfc ?? '-' }}
                </td>

                {{-- UUID --}}
                <td class="p-3 border text-xs text-slate-500">
                    {{ $detalle->uuid ?? '-' }}
                </td>

                {{-- FECHA --}}
                <td class="p-3 border">
                    {{ optional($detalle->fecha)->format('d/m/Y') ?? '-' }}
                </td>

                {{-- MONTO --}}
                <td class="p-3 border text-right font-bold text-slate-800">
                    ${{ number_format($detalle->monto, 2) }}
                </td>

            </tr>

        @empty
            <tr>
                <td colspan="7" class="p-8 text-center text-slate-400">
                    No hay conceptos agregados a esta reposición.
                </td>
            </tr>
        @endforelse
    </tbody>

    <tfoot class="bg-slate-50">
        <tr>
            <td colspan="6" class="p-3 border text-right font-bold text-slate-700">
                Total
            </td>

            <td class="p-3 border text-right font-bold text-green-700">
                ${{ number_format($reposicion->total, 2) }}
            </td>
        </tr>
    </tfoot>
</table>

<div class="mt-6 flex justify-end items-center gap-3">

    {{-- BOTÓN: PROGRAMAR (Azul Corporativo) --}}
    @if($reposicion->estatus === 'solicitado' && auth()->user()->can('reposicion_gastos.programar.access'))
        <button
            type="button"
            onclick="document.getElementById('modalProgramarReposicion').classList.remove('hidden')"
            class="inline-flex items-center gap-2 rounded-xl bg-blue-600/90 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition-all duration-200 hover:bg-blue-600 hover:scale-[1.02] focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900 active:scale-95"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Programar
        </button>
    @endif

    {{-- BOTÓN: APROVISIONAR (Esmeralda / Éxito) --}}
    @if($reposicion->estatus === 'programado_area' && auth()->user()->can('reposicion_gastos.aprovisionar.access'))
        <button
            type="button"
            onclick="document.getElementById('modalAprovisionarReposicion').classList.remove('hidden')"
            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600/90 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition-all duration-200 hover:bg-emerald-600 hover:scale-[1.02] focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-900 active:scale-95"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Aprovisionar
        </button>
    @endif
    {{-- BOTÓN: AUTORIZAR / RECHAZAR (CEO / Dirección) --}}
    @if($reposicion->estatus === 'pendiente_autorizacion' && auth()->user()->can('reposicion_gastos.autorizar.access'))
        <button
            type="button"
            onclick="document.getElementById('modalAutorizarReposicion').classList.remove('hidden')"
            class="inline-flex items-center gap-2 rounded-xl bg-amber-500/90 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-900/20 transition-all duration-200 hover:bg-amber-500 hover:scale-[1.02] focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-900 active:scale-95"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Autorizar
        </button>
    @endif
    {{-- BOTÓN: EXPORTAR PDF (Rojo Elegante) --}}
    <a
        href="{{ route('obras.reposicion-gastos.pdf', [$obra, $reposicion]) }}"
        target="_blank"
        class="inline-flex items-center gap-2 rounded-xl bg-slate-800 border border-slate-700 px-5 py-2.5 text-sm font-bold text-slate-200 shadow-xl transition-all duration-200 hover:bg-red-600 hover:text-white hover:border-red-500 hover:scale-[1.02] active:scale-95"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
        Exportar PDF
    </a>
</div>
        </div>
    </div>
<div id="modalProgramarReposicion" class="hidden fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center px-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
        <form method="POST" action="{{ route('obras.reposicion-gastos.programar', [$obra, $reposicion]) }}">
            @csrf
            @method('PATCH')

            <div class="px-6 py-4 bg-slate-50 border-b flex justify-between items-center">
                <h3 class="font-bold text-slate-800 text-lg">Programar reposición</h3>

                <button
                    type="button"
                    onclick="document.getElementById('modalProgramarReposicion').classList.add('hidden')"
                    class="text-slate-400 hover:text-slate-600"
                >
                    ✕
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Fecha programada de pago
                    </label>

                    <input
                        type="date"
                        name="fecha_programada_pago"
                        required
                        class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Comentarios de revisión
                    </label>

                    <textarea
                        name="comentarios_revision"
                        rows="3"
                        class="w-full rounded-xl border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Comentarios del área administrativa..."
                    ></textarea>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3">
                <button
                    type="button"
                    onclick="document.getElementById('modalProgramarReposicion').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-200"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700"
                >
                    Guardar programación
                </button>
            </div>
        </form>
    </div>
</div>
    {{-- OBSERVACIONES --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800">
                Observaciones
            </h3>
        </div>

        <div class="p-5 text-sm text-slate-600">
            {{ $reposicion->observaciones ?? 'Sin observaciones.' }}
        </div>
    </div>

</div>
{{-- MODAL APROVISIONAR REPOSICIÓN --}}
<div
    id="modalAprovisionarReposicion"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4 transition-all duration-300"
>
    <div class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl ring-1 ring-slate-200 overflow-hidden transform transition-all">

        {{-- Header con gradiente sutil --}}
        <div class="flex items-center justify-between bg-slate-50/50 border-b border-slate-100 px-8 py-6">
            <div>
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                    Aprovisionar reposición
                </h3>
                <p class="text-sm text-slate-500 mt-1">
                    Define la cuenta, método de pago y fecha tentativa de salida.
                </p>
            </div>

            <button
                type="button"
                onclick="document.getElementById('modalAprovisionarReposicion').classList.add('hidden')"
                class="group rounded-full p-2 text-slate-400 hover:bg-slate-200/50 hover:text-slate-600 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form
            method="POST"
            action="{{ route('obras.reposicion-gastos.aprovisionar', [$obra, $reposicion]) }}"
            class="px-8 py-6"
        >
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Cuenta Bancaria --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1">
                        Cuenta bancaria
                    </label>
                    <select
                        name="cuenta_banco_empresa_id"
                        required
                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 py-3 px-4 text-sm text-slate-700 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all cursor-pointer"
                    >
                        <option value="">Seleccione una cuenta</option>
                        @foreach($cuentasBanco as $cuenta)
                            <option value="{{ $cuenta->id }}">
                                {{ $cuenta->banco }} - {{ $cuenta->nombre }}
                                @if($cuenta->moneda) ({{ $cuenta->moneda }}) @endif
                                @if($cuenta->principal) - Principal @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Método de Pago --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1">
                        Método de pago
                    </label>
                    <select
                        name="metodo_pago_empresa_id"
                        required
                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 py-3 px-4 text-sm text-slate-700 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all cursor-pointer"
                    >
                        <option value="">Seleccione método</option>
                        @foreach($metodosPago as $metodo)
                            <option value="{{ $metodo->id }}">
                                {{ $metodo->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Fecha Tentativa --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1">
                        Fecha tentativa de salida
                    </label>
                    <input
                        type="date"
                        name="fecha_salida_programada"
                        required
                        value="{{ old('fecha_salida_programada', optional($reposicion->fecha_programada_pago)->format('Y-m-d')) }}"
                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 py-3 px-4 text-sm text-slate-700 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all"
                    >
                </div>

                {{-- Total (Visual Only) --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1">
                        Total a aprovisionar
                    </label>
                    <div class="flex items-center h-[46px] rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/30 px-4 text-lg font-black text-emerald-700">
                        <span class="text-sm mr-1 font-normal opacity-70">$</span>
                        {{ number_format($reposicion->total, 2) }}
                    </div>
                </div>

            </div>

            {{-- Comentarios --}}
            <div class="mt-6 space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1">
                    Comentarios administrativos
                </label>
                <textarea
                    name="comentarios_aprovisionamiento"
                    rows="3"
                    class="w-full rounded-2xl border-slate-200 bg-slate-50/50 py-3 px-4 text-sm text-slate-700 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all resize-none"
                    placeholder="Ejemplo: Se pagará desde cuenta principal por transferencia SPEI..."
                >{{ old('comentarios_aprovisionamiento') }}</textarea>
            </div>

            {{-- Footer con acciones --}}
            <div class="mt-8 flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <button
                    type="button"
                    onclick="document.getElementById('modalAprovisionarReposicion').classList.add('hidden')"
                    class="rounded-xl px-6 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-100 transition-all active:scale-95"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-8 py-2.5 text-sm font-black text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 hover:scale-[1.02] active:scale-95 transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Confirmar aprovisionamiento
                </button>
            </div>
        </form>
    </div>
</div>
{{-- MODAL AUTORIZAR / RECHAZAR --}}
<div
    id="modalAutorizarReposicion"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4 transition-all duration-300"
>
    <div class="w-full max-w-2xl rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200 overflow-hidden transform transition-all">

        {{-- HEADER --}}
        <div class="bg-slate-50/50 border-b border-slate-100 px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black text-slate-900 tracking-tight">
                        Autorización de reposición 🔐
                    </h3>
                    <p class="text-sm text-slate-500 mt-1 font-medium">
                        Revisión final de dirección y administración para liberación de fondos.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="document.getElementById('modalAutorizarReposicion').classList.add('hidden')"
                    class="group rounded-full p-2 text-slate-400 hover:bg-slate-200/50 hover:text-slate-600 transition-all"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- BODY --}}
        <div class="px-8 py-6">

            {{-- RESUMEN DE DATOS CRÍTICOS --}}
            <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">

                <div class="rounded-2xl border border-amber-100 bg-amber-50/30 p-4 ring-1 ring-amber-200/50">
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-600/80">Total a Pagar</p>
                    <p class="mt-1 text-2xl font-black text-amber-700">
                        <span class="text-sm font-normal">$</span>{{ number_format($reposicion->total, 2) }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Cuenta Origen</p>
                    <p class="mt-1 text-sm font-bold text-slate-800 truncate">
                        {{ $reposicion->cuentaBancoEmpresa->banco ?? 'N/D' }}
                    </p>
                    <p class="text-[11px] text-slate-500 font-medium truncate">
                        {{ $reposicion->cuentaBancoEmpresa->nombre ?? 'Sin especificar' }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Método de Pago</p>
                    <p class="mt-1 text-sm font-bold text-slate-800">
                        {{ $reposicion->metodoPagoEmpresa->nombre ?? 'N/D' }}
                    </p>
                    <p class="text-[11px] text-slate-500 font-medium">
                        Salida programada
                    </p>
                </div>

            </div>

            {{-- FORM AUTORIZAR --}}
            <form
                id="formAutorizarReposicion"
                method="POST"
                action="{{ route('obras.reposicion-gastos.autorizar', [$obra, $reposicion]) }}"
            >
                @csrf
                @method('PATCH')

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1">
                        Comentarios de autorización / Instrucciones
                    </label>

                    <textarea
                        name="comentarios_autorizacion"
                        rows="3"
                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 py-4 px-5 text-sm text-slate-700 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all resize-none shadow-inner"
                        placeholder="Escribe aquí cualquier observación relevante para tesorería..."
                    ></textarea>
                </div>
            </form>
        </div>

        {{-- FOOTER CON ACCIONES DIFERENCIADAS --}}
        <div class="flex items-center justify-between bg-slate-50/30 border-t border-slate-100 px-8 py-6">

            {{-- ACCIÓN DE RECHAZO (LADO IZQUIERDO) --}}
            <form
                method="POST"
                action="{{ route('obras.reposicion-gastos.rechazar', [$obra, $reposicion]) }}"
                onsubmit="return confirm('¿Estás seguro de que deseas rechazar esta solicitud? Esta acción no se puede deshacer.')"
            >
                @csrf
                @method('PATCH')

                <input type="hidden" name="comentarios_autorizacion" value="Solicitud rechazada por Dirección Administrativa.">

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-red-500 hover:bg-red-50 transition-all active:scale-95 group"
                >
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Rechazar Solicitud
                </button>
            </form>

            {{-- ACCIONES DE CONFIRMACIÓN (LADO DERECHO) --}}
            <div class="flex items-center gap-4">
                <button
                    type="button"
                    onclick="document.getElementById('modalAutorizarReposicion').classList.add('hidden')"
                    class="rounded-xl px-6 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-100 transition-all active:scale-95"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    form="formAutorizarReposicion"
                    class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-8 py-2.5 text-sm font-black text-white shadow-lg shadow-amber-500/30 hover:bg-amber-600 hover:scale-[1.02] transition-all active:scale-95"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Autorizar Pago
                </button>
            </div>

        </div>
    </div>
</div>
@endsection