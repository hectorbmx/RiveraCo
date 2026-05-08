@extends('layouts.admin')

@section('title', 'Pagos programados')

@section('content')
@php
    $inicioSemana = \Carbon\Carbon::parse($desde);
    $finSemana = \Carbon\Carbon::parse($hasta);

    $semanaAnterior = $inicioSemana->copy()->subWeek()->toDateString();
    $semanaSiguiente = $inicioSemana->copy()->addWeek()->toDateString();

    $totalSemana = $pagos->sum('monto');
@endphp

<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">
                Programación de pagos
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Pagos programados del {{ $inicioSemana->format('d/m/Y') }} al {{ $finSemana->format('d/m/Y') }}
            </p>
        </div>

        <a href="{{ route('proveedores.index') }}"
           class="text-sm text-slate-500 hover:text-slate-800">
            ← Volver a proveedores
        </a>
    </div>

    {{-- Filtros semana --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-5">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <form method="GET" action="{{ route('proveedores.pagos-programados') }}" class="flex items-end gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">
                        Semana
                    </label>
                    <input type="date"
                           name="desde"
                           value="{{ $desde }}"
                           class="rounded-xl border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">
                </div>

                <button type="submit"
                        class="rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12387f]">
                    Ver semana
                </button>
            </form>

            <div class="flex items-center gap-2">
                <a href="{{ route('proveedores.pagos-programados', ['desde' => $semanaAnterior]) }}"
                   class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    ← Semana anterior
                </a>

                <a href="{{ route('proveedores.pagos-programados', ['desde' => now()->startOfWeek()->toDateString()]) }}"
                   class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Semana actual
                </a>

                <a href="{{ route('proveedores.pagos-programados', ['desde' => $semanaSiguiente]) }}"
                   class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Semana siguiente →
                </a>
            </div>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <p class="text-xs text-slate-500">Total semana</p>
            <p class="mt-1 text-2xl font-bold text-[#0B265A]">
                ${{ number_format((float) $totalSemana, 2) }}
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <p class="text-xs text-slate-500">Programado</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">
                ${{ number_format((float) $totalProgramado, 2) }}
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <p class="text-xs text-slate-500">Pagado</p>
            <p class="mt-1 text-2xl font-bold text-green-700">
                ${{ number_format((float) $totalPagado, 2) }}
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <p class="text-xs text-slate-500">Cancelado</p>
            <p class="mt-1 text-2xl font-bold text-red-600">
                ${{ number_format((float) $totalCancelado, 2) }}
            </p>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-[#0B265A]">
                    Pagos de la semana
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Incluye pagos programados, pagados y cancelados dentro del rango seleccionado.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Proveedor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Factura</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Relación</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">Monto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Estatus</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($pagos as $pago)
                        @php
                            $cfdi = $pago->cfdi;
                            $estatus = $pago->estatus;

                            $badgeClass = match($estatus) {
                                'pagado' => 'bg-green-50 text-green-700 border-green-200',
                                'cancelado' => 'bg-red-50 text-red-700 border-red-200',
                                default => 'bg-amber-50 text-amber-700 border-amber-200',
                            };
                        @endphp

                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $pago->fecha_pago ? $pago->fecha_pago->format('d/m/Y') : '-' }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">
                                    {{ $cfdi->emisor_nombre ?? '-' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $cfdi->rfc_emisor ?? '-' }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-mono text-xs text-slate-700 max-w-[240px] truncate"
                                     title="{{ $cfdi->uuid ?? $pago->cfdi_uuid }}">
                                    {{ $cfdi->uuid ?? $pago->cfdi_uuid }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    Total CFDI:
                                    ${{ number_format((float)($cfdi->total ?? 0), 2) }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                @if($cfdi && $cfdi->ordenCompra)
                                    <span class="inline-flex rounded-lg bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 border border-blue-200">
                                        OC: {{ $cfdi->ordenCompra->folio ?? 'OC #'.$cfdi->ordenCompra->id }}
                                    </span>
                                @elseif($cfdi && $cfdi->obra)
                                    <span class="inline-flex rounded-lg bg-purple-50 px-2 py-1 text-xs font-semibold text-purple-700 border border-purple-200">
                                        Obra: {{ $cfdi->obra->nombre ?? $cfdi->obra->Nombre ?? 'Obra #'.$cfdi->obra->id }}
                                    </span>
                                @else
                                    <span class="inline-flex rounded-lg bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-500 border border-slate-200">
                                        Sin relación
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap font-bold">
                                ${{ number_format((float)$pago->monto, 2) }}
                                <div class="text-xs text-slate-500 font-normal">
                                    {{ $pago->moneda ?? 'MXN' }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold border {{ $badgeClass }}">
                                    {{ ucfirst($estatus) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($cfdi)
                                    <a href="{{ route('proveedores.facturas.show', [$cfdi->rfc_emisor ? optional(\App\Models\Proveedor::where('rfc', $cfdi->rfc_emisor)->first())->id : null, $cfdi->id]) }}"
                                       class="text-blue-600 hover:text-blue-800 font-semibold text-xs">
                                        Abrir factura
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">Sin CFDI</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                No hay pagos programados para esta semana.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection