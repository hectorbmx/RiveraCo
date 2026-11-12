@extends('layouts.admin')

@section('title', 'Clientes')

@section('content')

{{-- ENCABEZADO --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
    <div>
        <h1 class="text-2xl font-bold text-[#0B265A]">Clientes</h1>
        <p class="text-sm text-slate-500">Catalogo general de clientes</p>
    </div>

    <a href="{{ route('clientes.create') }}"
       class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
        + Nuevo Cliente
    </a>
</div>

{{-- FILTROS --}}
@php
    $perPageFiltroOpciones = [];

    foreach ($perPageOpciones as $opcion) {
        $perPageFiltroOpciones[$opcion] = $opcion . ' filas';
    }
@endphp

<x-filters.card action="{{ route('clientes.index') }}" class="mb-6">
    <x-filters.input
        name="search"
        label="Buscar"
        :value="$search ?? ''"
        placeholder="Nombre comercial, razon social o RFC..."
        span="md:col-span-5"
        type="search"
        glow />

    <x-filters.select
        name="activo"
        label="Estatus"
        :value="$activo ?? 'todos'"
        :options="['todos' => 'Todos', '1' => 'Activos', '0' => 'Inactivos']"
        span="md:col-span-2 md:max-w-44"
        onchange="this.form.submit()" />

    <x-filters.select
        name="per_page"
        label="Filas"
        :value="$perPage ?? 10"
        :options="$perPageFiltroOpciones"
        span="md:col-span-2 md:max-w-44"
        onchange="this.form.submit()" />

    <x-filters.actions
        submit-label="Filtrar"
        clear-url="{{ route('clientes.index') }}"
        span="md:col-span-3" />
</x-filters.card>
{{-- TABLA --}}
@if (session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-sm">
        {{ session('success') }}
    </div>
@endif
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-sm">
            <thead class="bg-[#0B265A] border-b border-[#0B265A] text-white">
                <tr class="text-white uppercase text-xs tracking-wide">
                    <th class="px-5 py-4 text-left">Nombre comercial</th>
                    <th class="px-5 py-4 text-left">Razón social</th>
                    <th class="px-5 py-4 text-left">RFC</th>
                    <th class="px-5 py-4 text-left">Teléfono</th>
                    <th class="px-5 py-4 text-left">Activo</th>
                    <th class="px-5 py-4 text-left">Docs obligatorios</th>
                    <th class="px-5 py-4 text-right">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($clientes as $cliente)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4">
                            @if($cliente->nombre_comercial)
                                <a href="{{ route('clientes.edit', $cliente) }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline">{{ $cliente->nombre_comercial }}</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($cliente->razon_social)
                                <a href="{{ route('clientes.edit', $cliente) }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline">{{ $cliente->razon_social }}</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">{{ $cliente->rfc ?? '-' }}</td>
                        <td class="px-5 py-4">{{ $cliente->telefono ?? '-' }}</td>

                        <td class="px-5 py-4">
                            @if($cliente->activo)
                                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs">Activo</span>
                            @else
                                <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs">Inactivo</span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            @php
                                $totalObligatorios = $totalDocumentosObligatoriosCliente ?? 0;
                                $cargadosObligatorios = min((int) ($cliente->documentos_obligatorios_cargados_count ?? 0), $totalObligatorios);
                                $porcentajeDocs = $totalObligatorios > 0 ? (int) round(($cargadosObligatorios / $totalObligatorios) * 100) : null;
                                $barraDocsClass = $porcentajeDocs === null
                                    ? 'bg-slate-300'
                                    : ($porcentajeDocs >= 100 ? 'bg-emerald-500' : ($porcentajeDocs >= 60 ? 'bg-amber-500' : 'bg-red-500'));
                            @endphp

                            <a href="{{ route('clientes.edit', ['cliente' => $cliente, 'tab' => 'docs']) }}" class="block min-w-[130px] rounded-xl border border-slate-200 px-3 py-2 hover:border-[#0B265A]/40 hover:bg-slate-50 transition">
                                @if($porcentajeDocs === null)
                                    <div class="text-xs font-semibold text-slate-500">Sin obligatorios</div>
                                    <div class="mt-2 h-1.5 rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-slate-300" style="width: 100%"></div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-sm font-semibold text-slate-800">{{ $porcentajeDocs }}%</span>
                                        <span class="text-xs text-slate-500">{{ $cargadosObligatorios }}/{{ $totalObligatorios }}</span>
                                    </div>
                                    <div class="mt-2 h-1.5 rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full {{ $barraDocsClass }}" style="width: {{ $porcentajeDocs }}%"></div>
                                    </div>
                                @endif
                            </a>
                        </td>

                        <td class="px-5 py-4 text-right whitespace-nowrap space-x-2">

                            {{-- EDITAR --}}
                            <a href="{{ route('clientes.edit', $cliente) }}"
                               title="Editar cliente" aria-label="Editar cliente"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 0 1 2.97 2.97L8.25 18.04l-4.125 1.179L5.303 15.1 16.862 3.487z"/></svg>
                            </a>

                            {{-- ELIMINAR --}}
                            <form action="{{ route('clientes.destroy', $cliente) }}"
                                  method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('¿Eliminar este cliente?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Eliminar cliente" aria-label="Eliminar cliente" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 hover:bg-red-50 hover:text-red-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M5 7l1 13h12l1-13M9 7V4h6v3"/></svg>
                                </button>
                            </form>

                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-500">
                            No hay clientes registrados aun.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>


    {{-- PAGINACION --}}
    <div class="border-t border-slate-200 px-5 py-4">
        {{ $clientes->links() }}
    </div>

</div>

@endsection

