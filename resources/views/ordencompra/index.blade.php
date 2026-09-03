@extends('layouts.admin')

@section('content')
<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-[#0B265A]">Órdenes de compra</h1>
        <a href="{{ route('ordenes_compra.create') }}"
           class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
            + Nueva orden
        </a>
    </div>
    {{-- BUSCADOR Y FILTROS --}}
    @php
        $areaCodigoActual = strtoupper(trim((string) request('area_codigo')));
        $estadoFiltroOpciones = [
            '' => 'Todos',
            'autorizada' => 'Autorizada',
            'programada' => 'Programada',
            'por autorizar' => 'Por autorizar',
        ];
        $areaFiltroOpciones = ['' => 'Todas las areas'];

        foreach ($areas as $areaItem) {
            $areaFiltroOpciones[$areaItem->id] = trim(($areaItem->codigo ? $areaItem->codigo . ' - ' : '') . $areaItem->nombre);
        }

        $limpiarFiltrosUrl = $areaCodigoActual === 'GL'
            ? route('ordenes_compra.index', ['area_codigo' => 'GL'])
            : route('ordenes_compra.index');
    @endphp

    <x-filters.card action="{{ route('ordenes_compra.index') }}" class="mb-6">
        @if($areaCodigoActual !== '')
            <input type="hidden" name="area_codigo" value="{{ $areaCodigoActual }}">
        @endif

        @if(request('semana'))
            <input type="hidden" name="semana" value="{{ request('semana') }}">
        @endif

        <x-filters.input
            name="search"
            label="Buscar"
            :value="$search ?? ''"
            placeholder="Proveedor, razon social o RFC..."
            span="{{ $areaCodigoActual === 'GL' ? 'md:col-span-7' : 'md:col-span-5' }}"
            type="search"
            glow />

        @if($areaCodigoActual !== 'GL')
            <x-filters.select
                name="area_id"
                label="Area"
                :value="request('area_id', '')"
                :options="$areaFiltroOpciones"
                span="md:col-span-2 md:max-w-56" />
        @endif

        <x-filters.select
            name="estado"
            label="Estado"
            :value="$estado ?? ''"
            :options="$estadoFiltroOpciones"
            span="md:col-span-2 md:max-w-48" />

        <x-filters.actions
            submit-label="Filtrar"
            :clear-url="$limpiarFiltrosUrl"
            span="md:col-span-3" />
    </x-filters.card>
@if (request('area_codigo') === 'GL')
    @php
        $filtrosSemana = request()->except([
            'page',
            'semana',
        ]);
    @endphp

    <div class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">

        {{-- Navegador de semanas --}}
        <div class="flex items-center gap-3">

            {{-- Semana anterior --}}
            <a
                href="{{ route('ordenes_compra.index', array_merge(
                    $filtrosSemana,
                    ['semana' => $semanaAnterior]
                )) }}"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition-colors hover:bg-slate-100"
                title="Semana anterior"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
            </a>

            {{-- Periodo seleccionado --}}
            <div class="min-w-[230px] text-center">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Semana seleccionada
                </p>

                <p class="text-sm font-semibold text-slate-800">
                    {{ $inicioSemana->format('d/m/Y') }}
                    al
                    {{ $finSemana->format('d/m/Y') }}
                </p>
            </div>

            {{-- Semana siguiente --}}
            @if ($semanaSiguiente)
                <a
                    href="{{ route('ordenes_compra.index', array_merge(
                        $filtrosSemana,
                        ['semana' => $semanaSiguiente]
                    )) }}"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition-colors hover:bg-slate-100"
                    title="Semana siguiente"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </a>
            @else
                <span
                    class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-slate-300"
                    title="Ya estás en la semana actual"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </span>
            @endif
        </div>

        @if($resumenSemanaGl)
            <div class="grid min-w-[260px] grid-cols-2 gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600 lg:min-w-[390px] lg:grid-cols-4">
                <div>
                    <p class="font-medium uppercase text-slate-400">Total GL</p>
                    <p class="text-sm font-bold text-[#0B265A]">${{ number_format($resumenSemanaGl['total_acumulado'], 2) }}</p>
                </div>

                <div>
                    <p class="font-medium uppercase text-slate-400">Autorizada</p>
                    <p class="text-sm font-bold text-amber-700">${{ number_format($resumenSemanaGl['total_pendiente_verificar'], 2) }}</p>
                    <p class="text-[11px] text-slate-400">{{ $resumenSemanaGl['pendientes_verificar'] }} OC</p>
                </div>

                <div>
                    <p class="font-medium uppercase text-slate-400">Verificado</p>
                    <p class="text-sm font-bold text-teal-700">${{ number_format($resumenSemanaGl['total_verificado'], 2) }}</p>
                    <p class="text-[11px] text-slate-400">{{ $resumenSemanaGl['verificadas'] }} OC</p>
                </div>

                <div>
                    <p class="font-medium uppercase text-slate-400">Reposición</p>
                    <p class="text-sm font-bold text-slate-800">{{ $resumenSemanaGl['reposicion_sugerida']->format('d/m/Y') }}</p>
                </div>
            </div>
        @endif

        {{-- Acciones --}}
        <div class="flex flex-wrap items-center gap-3">

            {{-- Volver a semana actual --}}
            @if (!$esSemanaActual)
                <a
                    href="{{ route('ordenes_compra.index', $filtrosSemana) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-100"
                >
                    Semana actual
                </a>
            @endif

            {{-- Exportar efectivo --}}
            <a
                href="{{ route('ordenes_compra.exportar_pagos', [
                    'formaPago' => '01',
                    'area_codigo' => request('area_codigo'),
                    'semana' => $inicioSemana->format('Y-m-d'),
                ]) }}"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 shadow-sm transition-colors hover:bg-emerald-100"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>

                Exportar efectivo
            </a>

            {{-- Exportar tarjeta --}}
            <a
                href="{{ route('ordenes_compra.exportar_pagos', [
                    'formaPago' => '04',
                    'area_codigo' => request('area_codigo'),
                    'semana' => $inicioSemana->format('Y-m-d'),
                ]) }}"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 shadow-sm transition-colors hover:bg-indigo-100"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>

                Exportar TC
            </a>

            {{-- Exportar gastos sin factura --}}
            <a
                href="{{ route('ordenes_compra.exportar_gastos_sin_factura', [
                    'area_codigo' => request('area_codigo'),
                    'semana' => $inicioSemana->format('Y-m-d'),
                ]) }}"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-lg border border-purple-200 bg-purple-50 px-4 py-2 text-sm font-medium text-purple-700 shadow-sm transition-colors hover:bg-purple-100"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>

                Exportar sin factura
            </a>

            {{-- Imprimir caratula --}}
            <a
                href="{{ route('ordenes_compra.caratula_giralda', [
                    'area_codigo' => request('area_codigo'),
                    'semana' => $inicioSemana->format('Y-m-d'),
                ]) }}"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-100"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6M7 4h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"
                    />
                </svg>

                Imprimir caratula
            </a>
        </div>
    </div>
