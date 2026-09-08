@extends('layouts.admin')

@section('title', 'HUENTITAN - Orden fabricacion')

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

    $estadosResumen = [
        'borrador' => $estados['borrador'],
        'calculada' => $estados['calculada'],
        'en_produccion' => $estados['en_produccion'],
        'cerrada' => $estados['cerrada'],
        'cancelada' => $estados['cancelada'],
    ];
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">HUENTITAN</div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Orden fabricacion</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $almacen->nombre }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('huentitan.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Panel</a>
            <a href="{{ route('huentitan.ordenes-fabricacion.create') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">Nueva orden</a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        <div class="bg-white border rounded-lg px-4 py-3 min-h-[82px]">
            <div class="text-xs text-gray-500">Total</div>
            <div class="mt-2 text-xl font-semibold text-gray-900">{{ number_format($resumen['total']) }}</div>
        </div>
        @foreach($estadosResumen as $estado => $label)
            <div class="bg-white border rounded-lg px-4 py-3 min-h-[82px]">
                <div class="text-xs text-gray-500">{{ $label }}</div>
                <div class="mt-2 text-xl font-semibold text-gray-900">{{ number_format($resumen[$estado] ?? 0) }}</div>
            </div>
        @endforeach
        {{-- Autorizacion reservada para una fase futura si el flujo la requiere.
        <div class="bg-white border rounded-lg px-4 py-3 min-h-[82px]">
            <div class="text-xs text-gray-500">{{ $estados['autorizada'] }}</div>
            <div class="mt-2 text-xl font-semibold text-gray-900">{{ number_format($resumen['autorizada'] ?? 0) }}</div>
        </div>
        --}}
    </div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Ordenes de fabricacion</h2>
                <p class="text-xs text-gray-500">{{ number_format($ordenes->total()) }} ordenes registradas</p>
            </div>
            <div class="text-xs text-gray-500">Flujo actual sin autorizacion</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Folio</th>
                        <th class="px-4 py-3 text-left">Producto</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Estado</th>
                        <th class="px-4 py-3 text-left">Usuario creador</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($ordenes as $orden)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs"><a href="{{ route('huentitan.ordenes-fabricacion.show', $orden) }}" class="font-semibold text-[#0B265A] hover:text-[#FFC107] hover:underline">{{ $orden->folio }}</a></td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $orden->producto?->nombre ?? 'Producto no disponible' }}</div>
                                <div class="text-xs text-gray-500">{{ $orden->producto?->sku ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $orden->cantidad_solicitada, 3) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ optional($orden->fecha)->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 rounded border text-xs {{ $estadoClases[$orden->estado] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                    {{ $estados[$orden->estado] ?? ucfirst(str_replace('_', ' ', $orden->estado)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $orden->creador?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Todavia no hay ordenes de fabricacion.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t">
            {{ $ordenes->links() }}
        </div>
    </div>
</div>
@endsection

