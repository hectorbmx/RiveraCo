@extends('layouts.admin')

@section('title', 'Corte Huentitan')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Corte de inventario Huentitan</h1>
            <p class="text-sm text-gray-600">
                {{ $corte->fecha_desde->format('Y-m-d') }} al {{ $corte->fecha_hasta->format('Y-m-d') }} - hoja {{ $corte->hoja_importada }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('huentitan.inventario.index') }}" class="px-4 py-2 rounded-lg border text-sm hover:bg-gray-50">Volver</a>
            @if($corte->estado !== 'aplicado')
                <form method="POST" action="{{ route('inventario.huentitan.cortes.aplicar', $corte) }}" onsubmit="return confirm('Aplicar este corte al stock actual de Huentitan?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Aplicar al stock</button>
                </form>
            @endif
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
            <div class="text-xs text-gray-500">Estado</div>
            <div class="text-lg font-semibold text-gray-900">{{ ucfirst($corte->estado) }}</div>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <div class="text-xs text-gray-500">Productos</div>
            <div class="text-lg font-semibold text-gray-900">{{ number_format($corte->total_productos) }}</div>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <div class="text-xs text-gray-500">Con existencia</div>
            <div class="text-lg font-semibold text-gray-900">{{ number_format($corte->total_con_existencia) }}</div>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <div class="text-xs text-gray-500">Valor actual</div>
            <div class="text-lg font-semibold text-gray-900">${{ number_format((float) $corte->valor_total_actual, 2) }}</div>
        </div>
    </div>

    <div class="bg-white border rounded-xl overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-900">Resumen por tipo</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Tipo</th>
                        <th class="px-4 py-2 text-right">Productos</th>
                        <th class="px-4 py-2 text-right">Existencia</th>
                        <th class="px-4 py-2 text-right">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($resumenTipos as $tipo)
                        <tr>
                            <td class="px-4 py-2">{{ str_replace('_', ' ', $tipo->tipo_inventario) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($tipo->total) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format((float) $tipo->existencia, 3) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $tipo->valor, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white border rounded-xl overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-900">Productos importados</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">No.</th>
                        <th class="px-3 py-2 text-left">Producto</th>
                        <th class="px-3 py-2 text-left">Unidad</th>
                        <th class="px-3 py-2 text-right">Anterior</th>
                        <th class="px-3 py-2 text-right">Entrada</th>
                        <th class="px-3 py-2 text-right">Salida</th>
                        <th class="px-3 py-2 text-right">Actual</th>
                        <th class="px-3 py-2 text-right">P.U.</th>
                        <th class="px-3 py-2 text-left">Clasificacion</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($detalles as $detalle)
                        <tr class="align-top {{ $detalle->es_danado ? 'bg-amber-50' : '' }}">
                            <td class="px-3 py-2 font-mono">{{ $detalle->numero_importacion }}</td>
                            <td class="px-3 py-2 min-w-72">
                                <div class="font-medium text-gray-900">{{ $detalle->nombre_producto }}</div>
                                <div class="text-gray-500">{{ optional($detalle->producto)->sku }}</div>
                                @if($detalle->es_danado)
                                    <div class="mt-1 text-amber-700">Producto danado / no conforme</div>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $detalle->unidad }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format((float) $detalle->existencia_anterior, 3) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format((float) $detalle->entrada_periodo, 3) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format((float) $detalle->salida_periodo, 3) }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ number_format((float) $detalle->existencia_actual, 3) }}</td>
                            <td class="px-3 py-2 text-right">${{ number_format((float) $detalle->precio_unitario, 2) }}</td>
                            <td class="px-3 py-2 min-w-80">
                                <form method="POST" action="{{ route('inventario.huentitan.detalles.update', $detalle) }}" class="grid grid-cols-2 gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="tipo_inventario" class="rounded border-gray-300 text-xs">
                                        <option value="materia_prima" @selected($detalle->tipo_inventario === 'materia_prima')>Materia prima</option>
                                        <option value="producto_terminado" @selected($detalle->tipo_inventario === 'producto_terminado')>Producto terminado</option>
                                        <option value="subensamble" @selected($detalle->tipo_inventario === 'subensamble')>Subensamble</option>
                                    </select>
                                    <select name="origen_abastecimiento" class="rounded border-gray-300 text-xs">
                                        <option value="compra" @selected($detalle->origen_abastecimiento === 'compra')>Compra</option>
                                        <option value="fabricacion" @selected($detalle->origen_abastecimiento === 'fabricacion')>Fabricacion</option>
                                        <option value="ambos" @selected($detalle->origen_abastecimiento === 'ambos')>Ambos</option>
                                    </select>
                                    <input type="number" step="0.001" min="0" name="stock_minimo" value="{{ optional($detalle->producto)->stock_minimo ?? 0 }}" class="rounded border-gray-300 text-xs" placeholder="Stock minimo">
                                    <input type="number" step="0.001" min="0" name="punto_reorden" value="{{ optional($detalle->producto)->punto_reorden ?? 0 }}" class="rounded border-gray-300 text-xs" placeholder="Punto reorden">
                                    <label class="inline-flex items-center gap-2 text-xs text-gray-700 col-span-1">
                                        <input type="checkbox" name="requiere_formula" value="1" @checked($detalle->requiere_formula) class="rounded border-gray-300">
                                        Formula
                                    </label>
                                    <button type="submit" class="px-3 py-1.5 rounded bg-gray-900 text-white text-xs">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $detalles->links() }}</div>
    </div>
</div>
@endsection
