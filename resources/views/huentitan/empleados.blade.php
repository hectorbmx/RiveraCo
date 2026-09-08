@extends('layouts.admin')

@section('title', 'HUENTITAN - Empleados')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">HUENTITAN</div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Empleados</h1>
            <p class="mt-1 text-sm text-gray-600">Personal relacionado con el area {{ $areaHuentitan?->codigo ?? 'HT' }}.</p>
        </div>
        <a href="{{ route('huentitan.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">
            Volver al panel
        </a>
    </div>

    @if(!$areaHuentitan)
        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            No encontre el area HUENTITAN con codigo HT. Crea o activa esa area para ver empleados.
        </div>
    @endif

    <div class="bg-[#0B265A] rounded-lg p-4 shadow-sm">
        <form method="GET" action="{{ route('huentitan.empleados.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-6">
                <label class="block text-xs font-semibold text-white/85 mb-1">Buscar empleado</label>
                <input type="text" name="q" value="{{ $busqueda }}" placeholder="Nombre, apellido, puesto o ID" class="w-full rounded-md border border-amber-300 bg-white px-3 py-2 text-sm focus:border-amber-400 focus:ring-4 focus:ring-yellow-200">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-white/85 mb-1">Estatus</label>
                <select name="estatus" class="w-full rounded-md border-slate-200 bg-white text-sm focus:border-slate-300 focus:ring-slate-300">
                    <option value="activo" @selected($estatus === 'activo')>Activos</option>
                    <option value="baja" @selected($estatus === 'baja')>Baja</option>
                    <option value="todos" @selected($estatus === 'todos')>Todos</option>
                </select>
            </div>
            <div class="md:col-span-3 flex gap-2 md:justify-end">
                <button class="px-4 py-2 rounded-md bg-[#FFC107] text-[#0B265A] text-sm font-semibold hover:opacity-90">Filtrar</button>
                <a href="{{ route('huentitan.empleados.index') }}" class="px-4 py-2 rounded-md border border-white/25 bg-white/10 text-white text-sm font-medium hover:bg-white/20">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Total filtrado</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenEmpleados['total']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Activos</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenEmpleados['activos']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Baja</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumenEmpleados['baja']) }}</div>
        </div>
    </div>
    <div class="bg-white border rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Listado operativo</h2>
                <p class="text-xs text-gray-500">{{ number_format($empleados->count()) }} empleados encontrados en {{ $areaHuentitan?->nombre ?? 'HUENTITAN' }}</p>
            </div>
            <div class="text-xs text-gray-500">Almacen: {{ $almacen->codigo ?? $almacen->nombre }}</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Empleado</th>
                        <th class="px-4 py-3 text-left">Puesto</th>
                        <th class="px-4 py-3 text-left">Area</th>
                        <th class="px-4 py-3 text-left">Contacto</th>
                        <th class="px-4 py-3 text-left">Estatus</th>
                        <th class="px-4 py-3 text-right">Accion</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($empleados as $empleado)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $empleado->nombre_completo }}</div>
                                <div class="text-xs text-gray-500">ID {{ $empleado->id_Empleado }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $empleado->Puesto ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $empleado->areaRef?->nombre ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                <div>{{ $empleado->Telefono ?: ($empleado->Celular ?: '-') }}</div>
                                @if($empleado->Email)
                                    <div class="text-xs text-gray-500">{{ $empleado->Email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 rounded text-xs {{ (int) $empleado->Estatus === 2 ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' }}">
                                    {{ (int) $empleado->Estatus === 2 ? 'Baja' : 'Activo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'datos']) }}" class="text-blue-700 hover:underline">Ver ficha</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay empleados relacionados con HUENTITAN.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

