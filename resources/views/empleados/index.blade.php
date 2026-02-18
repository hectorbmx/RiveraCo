@extends('layouts.admin')

@section('title', 'Empleados')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Empleados</h1>
            <p class="text-sm text-slate-500">
                Catálogo de personal de Rivera Construcciones.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <form action="{{ route('empleados.index') }}" method="GET" class="flex items-center gap-2">
                <input type="text" name="q" placeholder="Buscar por nombre, área o puesto"
                       value="{{ $search }}"
                       class="rounded-xl border-slate-200 text-sm shadow-sm px-3 py-2 focus:border-[#FFC107] focus:ring-[#FFC107]">
                <button class="px-3 py-2 bg-slate-100 rounded-xl text-xs text-slate-600 hover:bg-slate-200">
                    Buscar
                </button>
            </form>
   {{-- FILTROS DE ESTATUS --}}
    <div class="flex gap-2">

        <a href="{{ route('empleados.index', ['estatus' => 'activo']) }}"
            class="px-3 py-2 text-xs rounded-xl
            {{ $estatus === 'activo' ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Activos
        </a>

        <a href="{{ route('empleados.index', ['estatus' => 'baja']) }}"
            class="px-3 py-2 text-xs rounded-xl
            {{ $estatus === 'baja' ? 'bg-red-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Baja
        </a>

        <a href="{{ route('empleados.index') }}"
            class="px-3 py-2 text-xs rounded-xl
            {{ !$estatus ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Todos
        </a>

    </div>
            <a href="{{ route('empleados.create') }}"
               class="px-4 py-2 bg-[#FFC107] text-[#0B265A] text-sm font-semibold rounded-xl shadow hover:bg-[#e0ac05]">
                + Nuevo empleado
            </a>
        </div>
    </div>

    {{-- Mensaje flash --}}
    @if(session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-2 px-3">Empleado</th>
                    <th class="py-2 px-3">Área</th>
                    <th class="py-2 px-3">Puesto</th>
                    <th class="py-2 px-3">Sueldo</th>
                    <th class="py-2 px-3">Estatus</th>
                    <th class="py-2 px-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($empleados as $emp)
                    <tr class="border-b last:border-b-0 hover:bg-slate-50">
                        <td class="py-2 px-3">
                            <span class="font-medium">
                                {{ $emp->Nombre }} {{ $emp->Apellidos }}
                            </span><br>
                            <span class="text-xs text-slate-400">
                                ID: {{ $emp->id_Empleado }} · Ingreso:
                                {{ $emp->Fecha_ingreso ? $emp->Fecha_ingreso->format('d/m/Y') : '-' }}
                            </span>
                        </td>
                        <td class="py-2 px-3">{{ $emp->Area ?? '-' }}</td>
                        <td class="py-2 px-3">{{ $emp->Puesto ?? '-' }}</td>
                        <td class="py-2 px-3">
                            @if(!is_null($emp->Sueldo_real ?? $emp->Sueldo))
                                ${{ number_format($emp->Sueldo_real ?? $emp->Sueldo, 2) }}
                            @else
                                -
                            @endif
                        </td>
                       <td class="py-2 px-3">
                            @if((int)$emp->Estatus === 2)
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">Baja</span>
                            @else
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Activo</span>
                            @endif
                            </td>
                        <td class="py-2 px-3 text-right space-x-2">
                            <a href="{{ route('empleados.edit', ['empleado' => $emp->id_Empleado, 'tab' => 'datos']) }}"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                Expediente
                            </a>



                          <form action="{{ route('empleados.toggle-status', $emp->id_Empleado) }}"
                                method="POST"
                                class="inline-block"
                                onsubmit="return confirm('¿Cambiar estatus de este empleado?')">
                            @csrf
                            @method('PATCH')

                            <button class="text-xs text-slate-600 hover:text-slate-900 font-medium">
                                {{ (int)$emp->Estatus === 2 ? 'Reactivar' : 'Dar de baja' }}
                            </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-500">
                            No hay empleados registrados todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $empleados->links() }}
    </div>
</div>
@endsection
