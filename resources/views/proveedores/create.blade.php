@extends('layouts.admin')

@section('title', 'Nuevo Proveedor')

@section('content')

<div class="max-w-4xl mx-auto">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#0B265A]">
            Nuevo Proveedor
        </h1>

        <a href="{{ route('proveedores.index') }}"
           class="text-sm text-slate-600 hover:text-slate-900">
            ← Volver a la lista
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">

        {{-- ERROR GLOBAL --}}
        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-sm">
                Hay errores en el formulario, revisa la información.
            </div>
        @endif

        <form action="{{ route('proveedores.store') }}"
              method="POST"
              class="space-y-5">

            @csrf

            {{-- Nombre --}}
            <div>
                <label class="block text-sm font-medium text-slate-700">
                    Nombre <span class="text-red-500">*</span>
                </label>

                <input type="text"
                       name="nombre"
                       value="{{ old('nombre') }}"
                       required
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">

                @error('nombre')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Descripción --}}
            <div>
                <label class="block text-sm font-medium text-slate-700">
                    Descripción
                </label>

                <textarea name="descripcion"
                          rows="3"
                          class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                 focus:border-[#FFC107] focus:ring-[#FFC107]">{{ old('descripcion') }}</textarea>

                @error('descripcion')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- RFC + Fecha --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        RFC
                    </label>

                    <input type="text"
                           name="rfc"
                           value="{{ old('rfc') }}"
                           class="mt-1 block w-full uppercase rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">

                    @error('rfc')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Fecha de registro
                    </label>

                    <input type="date"
                           name="fecha_registro"
                           value="{{ old('fecha_registro', now()->toDateString()) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">

                    @error('fecha_registro')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Domicilio --}}
            <div>
                <label class="block text-sm font-medium text-slate-700">
                    Domicilio
                </label>

                <input type="text"
                       name="domicilio"
                       value="{{ old('domicilio') }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">

                @error('domicilio')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Teléfono + Email --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Teléfono
                    </label>

                    <input type="text"
                           name="telefono"
                           value="{{ old('telefono') }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">

                    @error('telefono')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Correo electrónico
                    </label>

                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">

                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- DATOS BANCARIOS --}}
            <div class="border-t pt-5">

                <h2 class="text-sm font-semibold text-slate-700 mb-4 uppercase tracking-wide">
                    Datos bancarios
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Banco
                        </label>

                        <input type="text"
                               name="banco"
                               value="{{ old('banco') }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]">

                        @error('banco')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Cuenta
                        </label>

                        <input type="text"
                               name="cuenta"
                               value="{{ old('cuenta') }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]">

                        @error('cuenta')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            CLABE
                        </label>

                        <input type="text"
                               name="clabe"
                               value="{{ old('clabe') }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]">

                        @error('clabe')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Activo --}}
            <div class="flex items-center gap-2 pt-2">

                <input type="checkbox"
                       name="activo"
                       value="1"
                       id="activo"
                       class="rounded border-slate-300 text-[#0B265A] focus:ring-[#FFC107]"
                       {{ old('activo', true) ? 'checked' : '' }}>

                <label for="activo" class="text-sm text-slate-700">
                    Proveedor activo
                </label>

            </div>

            {{-- BOTONES --}}
            <div class="pt-4 flex justify-end gap-3">

                <a href="{{ route('proveedores.index') }}"
                   class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 transition">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-[#0B265A] text-white font-medium hover:bg-[#163A7A] transition">
                    Guardar proveedor
                </button>

            </div>

        </form>

    </div>

</div>

@endsection