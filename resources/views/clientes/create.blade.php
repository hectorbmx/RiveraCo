@extends('layouts.admin')

@section('title', 'Nuevo Cliente')

@section('content')

<div class="max-w-3xl mx-auto">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#0B265A]">Nuevo Cliente</h1>

        <a href="{{ route('clientes.index') }}"
           class="text-sm text-slate-600 hover:text-slate-900">
            ← Volver a la lista
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">

        {{-- MENSAJES DE ERROR GLOBAL --}}
        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-sm">
                Hay errores en el formulario, revisa la información.
            </div>
        @endif

        <form action="{{ route('clientes.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Nombre comercial --}}
            <div>
                <label for="nombre_comercial" class="block text-sm font-medium text-slate-700">
                    Nombre comercial <span class="text-red-500">*</span>
                </label>
                <input type="text" id="nombre_comercial" name="nombre_comercial"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       value="{{ old('nombre_comercial') }}" required>
                @error('nombre_comercial')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Razón social --}}
            <div>
                <label for="razon_social" class="block text-sm font-medium text-slate-700">
                    Razón social
                </label>
                <input type="text" id="razon_social" name="razon_social"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       value="{{ old('razon_social') }}">
                @error('razon_social')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- RFC --}}
            <div>
                <label for="rfc" class="block text-sm font-medium text-slate-700">
                    RFC
                </label>
                <input type="text" id="rfc" name="rfc"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       value="{{ old('rfc') }}">
                @error('rfc')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Teléfono y correo (2 columnas en desktop) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label for="telefono" class="block text-sm font-medium text-slate-700">
                        Teléfono
                    </label>
                    <input type="text" id="telefono" name="telefono"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('telefono') }}">
                    @error('telefono')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">
                        Correo electrónico
                    </label>
                    <input type="email" id="email" name="email"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('email') }}">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Dirección detallada --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="calle" class="block text-sm font-medium text-slate-700">
            Calle
        </label>
        <input type="text" id="calle" name="calle"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]"
               value="{{ old('calle') }}">
        @error('calle')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="colonia" class="block text-sm font-medium text-slate-700">
            Colonia
        </label>
        <input type="text" id="colonia" name="colonia"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]"
               value="{{ old('colonia') }}">
        @error('colonia')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div>
        <label for="ciudad" class="block text-sm font-medium text-slate-700">
            Ciudad
        </label>
        <input type="text" id="ciudad" name="ciudad"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]"
               value="{{ old('ciudad') }}">
        @error('ciudad')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="estado" class="block text-sm font-medium text-slate-700">
            Estado
        </label>
        <input type="text" id="estado" name="estado"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]"
               value="{{ old('estado') }}">
        @error('estado')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="pais" class="block text-sm font-medium text-slate-700">
            País
        </label>
        <input type="text" id="pais" name="pais"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]"
               value="{{ old('pais', 'México') }}">
        @error('pais')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>


            {{-- Activo --}}
            <div class="flex items-center gap-2">
                <input type="checkbox" id="activo" name="activo" value="1"
                       class="rounded border-slate-300 text-[#FFC107] shadow-sm
                              focus:ring-[#FFC107]"
                       {{ old('activo', true) ? 'checked' : '' }}>
                <label for="activo" class="text-sm text-slate-700">
                    Cliente activo
                </label>
            </div>

            {{-- BOTONES --}}
            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('clientes.index') }}"
                   class="px-4 py-2 rounded-xl border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-[#FFC107] text-[#0B265A] text-sm font-semibold
                               shadow hover:bg-[#e0ac05]">
                    Guardar Cliente
                </button>
            </div>

        </form>
    </div>
</div>

@endsection
