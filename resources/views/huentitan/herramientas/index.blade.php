@extends('layouts.admin')

@section('title', 'HUENTITAN - Herramientas')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">HUENTITAN</div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Herramientas</h1>
            <p class="mt-1 text-sm text-gray-600">Catalogo por almacen para integrar costos al precio unitario.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('huentitan.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Panel</a>
            <a href="{{ route('huentitan.herramientas.create') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-[#FFC107] text-[#0B265A] text-sm font-semibold hover:opacity-90">Nueva herramienta</a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="bg-[#0B265A] rounded-lg p-4 shadow-sm">
        <form method="GET" action="{{ route('huentitan.herramientas.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-5">
                <label class="block text-xs font-semibold text-white/85 mb-1">Buscar herramienta</label>
                <input type="text" name="q" value="{{ $busqueda }}" placeholder="Nombre, codigo, marca, modelo o serie" class="w-full rounded-md border border-amber-300 bg-white px-3 py-2 text-sm focus:border-amber-400 focus:ring-4 focus:ring-yellow-200">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-white/85 mb-1">Estado</label>
                <select name="estado" class="w-full rounded-md border-slate-200 bg-white text-sm focus:border-slate-300 focus:ring-slate-300">
                    <option value="">Todos</option>
                    <option value="activa" @selected($estado === 'activa')>Activa</option>
                    <option value="en_mantenimiento" @selected($estado === 'en_mantenimiento')>En mantenimiento</option>
                    <option value="baja" @selected($estado === 'baja')>Baja</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-white/85 mb-1">Activo</label>
                <select name="activo" class="w-full rounded-md border-slate-200 bg-white text-sm focus:border-slate-300 focus:ring-slate-300">
                    <option value="activos" @selected($activo === 'activos')>Activos</option>
                    <option value="inactivos" @selected($activo === 'inactivos')>Inactivos</option>
                    <option value="todos" @selected($activo === 'todos')>Todos</option>
                </select>
            </div>
            <div class="md:col-span-3 flex gap-2 md:justify-end">
                <button class="px-4 py-2 rounded-md bg-[#FFC107] text-[#0B265A] text-sm font-semibold hover:opacity-90">Filtrar</button>
                <a href="{{ route('huentitan.herramientas.index') }}" class="px-4 py-2 rounded-md border border-white/25 bg-white/10 text-white text-sm font-medium hover:bg-white/20">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Total herramientas</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenHerramientas['total']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Activas</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenHerramientas['activas']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">En mantenimiento</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenHerramientas['en_mantenimiento']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Valor activo</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">${{ number_format($resumenHerramientas['valor'], 2) }}</div>
        </div>
    </div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Catalogo de herramientas</h2>
                <p class="text-xs text-gray-500">{{ number_format($herramientas->total()) }} herramientas encontradas</p>
            </div>
            <div class="text-xs text-gray-500">{{ $almacen->nombre }}</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Codigo</th>
                        <th class="px-4 py-3 text-left">Herramienta</th>
                        <th class="px-4 py-3 text-right">Costo</th>
                        <th class="px-4 py-3 text-right">Sugerido/pza</th>
                        <th class="px-4 py-3 text-left">Proveedor</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-center">Activo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($herramientas as $herramienta)
                        @php
                            $costo = (float) ($herramienta->costo ?? 0);
                            $residual = (float) ($herramienta->costo_residual ?? 0);
                            $vidaUtil = (int) ($herramienta->vida_util_piezas ?? 0);
                            $sugerido = $vidaUtil > 0 ? max(($costo - $residual) / $vidaUtil, 0) : null;
                            $estadoLabel = ['activa' => 'Activa', 'en_mantenimiento' => 'En mantenimiento', 'baja' => 'Baja'][$herramienta->estado] ?? $herramienta->estado;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $herramienta->codigo }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-[#0B265A]">{{ $herramienta->nombre }}</div>
                                @if($herramienta->marca || $herramienta->modelo || $herramienta->numero_serie)
                                    <div class="text-xs text-gray-500">
                                        {{ collect([$herramienta->marca, $herramienta->modelo, $herramienta->numero_serie])->filter()->implode(' / ') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">${{ number_format($costo, 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ $sugerido === null ? '-' : '$' . number_format($sugerido, 2) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $herramienta->proveedor?->nombre ?? $herramienta->proveedor_nombre ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-1 rounded text-xs font-semibold {{ $herramienta->estado === 'activa' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($herramienta->estado === 'en_mantenimiento' ? 'bg-yellow-50 text-yellow-700 border border-yellow-100' : 'bg-slate-50 text-slate-600 border border-slate-100') }}">
                                    {{ $estadoLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-1 rounded text-xs font-semibold {{ $herramienta->activo ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-50 text-slate-600 border border-slate-100' }}">
                                    {{ $herramienta->activo ? 'Si' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('huentitan.herramientas.edit', $herramienta) }}" class="text-xs font-semibold text-blue-600 hover:underline">Editar</a>
                                    @if($herramienta->activo)
                                        <form method="POST" action="{{ route('huentitan.herramientas.destroy', $herramienta) }}" onsubmit="return confirm('¿Desactivar esta herramienta HUENTITAN?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs font-semibold text-red-600 hover:underline">Desactivar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay herramientas HUENTITAN cargadas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t">
            {{ $herramientas->links() }}
        </div>
    </div>
</div>
@endsection

