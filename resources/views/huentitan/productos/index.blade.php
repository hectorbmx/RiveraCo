@extends('layouts.admin')

@section('title', 'HUENTITAN - Productos')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">HUENTITAN</div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Productos</h1>
            <p class="mt-1 text-sm text-gray-600">Catalogo operativo con stock vivo, costo promedio y ultimo movimiento.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('huentitan.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Panel</a>
            <a href="{{ route('huentitan.entradas.create') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Nueva entrada</a>
            <a href="{{ route('huentitan.inventario.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">Inventario</a>
        </div>
    </div>

    <div class="bg-[#0B265A] rounded-lg p-4 shadow-sm">
        <form method="GET" action="{{ route('huentitan.productos.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-5">
                <label class="block text-xs font-semibold text-white/85 mb-1">Buscar producto</label>
                <input type="text" name="q" value="{{ $busqueda }}" placeholder="Nombre, codigo o descripcion" class="w-full rounded-md border border-amber-300 bg-white px-3 py-2 text-sm focus:border-amber-400 focus:ring-4 focus:ring-yellow-200">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-white/85 mb-1">Tipo</label>
                <select name="tipo" class="w-full rounded-md border-slate-200 bg-white text-sm focus:border-slate-300 focus:ring-slate-300">
                    <option value="">Todos</option>
                    <option value="materia_prima" @selected($tipo === 'materia_prima')>Materia prima</option>
                    <option value="producto_terminado" @selected($tipo === 'producto_terminado')>Producto terminado</option>
                    <option value="subensamble" @selected($tipo === 'subensamble')>Subensamble</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-white/85 mb-1">Formula</label>
                <select name="formula" class="w-full rounded-md border-slate-200 bg-white text-sm focus:border-slate-300 focus:ring-slate-300">
                    <option value="">Todos</option>
                    <option value="si" @selected($formula === 'si')>Requiere</option>
                    <option value="no" @selected($formula === 'no')>No requiere</option>
                </select>
            </div>
            <div class="md:col-span-3 flex gap-2 md:justify-end">
                <button class="px-4 py-2 rounded-md bg-[#FFC107] text-[#0B265A] text-sm font-semibold hover:opacity-90">Filtrar</button>
                <a href="{{ route('huentitan.productos.index') }}" class="px-4 py-2 rounded-md border border-white/25 bg-white/10 text-white text-sm font-medium hover:bg-white/20">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Total catalogo</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenProductos['total']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Materia prima</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenProductos['materia_prima']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Producto terminado</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenProductos['producto_terminado']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Subensamble</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenProductos['subensamble']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Requieren formula</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenProductos['requieren_formula']) }}</div>
        </div>
    </div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Catalogo HUENTITAN</h2>
                <p class="text-xs text-gray-500">{{ number_format($productos->total()) }} productos encontrados</p>
            </div>
            <div class="text-xs text-gray-500">Stock calculado desde movimientos aplicados</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Codigo</th>
                        <th class="px-4 py-3 text-left">Producto</th>
                        <th class="px-4 py-3 text-left">Tipo</th>
                        <th class="px-4 py-3 text-center">Formula</th>
                        <th class="px-4 py-3 text-right">Stock actual</th>
                        <th class="px-4 py-3 text-right">Stock minimo</th>
                        <th class="px-4 py-3 text-right">Valor en stock</th>
                        <th class="px-4 py-3 text-left">Ultimo movimiento</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($productos as $producto)
                        @php
                            $stockProducto = $producto->inventarioStocks->first();
                            $stockActual = (float) ($stockProducto->stock_actual ?? 0);
                            $valorStock = (float) ($stockProducto->valor_total ?? 0);
                            $ultimoMovimiento = $ultimosMovimientos->get($producto->id);
                            $entradaMovimiento = $ultimoMovimiento ? $entradasMovimiento->get($ultimoMovimiento->documento_id) : null;
                            $tipoMovimiento = $ultimoMovimiento?->tipo_movimiento === 'in' ? 'Entrada' : ($ultimoMovimiento?->tipo_movimiento === 'out' ? 'Salida' : '-');
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $producto->sku }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('huentitan.productos.show', $producto) }}" class="font-medium text-[#0B265A] hover:text-[#FFC107] hover:underline">
                                    {{ $producto->nombre }}
                                </a>
                                @if($producto->descripcion)
                                    <div class="text-xs text-gray-500">{{ $producto->descripcion }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ str_replace('_', ' ', $producto->tipo_inventario ?? '-') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-1 rounded text-xs {{ $producto->requiere_formula ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-gray-50 text-gray-600 border border-gray-100' }}">
                                    {{ $producto->requiere_formula ? 'Si' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ number_format($stockActual, 3) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) ($producto->stock_minimo ?? 0), 3) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">${{ number_format($valorStock, 2) }}</td>
                            <td class="px-4 py-3">
                                @if($ultimoMovimiento)
                                    <div class="font-medium text-gray-900">
                                        {{ $tipoMovimiento }} de {{ number_format((float) $ultimoMovimiento->cantidad, 3) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ \Illuminate\Support\Carbon::parse($ultimoMovimiento->fecha)->format('Y-m-d H:i') }}
                                    </div>
                                    @if($entradaMovimiento)
                                        <a href="{{ route('huentitan.entradas.show', $entradaMovimiento->id) }}" class="text-xs font-semibold text-[#0B265A] hover:underline">
                                            {{ $entradaMovimiento->folio }}
                                        </a>
                                    @else
                                        <a href="{{ route('huentitan.productos.show', ['producto' => $producto->id, 'tab' => 'kardex']) }}" class="text-xs font-semibold text-[#0B265A] hover:underline">
                                            Movimiento #{{ $ultimoMovimiento->id }}
                                        </a>
                                    @endif
                                @else
                                    <span class="text-gray-400">Sin movimientos</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay productos HUENTITAN cargados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t">
            {{ $productos->links() }}
        </div>
    </div>
</div>
@endsection
