@extends('layouts.admin')

@section('title', 'Nuevo puesto')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <h1 class="text-xl font-semibold text-gray-900">Nuevo puesto</h1>
        <p class="text-sm text-gray-600">Crear un nuevo puesto en el catálogo.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('empresa_config.catalogo_roles.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700">ROL_KEY</label>
                    <input type="text" name="rol_key" required
                           class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" required
                           class="mt-1 w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900/20">
                </div>

                <div class="flex items-center gap-6 md:col-span-2 mt-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="comisionable" value="1"
                               class="rounded border-gray-300">
                        Comisionable
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="activo" value="1" checked
                               class="rounded border-gray-300">
                        Activo
                    </label>
                </div>

            </div>

            <div class="flex justify-end mt-6 gap-3">
                <a href="{{ route('empresa_config.edit', ['tab' => 'rrhh']) }}"
                   class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50">
                    Volver a la lista
                </a>

                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                    Guardar
                </button>
            </div>

        </form>
    </div>

</div>
@endsection