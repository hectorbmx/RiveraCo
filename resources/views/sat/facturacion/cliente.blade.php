@extends('layouts.admin')

@section('title', 'Facturacion cliente')

@section('content')
<div x-data="{ tab: 'vigentes' }" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                {{ $cliente->razon_social ?? $cliente->nombre_comercial }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                RFC: {{ $cliente->rfc ?? 'Sin RFC' }} · {{ $cliente->email ?? 'Sin correo' }}
            </p>
        </div>

        <a href="{{ route('sat.facturacion.index') }}"
           class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Volver
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Facturas vigentes</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $facturasVigentes->count() }}</p>
            <p class="text-sm text-slate-500 mt-1">${{ number_format($totalVigente, 2) }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Facturas canceladas</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $facturasCanceladas->count() }}</p>
            <p class="text-sm text-slate-500 mt-1">${{ number_format($totalCancelado, 2) }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Pagos timbrados</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $pagos->where('estado', 'timbrado')->count() }}</p>
            <p class="text-sm text-slate-500 mt-1">${{ number_format($totalPagado, 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex flex-wrap gap-2 border-b border-slate-200 px-5 py-4">
            <button type="button"
                    @click="tab = 'vigentes'"
                    :class="tab === 'vigentes' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-200'"
                    class="rounded-xl px-4 py-2 text-sm font-semibold">
                Vigentes
            </button>
            <button type="button"
                    @click="tab = 'canceladas'"
                    :class="tab === 'canceladas' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-200'"
                    class="rounded-xl px-4 py-2 text-sm font-semibold">
                Canceladas
            </button>
            <button type="button"
                    @click="tab = 'pagos'"
                    :class="tab === 'pagos' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-200'"
                    class="rounded-xl px-4 py-2 text-sm font-semibold">
                Pagos
            </button>
        </div>

        <div x-show="tab === 'vigentes'" class="overflow-x-auto">
            @include('sat.facturacion.partials._cliente_facturas_table', [
                'facturas' => $facturasVigentes,
                'emptyText' => 'No hay facturas vigentes para este cliente.',
            ])
        </div>

        <div x-show="tab === 'canceladas'" x-cloak class="overflow-x-auto">
            @include('sat.facturacion.partials._cliente_facturas_table', [
                'facturas' => $facturasCanceladas,
                'emptyText' => 'No hay facturas canceladas para este cliente.',
            ])
        </div>

        <div x-show="tab === 'pagos'" x-cloak class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-slate-500 uppercase text-xs tracking-wide">
                        <th class="px-5 py-4 text-left">Factura</th>
                        <th class="px-5 py-4 text-left">Fecha pago</th>
                        <th class="px-5 py-4 text-left">UUID pago</th>
                        <th class="px-5 py-4 text-right">Monto</th>
                        <th class="px-5 py-4 text-right">Saldo insoluto</th>
                        <th class="px-5 py-4 text-left">Estado</th>
                        <th class="px-5 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pagos as $pago)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <a href="{{ route('sat.facturacion.show', $pago->factura) }}"
                                   class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                                    {{ $pago->factura->serie }}-{{ $pago->factura->folio }}
                                </a>
                            </td>
                            <td class="px-5 py-4">{{ $pago->fecha_pago?->format('d/m/Y H:i') ?? '---' }}</td>
                            <td class="px-5 py-4 text-xs">{{ $pago->uuid ?? 'Sin UUID' }}</td>
                            <td class="px-5 py-4 text-right font-semibold">${{ number_format($pago->monto, 2) }}</td>
                            <td class="px-5 py-4 text-right">${{ number_format($pago->saldo_insoluto, 2) }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                                    {{ $pago->estado === 'timbrado'
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : 'bg-slate-50 text-slate-700 border border-slate-200' }}">
                                    {{ ucfirst($pago->estado) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('sat.facturacion.pagos.show', $pago) }}"
                                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                    Ver pago
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                No hay pagos registrados para este cliente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