@endif
<div class="bg-white rounded-2xl shadow overflow-hidden">
    @php
        $estadoBadge = function ($estado) {
            $estado = strtolower(trim((string) $estado));

            return match ($estado) {
                'autorizada', 'autorizado' => 'bg-green-100 text-green-800 border-green-200',
                'verificada', 'verificado' => 'bg-teal-100 text-teal-800 border-teal-200',
                'cancelada', 'cancelado' => 'bg-red-100 text-red-800 border-red-200',
                'borrador' => 'bg-gray-100 text-gray-800 border-gray-200',
                'pendiente' => 'bg-amber-100 text-amber-800 border-amber-200',
                default => 'bg-slate-100 text-slate-800 border-slate-200',
            };
        };
    @endphp

    <div class="bg-white rounded-2xl shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b text-slate-500 font-medium">
                    <th class="py-3 px-4 text-center">Folio</th>
                    <th class="py-3 px-4 text-left">Proveedor</th>
                    <th class="py-3 px-4 text-left">Área</th>
                    <th class="py-3 px-4 text-left">Destino</th>
                    <th class="py-3 px-4 text-center">Fecha</th>
                    <th class="py-3 px-4 text-center">Estado</th>
                    <th class="py-3 px-4 text-center">F.Pago</th>
                    <th class="py-3 px-4 text-right">Total</th>
                    <th class="py-3 px-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($ordenes as $oc)
                <tr class="border-b hover:bg-slate-50 transition">
                                        <td class="py-3 px-4 text-center font-medium">
                        <div>{{ $oc->folio }}</div>
                        <div class="flex flex-col items-center gap-1 mt-1">
                            @if($oc->es_caja_chica)
                                <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-700">Caja chica</span>
                            @endif
                            @if($oc->gastos_sin_factura)
                                <span class="inline-flex rounded-full border border-purple-200 bg-purple-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-purple-700">Sin Factura</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        @if($oc->proveedor)
                            <div class="flex flex-col">
                                <a href="{{ route('proveedores.show', ['proveedor' => $oc->proveedor->id, 'tab' => 'general']) }}"
                                   class="font-semibold text-blue-700 hover:text-blue-900 hover:underline">
                                    {{ $oc->proveedor->nombre }}
                                </a>
                                @if($oc->proveedor->rfc)
                                    <span class="text-xs text-slate-400">{{ $oc->proveedor->rfc }}</span>
                                @endif
                            </div>
                        @else
                            @if($oc->es_caja_chica || $oc->gastos_sin_factura)
                                <span class="text-xs font-semibold text-slate-500">Sin proveedor</span>
                            @else
                                -
                            @endif
                        @endif
                    </td>
                    
                    <td class="py-3 px-4">{{ $oc->areaCatalogo->nombre ?? $oc->area }}</td>
                    <td class="py-3 px-4">
                        @if($oc->obra)
                            <span class="text-slate-700">{{ $oc->obra->nombre }}</span>
                        @elseif($oc->centroCosto)
                            <span class="text-slate-700">{{ $oc->centroCosto->codigo ? $oc->centroCosto->codigo . ' - ' : '' }}{{ $oc->centroCosto->nombre }}</span>
                        @else
                            <span class="text-slate-400">Compra general</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center text-slate-600">{{ $oc->fecha->format('d/m/Y') }}</td>
                    <td class="py-3 px-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $estadoBadge($oc->estado_normalizado) }}">
                            {{ ucfirst($oc->estado) }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center text-slate-600">
    @php
        $formaPago = match ((string) $oc->forma_pago) {
            '01' => 'Efectivo',
            '02' => 'Cheque nominativo',
            '03' => 'Transferencia electrónica',
            '04' => 'Tarjeta de crédito',
            '28' => 'Tarjeta de débito',
            '99' => 'Por definir',
            default => 'Sin definir',
        };
    @endphp

    {{ $formaPago }}
</td>

                    <td class="py-3 px-4 text-right font-bold text-[#0B265A]">${{ number_format($oc->total,2) }}</td>
                    <td class="py-3 px-4">
                        <div class="flex items-center justify-end gap-3">
                        {{-- Imprimir --}}
                                @canany(['ordenes_compra.print.access', 'ordenes_compra.imprimir'])
                                    <a href="{{ route('ordenes_compra.print', $oc->id) }}"
                                    target="_blank"
                                    class="text-slate-400 hover:text-slate-600 transition"
                                    title="Imprimir OC">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6v-8z" />
                                        </svg>
                                    </a>
                                @endcanany

                            {{-- Abrir --}}
                            <a href="{{ route('ordenes_compra.edit',$oc->id) }}"
                            class="text-blue-600 hover:text-blue-800 font-medium text-sm transition">
                                Editar
                            </a>

                            @if(request('area_codigo') === 'GL' && $oc->es_caja_chica && $oc->estado_normalizado === 'autorizada')
                                @can('ordenes_compra.verify.access')
                                    <form method="POST" action="{{ route('ordenes_compra.verificar', $oc->id) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="text-teal-600 hover:text-teal-800 font-medium text-sm transition"
                                                onclick="return confirm('¿Verificar la orden {{ $oc->folio }} para el corte semanal?');">
                                            Verificar
                                        </button>
                                    </form>
                                @endcan
                            @endif

                            @if(! $oc->es_caja_chica && $oc->estado_normalizado === 'autorizada' && !$oc->pagoProveedorActivo && auth()->user()?->can('pagos_proveedores.schedule.access'))
                                <a href="{{ route('pagos-proveedores.create', ['orden_compra_id' => $oc->id]) }}"
                                   class="text-amber-600 hover:text-amber-800 font-medium text-sm transition">
                                    Pagar
                                </a>
                            @endif

                            @php
                                $estadoNorm = strtolower(trim((string) ($oc->estado ?? 'borrador')));
                                $confirmarSobregiroCivil = (int) session('civil_sobregiro_confirm_oc_id') === (int) $oc->id;
                            @endphp

                            @if(!in_array($estadoNorm, ['autorizada','autorizado','verificada','verificado','cancelada','cancelado']))
                                @canany(['ordenes_compra.authorize.access', 'ordenes_compra.autorizar'])
                                    <form method="POST" action="{{ route('ordenes_compra.autorizar', $oc->id) }}" class="inline">
                                        @csrf
                                        @if($confirmarSobregiroCivil)
                                            <input type="hidden" name="confirmar_sobregiro_civil" value="1">
                                        @endif
                                        <button type="submit"
                                                class="{{ $confirmarSobregiroCivil ? 'text-amber-700 hover:text-amber-900' : 'text-green-600 hover:text-green-800' }} font-medium text-sm transition"
                                                onclick="return confirm('{{ $confirmarSobregiroCivil ? 'La orden excede el disponible civil. Si continuas, el saldo quedara negativo. ¿Autorizar con sobregiro?' : '¿Autorizar la orden ' . $oc->folio . '?' }}');">
                                            {{ $confirmarSobregiroCivil ? 'Autorizar con sobregiro' : 'Autorizar' }}
                                        </button>
                                    </form>
                                @endcanany
                            @endif

                            {{-- Cancelar (solo si NO está cancelada) --}}
                            @if(!in_array($oc->estado_normalizado, ['autorizada','autorizado','verificada','verificado','cancelada','cancelado']) && auth()->user()?->can('ordenes_compra.cancel.access'))
                                <form method="POST" action="{{ route('ordenes_compra.cancelar', $oc->id) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="text-red-500 hover:text-red-700 font-medium text-sm transition"
                                            onclick="return confirm('¿Cancelar la orden {{ $oc->folio }}?');">
                                        Cancelar
                                    </button>
                                </form>
                            @endif

                            @if(!in_array($estadoNorm, ['autorizada','autorizado','verificada','verificado','cancelada','cancelado']) && auth()->user()?->can('delete_ordenes_compra'))
                                <form method="POST" action="{{ route('ordenes_compra.destroy', $oc->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-500 transition hover:text-red-700"
                                            title="Eliminar OC"
                                            onclick="return confirm('¿Eliminar definitivamente la orden {{ $oc->folio }}? Esta acción no se puede deshacer.');">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10" />
                                        </svg>
                                    </button>
                                </form>
                            @endif

                        </div>
                    </td>

                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $ordenes->links() }}
    </div>
</div>
@endsection

