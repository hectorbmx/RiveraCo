@extends('layouts.admin')

@section('title', 'Editar puesto')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <h1 class="text-xl font-semibold text-gray-900">Editar puesto</h1>
        <p class="text-sm text-gray-600">
            Modificar información del puesto.
        </p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">

        <form method="POST" action="{{ route('empresa_config.catalogo_roles.update', $rol->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- ROL_KEY --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        ROL_KEY
                    </label>
                    <input type="text"
                           name="rol_key"
                           value="{{ old('rol_key', $rol->rol_key) }}"
                           required
                           class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                    @error('rol_key')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nombre --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Nombre
                    </label>
                    <input type="text"
                           name="nombre"
                           value="{{ old('nombre', $rol->nombre) }}"
                           required
                           class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                    @error('nombre')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Checkboxes --}}
                <div class="flex items-center gap-6 md:col-span-2 mt-2">

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox"
                               name="comisionable"
                               value="1"
                               class="rounded border-gray-300"
                               @checked(old('comisionable', $rol->comisionable))>
                        Comisionable
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox"
                               name="activo"
                               value="1"
                               class="rounded border-gray-300"
                               @checked(old('activo', $rol->activo))>
                        Activo
                    </label>

                </div>

            </div>

            {{-- Botones --}}
            <div class="flex justify-end mt-6 gap-3">

                <a href="{{ route('empresa_config.edit', ['tab' => 'rrhh']) }}"
                   class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                    Guardar cambios
                </button>

            </div>

        </form>

    </div>

</div>
@endsection