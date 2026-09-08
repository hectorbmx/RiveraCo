@extends('layouts.admin')

@section('title', 'HUENTITAN - Orden ' . $orden->folio)

@section('content')
@php
    $estadoClases = [
        'borrador' => 'bg-gray-50 text-gray-700 border-gray-200',
        'calculada' => 'bg-blue-50 text-blue-700 border-blue-200',
        'autorizada' => 'bg-amber-50 text-amber-700 border-amber-200',
        'en_produccion' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'cerrada' => 'bg-green-50 text-green-700 border-green-200',
        'cancelada' => 'bg-red-50 text-red-700 border-red-200',
    ];
@endphp

<div class="max-w-8xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">HUENTITAN</div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">{{ $orden->folio }}</h1>
            <p class="mt-1 text-sm text-gray-600">Orden de fabricacion</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('huentitan.ordenes-fabricacion.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Regresar</a>
            <a href="{{ route('huentitan.ordenes-fabricacion.create') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">Nueva orden</a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="bg-white border rounded-lg overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-8 divide-y md:divide-y-0 md:divide-x divide-gray-200 text-sm">
            <div class="p-3 min-w-0">
                <div class="text-xs text-gray-500">Producto a fabricar</div>
                <div class="mt-1 font-semibold text-gray-900 truncate" title="{{ $orden->producto?->nombre ?? 'Producto no disponible' }}">{{ $orden->producto?->nombre ?? 'Producto no disponible' }}</div>
                <div class="text-xs text-gray-500 truncate">{{ $orden->producto?->sku ?? '-' }}</div>
            </div>
            <div class="p-3 min-w-0">
                <div class="text-xs text-gray-500">Estado</div>
                <div class="mt-1"><span class="inline-flex px-2 py-0.5 rounded border text-xs font-medium {{ $estadoClases[$orden->estado] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">{{ $estados[$orden->estado] ?? ucfirst(str_replace('_', ' ', $orden->estado)) }}</span></div>
            </div>
            <div class="p-3 min-w-0">
                <div class="text-xs text-gray-500">Cantidad solicitada</div>
                <div class="mt-1 font-semibold text-gray-900 truncate">{{ number_format((float) $orden->cantidad_solicitada, 3) }} {{ $orden->producto?->unidad }}</div>
            </div>
            <div class="p-3 min-w-0">
                <div class="text-xs text-gray-500">Fecha</div>
                <div class="mt-1 font-semibold text-gray-900 truncate">{{ optional($orden->fecha)->format('Y-m-d') }}</div>
            </div>
            <div class="p-3 min-w-0">
                <div class="text-xs text-gray-500">Usuario creador</div>
                <div class="mt-1 font-semibold text-gray-900 truncate" title="{{ $orden->creador?->name ?? '-' }}">{{ $orden->creador?->name ?? '-' }}</div>
            </div>
            <div class="p-3 min-w-0">
                <div class="text-xs text-gray-500">Formula base</div>
                <div class="mt-1 font-semibold text-gray-900 truncate">{{ number_format((float) ($orden->formula_cantidad_base ?? 0), 3) }} {{ $orden->formula_unidad_base }}</div>
            </div>
            <div class="p-3 min-w-0">
                <div class="text-xs text-gray-500">Calculada</div>
                <div class="mt-1 font-semibold text-gray-900 truncate">{{ $orden->calculada_at ? $orden->calculada_at->format('Y-m-d H:i') : 'Pendiente' }}</div>
                @if($orden->calculador)
                    <div class="text-xs text-gray-500 truncate" title="{{ $orden->calculador->name }}">{{ $orden->calculador->name }}</div>
                @endif
            </div>
            <div class="p-3 min-w-0 bg-gray-50 flex flex-col justify-center gap-1.5">
                <div class="flex items-center justify-between text-xs"><span class="text-gray-500">Materiales:</span><span class="font-semibold text-gray-900">{{ number_format($resumenMateriales['materiales']) }}</span></div>
                <div class="flex items-center justify-between text-xs"><span class="{{ $resumenMateriales['con_faltante'] > 0 ? 'text-red-700 font-medium' : 'text-gray-500' }}">Faltantes:</span><span class="font-semibold {{ $resumenMateriales['con_faltante'] > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ number_format($resumenMateriales['con_faltante']) }}</span></div>
                <div class="flex items-center justify-between text-xs"><span class="{{ ($resumenMateriales['requieren_compra'] ?? 0) > 0 ? 'text-amber-700 font-medium' : 'text-gray-500' }}">Para compra:</span><span class="font-semibold {{ ($resumenMateriales['requieren_compra'] ?? 0) > 0 ? 'text-amber-700' : 'text-gray-900' }}">{{ number_format($resumenMateriales['requieren_compra'] ?? 0) }}</span></div>
                <div class="flex items-center justify-between text-xs border-t pt-1 border-gray-200"><span class="text-gray-500">Costo est.:</span><span class="font-semibold text-gray-900">${{ number_format($resumenMateriales['costo_estimado'], 2) }}</span></div>
            </div>
        </div>
    </div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Materiales de la orden</h2>
                <p class="text-xs text-gray-500">{{ $orden->estado === 'calculada' ? 'Calculo congelado contra stock al momento de calcular.' : 'Snapshot de formula comparado contra stock actual de ' . $almacen->nombre }}</p>
            </div>
            <div class="text-xs text-gray-500">Sin afectacion de inventario</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Codigo</th>
                        <th class="px-4 py-3 text-left">Material</th>
                        <th class="px-4 py-3 text-right">Cant. unidad</th>
                        <th class="px-4 py-3 text-right">Merma %</th>
                        <th class="px-4 py-3 text-right">Requerido</th>
                        <th class="px-4 py-3 text-left">Unidad</th>
                        <th class="px-4 py-3 text-right">Stock actual</th>
                        <th class="px-4 py-3 text-right">Reservado</th>
                        <th class="px-4 py-3 text-right">Disponible</th>
                        <th class="px-4 py-3 text-right">Faltante</th>
                        <th class="px-4 py-3 text-right">Compra</th>
                        <th class="px-4 py-3 text-right">Costo est.</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($materiales as $materialDetalle)
                        @php
                            $material = $materialDetalle['snapshot'];
                            $stockActual = $orden->estado === 'calculada' ? (float) $material->stock_actual_calculado : (float) $materialDetalle['stock_actual'];
                            $stockReservado = $orden->estado === 'calculada' ? (float) $material->stock_reservado_calculado : (float) $materialDetalle['stock_reservado'];
                            $stockDisponible = $orden->estado === 'calculada' ? (float) $material->stock_disponible_calculado : (float) $materialDetalle['stock_disponible'];
                            $faltante = $orden->estado === 'calculada' ? (float) $material->faltante_calculado : (float) $materialDetalle['faltante'];
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $material->material_sku ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $material->material_nombre }}</div>
                                @if($material->notas)
                                    <div class="text-xs text-gray-500">{{ $material->notas }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $material->cantidad_por_unidad, 3) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $material->merma_porcentaje, 3) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ number_format((float) $material->cantidad_requerida, 3) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $material->unidad ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($stockActual, 3) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($stockReservado, 3) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($stockDisponible, 3) }}</td>
                            <td class="px-4 py-3 text-right {{ $faltante > 0 ? 'bg-red-50 text-red-700 font-semibold' : 'text-gray-700' }}">{{ number_format($faltante, 3) }}</td>
                            <td class="px-4 py-3 text-right">
                                @if(in_array($orden->estado, ['borrador', 'calculada'], true))
                                    <form method="POST" action="{{ route('huentitan.ordenes-fabricacion.materiales.compra', ['orden' => $orden, 'material' => $material]) }}" class="flex justify-end">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="requiere_compra" value="0">
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                            <input type="checkbox" name="requiere_compra" value="1" class="rounded border-slate-300 text-[#0B265A] focus:ring-[#0B265A]" @checked($material->requiere_compra) onchange="this.form.submit()">
                                            Incluir
                                        </label>
                                    </form>
                                @else
                                    <span class="inline-flex px-2 py-1 rounded border text-xs {{ $material->requiere_compra ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">{{ $material->requiere_compra ? 'Si' : 'No' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">${{ number_format((float) $material->costo_total_estimado, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-gray-500">Esta orden no tiene materiales snapshot.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 text-sm font-semibold text-gray-900">
                    <tr>
                        <td colspan="11" class="px-4 py-3 text-right">Costo estimado materiales</td>
                        <td class="px-4 py-3 text-right">${{ number_format((float) $orden->costo_material_estimado, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @if(in_array($orden->estado, ['borrador', 'calculada'], true))
            <form method="POST" action="{{ route('huentitan.ordenes-fabricacion.calcular', $orden) }}">
                @csrf
                <button type="submit" class="w-full px-4 py-2 rounded-md bg-[#0B265A] text-white text-sm font-medium hover:opacity-90">Calcular materiales</button>
            </form>
        @else
            <button type="button" class="px-4 py-2 rounded-md border bg-gray-100 text-gray-500 text-sm font-medium cursor-not-allowed" disabled>Calcular materiales</button>
        @endif
        @if(($resumenMateriales['requieren_compra'] ?? 0) > 0)
            <a href="{{ route('huentitan.ordenes-compra.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border border-amber-200 bg-amber-50 text-amber-700 text-sm font-medium hover:bg-amber-100">Ver materiales para compra</a>
        @else
            <button type="button" class="px-4 py-2 rounded-md border bg-gray-100 text-gray-500 text-sm font-medium cursor-not-allowed" disabled>Generar compra por faltantes</button>
        @endif
        <button type="button" class="px-4 py-2 rounded-md border bg-gray-100 text-gray-500 text-sm font-medium cursor-not-allowed" disabled>Apartar material</button>
        {{-- Autorizacion reservada para fase futura si el flujo operativo la requiere. --}}
    </div>
</div>
@endsection

