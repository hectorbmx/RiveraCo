@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-xl">
    <h1 class="text-xl font-semibold mb-4">Editar usuario App</h1>
@php
    $empleado = $usuario->usuarioApp?->empleado;
@endphp

<div class="border rounded-lg p-4 bg-gray-50">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold">Empleado asignado</h2>

        @if($empleado)
            <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800">
                Vinculado
            </span>
        @else
            <span class="text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-800">
                Sin vínculo
            </span>
        @endif
    </div>

    @if($empleado)
        <div class="mt-3 grid grid-cols-1 gap-2 text-sm">
            <div><span class="text-gray-500">ID:</span> <span class="font-medium">{{ $empleado->id_Empleado }}</span></div>
            <div>
                <span class="text-gray-500">Nombre:</span>
                <span class="font-medium">{{ $empleado->Nombre }} {{ $empleado->Apellidos }}</span>
            </div>
            <div><span class="text-gray-500">Email:</span> <span class="font-medium">{{ $empleado->Email }}</span></div>
            <div><span class="text-gray-500">Área:</span> <span class="font-medium">{{ $empleado->Area }}</span></div>
            <div><span class="text-gray-500">Puesto:</span> <span class="font-medium">{{ $empleado->Puesto }}</span></div>
        </div>
    @else
        <p class="mt-3 text-sm text-gray-600">
            Este usuario no tiene un empleado ligado en <code>usuarios_app</code>.
        </p>
    @endif
</div>

    <form method="POST" action="{{ route('usuarios.update', $usuario->id) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-1">Nombre</label>
            <input type="text" name="name"
                   class="w-full border rounded px-3 py-2"
                   value="{{ old('name', $usuario->name) }}" required>
            @error('name')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email"
                   class="w-full border rounded px-3 py-2"
                   value="{{ old('email', $usuario->email) }}" required>
            @error('email')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <hr class="my-4">

        <div>
            <label class="block text-sm font-medium mb-1">Nueva contraseña (opcional)</label>
            <input type="password" name="password"
                   class="w-full border rounded px-3 py-2"
                   autocomplete="new-password">
            @error('password')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-gray-500 mt-1">Déjalo vacío para mantener la contraseña actual.</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Confirmar nueva contraseña</label>
            <input type="password" name="password_confirmation"
                   class="w-full border rounded px-3 py-2"
                   autocomplete="new-password">
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded">
                Guardar cambios
            </button>

            <a href="{{ route('usuarios.index') }}"
               class="px-4 py-2 rounded border">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
