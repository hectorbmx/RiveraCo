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
            <a href="{{ route('proveedores.pagos-programados') }}"
            class="group relative inline-flex items-center gap-2.5 rounded-xl border border-amber-200 bg-white px-5 py-2.5 text-sm font-bold text-amber-700 
                    transition-all duration-300 ease-out
                    hover:border-amber-300 hover:bg-amber-50 hover:shadow-[0_10px_20px_-10px_rgba(245,158,11,0.4)]
                    active:scale-95 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                
                <svg xmlns="http://www.w3.org/2000/svg" 
                    class="h-5 w-5 text-amber-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3" 
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>

                <span class="tracking-tight">Programación de pagos</span>

                <div class="absolute inset-0 rounded-xl bg-gradient-to-tr from-transparent via-transparent to-amber-400/10 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
            </a>
            <a href="{{ route('proveedores.create') }}"
                class="group relative inline-flex items-center gap-2.5 rounded-xl bg-[#0B265A] px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-900/20 
                        transition-all duration-300 ease-out
                        hover:bg-[#12387f] hover:-translate-y-0.5 hover:shadow-[0_10px_25px_-5px_rgba(11,38,90,0.4)]
                        active:scale-95 focus:outline-none focus:ring-2 focus:ring-[#0B265A]/20">
                    
                    <div class="relative flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            class="h-5 w-5 text-blue-200 transition-all duration-300 group-hover:rotate-90 group-hover:text-white" 
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>

                    <span class="tracking-tight"> Nuevo proveedor</span>

                    <div class="absolute inset-0 rounded-xl bg-gradient-to-tr from-white/0 via-white/5 to-white/10 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
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
        <form method="GET" action="{{ route('proveedores.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
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

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Mostrar</label>
                <select name="per_page"
                        onchange="this.form.submit()"
                        class="w-full rounded-lg border-slate-300 focus:border-[#0B265A] focus:ring-[#0B265A]">
                    @foreach($perPageOpciones as $opcion)
                        <option value="{{ $opcion }}" @selected((int) ($perPage ?? 20) === $opcion)>
                            {{ $opcion }} elementos
                        </option>
                    @endforeach
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
