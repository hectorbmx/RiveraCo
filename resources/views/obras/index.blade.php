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
    <div class="md:col-span-7">
        <x-filters.input
            name="search"
            label="Buscar"
            :value="$search ?? ''"
            placeholder="Nombre, clave o cliente..."
            type="search"
            glow />
    </div>

    <div class="md:col-start-9 md:col-span-2 flex justify-end">
        <div class="w-full md:max-w-48">
            <x-filters.select
                name="status"
                label="Estatus"
                :value="$status ?? ''"
                :options="$statusFiltroOpciones"
                span="w-full" />
        </div>
    </div>

    <div class="md:col-start-11 md:col-span-2 flex justify-end">
        <div class="w-full md:max-w-52">
            <x-filters.select
                name="area_id"
                label="Área"
                :value="$areaId ?? ''"
                :options="collect($areas ?? [])->mapWithKeys(fn($area) => [$area->id => $area->nombre ?: $area->codigo])->prepend('Todos', '')->all()"
                span="w-full" />
        </div>
    </div>

    <div class="md:col-start-13 md:col-span-2 flex justify-end">
        <x-filters.actions
            submit-label="Filtrar"
            clear-url="{{ route('obras.index') }}"
            span="w-full" />
    </div>
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
        <table class="w-full min-w-[1100px] text-sm">
            <thead>
                <tr class="border-b text-slate-500 font-medium">
                    <th class="py-3 px-2 text-left">Clave</th>
                    <th class="py-3 px-2 text-left">Nombre</th>
                    <th class="py-3 px-2 text-left">Cliente</th>
                    <th class="py-3 px-2 text-left">Status</th>
                    <th class="py-3 px-2 text-right">Valor obra</th>
                    <th class="py-3 px-2 text-right">Facturado</th>
                    <th class="py-3 px-2 text-right">Cobrado</th>
                    <th class="py-3 px-2 text-right">Gastado</th>
                    <th class="py-3 px-2 text-right">Por facturar</th>
                    <th class="py-3 px-2 text-center">Avance</th>
                    <th class="py-3 px-2 text-right">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($obras as $obra)
                    @php
                        $valorObra = (float) ($obra->monto_contratado ?? 0);
                        $facturado = (float) \App\Models\ObraFactura::where('obra_id', $obra->id)
                            ->where('estado', '!=', 'cancelada')
                            ->sum('monto');
                        $cobrado = (float) \App\Models\ObraFacturaPago::where('obra_id', $obra->id)->sum('monto');
                        $gastado = (float) \App\Models\OrdenCompra::where('obra_id', $obra->id)
                            ->whereNotIn('estado', ['cancelada'])
                            ->sum('total');
                        $porFacturar = max(0, $valorObra - $facturado);
                        $porCobrar = max(0, $facturado - $cobrado);
                        $avancePct = $valorObra > 0 ? min(100, round(($facturado / $valorObra) * 100)) : 0;
                        $money = fn ($value) => '$' . number_format((float) $value, 2);
                    @endphp
                    <tr class="border-b hover:bg-slate-50 align-top">
                        <td class="py-3 px-2 font-medium text-slate-700">{{ $obra->clave_obra }}</td>

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

                        <td class="py-3 px-2">
                            @php
                                $val = (int)($obra->estatus_nuevo ?? 1);
                                $cls = \App\Models\Obra::estatusBadgeClasses()[$val] ?? 'bg-slate-100 text-slate-700';
                                $lbl = $obra->estatus_label;
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $cls }}">
                                {{ $lbl }}
                            </span>
                        </td>

                        <td class="py-3 px-2 text-right font-medium text-slate-800">
                            {{ $money($valorObra) }}
                        </td>

                        <td class="py-3 px-2 text-right font-medium text-blue-700">
                            {{ $money($facturado) }}
                        </td>

                        <td class="py-3 px-2 text-right font-medium text-emerald-700">
                            {{ $money($cobrado) }}
                        </td>

                        <td class="py-3 px-2 text-right font-medium text-orange-600">
                            {{ $money($gastado) }}
                        </td>

                        <td class="py-3 px-2 text-right font-medium text-amber-700">
                            {{ $money($porFacturar) }}
                        </td>

                        <td class="py-3 px-2">
                            <div class="min-w-[110px]">
                                <div class="flex items-center justify-between text-[10px] text-slate-500 mb-1">
                                    <span>{{ $avancePct }}%</span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-[#0B265A] to-[#3B82F6]" style="width: {{ $avancePct }}%"></div>
                                </div>
                            </div>
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
                        <td colspan="10" class="py-6 text-center text-slate-500">
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
