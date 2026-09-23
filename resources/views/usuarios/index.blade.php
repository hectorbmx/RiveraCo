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

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('usuarios.index') }}" class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_260px_auto_auto] gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Buscar usuario</label>
                <input type="text"
                       name="q"
                       value="{{ $search }}"
                       placeholder="Nombre o email"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Rol</label>
                <select name="role"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos los roles</option>
                    @foreach($roles as $rol)
                        <option value="{{ $rol->name }}" @selected($role === $rol->name)>
                            {{ $rol->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="inline-flex justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Filtrar
            </button>

            <a href="{{ route('usuarios.index') }}"
               class="inline-flex justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Limpiar
            </a>
        </form>
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
                            {{ $user->roles->pluck('name')->first() ?? '-' }}
                        </td>
                        <td class="p-3">
                            @if($user->usuarioApp)
                                <span class="inline-flex px-2 py-1 text-xs rounded {{ $user->usuarioApp->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $user->usuarioApp->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                                    Sin vínculo App
                                </span>
                            @endif
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
                            No hay usuarios registrados con esos filtros.
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