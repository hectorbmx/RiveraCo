@extends('layouts.admin')

@section('title', 'Productos')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Productos</h1>
            <p class="text-sm text-slate-500">Catálogo general de productos</p>
        </div>

        <a href="{{ route('productos.create') }}"
           class="bg-[#0B265A] text-white px-4 py-2 rounded-xl text-sm hover:opacity-90">
            + Nuevo producto
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-2xl shadow p-4 mb-4">
        <div class="grid md:grid-cols-4 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Buscar</label>
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Nombre, SKU o legacy_prod_id..."
                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#FFC107]/40">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Estado</label>
                <select name="estado"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    <option value="activos" {{ request('estado')==='activos' ? 'selected' : '' }}>Activos</option>
                    <option value="inactivos" {{ request('estado')==='inactivos' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl text-sm hover:opacity-90">
                    Filtrar
                </button>

                <a href="{{ route('productos.index') }}"
                   class="px-4 py-2 rounded-xl text-sm border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Limpiar
                </a>
            </div>
        </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Producto</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Unidad</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Estatus</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($productos as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-500">{{ $p->id }}</td>

                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">{{ $p->nombre }}</div>
                            <!-- <div class="text-xs text-slate-500">
                                Legacy: {{ $p->legacy_prod_id ?? '-' }}
                            </div> -->
                        </td>

                        <td class="px-4 py-3 text-slate-700">{{ $p->sku ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $p->unidad ?? '-' }}</td>

                        <td class="px-4 py-3">
                            @if($p->activo)
                                <span class="text-green-700 bg-green-100 px-2 py-1 rounded-lg text-xs font-semibold">Activo</span>
                            @else
                                <span class="text-red-700 bg-red-100 px-2 py-1 rounded-lg text-xs font-semibold">Inactivo</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('productos.edit', $p->id) }}"
                               class="text-blue-600 hover:underline">
                                Abrir
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                            No hay productos con los filtros actuales.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4">
            {{ $productos->links() }}
        </div>
    </div>

</div>
@endsection
