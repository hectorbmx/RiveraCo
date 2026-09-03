@extends('layouts.admin')

@section('title', 'Obras')

@section('content')

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
    <div>
        <h1 class="text-2xl font-bold text-[#0B265A]">Obras</h1>
        <p class="text-sm text-slate-500">Listado general de obras</p>
    </div>

    <a href="{{ route('obras.create') }}"
       class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
        + Nueva Obra
    </a>
</div>

@php
    $statusFiltroOpciones = ['' => 'Todos'];
    $availableStatuses = \App\Models\Obra::estatusSlugs();
    $statusLabels = \App\Models\Obra::estatusLabels();

    foreach ($availableStatuses as $key => $value) {
        $statusFiltroOpciones[$key] = $statusLabels[$value] ?? $key;
    }
@endphp

<x-filters.card action="{{ route('obras.index') }}" class="mb-6">
    <x-filters.input
        name="search"
        label="Buscar"
        :value="$search ?? ''"
        placeholder="Nombre, clave o cliente..."
        span="md:col-span-7"
        type="search"
        glow />

    <x-filters.select
        name="status"
        label="Estatus"
        :value="$status ?? ''"
        :options="$statusFiltroOpciones"
        span="md:col-span-2 md:max-w-48" />

    <x-filters.actions
        submit-label="Filtrar"
        clear-url="{{ route('obras.index') }}"
        span="md:col-span-3" />
</x-filters.card>
{{--
KPIs ejecutivos comentados temporalmente mientras se homologa la vista de filtros.

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
--}}

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
                        <td class="py-3 px-2">
                            <a href="{{ route('obras.edit', $obra) }}"
                               class="font-semibold text-slate-800 hover:text-blue-700 hover:underline">
                                {{ $obra->nombre }}
                            </a>
                        </td>
                        <td class="py-3 px-2">
                            @if($obra->cliente)
                                <a href="{{ route('clientes.edit', $obra->cliente) }}"
                                   class="text-slate-700 hover:text-blue-700 hover:underline">
                                    {{ $obra->cliente->nombre_comercial }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
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
                                Detalles
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
