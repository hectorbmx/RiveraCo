@extends('layouts.admin')

@section('title', 'Salidas - HUENTITAN')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <a href="{{ route('huentitan.index') }}" class="hover:underline">HUENTITAN</a>
                <span>/</span>
                <span>Salidas</span>
            </div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Salidas a obra</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $almacen->nombre }} &bull; Entrega de materia prima o producto terminado hacia obra.</p>
        </div>
        @can('huentitan.salidas.create')
        <a href="{{ route('huentitan.salidas.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva salida
        </a>
        @endcan
    </div>

    @if(session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border rounded-lg p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500">Total registradas</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($resumen['total'] ?? 0) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4 shadow-sm">
            <div class="text-xs font-medium text-green-600">Aplicadas al inventario</div>
            <div class="mt-1 text-2xl font-semibold text-green-700">{{ number_format($resumen['aplicadas'] ?? 0) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4 shadow-sm">
            <div class="text-xs font-medium text-amber-600">En borrador</div>
            <div class="mt-1 text-2xl font-semibold text-amber-700">{{ number_format($resumen['borrador'] ?? 0) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4 shadow-sm">
            <div class="text-xs font-medium text-red-500">Canceladas</div>
            <div class="mt-1 text-2xl font-semibold text-red-600">{{ number_format($resumen['canceladas'] ?? 0) }}</div>
        </div>
    </div>

    <div class="bg-white border rounded-lg p-4 shadow-sm">
        <form method="GET" action="{{ route('huentitan.salidas.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <div class="lg:col-span-4">
                <label class="block text-xs font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" name="q" value="{{ $busqueda }}" placeholder="Folio, obra o clave de obra..." class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-700 mb-1">Estado</label>
                <select name="estado" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="todos" @selected($estado === 'todos')>Todos</option>
                    <option value="borrador" @selected($estado === 'borrador')>Borrador</option>
                    <option value="aplicada" @selected($estado === 'aplicada')>Aplicada</option>
                    <option value="cancelada" @selected($estado === 'cancelada')>Cancelada</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-700 mb-1">Fecha desde</label>
                <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-700 mb-1">Fecha hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div class="lg:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">
                    Filtrar
                </button>
                @if($busqueda || $estado !== 'todos' || $fechaDesde || $fechaHasta)
                    <a href="{{ route('huentitan.salidas.index') }}" title="Limpiar filtros" class="p-2 text-sm text-gray-500 hover:text-gray-700 rounded-md border border-gray-300 hover:bg-gray-50 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white border rounded-lg overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Folio</th>
                        <th class="px-4 py-3 text-left font-semibold">Fecha</th>
                        <th class="px-4 py-3 text-left font-semibold">Destino</th>
                        <th class="px-4 py-3 text-left font-semibold">Obra</th>
                        <th class="px-4 py-3 text-center font-semibold">Partidas</th>
                        <th class="px-4 py-3 text-right font-semibold">Cantidad</th>
                        <th class="px-4 py-3 text-left font-semibold">Usuario</th>
                        <th class="px-4 py-3 text-center font-semibold">Estado</th>
                        <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-800">
                    @forelse($salidas as $salida)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-semibold text-gray-900 whitespace-nowrap">
                                <a href="{{ route('huentitan.salidas.show', $salida) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ $salida->folio }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-xs">
                                {{ $salida->fecha ? $salida->fecha->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ ucfirst(str_replace('_', ' ', $salida->tipo_destino ?? 'obra')) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 max-w-sm">
                                @if($salida->obra)
                                    <div class="font-medium text-gray-900 truncate" title="{{ $salida->obra->nombre }}">{{ $salida->obra->nombre }}</div>
                                    <div class="text-xs text-gray-500">{{ $salida->obra->clave_obra ?? 'Sin clave' }}</div>
                                @else
                                    <span class="text-xs text-gray-400">Sin obra</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap font-medium text-gray-600">
                                {{ $salida->detalles->count() }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold whitespace-nowrap text-gray-900">
                                {{ number_format($salida->total_salida, 2) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                                {{ $salida->usuario?->name ?? 'Sistema' }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($salida->estado === 'aplicada')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aplicada</span>
                                @elseif($salida->estado === 'cancelada')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Cancelada</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Borrador</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('huentitan.salidas.show', $salida) }}" class="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                    Ver detalle &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="max-w-sm mx-auto">
                                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay salidas registradas</h3>
                                    <p class="mt-1 text-xs text-gray-500">
                                        @if($busqueda || $estado !== 'todos' || $fechaDesde || $fechaHasta)
                                            No se encontraron salidas con los filtros seleccionados.
                                        @else
                                            Aun no se han registrado entregas de HUENTITAN hacia obra.
                                        @endif
                                    </p>
                                    @can('huentitan.salidas.create')
                                    <div class="mt-4">
                                        <a href="{{ route('huentitan.salidas.create') }}" class="inline-flex items-center px-3 py-2 rounded-md bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 shadow-sm">
                                            + Registrar primera salida
                                        </a>
                                    </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($salidas->hasPages())
            <div class="px-4 py-3 border-t bg-gray-50">
                {{ $salidas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
