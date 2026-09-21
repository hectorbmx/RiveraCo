@php
    $isEdit = ($modo ?? 'crear') === 'editar';
    $action = $isEdit
        ? route('huentitan.productos.catalogo.update', $producto)
        : route('huentitan.productos.store');

    $unidadCompraActual = old('unidad_compra', $producto->unidad_compra ?: ($producto->unidad_base ?: 'PZA'));
    $cantidadUnidadActual = old('cantidad_por_unidad_compra', $producto->cantidad_por_unidad_compra ?: 1);
    $unidadBaseActual = old('unidad_base', $producto->unidad_base ?: ($producto->unidad ?: 'PZA'));
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($isEdit)
        @method('PATCH')
    @endif

    <div class="bg-white border rounded-lg p-6 shadow-sm space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <label class="block md:col-span-2">
                <span class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></span>
                <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}" required maxlength="255" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('nombre')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block md:col-span-2">
                <span class="block text-sm font-medium text-gray-700 mb-1">Descripcion</span>
                <textarea name="descripcion" rows="3" maxlength="500" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('descripcion', $producto->descripcion) }}</textarea>
                @error('descripcion')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            @if($isEdit)
                <div class="rounded-md border bg-gray-50 p-3 md:col-span-2">
                    <div class="text-xs text-gray-500">Codigo</div>
                    <div class="mt-1 font-mono text-sm font-semibold text-gray-900">{{ $producto->sku }}</div>
                </div>
            @endif

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Unidad compra <span class="text-red-500">*</span></span>
                <select name="unidad_compra" required class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($unidadesCompra as $codigo => $nombre)
                        <option value="{{ $codigo }}" @selected($unidadCompraActual === $codigo)>{{ $codigo }} - {{ $nombre }}</option>
                    @endforeach
                </select>
                @error('unidad_compra')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Cantidad por unidad <span class="text-red-500">*</span></span>
                <input type="number" step="0.000001" min="0.000001" name="cantidad_por_unidad_compra" value="{{ $cantidadUnidadActual }}" required class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('cantidad_por_unidad_compra')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Unidad base <span class="text-red-500">*</span></span>
                <select name="unidad_base" required class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($unidadesBase as $codigo => $nombre)
                        <option value="{{ $codigo }}" @selected($unidadBaseActual === $codigo)>{{ $codigo }} - {{ $nombre }}</option>
                    @endforeach
                </select>
                @error('unidad_base')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></span>
                <select name="tipo_inventario" required class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="materia_prima" @selected(old('tipo_inventario', $producto->tipo_inventario ?? 'materia_prima') === 'materia_prima')>Materia prima</option>
                    <option value="producto_terminado" @selected(old('tipo_inventario', $producto->tipo_inventario) === 'producto_terminado')>Producto terminado</option>
                    <option value="subensamble" @selected(old('tipo_inventario', $producto->tipo_inventario) === 'subensamble')>Subensamble</option>
                </select>
                @error('tipo_inventario')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Origen <span class="text-red-500">*</span></span>
                <select name="origen_abastecimiento" required class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="compra" @selected(old('origen_abastecimiento', $producto->origen_abastecimiento ?? 'compra') === 'compra')>Compra</option>
                    <option value="fabricacion" @selected(old('origen_abastecimiento', $producto->origen_abastecimiento) === 'fabricacion')>Fabricacion</option>
                    <option value="ambos" @selected(old('origen_abastecimiento', $producto->origen_abastecimiento) === 'ambos')>Ambos</option>
                </select>
                @error('origen_abastecimiento')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Stock minimo</span>
                <input type="number" step="0.001" min="0" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo ?? 0) }}" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('stock_minimo')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label class="flex items-start gap-3 rounded-md border border-blue-100 bg-blue-50 p-3">
                <input type="checkbox" name="requiere_formula" value="1" @checked(old('requiere_formula', $producto->requiere_formula)) class="mt-1 rounded border-blue-300 text-[#0B265A] focus:ring-[#0B265A]">
                <span>
                    <span class="block text-sm font-semibold text-[#0B265A]">Requiere formula</span>
                    <span class="block text-xs text-blue-700">Activa la formula de materiales y costos de produccion.</span>
                </span>
            </label>

            <label class="flex items-start gap-3 rounded-md border border-emerald-100 bg-emerald-50 p-3">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $producto->activo ?? true)) class="mt-1 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-700">
                <span>
                    <span class="block text-sm font-semibold text-emerald-800">Activo</span>
                    <span class="block text-xs text-emerald-700">Disponible para compras, entradas e inventario HUENTITAN.</span>
                </span>
            </label>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
        <a href="{{ $isEdit ? route('huentitan.productos.show', $producto) : route('huentitan.productos.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Cancelar</a>
        <button class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-[#0B265A] text-white text-sm font-semibold hover:bg-[#12336f]">
            {{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}
        </button>
    </div>
</form>
