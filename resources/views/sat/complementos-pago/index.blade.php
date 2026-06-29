@extends('layouts.admin')

@section('title', 'Complementos de pago')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Complementos de pago</h1>
            <p class="text-sm text-slate-500 mt-1">
                Consulta de CFDI tipo P generados para facturas PPD.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sat.facturacion.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver a facturas
            </a>
            <a href="{{ route('sat.complementos-pago.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                <span>+</span>
                Agregar pago
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Complementos este mes</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($kpis['timbrados_mes']) }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Monto complementado este mes</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($kpis['monto_timbrado_mes'], 2) }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Facturas PPD pendientes</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($kpis['facturas_pendientes']) }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Monto PPD pendiente</p>
            <p class="text-2xl font-bold text-red-600 mt-1">${{ number_format($kpis['monto_pendiente'], 2) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('sat.complementos-pago.index') }}"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Buscar</label>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="UUID, factura, cliente o RFC"
                       class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-200">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Cliente</label>
                <input type="text"
                       name="cliente"
                       value="{{ $clienteSearch }}"
                       placeholder="Nombre o RFC"
                       class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-200">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Estado</label>
                <select name="estado"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-200">
                    <option value="">Todos</option>
                    @foreach($estados as $estadoOption)
                        <option value="{{ $estadoOption }}" @selected($estado === $estadoOption)>
                            {{ ucfirst($estadoOption) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Desde</label>
                <input type="date"
                       name="fecha_desde"
                       value="{{ $fechaDesde }}"
                       class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-200">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Hasta</label>
                <input type="date"
                       name="fecha_hasta"
                       value="{{ $fechaHasta }}"
                       class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-200">
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-4">
            <a href="{{ route('sat.complementos-pago.index') }}"
               class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Limpiar
            </a>
            <button type="submit"
                    class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Filtrar
            </button>
        </div>
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Pendientes por complementar</h2>
                <p class="text-sm text-slate-500 mt-1">Facturas PPD timbradas con saldo fiscal pendiente.</p>
            </div>
            <span class="text-xs text-slate-500">{{ $facturasPendientes->count() }} factura(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-slate-500 uppercase text-xs tracking-wide">
                        <th class="px-5 py-4 text-left">Factura</th>
                        <th class="px-5 py-4 text-left">Cliente</th>
                        <th class="px-5 py-4 text-left">Obra</th>
                        <th class="px-5 py-4 text-right">Total</th>
                        <th class="px-5 py-4 text-right">Complementado</th>
                        <th class="px-5 py-4 text-right">Saldo</th>
                        <th class="px-5 py-4 text-left">Pago interno</th>
                        <th class="px-5 py-4 text-left">Estado</th>
                        <th class="px-5 py-4 text-left">Pago interno</th>
                        <th class="px-5 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($facturasPendientes as $facturaPendiente)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <a href="{{ route('sat.facturacion.show', $facturaPendiente) }}"
                                   class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                                    {{ trim(($facturaPendiente->serie ? $facturaPendiente->serie . '-' : '') . $facturaPendiente->folio) ?: 'Factura #' . $facturaPendiente->id }}
                                </a>
                                <div class="text-xs text-slate-500 font-mono">{{ $facturaPendiente->uuid }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">
                                    {{ $facturaPendiente->receptor_nombre ?: $facturaPendiente->cliente?->razon_social ?: 'Sin cliente' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $facturaPendiente->receptor_rfc ?: $facturaPendiente->cliente?->rfc ?: '-' }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if($facturaPendiente->obra)
                                    <a href="{{ route('obras.edit', $facturaPendiente->obra) }}"
                                       class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                                        {{ $facturaPendiente->obra->nombre ?? 'Obra #' . $facturaPendiente->obra->id }}
                                    </a>
                                @else
                                    <span class="text-slate-400">Sin obra</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right font-semibold">
                                ${{ number_format($facturaPendiente->total, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right text-emerald-700 font-semibold">
                                ${{ number_format($facturaPendiente->monto_complementado, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right text-amber-700 font-semibold">
                                ${{ number_format($facturaPendiente->saldo_por_complementar, 2) }}
                            </td>
                            <td class="px-5 py-4">
                                @if($facturaPendiente->pagos_internos_pendientes->isNotEmpty())
                                    <div class="font-semibold text-slate-900">
                                        ${{ number_format($facturaPendiente->monto_interno_pendiente, 2) }}
                                    </div>
                                    <div class="text-xs text-amber-700">
                                        {{ $facturaPendiente->pagos_internos_pendientes->count() }} pago(s) sin complemento ligado
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Sin pago interno pendiente</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                                    {{ $facturaPendiente->estado_complemento === 'sin_complemento'
                                        ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                        : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                    {{ $facturaPendiente->estado_complemento === 'sin_complemento' ? 'Sin complemento' : 'Parcial' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('sat.facturacion.show', $facturaPendiente) }}"
                                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                        Ver factura
                                    </a>
                                    <a href="{{ route('sat.complementos-pago.create', ['factura_id' => $facturaPendiente->id]) }}"
                                       class="text-xs font-semibold text-emerald-700 hover:text-emerald-900">
                                        Agregar pago
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-slate-500">
                                No hay facturas PPD pendientes por complementar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Complementos registrados</h2>
            <span class="text-xs text-slate-500">{{ $pagos->total() }} registro(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-slate-500 uppercase text-xs tracking-wide">
                        <th class="px-5 py-4 text-left">Fecha pago</th>
                        <th class="px-5 py-4 text-left">Cliente</th>
                        <th class="px-5 py-4 text-left">Factura</th>
                        <th class="px-5 py-4 text-left">UUID complemento</th>
                        <th class="px-5 py-4 text-center">Parcialidad</th>
                        <th class="px-5 py-4 text-right">Monto</th>
                        <th class="px-5 py-4 text-right">Saldo</th>
                        <th class="px-5 py-4 text-left">Estado</th>
                        <th class="px-5 py-4 text-left">Pago interno</th>
                        <th class="px-5 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pagos as $pago)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 whitespace-nowrap">
                                {{ $pago->fecha_pago?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">
                                    {{ $pago->factura?->receptor_nombre ?: $pago->factura?->cliente?->razon_social ?: 'Sin cliente' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $pago->factura?->receptor_rfc ?: $pago->factura?->cliente?->rfc ?: '-' }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if($pago->factura)
                                    <a href="{{ route('sat.facturacion.show', $pago->factura) }}"
                                       class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                                        {{ trim(($pago->factura->serie ? $pago->factura->serie . '-' : '') . $pago->factura->folio) ?: 'Factura #' . $pago->factura->id }}
                                    </a>
                                    <div class="text-xs text-slate-500 font-mono">{{ $pago->factura->uuid }}</div>
                                @else
                                    <span class="text-slate-400">Factura no encontrada</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs font-mono text-slate-700">{{ $pago->uuid ?: 'Sin UUID' }}</div>
                            </td>
                            <td class="px-5 py-4 text-center">{{ $pago->numero_parcialidad ?: '-' }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-emerald-700">
                                ${{ number_format($pago->monto, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                ${{ number_format($pago->saldo_insoluto, 2) }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                                    @class([
                                        'bg-emerald-50 text-emerald-700 border border-emerald-200' => $pago->estado === 'timbrado',
                                        'bg-red-50 text-red-700 border border-red-200' => $pago->estado === 'cancelado',
                                        'bg-amber-50 text-amber-700 border border-amber-200' => !in_array($pago->estado, ['timbrado', 'cancelado']),
                                    ])">
                                    {{ ucfirst($pago->estado) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                @if($pago->pagosInternosObra->isNotEmpty())
                                    <div class="text-xs font-semibold text-emerald-700">
                                        {{ $pago->pagosInternosObra->count() }} ligado(s)
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        ${{ number_format($pago->pagosInternosObra->sum('monto'), 2) }}
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Sin liga</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('sat.facturacion.pagos.show', $pago) }}"
                                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                        Ver
                                    </a>
                                    @if($pago->xml_path)
                                        <a href="{{ route('sat.facturacion.pagos.xml', $pago) }}"
                                           class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                                            XML
                                        </a>
                                    @endif
                                    @if($pago->pdf_path)
                                        <a href="{{ route('sat.facturacion.pagos.pdf', $pago) }}"
                                           class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                                            PDF
                                        </a>
                                    @endif
                                    @if($pago->estado === 'timbrado')
                                        <form method="POST"
                                              action="{{ route('sat.facturacion.pagos.cancelar', $pago) }}"
                                              onsubmit="return confirm('¿Cancelar este complemento de pago?');">
                                            @csrf
                                            <input type="hidden" name="motivo_cancelacion" value="02">
                                            <button type="submit"
                                                    class="text-xs font-semibold text-red-600 hover:text-red-800">
                                                Cancelar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-10 text-center text-slate-500">
                                No hay complementos de pago registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pagos->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $pagos->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
