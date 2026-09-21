@extends('layouts.admin')

@section('title', 'HUENTITAN - Producto')

@section('content')
@php
    $tabs = [
        'resumen' => 'Informacion general',
        'inventario' => 'Inventario',
        'especificaciones' => 'Especificaciones',
        'kardex' => 'Kardex',
        'proveedores' => 'Proveedores',
        'costos' => 'Costos',
    ];

    if ($producto->requiere_formula) {
        $tabs = array_slice($tabs, 0, 4, true) + ['formula' => 'Formula'] + array_slice($tabs, 4, null, true);
    }

    $tipoLabel = str_replace('_', ' ', $producto->tipo_inventario ?? '-');
    $origenLabel = str_replace('_', ' ', $producto->origen_abastecimiento ?? '-');
    $estaBajoMinimo = $resumen['stock_minimo'] > 0 && $resumen['stock_disponible'] <= $resumen['stock_minimo'];
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">HUENTITAN / Productos</div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">{{ $producto->nombre }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ $producto->sku ?? 'Sin codigo' }} &middot; {{ $producto->unidad ?? 'Sin unidad' }} &middot; {{ $producto->activo ? 'Activo' : 'Inactivo' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('huentitan.productos.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Volver a productos</a>
            <a href="{{ route('productos.edit', ['producto' => $producto->id, 'tab' => 'general']) }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">Editar catalogo general</a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            Hay errores en la informacion capturada. Revisa los campos marcados.
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Stock actual</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumen['stock_actual'], 3) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Disponible</div>
            <div class="mt-2 text-2xl font-semibold {{ $estaBajoMinimo ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($resumen['stock_disponible'], 3) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Reservado</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumen['stock_reservado'], 3) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Stock minimo</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumen['stock_minimo'], 3) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Costo promedio</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">${{ number_format($resumen['costo_promedio'], 2) }}</div>
        </div>
    </div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <div class="px-4 py-4 border-b bg-[#0B265A] text-white">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <div class="text-white/65 text-xs">Tipo</div>
                    <div class="font-semibold capitalize">{{ $tipoLabel }}</div>
                </div>
                <div>
                    <div class="text-white/65 text-xs">Origen</div>
                    <div class="font-semibold capitalize">{{ $origenLabel }}</div>
                </div>
                <div>
                    <div class="text-white/65 text-xs">Formula</div>
                    <div class="font-semibold">{{ $producto->requiere_formula ? 'Requiere formula' : 'No requiere formula' }}</div>
                </div>
                <div>
                    <div class="text-white/65 text-xs">Almacen</div>
                    <div class="font-semibold">{{ $almacen->nombre }}</div>
                </div>
            </div>
        </div>

        <div class="border-b bg-gray-50 px-4 overflow-x-auto">
            <nav class="flex gap-6 text-sm min-w-max">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('huentitan.productos.show', ['producto' => $producto->id, 'tab' => $key]) }}"
                       class="py-3 border-b-2 {{ $tab === $key ? 'border-[#FFC107] text-[#0B265A] font-semibold' : 'border-transparent text-gray-500 hover:text-gray-900' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="p-4 md:p-6">
            @if($tab === 'resumen')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-gray-900">Informacion general</h2>
                        <form method="POST" action="{{ route('huentitan.productos.update', $producto) }}" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div class="border rounded-md p-3 sm:col-span-2">
                                    <div class="text-xs text-gray-500">Descripcion</div>
                                    <div class="mt-1 text-gray-900">{{ $producto->descripcion ?: 'Sin descripcion capturada.' }}</div>
                                </div>

                                <label class="block">
                                    <span class="block text-xs font-semibold text-gray-500 mb-1">Tipo</span>
                                    <select name="tipo_inventario" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                        <option value="materia_prima" @selected(($producto->tipo_inventario ?? '') === 'materia_prima')>Materia prima</option>
                                        <option value="producto_terminado" @selected(($producto->tipo_inventario ?? '') === 'producto_terminado')>Producto terminado</option>
                                        <option value="subensamble" @selected(($producto->tipo_inventario ?? '') === 'subensamble')>Subensamble</option>
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="block text-xs font-semibold text-gray-500 mb-1">Origen</span>
                                    <select name="origen_abastecimiento" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                        <option value="compra" @selected(($producto->origen_abastecimiento ?? '') === 'compra')>Compra</option>
                                        <option value="fabricacion" @selected(($producto->origen_abastecimiento ?? '') === 'fabricacion')>Fabricacion</option>
                                        <option value="ambos" @selected(($producto->origen_abastecimiento ?? '') === 'ambos')>Ambos</option>
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="block text-xs font-semibold text-gray-500 mb-1">Stock minimo</span>
                                    <input type="number" step="0.001" min="0" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo ?? 0) }}" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                </label>

                                <div class="border rounded-md p-3">
                                    <div class="text-xs text-gray-500">Unidad</div>
                                    <div class="mt-1 text-gray-900">{{ $producto->unidad ?: '-' }}</div>
                                </div>

                                <label class="sm:col-span-2 flex items-start gap-3 rounded-md border border-blue-100 bg-blue-50 p-3">
                                    <input type="checkbox" name="requiere_formula" value="1" @checked(old('requiere_formula', $producto->requiere_formula)) class="mt-1 rounded border-blue-300 text-[#0B265A] focus:ring-[#0B265A]">
                                    <span>
                                        <span class="block text-sm font-semibold text-[#0B265A]">Requiere formula</span>
                                        <span class="block text-xs text-blue-700">Activa el tab Formula para capturar materiales internos de HUENTITAN y calcular consumos de produccion.</span>
                                    </span>
                                </label>
                            </div>

                            <div class="flex justify-end">
                                <button class="px-4 py-2 rounded-md bg-[#0B265A] text-white text-sm font-semibold hover:bg-[#12336f]">Guardar informacion</button>
                            </div>
                        </form>
                    </section>

                    <section class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-sm font-semibold text-gray-900">Ultimo movimiento</h2>
                            <a href="{{ route('huentitan.productos.show', ['producto' => $producto->id, 'tab' => 'kardex']) }}" class="text-xs font-semibold text-[#0B265A] hover:underline">Ver kardex</a>
                        </div>
                        @if($ultimoMovimiento)
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div class="border rounded-md p-3">
                                    <dt class="text-xs text-gray-500">Fecha</dt>
                                    <dd class="mt-1 text-gray-900">{{ $ultimoMovimiento->fecha ? \Illuminate\Support\Carbon::parse($ultimoMovimiento->fecha)->format('Y-m-d') : '-' }}</dd>
                                </div>
                                <div class="border rounded-md p-3">
                                    <dt class="text-xs text-gray-500">Movimiento</dt>
                                    <dd class="mt-1 text-gray-900">{{ $ultimoMovimiento->tipo_movimiento }}</dd>
                                </div>
                                <div class="border rounded-md p-3">
                                    <dt class="text-xs text-gray-500">Cantidad</dt>
                                    <dd class="mt-1 text-gray-900">{{ number_format((float) $ultimoMovimiento->cantidad, 3) }}</dd>
                                </div>
                                <div class="border rounded-md p-3">
                                    <dt class="text-xs text-gray-500">Saldo resultante</dt>
                                    <dd class="mt-1 text-gray-900">{{ number_format((float) $ultimoMovimiento->saldo_cantidad, 3) }}</dd>
                                </div>
                                <div class="border rounded-md p-3 sm:col-span-2">
                                    <dt class="text-xs text-gray-500">Documento origen</dt>
                                    <dd class="mt-1 text-gray-900">
                                        @if($ultimoMovimiento->documento_huentitan_route)
                                            <a href="{{ $ultimoMovimiento->documento_huentitan_route }}" class="font-semibold text-[#0B265A] hover:underline">
                                                {{ $ultimoMovimiento->documento_huentitan_tipo }} {{ $ultimoMovimiento->documento_huentitan_folio }}
                                            </a>
                                            @if($ultimoMovimiento->documento_huentitan_obra)
                                                <div class="mt-1 text-xs text-gray-500">{{ $ultimoMovimiento->documento_huentitan_obra }}</div>
                                            @endif
                                        @else
                                            {{ $ultimoMovimiento->documento_tipo ?? 'Sin documento' }} {{ $ultimoMovimiento->documento_id ? '#' . $ultimoMovimiento->documento_id : '' }}
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        @else
                            <div class="border rounded-md p-4 text-sm text-gray-500">Sin movimientos registrados para este producto en HUENTITAN.</div>
                        @endif
                    </section>
                </div>
            @endif

            @if($tab === 'inventario')
                <div class="space-y-4">
                    @if($estaBajoMinimo)
                        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            El stock disponible esta en o por debajo del minimo configurado.
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div class="border rounded-md p-4">
                            <div class="text-xs text-gray-500">Stock actual</div>
                            <div class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($resumen['stock_actual'], 3) }}</div>
                        </div>
                        <div class="border rounded-md p-4">
                            <div class="text-xs text-gray-500">Stock reservado</div>
                            <div class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($resumen['stock_reservado'], 3) }}</div>
                        </div>
                        <div class="border rounded-md p-4">
                            <div class="text-xs text-gray-500">Stock disponible</div>
                            <div class="mt-1 text-xl font-semibold {{ $estaBajoMinimo ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($resumen['stock_disponible'], 3) }}</div>
                        </div>
                        <div class="border rounded-md p-4">
                            <div class="text-xs text-gray-500">Stock minimo</div>
                            <div class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($resumen['stock_minimo'], 3) }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="border rounded-md p-4">
                            <div class="text-xs text-gray-500">Costo promedio</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">${{ number_format($resumen['costo_promedio'], 4) }}</div>
                        </div>
                        <div class="border rounded-md p-4">
                            <div class="text-xs text-gray-500">Valor total</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">${{ number_format($resumen['valor_total'], 2) }}</div>
                        </div>
                        <div class="border rounded-md p-4">
                            <div class="text-xs text-gray-500">Almacen</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{{ $almacen->nombre }}</div>
                        </div>
                    </div>
                </div>
            @endif

            @if($tab === 'kardex')
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-3 py-2 text-left">Fecha</th>
                                <th class="px-3 py-2 text-left">Movimiento</th>
                                <th class="px-3 py-2 text-right">Cantidad</th>
                                <th class="px-3 py-2 text-right">Costo</th>
                                <th class="px-3 py-2 text-right">Saldo</th>
                                <th class="px-3 py-2 text-left">Documento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($movimientos as $movimiento)
                                <tr>
                                    <td class="px-3 py-2">{{ $movimiento->fecha ? \Illuminate\Support\Carbon::parse($movimiento->fecha)->format('Y-m-d') : '-' }}</td>
                                    <td class="px-3 py-2">{{ $movimiento->tipo_movimiento }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $movimiento->cantidad, 3) }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format((float) $movimiento->costo_unitario, 4) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $movimiento->saldo_cantidad, 3) }}</td>
                                    <td class="px-3 py-2">
                                        @if($movimiento->documento_huentitan_route)
                                            <a href="{{ $movimiento->documento_huentitan_route }}" class="font-semibold text-[#0B265A] hover:underline">
                                                {{ $movimiento->documento_huentitan_tipo }} {{ $movimiento->documento_huentitan_folio }}
                                            </a>
                                            @if($movimiento->documento_huentitan_obra)
                                                <div class="text-xs text-gray-500">{{ $movimiento->documento_huentitan_obra }}</div>
                                            @endif
                                        @else
                                            {{ $movimiento->documento_tipo ?? 'Sin documento' }} {{ $movimiento->documento_id ? '#' . $movimiento->documento_id : '' }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-gray-500">Todavia no hay movimientos aplicados para este producto en HUENTITAN.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $movimientos->links() }}</div>
            @endif

            @if($tab === 'formula')
                <div class="space-y-5">
                    <form method="POST" action="{{ route('huentitan.productos.formula.update', $producto) }}" class="border rounded-lg p-5 space-y-4">
                        @csrf
                        @method('PATCH')
                        <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Formula de fabricacion</h2>
                                <p class="mt-1 text-sm text-gray-600">Define el consumo esperado para fabricar la cantidad base de este producto.</p>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <div class="text-sm font-semibold text-[#0B265A]">
                                    Materiales: ${{ number_format((float) ($costoMaterialesFormula ?? 0), 2) }} &middot; Herramientas: ${{ number_format((float) ($costoHerramientasFormula ?? 0), 2) }} &middot; Total: ${{ number_format((float) $costoFormulaEstimado, 2) }} &middot; Unitario: ${{ number_format((float) $costoFormulaUnitario, 2) }}
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" data-open-modal="modal-material-formula" class="px-3 py-2 rounded-md bg-[#FFC107] text-[#0B265A] text-xs font-semibold hover:opacity-90">Agregar material</button>
                                    <button type="button" data-open-modal="modal-herramienta-formula" class="px-3 py-2 rounded-md bg-[#0B265A] text-white text-xs font-semibold hover:bg-[#12336f]">Agregar herramienta</button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                            <label class="block">
                                <span class="block text-xs font-semibold text-gray-500 mb-1">Cantidad base</span>
                                <input type="number" step="0.001" min="0.001" name="cantidad_base" value="{{ old('cantidad_base', $formulaProducto->cantidad_base ?? 1) }}" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                            </label>
                            <label class="block">
                                <span class="block text-xs font-semibold text-gray-500 mb-1">Unidad base</span>
                                @php($formulaUnidadBase = old('unidad_base', $formulaProducto->unidad_base ?? ($producto->unidad_base ?: $producto->unidad)))
                                <select name="unidad_base" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                    @foreach($unidadesBase as $codigo => $nombre)
                                        <option value="{{ $codigo }}" @selected($formulaUnidadBase === $codigo)>{{ $codigo }} - {{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="block text-xs font-semibold text-gray-500 mb-1">Merma esperada %</span>
                                <input type="number" step="0.001" min="0" max="100" name="merma_esperada_porcentaje" value="{{ old('merma_esperada_porcentaje', $formulaProducto->merma_esperada_porcentaje ?? 0) }}" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                            </label>
                            <label class="block">
                                <span class="block text-xs font-semibold text-gray-500 mb-1">Tiempo min.</span>
                                <input type="number" step="1" min="0" name="tiempo_estimado_minutos" value="{{ old('tiempo_estimado_minutos', $formulaProducto->tiempo_estimado_minutos ?? '') }}" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                            </label>
                        </div>

                        <label class="block text-sm">
                            <span class="block text-xs font-semibold text-gray-500 mb-1">Notas de fabricacion</span>
                            <textarea name="notas" rows="3" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">{{ old('notas', $formulaProducto->notas ?? '') }}</textarea>
                        </label>

                        <div class="flex justify-end">
                            <button class="px-4 py-2 rounded-md bg-[#0B265A] text-white text-sm font-semibold hover:bg-[#12336f]">Guardar formula</button>
                        </div>
                    </form>

                    <div id="modal-material-formula" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6" data-formula-modal>
                        <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl">
                            <div class="flex items-center justify-between border-b px-5 py-4">
                                <h3 class="text-sm font-semibold text-gray-900">Agregar material</h3>
                                <button type="button" data-close-modal class="text-sm font-semibold text-gray-500 hover:text-gray-900">Cerrar</button>
                            </div>
                            <form method="POST" action="{{ route('huentitan.productos.formula-materiales.store', $producto) }}" class="p-5 space-y-4">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 text-sm items-end">
                                    <div class="block md:col-span-6 relative" data-formula-material-search data-search-url="{{ route('huentitan.productos.formula-materiales.buscar', $producto) }}">
                                        <label class="block">
                                            <span class="block text-xs font-semibold text-gray-500 mb-1">Material HUENTITAN</span>
                                            <input type="hidden" name="material_producto_id" data-material-id value="{{ old('material_producto_id') }}">
                                            <input type="text" data-material-search-input autocomplete="off" placeholder="Buscar por nombre o codigo" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]" required>
                                        </label>
                                        <div data-material-selected class="mt-2 hidden rounded-md border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-[#0B265A]"></div>
                                        <div data-material-results class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-md border bg-white shadow-lg"></div>
                                    </div>
                                    <label class="block md:col-span-2">
                                        <span class="block text-xs font-semibold text-gray-500 mb-1">Cantidad</span>
                                        <input type="number" step="0.001" min="0.001" name="cantidad" value="{{ old('cantidad') }}" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]" required>
                                    </label>
                                    <label class="block md:col-span-2">
                                        <span class="block text-xs font-semibold text-gray-500 mb-1">Unidad</span>
                                        @php($materialUnidad = old('unidad'))
                                        <select name="unidad" data-material-unit class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                            <option value="">Usar unidad del material</option>
                                            @foreach($unidadesBase as $codigo => $nombre)
                                                <option value="{{ $codigo }}" @selected($materialUnidad === $codigo)>{{ $codigo }} - {{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="block md:col-span-2">
                                        <span class="block text-xs font-semibold text-gray-500 mb-1">Merma %</span>
                                        <input type="number" step="0.001" min="0" max="100" name="merma_porcentaje" value="{{ old('merma_porcentaje', 0) }}" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                    </label>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm items-end">
                                    <label class="block">
                                        <span class="block text-xs font-semibold text-gray-500 mb-1">Metodo costo</span>
                                        <select name="metodo_costo" data-material-cost-method class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                            <option value="promedio_inventario">Promedio inventario</option>
                                            <option value="manual">Manual</option>
                                        </select>
                                    </label>
                                    <label class="block">
                                        <span class="block text-xs font-semibold text-gray-500 mb-1">Costo promedio detectado</span>
                                        <input type="text" data-material-average-cost readonly value="$0.0000" class="w-full rounded-md border-slate-200 bg-gray-50 text-sm text-gray-700">
                                    </label>
                                    <label class="block">
                                        <span class="block text-xs font-semibold text-gray-500 mb-1">Costo manual</span>
                                        <input type="number" step="0.0001" min="0" name="costo_unitario_override" data-material-manual-cost class="w-full rounded-md border-slate-200 bg-gray-50 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                    </label>
                                </div>
                                <label class="block text-sm">
                                    <span class="block text-xs font-semibold text-gray-500 mb-1">Notas del material</span>
                                    <input type="text" name="notas" value="{{ old('notas') }}" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                </label>
                                <div class="flex justify-end gap-2">
                                    <button type="button" data-close-modal class="px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Cancelar</button>
                                    <button class="px-4 py-2 rounded-md bg-[#FFC107] text-[#0B265A] text-sm font-semibold hover:opacity-90">Agregar material</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="modal-herramienta-formula" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6" data-formula-modal>
                        <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl">
                            <div class="flex items-center justify-between border-b px-5 py-4">
                                <h3 class="text-sm font-semibold text-gray-900">Agregar herramienta al precio unitario</h3>
                                <button type="button" data-close-modal class="text-sm font-semibold text-gray-500 hover:text-gray-900">Cerrar</button>
                            </div>
                            <form method="POST" action="{{ route('huentitan.productos.formula-herramientas.store', $producto) }}" class="p-5 space-y-4">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 text-sm items-end">
                                    <div class="block md:col-span-6 relative" data-formula-herramienta-search data-search-url="{{ route('huentitan.productos.formula-herramientas.buscar', $producto) }}">
                                        <label class="block">
                                            <span class="block text-xs font-semibold text-gray-500 mb-1">Herramienta HUENTITAN</span>
                                            <input type="hidden" name="herramienta_id" data-herramienta-id value="{{ old('herramienta_id') }}">
                                            <input type="text" data-herramienta-search-input autocomplete="off" placeholder="Buscar por nombre, codigo o serie" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]" required>
                                        </label>
                                        <div data-herramienta-selected class="mt-2 hidden rounded-md border border-yellow-100 bg-yellow-50 px-3 py-2 text-xs text-yellow-900"></div>
                                        <div data-herramienta-results class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-md border bg-white shadow-lg"></div>
                                    </div>
                                    <label class="block md:col-span-2">
                                        <span class="block text-xs font-semibold text-gray-500 mb-1">Cantidad</span>
                                        <input type="number" step="0.001" min="0.001" name="cantidad" value="{{ old('cantidad', 1) }}" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]" required>
                                    </label>
                                    <label class="block md:col-span-2">
                                        <span class="block text-xs font-semibold text-gray-500 mb-1">Costo aplicado</span>
                                        <input type="number" step="0.0001" min="0" name="costo_unitario_aplicado" value="{{ old('costo_unitario_aplicado') }}" data-herramienta-costo class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]" required>
                                    </label>
                                    <label class="block md:col-span-2">
                                        <span class="block text-xs font-semibold text-gray-500 mb-1">Metodo</span>
                                        <select name="metodo_calculo" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                            <option value="manual">Manual</option>
                                            <option value="prorrateo_por_piezas">Prorrateo por piezas</option>
                                        </select>
                                    </label>
                                </div>
                                <label class="block text-sm">
                                    <span class="block text-xs font-semibold text-gray-500 mb-1">Notas de herramienta</span>
                                    <input type="text" name="notas" value="{{ old('notas') }}" class="w-full rounded-md border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                </label>
                                <div class="flex justify-end gap-2">
                                    <button type="button" data-close-modal class="px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Cancelar</button>
                                    <button class="px-4 py-2 rounded-md bg-[#0B265A] text-white text-sm font-semibold hover:bg-[#12336f]">Agregar herramienta</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="border rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-gray-900">Materiales de la formula</h3>
                            <span class="text-xs text-gray-500">{{ $formulaProducto ? $formulaProducto->materiales->count() : 0 }} materiales</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Material</th>
                                        <th class="px-3 py-2 text-right">Cantidad</th>
                                        <th class="px-3 py-2 text-right">Merma</th>
                                        <th class="px-3 py-2 text-right">Stock disp.</th>
                                        <th class="px-3 py-2 text-right">Costo unit.</th>
                                        <th class="px-3 py-2 text-right">Costo esperado</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse(($formulaProducto->materiales ?? collect()) as $materialFormula)
                                        @php
                                            $stockMaterial = $materialStockMap->get($materialFormula->material_producto_id);
                                            $cantidadConMerma = (float) $materialFormula->cantidad * (1 + ((float) $materialFormula->merma_porcentaje / 100));
                                            $costoUnitarioMaterial = $materialFormula->metodo_costo === 'manual' ? (float) ($materialFormula->costo_unitario_override ?? 0) : (float) ($stockMaterial->costo_promedio ?? 0);
                                            $costoEsperadoMaterial = $cantidadConMerma * $costoUnitarioMaterial;
                                            $metodoCostoMaterial = $materialFormula->metodo_costo === 'manual' ? 'Manual' : 'Promedio';
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-2">
                                                <div class="font-medium text-gray-900">{{ $materialFormula->material->nombre ?? 'Material no encontrado' }}</div>
                                                <div class="text-xs text-gray-500">{{ $materialFormula->material->sku ?? '-' }} · {{ $metodoCostoMaterial }}</div>
                                                @if($materialFormula->notas)<div class="text-xs text-gray-500">{{ $materialFormula->notas }}</div>@endif
                                            </td>
                                            <td class="px-3 py-2 text-right">{{ number_format((float) $materialFormula->cantidad, 3) }} {{ $materialFormula->unidad }}</td>
                                            <td class="px-3 py-2 text-right">{{ number_format((float) $materialFormula->merma_porcentaje, 3) }}%</td>
                                            <td class="px-3 py-2 text-right">{{ number_format(max(0, (float) ($stockMaterial->stock_actual ?? 0) - (float) ($stockMaterial->stock_reservado ?? 0)), 3) }}</td>
                                            <td class="px-3 py-2 text-right">${{ number_format($costoUnitarioMaterial, 4) }}</td>
                                            <td class="px-3 py-2 text-right">${{ number_format($costoEsperadoMaterial, 2) }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <form method="POST" action="{{ route('huentitan.productos.formula-materiales.destroy', ['producto' => $producto->id, 'material' => $materialFormula->id]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-xs font-semibold text-red-600 hover:underline">Quitar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-3 py-8 text-center text-gray-500">Aun no hay materiales capturados en la formula.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-gray-900">Herramientas del precio unitario</h3>
                            <span class="text-xs text-gray-500">{{ $formulaProducto ? $formulaProducto->herramientas->count() : 0 }} herramientas</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Herramienta</th>
                                        <th class="px-3 py-2 text-right">Cantidad</th>
                                        <th class="px-3 py-2 text-right">Costo aplicado</th>
                                        <th class="px-3 py-2 text-right">Costo esperado</th>
                                        <th class="px-3 py-2 text-left">Metodo</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse(($formulaProducto->herramientas ?? collect()) as $herramientaFormula)
                                        @php
                                            $costoEsperadoHerramienta = (float) $herramientaFormula->cantidad * (float) $herramientaFormula->costo_unitario_aplicado;
                                            $metodoHerramienta = $herramientaFormula->metodo_calculo === 'prorrateo_por_piezas' ? 'Prorrateo por piezas' : 'Manual';
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-2">
                                                <div class="font-medium text-gray-900">{{ $herramientaFormula->herramienta->nombre ?? 'Herramienta no encontrada' }}</div>
                                                <div class="text-xs text-gray-500">{{ $herramientaFormula->herramienta->codigo ?? '-' }}</div>
                                                @if($herramientaFormula->notas)<div class="text-xs text-gray-500">{{ $herramientaFormula->notas }}</div>@endif
                                            </td>
                                            <td class="px-3 py-2 text-right">{{ number_format((float) $herramientaFormula->cantidad, 3) }}</td>
                                            <td class="px-3 py-2 text-right">${{ number_format((float) $herramientaFormula->costo_unitario_aplicado, 4) }}</td>
                                            <td class="px-3 py-2 text-right">${{ number_format($costoEsperadoHerramienta, 2) }}</td>
                                            <td class="px-3 py-2">{{ $metodoHerramienta }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <form method="POST" action="{{ route('huentitan.productos.formula-herramientas.destroy', ['producto' => $producto->id, 'herramienta' => $herramientaFormula->id]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-xs font-semibold text-red-600 hover:underline">Quitar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-8 text-center text-gray-500">Aun no hay herramientas capturadas en el precio unitario.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
            @if($tab === 'proveedores')
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-3 py-2 text-left">Proveedor</th>
                                <th class="px-3 py-2 text-left">RFC</th>
                                <th class="px-3 py-2 text-right">Precio lista</th>
                                <th class="px-3 py-2 text-left">Moneda</th>
                                <th class="px-3 py-2 text-right">Entrega</th>
                                <th class="px-3 py-2 text-left">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($producto->proveedores as $proveedor)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $proveedor->nombre }}</td>
                                    <td class="px-3 py-2">{{ $proveedor->rfc ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format((float) $proveedor->pivot->precio_lista, 2) }}</td>
                                    <td class="px-3 py-2">{{ $proveedor->pivot->moneda ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right">{{ $proveedor->pivot->tiempo_entrega_dias !== null ? $proveedor->pivot->tiempo_entrega_dias . ' dias' : '-' }}</td>
                                    <td class="px-3 py-2">{{ $proveedor->pivot->activo ? 'Activo' : 'Inactivo' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-gray-500">Todavia no hay proveedores ligados a este producto.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if($tab === 'costos')
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="border rounded-md p-4">
                            <div class="text-xs text-gray-500">Costo promedio inventario</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">${{ number_format($resumen['costo_promedio'], 4) }}</div>
                        </div>
                        <div class="border rounded-md p-4">
                            <div class="text-xs text-gray-500">Valor actual en stock</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">${{ number_format($resumen['valor_total'], 2) }}</div>
                        </div>
                        <div class="border rounded-md p-4">
                            <div class="text-xs text-gray-500">Proveedores ligados</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($resumen['proveedores']) }}</div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-3 py-2 text-left">Fecha</th>
                                    <th class="px-3 py-2 text-left">Proveedor</th>
                                    <th class="px-3 py-2 text-right">Precio</th>
                                    <th class="px-3 py-2 text-left">Moneda</th>
                                    <th class="px-3 py-2 text-left">Orden compra</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($historialCostos as $costo)
                                    <tr>
                                        <td class="px-3 py-2">{{ $costo->created_at ? \Illuminate\Support\Carbon::parse($costo->created_at)->format('Y-m-d') : '-' }}</td>
                                        <td class="px-3 py-2">{{ $costo->proveedor_nombre }}</td>
                                        <td class="px-3 py-2 text-right">${{ number_format((float) $costo->precio, 2) }}</td>
                                        <td class="px-3 py-2">{{ $costo->moneda ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $costo->orden_compra_id ? '#' . $costo->orden_compra_id : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-center text-gray-500">No hay historial de costos para este producto.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-formula-material-search]').forEach((root) => {
        const input = root.querySelector('[data-material-search-input]');
        const hiddenId = root.querySelector('[data-material-id]');
        const results = root.querySelector('[data-material-results]');
        const selected = root.querySelector('[data-material-selected]');
        const form = root.closest('form');
        const unitInput = form.querySelector('[data-material-unit]');
        const costMethodInput = form.querySelector('[data-material-cost-method]');
        const manualCostInput = form.querySelector('[data-material-manual-cost]');
        const averageCostInput = form.querySelector('[data-material-average-cost]');
        const searchUrl = root.dataset.searchUrl;
        let timer = null;

        const clearResults = () => {
            results.innerHTML = '';
            results.classList.add('hidden');
        };

        const setSelected = (material) => {
            hiddenId.value = material.id;
            input.value = material.label;
            if (unitInput) {
                unitInput.value = material.unidad || '';
            }
            if (averageCostInput) {
                averageCostInput.value = '$' + Number(material.costo_promedio || 0).toFixed(4);
            }
            selected.textContent = material.label + (material.unidad ? ' / ' + material.unidad : '');
            selected.classList.remove('hidden');
            clearResults();
        };

        const renderResults = (items) => {
            results.innerHTML = '';
            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'px-3 py-2 text-sm text-gray-500';
                empty.textContent = 'Sin materiales encontrados';
                results.appendChild(empty);
                results.classList.remove('hidden');
                return;
            }

            items.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-blue-50';

                const label = document.createElement('span');
                label.className = 'font-medium text-gray-900';
                label.textContent = item.label;

                const unit = document.createElement('span');
                unit.className = 'block text-xs text-gray-500';
                unit.textContent = (item.unidad ? 'Unidad: ' + item.unidad : 'Sin unidad') + ' / costo prom. $' + Number(item.costo_promedio || 0).toFixed(4);

                button.appendChild(label);
                button.appendChild(unit);
                button.addEventListener('click', () => setSelected(item));
                results.appendChild(button);
            });

            results.classList.remove('hidden');
        };

        input.addEventListener('input', () => {
            hiddenId.value = '';
            if (unitInput) {
                unitInput.value = '';
            }
            selected.classList.add('hidden');
            selected.textContent = '';
            clearTimeout(timer);

            const term = input.value.trim();
            if (term.length < 2) {
                clearResults();
                return;
            }

            timer = setTimeout(async () => {
                const response = await fetch(searchUrl + '?q=' + encodeURIComponent(term), {
                    headers: { 'Accept': 'application/json' },
                });
                renderResults(response.ok ? await response.json() : []);
            }, 250);
        });

        if (costMethodInput && manualCostInput) {
            const syncManualCostInput = () => {
                const isManual = costMethodInput.value === 'manual';
                if (isManual) {
                    manualCostInput.removeAttribute('readonly');
                } else {
                    manualCostInput.setAttribute('readonly', 'readonly');
                }
                manualCostInput.classList.toggle('bg-gray-50', !isManual);
                manualCostInput.classList.toggle('bg-white', isManual);
                if (!isManual) {
                    manualCostInput.value = '';
                }
            };
            costMethodInput.addEventListener('change', syncManualCostInput);
            syncManualCostInput();
        }

        form.addEventListener('submit', (event) => {
            if (!hiddenId.value) {
                event.preventDefault();
                input.focus();
                selected.textContent = 'Selecciona un material de la lista.';
                selected.classList.remove('hidden');
                return;
            }

            if (costMethodInput && manualCostInput && costMethodInput.value === 'manual' && Number(manualCostInput.value || 0) <= 0) {
                event.preventDefault();
                manualCostInput.readOnly = false;
                manualCostInput.classList.remove('bg-gray-50');
                manualCostInput.classList.add('bg-white');
                manualCostInput.focus();
            }
        });

        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) {
                clearResults();
            }
        });
    });
});
</script>
@endpush
@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-formula-herramienta-search]').forEach((root) => {
        const input = root.querySelector('[data-herramienta-search-input]');
        const hiddenId = root.querySelector('[data-herramienta-id]');
        const selected = root.querySelector('[data-herramienta-selected]');
        const results = root.querySelector('[data-herramienta-results]');
        const costoInput = document.querySelector('[data-herramienta-costo]');
        const searchUrl = root.dataset.searchUrl;
        let timer = null;
        let controller = null;
        if (!input || !hiddenId || !selected || !results || !searchUrl) return;

        const closeResults = () => { results.classList.add('hidden'); results.innerHTML = ''; };
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

        function showSelected(item) {
            hiddenId.value = item.id;
            input.value = item.label || item.nombre;
            if (costoInput && item.costo_sugerido !== null && item.costo_sugerido !== undefined) {
                costoInput.value = Number(item.costo_sugerido).toFixed(4);
            }
            selected.innerHTML = `<strong>${escapeHtml(item.label || item.nombre)}</strong>${item.costo_sugerido !== null && item.costo_sugerido !== undefined ? ` · sugerido $${Number(item.costo_sugerido).toFixed(4)}` : ''}`;
            selected.classList.remove('hidden');
            closeResults();
        }

        function render(items) {
            if (!items.length) {
                results.innerHTML = '<div class="p-2 text-sm text-gray-500">Sin resultados</div>';
                results.classList.remove('hidden');
                return;
            }
            results.innerHTML = items.map((item) => {
                const encoded = escapeHtml(JSON.stringify(item));
                const suggested = item.costo_sugerido !== null && item.costo_sugerido !== undefined ? `Sugerido: $${Number(item.costo_sugerido).toFixed(4)}` : 'Sin vida util capturada';
                return `<button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 border-b last:border-b-0" data-item="${encoded}"><div class="font-medium text-gray-900">${escapeHtml(item.label || item.nombre)}</div><div class="text-xs text-gray-500">${escapeHtml(suggested)}</div></button>`;
            }).join('');
            results.classList.remove('hidden');
            results.querySelectorAll('button[data-item]').forEach((button) => {
                button.addEventListener('click', () => showSelected(JSON.parse(button.dataset.item)));
            });
        }

        async function search(q) {
            if (q.length < 2) {
                hiddenId.value = '';
                selected.classList.add('hidden');
                closeResults();
                return;
            }
            if (controller) controller.abort();
            controller = new AbortController();
            try {
                const response = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, { signal: controller.signal, headers: { 'Accept': 'application/json' } });
                if (!response.ok) throw new Error('Error buscando herramientas');
                render(await response.json());
            } catch (error) {
                if (error.name === 'AbortError') return;
                results.innerHTML = '<div class="p-2 text-sm text-red-600">Error buscando herramientas</div>';
                results.classList.remove('hidden');
            }
        }

        input.addEventListener('input', (event) => {
            hiddenId.value = '';
            selected.classList.add('hidden');
            clearTimeout(timer);
            timer = setTimeout(() => search(event.target.value.trim()), 250);
        });
        document.addEventListener('click', (event) => { if (!root.contains(event.target)) closeResults(); });
    });
})();
</script>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const openFormulaModal = (modal) => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const closeFormulaModal = (modal) => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (!document.querySelector('[data-formula-modal]:not(.hidden)')) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.openModal);
            if (modal) {
                openFormulaModal(modal);
            }
        });
    });

    document.querySelectorAll('[data-formula-modal]').forEach((modal) => {
        modal.querySelectorAll('[data-close-modal]').forEach((button) => {
            button.addEventListener('click', () => closeFormulaModal(modal));
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeFormulaModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-formula-modal]:not(.hidden)').forEach(closeFormulaModal);
    });
});
</script>
@endpush



