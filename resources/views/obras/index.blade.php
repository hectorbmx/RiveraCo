@extends('layouts.admin')

@section('title', 'Obras')

@section('content')

<div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
    <h1 class="text-2xl font-bold text-[#0B265A]">Obras</h1>

    <div class="flex flex-1 w-full md:max-w-md">
        <form action="{{ route('obras.index') }}" method="GET" class="w-full flex gap-2">
            <div class="relative flex-1">
                <input type="text" 
                       name="search" 
                       value="{{ $search ?? '' }}"
                       placeholder="Buscar por nombre, clave o cliente..." 
                       class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0B265A] focus:border-transparent transition text-sm">
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            <button type="submit" class="bg-[#0B265A] text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-[#163a7a] transition">
                Buscar
            </button>
            @if(request('search') || request('status'))
                <a href="{{ route('obras.index') }}" class="bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-300 transition text-center">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    <a href="{{ route('obras.create') }}"
       class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
        + Nueva Obra
    </a>
</div>

@if($kpisObras)
    @php
        $money = fn ($value) => '$' . number_format((float) $value, 2);
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase">Obras en ejecución</div>
            <div class="mt-2 text-2xl font-bold text-[#0B265A]">{{ number_format($kpisObras['obras_ejecucion']) }}</div>
            <div class="mt-1 text-xs text-slate-500">Activas actualmente</div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase">Monto vendido</div>
            <div class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $money($kpisObras['monto_vendido']) }}</div>
            <div class="mt-1 text-xs text-slate-500">Obras en ejecución</div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase">Facturado</div>
            <div class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $money($kpisObras['monto_facturado']) }}</div>
            <div class="mt-1 text-xs text-slate-500">Facturas ligadas a obra</div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase">Cobrado</div>
            <div class="mt-2 text-2xl font-bold text-emerald-700">{{ $money($kpisObras['monto_cobrado']) }}</div>
            <div class="mt-1 text-xs text-slate-500">Pagos registrados</div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase">Pendiente por cobrar</div>
            <div class="mt-2 text-2xl font-bold text-amber-700">{{ $money($kpisObras['pendiente_cobrar']) }}</div>
            <div class="mt-1 text-xs text-slate-500">Facturado menos cobrado</div>
        </div>
    </div>
@endif

{{-- FILTROS POR STATUS --}}
<div class="flex flex-wrap gap-2 mb-6">
    @php
        $availableStatuses = [
            'planeacion' => 'Planeación',
            'ejecucion'  => 'Ejecución',
            'suspendida' => 'Suspendida',
            'terminada'  => 'Terminada',
            'cancelada'  => 'Cancelada',
        ];
        
        $statusClasses = [
            'planeacion' => 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100',
            'ejecucion'  => 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100',
            'suspendida' => 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100',
            'terminada'  => 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100',
            'cancelada'  => 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100',
        ];

        $statusActiveClasses = [
            'planeacion' => 'bg-slate-600 text-white border-slate-600',
            'ejecucion'  => 'bg-blue-600 text-white border-blue-600',
            'suspendida' => 'bg-yellow-600 text-white border-yellow-600',
            'terminada'  => 'bg-green-600 text-white border-green-600',
            'cancelada'  => 'bg-red-600 text-white border-red-600',
        ];
    @endphp

    @php
        $availableStatuses = \App\Models\Obra::estatusSlugs();
        $statusLabels = \App\Models\Obra::estatusLabels();
        $statusClasses = \App\Models\Obra::estatusFilterClasses();
        $statusActiveClasses = \App\Models\Obra::estatusFilterActiveClasses();
    @endphp

    <span class="text-sm font-medium text-slate-500 self-center mr-2">Estatus:</span>

    @foreach($availableStatuses as $key => $value)
        @php $label = $statusLabels[$value] ?? $key; @endphp
        <a href="{{ route('obras.index', array_merge(request()->query(), ['status' => $key])) }}" 
           class="px-4 py-1.5 rounded-full text-xs font-semibold border transition {{ (request('status') == $key) ? ($statusActiveClasses[$value] ?? 'bg-blue-600 text-white') : ($statusClasses[$value] ?? 'bg-slate-50 text-slate-700 border-slate-200') }}">
            {{ $label }}
        </a>
    @endforeach
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
                                    1 => 'bg-slate-100 text-slate-700', // Planeacion
                                    2 => 'bg-blue-100 text-blue-700',   // Ejecucion
                                    3 => 'bg-yellow-100 text-yellow-700', // Suspendida
                                    4 => 'bg-green-100 text-green-700',  // Terminada
                                    5 => 'bg-red-100 text-red-700',      // Cancelada
                                ];
                                $statusLabels = [
                                    1 => 'Planeación',
                                    2 => 'En ejecución',
                                    3 => 'Suspendida',
                                    4 => 'Terminada',
                                    5 => 'Cancelada',
                                ];
                                $val = (int)($obra->estatus_nuevo ?? 1);
                                $cls = \App\Models\Obra::estatusBadgeClasses()[$val] ?? 'bg-slate-100 text-slate-700';
                                $lbl = $obra->estatus_label;
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $cls }}">
                                {{ $lbl }}
                            </span>
                        </td>

                        <td class="py-3 px-2">
                            {{ $obra->fecha_inicio_programada ? $obra->fecha_inicio_programada->format('d/m/Y') : '-' }}
                        </td>

                        <td class="py-3 px-2">
                            {{ $obra->responsable->nombre_completo ?? '-' }}
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
