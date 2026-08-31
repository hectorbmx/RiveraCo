@extends('layouts.admin')

@section('title', 'Reposicion de caja chica')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Reposicion de caja chica</h1>
            <p class="text-sm text-slate-500">Gastos capturados por ingenieria con autorizacion individual.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('reposicion-caja-chica.imprimir', request()->query()) }}" target="_blank" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Imprimir</a>
            <a href="{{ route('reposicion-caja-chica.exportar-excel', request()->query()) }}" class="rounded-lg border border-green-300 bg-green-50 px-4 py-2 text-sm font-semibold text-green-800 hover:bg-green-100">Exportar Excel</a>
            <a href="{{ route('reposicion-caja-chica.revision') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Revision oficina</a>
            <a href="{{ route('reposicion-caja-chica.relaciones.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Relaciones</a>
            <a href="{{ route('reposicion-caja-chica.create') }}" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900">Nuevo gasto</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-slate-500">Registrado</p>
            <p class="mt-1 text-xl font-bold text-[#0B265A]">${{ number_format((float) $stats['registrado'], 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-slate-500">Autorizado</p>
            <p class="mt-1 text-xl font-bold text-green-700">${{ number_format((float) $stats['autorizado'], 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-slate-500">Borradores</p>
            <p class="mt-1 text-xl font-bold text-slate-800">{{ number_format($stats['borrador']) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-slate-500">Pendientes</p>
            <p class="mt-1 text-xl font-bold text-amber-700">{{ number_format($stats['pendiente']) }}</p>
        </div>
    </div>

    <form method="GET" class="rounded-lg bg-white p-4 shadow-sm border border-slate-200 space-y-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase text-slate-500">Semana mostrada</p>
                <p class="text-sm font-bold text-slate-800">
                    {{ $fechaInicio->format('d/m/Y') }} al {{ $fechaFin->format('d/m/Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" value="{{ $fechaInicio->format('Y-m-d') }}" class="rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha fin</label>
                    <input type="date" name="fecha_fin" value="{{ $fechaFin->format('Y-m-d') }}" class="rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Estado</label>
                    <select name="estado" class="rounded-lg border-slate-300 text-sm">
                        <option value="">Todos</option>
                        @foreach(['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'autorizado' => 'Autorizado', 'autorizado_parcial' => 'Autorizado parcial', 'rechazado' => 'Rechazado'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('estado') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo de comprobacion</label>
                    <select name="categoria_id" class="rounded-lg border-slate-300 text-sm">
                        <option value="">Todas</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected(request('categoria_id') == $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Destino</label>
                    <select name="destino" class="rounded-lg border-slate-300 text-sm">
                        <option value="">Todos</option>
                        <option value="obra" @selected(request('destino') === 'obra')>Obra</option>
                        <option value="almacen" @selected(request('destino') === 'almacen')>Almacen</option>
                    </select>
                </div>
                <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Filtrar</button>
                <a href="{{ route('reposicion-caja-chica.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Limpiar</a>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('reposicion-caja-chica.index', [
                'fecha_inicio' => $semanaAnteriorInicio,
                'fecha_fin' => $semanaAnteriorFin,
                'estado' => request('estado'),
                'categoria_id' => request('categoria_id'),
                'destino' => request('destino'),
            ]) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                ← Semana anterior
            </a>
            <a href="{{ route('reposicion-caja-chica.index', [
                'estado' => request('estado'),
                'categoria_id' => request('categoria_id'),
                'destino' => request('destino'),
            ]) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Semana actual
            </a>
            <a href="{{ route('reposicion-caja-chica.index', [
                'fecha_inicio' => $semanaSiguienteInicio,
                'fecha_fin' => $semanaSiguienteFin,
                'estado' => request('estado'),
                'categoria_id' => request('categoria_id'),
                'destino' => request('destino'),
            ]) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Semana siguiente →
            </a>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm border border-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Folio</th>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3 text-left">Tipo de comprobacion</th>
                    <th class="px-4 py-3 text-left">Proveedor / concepto</th>
                    <th class="px-4 py-3 text-left">Destino</th>
                    <th class="px-4 py-3 text-right">Registrado</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($gastos as $gasto)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-semibold text-slate-800">RCC-G-{{ str_pad($gasto->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">{{ optional($gasto->fecha_gasto)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">{{ $gasto->categoria->nombre ?? '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $gasto->subcategoria->nombre ?? 'Sin categoria' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">{{ $gasto->proveedor_nombre }}</div>
                            <div class="text-xs text-slate-500">{{ $gasto->concepto }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($gasto->destino === 'obra')
                                {{ $gasto->obra->nombre ?? 'Obra no definida' }}
                            @else
                                {{ $gasto->almacen->nombre ?? 'Almacen no definido' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">${{ number_format((float) $gasto->importe_registrado, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str_replace('_', ' ', $gasto->estado_autorizacion) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('reposicion-caja-chica.show', $gasto) }}" class="font-semibold text-blue-700 hover:underline">Ver</a>

                                @if($gasto->estado_autorizacion === 'pendiente' && auth()->user()?->can('caja_chica.authorize'))
                                    <form method="POST" action="{{ route('reposicion-caja-chica.autorizar', $gasto) }}" onsubmit="return confirm('¿Autorizar este gasto completo?')">
                                        @csrf
                                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white hover:bg-green-700" title="Autorizar gasto">
                                            ✓
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-slate-500">Aun no hay gastos de reposicion de caja chica.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $gastos->links() }}</div>
</div>
@endsection




