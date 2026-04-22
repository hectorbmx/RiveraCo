@extends('layouts.admin')

@section('title', 'Nueva empresa SAT')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Nueva empresa SAT</h1>
            <p class="text-sm text-gray-600 mt-1">
                Captura la empresa y sus archivos de FIEL para usarla en las solicitudes SAT.
            </p>
        </div>

        <a href="{{ route('sat.empresas.index') }}"
           class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <div class="font-medium text-red-800 mb-2">Hay errores en el formulario:</div>
            <ul class="list-disc ml-5 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('sat.empresas.store') }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la empresa</label>
                <input type="text"
                       name="nombre"
                       value="{{ old('nombre') }}"
                       class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="Ej. Rivera Construcciones SA de CV"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RFC</label>
                <input type="text"
                       name="rfc"
                       value="{{ old('rfc') }}"
                       class="w-full rounded-xl border-gray-300 text-sm uppercase focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="RFC"
                       maxlength="13"
                       required>
            </div>

           <div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña FIEL</label>
    <input type="password"
           name="fiel_password"
           class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
           placeholder="Contraseña de la llave privada"
           required>

    <label class="inline-flex items-center gap-2 mt-3">
        <input type="checkbox"
               name="fiel_password_has_trailing_space"
               value="1"
               {{ old('fiel_password_has_trailing_space') ? 'checked' : '' }}
               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
        <span class="text-sm text-gray-700">La contraseña termina con espacio</span>
    </label>

    <p class="text-xs text-amber-600 mt-1">
        Si tu contraseña de la FIEL incluye un espacio al final, marca esta opción.
    </p>
</div>
            

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Archivo .cer</label>
                <input type="file"
                       name="cer_file"
                       accept=".cer"
                       class="w-full rounded-xl border-gray-300 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Archivo .key</label>
                <input type="file"
                       name="key_file"
                       accept=".key"
                       class="w-full rounded-xl border-gray-300 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                       required>
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox"
                           name="activo"
                           value="1"
                           {{ old('activo', 1) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">Empresa activa</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('sat.empresas.index') }}"
               class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>

            <button type="submit"
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                Guardar empresa
            </button>
        </div>
    </form>
</div>
@endsection