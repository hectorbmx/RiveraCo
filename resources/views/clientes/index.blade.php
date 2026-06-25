@extends('layouts.admin')

@section('title', 'Clientes')

@section('content')

{{-- ENCABEZADO --}}
<div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
    <h1 class="text-2xl font-bold text-[#0B265A]">Clientes</h1>

    <div class="flex flex-1 w-full md:max-w-4xl">
        <form action="{{ route('clientes.index') }}" method="GET" class="w-full grid grid-cols-1 md:grid-cols-[1fr_150px_130px_auto_auto] gap-2">
            <div class="relative flex-1">
                <input type="text" 
                       name="search" 
                       value="{{ $search ?? '' }}"
                       placeholder="Buscar por nombre, razón social o RFC..." 
                       class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0B265A] focus:border-transparent transition text-sm">
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            <select name="activo"
                    onchange="this.form.submit()"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0B265A] focus:border-transparent transition">
                <option value="todos" @selected(($activo ?? 'todos') === 'todos')>Todos</option>
                <option value="1" @selected(($activo ?? 'todos') === '1')>Activos</option>
                <option value="0" @selected(($activo ?? 'todos') === '0')>Inactivos</option>
            </select>
            <select name="per_page"
                    onchange="this.form.submit()"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0B265A] focus:border-transparent transition">
                @foreach($perPageOpciones as $opcion)
                    <option value="{{ $opcion }}" @selected((int) ($perPage ?? 10) === $opcion)>
                        {{ $opcion }} filas
                    </option>
                @endforeach
            </select>
            <button type="submit" class="bg-[#0B265A] text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-[#163a7a] transition">
                Buscar
            </button>
            @if(request('search') || request('activo', 'todos') !== 'todos' || (int) request('per_page', 10) !== 10)
                <a href="{{ route('clientes.index') }}" class="bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-300 transition">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    <a href="{{ route('clientes.create') }}"
       class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
        + Nuevo Cliente
    </a>
</div>


{{-- TABLA --}}
<div class="bg-white rounded-2xl shadow p-6">

    @if (session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full min-w-[700px] text-sm">
            <thead>
                <tr class="border-b text-slate-500 font-medium">
                    <th class="py-3 px-2 text-left">Nombre Comercial</th>
                    <th class="py-3 px-2 text-left">Razón Social</th>
                    <th class="py-3 px-2 text-left">RFC</th>
                    <th class="py-3 px-2 text-left">Teléfono</th>
                    <th class="py-3 px-2 text-left">Activo</th>
                    <th class="py-3 px-2 text-right">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($clientes as $cliente)
                    <tr class="border-b hover:bg-slate-50">
                        <td class="py-3 px-2">{{ $cliente->nombre_comercial }}</td>
                        <td class="py-3 px-2">{{ $cliente->razon_social ?? '-' }}</td>
                        <td class="py-3 px-2">{{ $cliente->rfc ?? '-' }}</td>
                        <td class="py-3 px-2">{{ $cliente->telefono ?? '-' }}</td>

                        <td class="py-3 px-2">
                            @if($cliente->activo)
                                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs">Activo</span>
                            @else
                                <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs">Inactivo</span>
                            @endif
                        </td>

                        <td class="py-3 px-2 text-right space-x-2">

                            {{-- EDITAR --}}
                            <a href="{{ route('clientes.edit', $cliente) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                Editar
                            </a>

                            {{-- ELIMINAR --}}
                            <form action="{{ route('clientes.destroy', $cliente) }}"
                                  method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('¿Eliminar este cliente?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:text-red-800 font-medium text-sm">
                                    Eliminar
                                </button>
                            </form>

                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-500">
                            No hay clientes registrados aún.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>


    {{-- PAGINACIÓN --}}
    <div class="mt-4">
        {{ $clientes->links() }}
    </div>

</div>

@endsection
