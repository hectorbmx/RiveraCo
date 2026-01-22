<form method="POST" action="{{ route('productos.update', $producto->id) }}" class="space-y-4">
    @csrf
    @method('PUT')

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre</label>
            <input name="nombre" value="{{ old('nombre', $producto->nombre) }}"
                   class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">SKU</label>
            <input name="sku" value="{{ old('sku', $producto->sku) }}"
                   class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Unidad</label>
            <input name="unidad" value="{{ old('unidad', $producto->unidad) }}"
                   class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo</label>
            <input name="tipo" value="{{ old('tipo', $producto->tipo ?? 'PRODUCTO') }}"
                   class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
            <p class="text-xs text-slate-400 mt-1">Sugerido: PRODUCTO</p>
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Descripción</label>
        <textarea name="descripcion"
                  class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm"
                  rows="3">{{ old('descripcion', $producto->descripcion) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="activo" value="1" {{ old('activo', $producto->activo) ? 'checked' : '' }}>
            Activo
        </label>

        <button class="ml-auto bg-[#0B265A] text-white px-4 py-2 rounded-xl text-sm hover:opacity-90">
            Guardar
        </button>
    </div>
</form>
