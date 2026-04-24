@extends('layouts.admin')

@section('title', 'Editar empresa SAT')

@section('content')
<div class="max-w-3xl mx-auto">

    <h1 class="text-xl font-semibold mb-6">Editar Empresa SAT</h1>

    <form action="{{ route('sat.empresas.update', $empresa->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Nombre -->
        <div class="mb-4">
            <label class="block text-sm mb-1">Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre', $empresa->nombre) }}"
                   class="w-full border rounded-lg px-3 py-2" required>
        </div>

        <!-- RFC -->
        <div class="mb-4">
            <label class="block text-sm mb-1">RFC</label>
            <input type="text" name="rfc" value="{{ old('rfc', $empresa->rfc) }}"
                   class="w-full border rounded-lg px-3 py-2" required>
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label class="block text-sm mb-1">Contraseña FIEL (opcional)</label>
            <input type="text" name="fiel_password"
                   class="w-full border rounded-lg px-3 py-2">

            <label class="inline-flex items-center gap-2 mt-2">
                <input type="checkbox" name="fiel_password_has_trailing_space" value="1">
                <span class="text-sm">La contraseña termina con espacio</span>
            </label>
        </div>  
        <div class="mb-4">
    <label class="block text-sm mb-1">Contraseña SAT / CIEC (opcional)</label>
    <input type="text" name="sat_password"
           class="w-full border rounded-lg px-3 py-2">

    <p class="text-xs text-gray-500 mt-1">
        Déjalo vacío si no deseas cambiar la contraseña SAT / CIEC actual.
    </p>
</div>

        <!-- CER -->
        <div class="mb-4">
            <label class="block text-sm mb-1">Archivo CER (opcional)</label>
            <input type="file" name="cer_file" class="w-full">
        </div>

        <!-- KEY -->
        <div class="mb-4">
            <label class="block text-sm mb-1">Archivo KEY (opcional)</label>
            <input type="file" name="key_file" class="w-full">
        </div>

        <!-- Activo -->
        <div class="mb-6">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="activo" value="1" {{ $empresa->activo ? 'checked' : '' }}>
                <span>Activo</span>
            </label>
        </div>

        <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg">
            Guardar cambios
        </button>

    </form>
</div>
@endsection