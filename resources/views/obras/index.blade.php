@extends('layouts.admin')

@section('title', 'Obras')

@section('content')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-[#0B265A]">Obras</h1>

    <a href="{{ route('obras.create') }}"
       class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
        + Nueva Obra
    </a>
</div>

<div class="bg-white rounded-2xl shadow p-6">

    @if (session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full min-w-[850px] text-sm">
            <thead>
                <tr class="border-b text-slate-500 font-medium">
                    <th class="py-3 px-2 text-left">Nombre</th>
                    <th class="py-3 px-2 text-left">Cliente</th>
                    <th class="py-3 px-2 text-left">Clave</th>
                    <th class="py-3 px-2 text-left">Status</th>
                    <th class="py-3 px-2 text-left">Inicio Prog.</th>
                    <th class="py-3 px-2 text-left">Responsable</th>
                    <th class="py-3 px-2 text-right">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($obras as $obra)
                    <tr class="border-b hover:bg-slate-50">
                        <td class="py-3 px-2">{{ $obra->nombre }}</td>
                        <td class="py-3 px-2">{{ $obra->cliente->nombre_comercial ?? '-' }}</td>
                        <td class="py-3 px-2">{{ $obra->clave_obra }}</td>

                        <td class="py-3 px-2">
                            @php
                                $statusColors = [
                                    'planeacion' => 'bg-slate-100 text-slate-700',
                                    'ejecucion'  => 'bg-blue-100 text-blue-700',
                                    'suspendida' => 'bg-yellow-100 text-yellow-700',
                                    'terminada'  => 'bg-green-100 text-green-700',
                                    'cancelada'  => 'bg-red-100 text-red-700',
                                ];
                                $cls = $statusColors[$obra->status] ?? 'bg-slate-100 text-slate-700';
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs {{ $cls }}">
                                {{ ucfirst($obra->status) }}
                            </span>
                        </td>

                        <td class="py-3 px-2">
                            {{ $obra->fecha_inicio_programada ? $obra->fecha_inicio_programada->format('d/m/Y') : '-' }}
                        </td>

                        <td class="py-3 px-2">
                            {{ $obra->responsable->name ?? '-' }}
                        </td>

                        <td class="py-3 px-2 text-right space-x-2">
                            <a href="{{ route('obras.edit', $obra) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                Editar
                            </a>

                            <form action="{{ route('obras.destroy', $obra) }}"
                                  method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('¿Eliminar esta obra?')">
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
                        <td colspan="7" class="py-6 text-center text-slate-500">
                            No hay obras registradas aún.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $obras->links() }}
    </div>
</div>

@endsection
