@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-xl">
    <h1 class="text-xl font-semibold mb-4">Nuevo usuario App</h1>

    <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Nombre</label>
            <input type="text" name="name"
                   class="w-full border rounded px-3 py-2"
                   value="{{ old('name') }}" required>
            @error('name')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email"
                   class="w-full border rounded px-3 py-2"
                   value="{{ old('email') }}" required>
            @error('email')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Contraseña</label>
            <input type="password" name="password"
                   class="w-full border rounded px-3 py-2"
                   required>
            @error('password')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Confirmar contraseña</label>
            <input type="password" name="password_confirmation"
                   class="w-full border rounded px-3 py-2"
                   required>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded">
                Guardar
            </button>

            <a href="{{ route('usuarios.index') }}"
               class="px-4 py-2 rounded border">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
