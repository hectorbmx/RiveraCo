@extends('layouts.admin')

@section('title', 'Facturación SAT')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Facturación SAT
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                Emisión, consulta y control de facturas CFDI.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sat.complementos-pago.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Complementos de pago
            </a>

            <a href="{{ route('sat.facturacion.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                <span>+</span>
                Nueva Factura
            </a>
        </div>
    </div>

    {{-- ACCESOS --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('clientes.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            Clientes
        </a>

        <a href="{{ route('sat.catalogos.conceptos') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            Catálogos
        </a>
    </div>

    {{-- KPIS --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total facturado</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($totalFacturado, 2) }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Timbradas</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $timbradas }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Pendientes</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ $pendientes }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Canceladas</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $canceladas }}</p>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-slate-500 uppercase text-xs tracking-wide">
                        <th class="px-5 py-4 text-left">Folio</th>
                        <th class="px-5 py-4 text-left">Fecha</th>
                        <th class="px-5 py-4 text-left">Cliente</th>
                        <th class="px-5 py-4 text-left">RFC</th>
                        <th class="px-5 py-4 text-left">Relación</th>
                        <th class="px-5 py-4 text-right">Total</th>
                        <th class="px-5 py-4 text-left">Estado</th>
                        <th class="px-5 py-4 text-left">Estatus SAT</th>
                        <th class="px-5 py-4 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
        @forelse($facturas as $factura)
            @php
                $esBorradorCfdi = $factura instanceof \App\Models\SatFacturaBorrador;
                $payload = $esBorradorCfdi ? ($factura->payload ?: []) : [];
                $conceptosBorrador = collect($payload['conceptos'] ?? []);
                $subtotalBorrador = $conceptosBorrador->sum(fn ($concepto) => (float) ($concepto['cantidad'] ?? 0) * (float) ($concepto['precio_unitario'] ?? 0));
                $tipoIvaBorrador = $payload['tipo_iva'] ?? '0.16';
                $ivaTasaBorrador = in_array($tipoIvaBorrador, ['0.16', '0.08'], true) ? (float) $tipoIvaBorrador : 0;
                $baseBorrador = max(0, $subtotalBorrador - (float) ($payload['amortizacion'] ?? 0) - (float) ($payload['descuento'] ?? 0));
                $totalBorrador = max(0, $baseBorrador + ($baseBorrador * $ivaTasaBorrador) - (float) ($payload['retenciones'] ?? 0));
            @endphp
          <tr
                    @class([
                        'hover:bg-slate-50' => $factura->estado !== 'cancelada',

                        'bg-red-50/60 hover:bg-red-100/70 text-slate-500' =>
                            $factura->estado === 'cancelada',
                    ])>
                <td class="px-5 py-4">
                    @if($esBorradorCfdi)
                        <span class="text-slate-400">Sin folio</span>
                    @else
                        {{ trim(($factura->serie ? $factura->serie . '-' : '') . ($factura->folio ?: '')) ?: 'Sin folio' }}
                    @endif
                </td>

                <td class="px-5 py-4">
                    {{ $esBorradorCfdi ? $factura->created_at->format('d/m/Y') : ($factura->fecha_emision?->format('d/m/Y') ?? $factura->created_at->format('d/m/Y')) }}
                </td>

                <td class="px-5 py-4">
                    @if($factura->cliente)
                        <a href="{{ route('sat.facturacion.clientes.show', $factura->cliente) }}"
                           class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                            {{ $esBorradorCfdi ? ($factura->cliente->razon_social ?? $factura->cliente->nombre_comercial ?? 'Cliente sin nombre') : ($factura->receptor_nombre ?? $factura->cliente->razon_social ?? $factura->cliente->nombre_comercial) }}
                        </a>
                    @else
                        {{ $esBorradorCfdi ? ($factura->titulo ?: 'Borrador CFDI') : ($factura->receptor_nombre ?? 'Sin cliente') }}
                    @endif
                </td>

                <td class="px-5 py-4">
                    {{ $esBorradorCfdi ? ($factura->cliente->rfc ?? '—') : ($factura->receptor_rfc ?? $factura->cliente->rfc ?? '—') }}
                </td>

                <td class="px-5 py-4">
                    @if($factura->obra)
                        Obra:
                        <a href="{{ route('obras.edit', $factura->obra) }}"
                           class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                            {{ $factura->obra->nombre ?? $factura->obra->Nombre ?? 'Obra #' . $factura->obra->id }}
                        </a>
                    @elseif($factura->ordenCompra)
                        OC: {{ $factura->ordenCompra->folio ?? 'OC #' . $factura->ordenCompra->id }}
                    @else
                        —
                    @endif
                </td>

                <td class="px-5 py-4 text-right font-semibold">
                    ${{ number_format($esBorradorCfdi ? $totalBorrador : $factura->total, 2) }}
                </td>

                <td class="px-5 py-4">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                        @class([
                            'bg-emerald-50 text-emerald-700 border border-emerald-200' => $factura->estado === 'timbrada',
                            'bg-amber-50 text-amber-700 border border-amber-200' => $factura->estado === 'borrador',
                            'bg-red-50 text-red-700 border border-red-200' => $factura->estado === 'cancelada',
                            'bg-slate-50 text-slate-700 border border-slate-200' => !in_array($factura->estado, ['timbrada', 'borrador', 'cancelada']),
                        ])">
                        {{ $esBorradorCfdi ? 'Borrador' : ucfirst($factura->estado) }}
                    </span>
                </td>
               <td class="px-5 py-4">
    @php
        $estatusSat = match ($factura->estado) {
            'cancelada' => 'cancelada',
            'cancelacion_solicitada' => 'solicitud_cancelacion',
            default => 'vigente',
        };

        $estatusSatLabel = match ($estatusSat) {
            'vigente' => 'Vigente',
            'cancelada' => 'Cancelada',
            'solicitud_cancelacion' => 'En proceso de cancelación',
        };
    @endphp

    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
        @class([
            'bg-emerald-50 text-emerald-700 border border-emerald-200' => $estatusSat === 'vigente',
            'bg-red-50 text-red-700 border border-red-200' => $estatusSat === 'cancelada',
            'bg-amber-50 text-amber-700 border border-amber-200' => $estatusSat === 'solicitud_cancelacion',
        ])">
        {{ $estatusSatLabel }}
    </span>
</td>
                <td class="px-5 py-4 text-right">
                    @if($esBorradorCfdi)
                        <a href="{{ route('sat.facturacion.create', ['cfdi_borrador_id' => $factura->id]) }}"
                           class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            Continuar
                        </a>
                    @else
                        <a href="{{ route('sat.facturacion.show', $factura) }}"
                           class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            Ver
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="px-5 py-10 text-center text-slate-500">
                    Aún no hay facturas emitidas.
                </td>
            </tr>
        @endforelse
    </tbody>
            </table>
            @if($facturas->hasPages())
                <div class="px-5 py-4 border-t border-slate-200">
                    {{ $facturas->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
