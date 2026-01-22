@extends('layouts.admin')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-semibold">Usuarios App</h1>

        <a href="{{ route('usuarios.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Nuevo usuario
        </a>
    </div>

    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Nombre</th>
                    <th class="p-3 text-left">Email</th>
                    <th class="p-3 text-left">Rol</th>
                    <th class="p-3 text-left">Estado</th>
                    <th class="p-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $user)
                    <tr class="border-t">
                        <td class="p-3">{{ $user->id }}</td>
                        <td class="p-3">{{ $user->name }}</td>
                        <td class="p-3">{{ $user->email }}</td>
                        <td class="p-3">
                            {{ $user->getRoleNames()->first() ?? '-' }}
                        </td>
                        <td class="p-3">
                            <span class="inline-flex px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                Activo
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            <a href="{{ route('usuarios.edit', $user->id) }}"
                               class="text-blue-600 hover:underline">
                                Editar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500">
                            No hay usuarios registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $usuarios->links() }}
    </div>
</div>
@endsection
