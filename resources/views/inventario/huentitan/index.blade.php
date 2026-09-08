@extends('layouts.admin')

@section('title', 'Inventario HUENTITAN')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Inventario HUENTITAN</h1>
            <p class="text-sm text-gray-600">Importacion semanal, clasificacion de productos y stock aislado del almacen.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('huentitan.index') }}" class="px-4 py-2 rounded-lg border text-sm hover:bg-gray-50">Panel HUENTITAN</a>
            <a href="{{ route('inventario.stock.index', ['almacen_id' => $almacen->id]) }}" class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Ver stock completo</a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <div class="font-semibold mb-1">Revisa lo siguiente:</div>
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border rounded-xl p-4">
            <div class="text-xs text-gray-500">Almacen</div>
            <div class="text-lg font-semibold text-gray-900">{{ $almacen->nombre }}</div>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <div class="text-xs text-gray-500">Productos con stock</div>
            <div class="text-lg font-semibold text-gray-900">{{ number_format($resumen['con_existencia']) }}</div>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <div class="text-xs text-gray-500">Valor actual</div>
            <div class="text-lg font-semibold text-gray-900">${{ number_format($resumen['valor_total'], 2) }}</div>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <div class="text-xs text-gray-500">Bajo minimo</div>
            <div class="text-lg font-semibold text-gray-900">{{ number_format($resumen['bajo_minimo']) }}</div>
        </div>
    </div>

    <div class="bg-white border rounded-xl p-5">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Importar reporte semanal</h2>
        <form method="POST" action="{{ route('inventario.huentitan.importar') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-xs text-gray-600 mb-1">Desde</label>
                <input type="date" name="fecha_desde" value="{{ old('fecha_desde') }}" class="w-full rounded-lg border-gray-300" required>
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ old('fecha_hasta') }}" class="w-full rounded-lg border-gray-300" required>
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Titulo</label>
                <input type="text" name="titulo" value="{{ old('titulo') }}" class="w-full rounded-lg border-gray-300" placeholder="Corte semanal">
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Archivo XLSX</label>
                <input type="file" name="archivo" accept=".xlsx" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div class="md:text-right">
                <button type="submit" class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Importar</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900">Cortes importados</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Periodo</th>
                            <th class="px-4 py-2 text-left">Hoja</th>
                            <th class="px-4 py-2 text-right">Productos</th>
                            <th class="px-4 py-2 text-left">Estado</th>
                            <th class="px-4 py-2 text-right">Accion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($cortes as $corte)
                            <tr>
                                <td class="px-4 py-2">{{ $corte->fecha_desde->format('Y-m-d') }} al {{ $corte->fecha_hasta->format('Y-m-d') }}</td>
                                <td class="px-4 py-2">{{ $corte->hoja_importada }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($corte->detalles_count) }}</td>
                                <td class="px-4 py-2">{{ ucfirst($corte->estado) }}</td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('inventario.huentitan.cortes.show', $corte) }}" class="text-blue-700 hover:underline">Revisar</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay cortes importados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3">{{ $cortes->links() }}</div>
        </div>

        <div class="bg-white border rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900">Existencias principales</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Producto</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-right">Stock</th>
                            <th class="px-4 py-2 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($stocks as $stock)
                            <tr>
                                <td class="px-4 py-2">{{ $stock->producto->nombre }}</td>
                                <td class="px-4 py-2">{{ str_replace('_', ' ', $stock->producto->tipo_inventario ?? '-') }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format((float) $stock->stock_actual, 3) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format((float) $stock->valor_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Aun no hay stock aplicado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
