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

    @if(session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            {{ session('error') }}
        </div>
    @endif

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
    @php
        $filtroBase = $busqueda !== '' ? ['q' => $busqueda] : [];
        $estadoCards = [
            [
                'estado' => null,
                'label' => 'Todas',
                'count' => $totalFacturas,
                'color' => 'text-slate-900',
                'activeCard' => 'border-slate-900 bg-slate-900 ring-2 ring-slate-900 ring-offset-2',
                'activeLabel' => 'text-slate-200',
                'activeCount' => 'text-white',
            ],
            [
                'estado' => 'timbradas',
                'label' => 'Timbradas',
                'count' => $timbradas,
                'color' => 'text-emerald-600',
                'activeCard' => 'border-emerald-300 bg-emerald-50 ring-2 ring-emerald-200 ring-offset-2',
                'activeLabel' => 'text-emerald-700',
                'activeCount' => 'text-emerald-700',
            ],
            [
                'estado' => 'pendientes',
                'label' => 'Pendientes',
                'count' => $pendientes,
                'color' => 'text-amber-600',
                'activeCard' => 'border-amber-300 bg-amber-50 ring-2 ring-amber-200 ring-offset-2',
                'activeLabel' => 'text-amber-700',
                'activeCount' => 'text-amber-700',
            ],
            [
                'estado' => 'canceladas',
                'label' => 'Canceladas',
                'count' => $canceladas,
                'color' => 'text-red-600',
                'activeCard' => 'border-red-300 bg-red-50 ring-2 ring-red-200 ring-offset-2',
                'activeLabel' => 'text-red-700',
                'activeCount' => 'text-red-700',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-5">
        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total facturado</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($totalFacturado, 2) }}</p>
        </div>

        @foreach($estadoCards as $card)
            @php
                $activo = $estadoFiltro === $card['estado'];
                $queryCard = $card['estado'] ? array_merge($filtroBase, ['estado' => $card['estado']]) : $filtroBase;
            @endphp

            <a href="{{ route('sat.facturacion.index', $queryCard) }}"
               @class([
                   'block rounded-2xl border p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2',
                   'bg-white border-slate-200' => ! $activo,
                   $card['activeCard'] => $activo,
               ])>
                <p class="text-sm {{ $activo ? $card['activeLabel'] : 'text-slate-500' }}">{{ $card['label'] }}</p>
                <p class="text-2xl font-bold mt-1 {{ $activo ? $card['activeCount'] : $card['color'] }}">{{ $card['count'] }}</p>
            </a>
        @endforeach
    </div>

    {{-- FILTROS --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('sat.facturacion.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
            <div>
                <label for="facturacion-search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Buscar por RFC, cliente u obra
                </label>
                <div class="mt-2 flex gap-2">
                    <input
                        id="facturacion-search"
                        name="q"
                        type="search"
                        value="{{ $busqueda }}"
                        placeholder="RFC, cliente u obra"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    @if($estadoFiltro)
                        <input type="hidden" name="estado" value="{{ $estadoFiltro }}">
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                    Buscar
                </button>

                @if($busqueda !== '' || $estadoFiltro)
                    <a href="{{ route('sat.facturacion.index') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
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
                $esBorradorObra = $factura instanceof \App\Models\ObraFacturaBorrador;
                $payload = $esBorradorCfdi ? ($factura->payload ?: []) : [];
                $conceptosBorrador = collect($payload['conceptos'] ?? []);
                $subtotalBorrador = $conceptosBorrador->sum(fn ($concepto) => (float) ($concepto['cantidad'] ?? 0) * (float) ($concepto['precio_unitario'] ?? 0));
                $tipoIvaBorrador = $payload['tipo_iva'] ?? '0.16';
                $ivaTasaBorrador = in_array($tipoIvaBorrador, ['0.16', '0.08'], true) ? (float) $tipoIvaBorrador : 0;
                $baseBorrador = max(0, $subtotalBorrador - (float) ($payload['amortizacion'] ?? 0) - (float) ($payload['descuento'] ?? 0));
                $totalBorrador = max(0, $baseBorrador + ($baseBorrador * $ivaTasaBorrador) - (float) ($payload['retenciones'] ?? 0));
                $estadoFactura = $esBorradorObra ? $factura->estatus : $factura->estado;
                $estadoCancelada = in_array($estadoFactura, ['cancelada', \App\Models\ObraFacturaBorrador::ESTATUS_CANCELADO], true);
                $totalFila = $esBorradorObra ? (float) $factura->total : ($esBorradorCfdi ? $totalBorrador : (float) $factura->total);
                $fechaFila = $esBorradorObra
                    ? optional($factura->fecha ?: $factura->created_at)->format('d/m/Y')
                    : ($esBorradorCfdi ? $factura->created_at->format('d/m/Y') : ($factura->fecha_emision?->format('d/m/Y') ?? $factura->created_at->format('d/m/Y')));
            @endphp
          <tr
                    @class([
                        'hover:bg-slate-50' => !$estadoCancelada,
                        'bg-red-50/60 hover:bg-red-100/70 text-slate-500' => $estadoCancelada,
                    ])>
                <td class="px-5 py-4">
                    @if($esBorradorObra)
                        BF-{{ str_pad($factura->id, 5, '0', STR_PAD_LEFT) }}
                    @elseif($esBorradorCfdi)
                        <span class="text-slate-400">Sin folio</span>
                    @else
                        {{ trim(($factura->serie ? $factura->serie . '-' : '') . ($factura->folio ?: '')) ?: 'Sin folio' }}
                    @endif
                </td>

                <td class="px-5 py-4">{{ $fechaFila }}</td>

                <td class="px-5 py-4">
                    @if($factura->cliente)
                        <a href="{{ route('sat.facturacion.clientes.show', $factura->cliente) }}"
                           class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                            @if($esBorradorObra)
                                {{ $factura->cliente->razon_social ?? $factura->cliente->nombre_comercial ?? 'Cliente sin nombre' }}
                            @elseif($esBorradorCfdi)
                                {{ $factura->cliente->razon_social ?? $factura->cliente->nombre_comercial ?? 'Cliente sin nombre' }}
                            @else
                                {{ $factura->receptor_nombre ?? $factura->cliente->razon_social ?? $factura->cliente->nombre_comercial }}
                            @endif
                        </a>
                    @else
                        {{ $esBorradorObra ? 'Cliente sin nombre' : ($esBorradorCfdi ? ($factura->titulo ?: 'Borrador CFDI') : ($factura->receptor_nombre ?? 'Sin cliente')) }}
                    @endif
                </td>

                <td class="px-5 py-4">
                    {{ ($factura->cliente->rfc ?? null) ?: ($factura->receptor_rfc ?? '-') }}
                </td>

                <td class="px-5 py-4">
                    @if($factura->obra)
                        Obra:
                        <a href="{{ route('obras.edit', $factura->obra) }}"
                           class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                            {{ $factura->obra->nombre ?? $factura->obra->Nombre ?? 'Obra #' . $factura->obra->id }}
                        </a>
                    @elseif(!$esBorradorObra && !$esBorradorCfdi && $factura->ordenCompra)
                        OC: {{ $factura->ordenCompra->folio ?? 'OC #' . $factura->ordenCompra->id }}
                    @else
                        -
                    @endif
                </td>

                <td class="px-5 py-4 text-right font-semibold">
                    ${{ number_format($totalFila, 2) }}
                </td>

                <td class="px-5 py-4">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                        @class([
                            'bg-emerald-50 text-emerald-700 border border-emerald-200' => in_array($estadoFactura, ['timbrada', \App\Models\ObraFacturaBorrador::ESTATUS_AUTORIZADO], true),
                            'bg-amber-50 text-amber-700 border border-amber-200' => in_array($estadoFactura, ['borrador', \App\Models\ObraFacturaBorrador::ESTATUS_PENDIENTE_REVISION], true),
                            'bg-red-50 text-red-700 border border-red-200' => in_array($estadoFactura, ['cancelada', \App\Models\ObraFacturaBorrador::ESTATUS_CANCELADO, \App\Models\ObraFacturaBorrador::ESTATUS_RECHAZADO], true),
                            'bg-slate-50 text-slate-700 border border-slate-200' => !in_array($estadoFactura, ['timbrada', 'borrador', 'cancelada', \App\Models\ObraFacturaBorrador::ESTATUS_AUTORIZADO, \App\Models\ObraFacturaBorrador::ESTATUS_PENDIENTE_REVISION, \App\Models\ObraFacturaBorrador::ESTATUS_CANCELADO, \App\Models\ObraFacturaBorrador::ESTATUS_RECHAZADO], true),
                        ])">
                        @if($esBorradorObra)
                            {{ \App\Models\ObraFacturaBorrador::estatusLabels()[$factura->estatus] ?? ucfirst($factura->estatus) }}
                        @elseif($esBorradorCfdi)
                            Borrador
                        @else
                            {{ ucfirst($factura->estado) }}
                        @endif
                    </span>
                </td>
               <td class="px-5 py-4">
    @php
        $estatusSat = match (true) {
            $esBorradorObra || $esBorradorCfdi => 'pendiente',
            $factura->estado === 'cancelada' => 'cancelada',
            $factura->estado === 'cancelacion_solicitada' => 'solicitud_cancelacion',
            default => 'vigente',
        };

        $estatusSatLabel = match ($estatusSat) {
            'pendiente' => 'Pendiente de timbrar',
            'vigente' => 'Vigente',
            'cancelada' => 'Cancelada',
            'solicitud_cancelacion' => 'En proceso de cancelacion',
        };
    @endphp

    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
        @class([
            'bg-emerald-50 text-emerald-700 border border-emerald-200' => $estatusSat === 'vigente',
            'bg-red-50 text-red-700 border border-red-200' => $estatusSat === 'cancelada',
            'bg-amber-50 text-amber-700 border border-amber-200' => in_array($estatusSat, ['solicitud_cancelacion', 'pendiente'], true),
        ])">
        {{ $estatusSatLabel }}
    </span>
</td>
                <td class="px-5 py-4 text-right">
                    @if($esBorradorObra)
                        <div class="flex justify-end gap-3">
                            @if($factura->estatus === \App\Models\ObraFacturaBorrador::ESTATUS_AUTORIZADO)
                                <a href="{{ route('sat.facturacion.create', ['borrador_id' => $factura->id]) }}"
                                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                    Facturar
                                </a>
                            @endif
                            <a href="{{ route('obras.factura-borradores.show', [$factura->obra_id, $factura->id]) }}"
                               class="text-sm font-medium text-slate-600 hover:text-slate-800">
                                Detalle
                            </a>
                        </div>
                    @elseif($esBorradorCfdi)
                        <div class="flex justify-end gap-3">
                            <a href="{{ route('sat.facturacion.create', ['cfdi_borrador_id' => $factura->id]) }}"
                               class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                Continuar
                            </a>

                            <form method="POST"
                                  action="{{ route('sat.facturacion.borradores.destroy', $factura) }}"
                                  onsubmit="return confirm('Eliminar este borrador CFDI? Esta accion no se puede deshacer.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">
                                    Borrar
                                </button>
                            </form>
                        </div>
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
                    No hay facturas que coincidan con los filtros.
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
