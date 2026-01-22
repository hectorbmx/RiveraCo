@extends('layouts.admin')

@section('title', 'Proveedores')

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Proveedores</h1>
            <p class="text-sm text-slate-500">
                Gestión de proveedores (sin eliminar; solo activar/inactivar).
            </p>
        </div>

        <a href="{{ route('proveedores.create') }}"
           class="bg-[#0B265A] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90">
            + Nuevo proveedor
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-2xl shadow p-4 mb-4">
        <form method="GET" action="{{ route('proveedores.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Buscar</label>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Nombre, descripción o RFC"
                       class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Estatus</label>
                <select name="activo"
                        class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">
                    <option value="">Todos</option>
                    <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activos</option>
                    <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button class="bg-[#FFC107] text-[#0B265A] px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90">
                    Filtrar
                </button>

                <a href="{{ route('proveedores.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-semibold border border-slate-200 text-slate-600 hover:text-slate-900">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Proveedor</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">RFC</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Teléfono</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Estatus</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($proveedores as $p)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3">{{ $p->id }}</td>

                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-900">{{ $p->nombre }}</div>
                            @if($p->domicilio)
                                <div class="text-xs text-slate-500 line-clamp-1">{{ $p->domicilio }}</div>
                            @endif
                        </td>

                        <td class="px-4 py-3">{{ $p->rfc ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $p->telefono ?? '-' }}</td>

                        <td class="px-4 py-3">
                            @if($p->activo)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    Inactivo
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('proveedores.show', $p) }}"
                                   class="text-blue-600 hover:underline">
                                    Abrir
                                </a>

                                <a href="{{ route('proveedores.edit', $p) }}"
                                   class="text-slate-600 hover:underline">
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('proveedores.toggleActivo', $p) }}"
                                      onsubmit="return confirm('¿Seguro que deseas cambiar el estatus?');">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs font-semibold px-3 py-1 rounded-lg border
                                            {{ $p->activo ? 'border-red-200 text-red-700 hover:bg-red-50' : 'border-green-200 text-green-700 hover:bg-green-50' }}">
                                        {{ $p->activo ? 'Inactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                            No hay proveedores con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($proveedores->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $proveedores->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
