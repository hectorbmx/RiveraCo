@extends('layouts.admin')

@section('title', 'Nuevo producto')

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Nuevo producto</h1>
            <p class="text-sm text-slate-500">Alta rápida del producto (después podrás completar en el expediente).</p>
        </div>

        <a href="{{ route('productos.index') }}"
           class="text-sm text-slate-500 hover:text-slate-800">
            ← Volver
        </a>
    </div>

    {{-- Errores --}}
    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            Hay errores en el formulario, revisa la información.
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow p-6">
        <form method="POST" action="{{ route('productos.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre *</label>
                <input name="nombre"
                       value="{{ old('nombre', $producto->nombre ?? '') }}"
                       required
                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                @error('nombre')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">SKU</label>
                    <input name="sku"
                           value="{{ old('sku', $producto->sku ?? '') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                    @error('sku')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Unidad</label>
                    <input name="unidad"
                           value="{{ old('unidad', $producto->unidad ?? '') }}"
                           placeholder="pza, kg, m, caja..."
                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                    @error('unidad')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo</label>
                    <input name="tipo"
                           value="{{ old('tipo', $producto->tipo ?? 'PRODUCTO') }}"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                    @error('tipo')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox"
                           name="activo"
                           value="1"
                           {{ old('activo', $producto->activo ?? true) ? 'checked' : '' }}>
                    <label class="text-sm text-slate-700">Activo</label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Descripción</label>
                <textarea name="descripcion"
                          rows="3"
                          class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
                @error('descripcion')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('productos.index') }}"
                   class="px-4 py-2 rounded-xl text-sm border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Cancelar
                </a>

                <button class="bg-[#0B265A] text-white px-4 py-2 rounded-xl text-sm hover:opacity-90">
                    Crear producto
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
