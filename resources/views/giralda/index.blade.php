@extends('layouts.admin')

@section('title', 'GIRALDA')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">GIRALDA</h1>
            <p class="text-sm text-slate-500">Almacen y centro operativo independiente</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('ordenes_compra.index', ['area_codigo' => 'GL']) }}" class="px-4 py-2 rounded bg-white border text-sm hover:bg-slate-50">OC Giralda</a>
            <a href="{{ route('ordenes_compra.create', ['area_codigo' => 'GL']) }}" class="px-4 py-2 rounded bg-[#0B265A] text-white text-sm hover:bg-blue-900">Nueva OC</a>
        </div>
    </div>

    @if(!$areaGiralda)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded p-4 text-sm">
            No encontre un area con codigo GL o nombre Giralda. Crea/activa esa area para ver empleados y folios OC-GL.
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs uppercase text-slate-400">Empleados mostrados</div>
            <div class="text-3xl font-bold text-[#0B265A]">{{ $empleados->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs uppercase text-slate-400">Horas del periodo</div>
            <div class="text-3xl font-bold text-[#0B265A]">{{ number_format($horasExtras->sum('total_horas'), 2) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs uppercase text-slate-400">Pendientes por autorizar</div>
            <div class="text-3xl font-bold text-[#0B265A]">{{ $horasExtras->where('estado', 'pendiente')->count() }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('giralda.index') }}" class="grid md:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium mb-1">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}" class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}" class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Empleado</label>
                <select name="empleado_id" class="w-full border rounded p-2">
                    <option value="">Todos</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id_Empleado }}" @selected((string)$empleadoId === (string)$empleado->id_Empleado)>
                            {{ $empleado->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Estatus empleado</label>
                <select name="estatus" class="w-full border rounded p-2">
                    <option value="activo" @selected($estatus === 'activo')>Activos</option>
                    <option value="baja" @selected($estatus === 'baja')>Baja</option>
                    <option value="todos" @selected($estatus === 'todos')>Todos</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button class="px-4 py-2 rounded bg-[#0B265A] text-white">Filtrar</button>
                <a href="{{ route('giralda.index') }}" class="px-4 py-2 rounded bg-slate-200">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="grid xl:grid-cols-3 gap-6">
        <div class="xl:col-span-1 bg-white rounded-lg shadow p-4">
            <h2 class="font-semibold text-[#0B265A] mb-3">Registrar horas extra</h2>
            <form method="POST" action="{{ route('giralda.horas-extras.store', request()->query()) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Empleado</label>
                    <select name="empleado_id" class="w-full border rounded p-2" required>
                        <option value="">Selecciona empleado</option>
                        @foreach($empleados->where('Estatus', 1) as $empleado)
                            <option value="{{ $empleado->id_Empleado }}">{{ $empleado->nombre_completo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Fecha</label>
                        <input type="date" name="fecha" value="{{ now()->toDateString() }}" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Inicio</label>
                        <input type="time" name="hora_inicio" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Fin</label>
                        <input type="time" name="hora_fin" class="w-full border rounded p-2" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Motivo</label>
                    <input name="motivo" class="w-full border rounded p-2" required>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Solicita</label>
                        <input name="responsable_solicita" value="{{ auth()->user()->name ?? '' }}" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Autoriza</label>
                        <input name="responsable_autoriza" class="w-full border rounded p-2">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="3" class="w-full border rounded p-2"></textarea>
                </div>
                <button class="w-full px-4 py-2 rounded bg-[#0B265A] text-white">Guardar</button>
            </form>
        </div>

        <div id="horas-extras" class="xl:col-span-2 bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold text-[#0B265A]">Horas extra</h2>
                <div class="flex gap-2">
                    <a href="{{ route('giralda.horas-extras.print', request()->query()) }}" target="_blank" class="px-3 py-2 rounded border text-sm">Imprimir</a>
                    <a href="{{ route('giralda.horas-extras.export', request()->query()) }}" class="px-3 py-2 rounded border text-sm">Exportar CSV</a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left p-3">Empleado</th>
                            <th class="text-left p-3">Fecha</th>
                            <th class="text-left p-3">Horario</th>
                            <th class="text-right p-3">Horas</th>
                            <th class="text-left p-3">Motivo</th>
                            <th class="text-left p-3">Estado</th>
                            <th class="text-right p-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($horasExtras as $registro)
                            <tr>
                                <td class="p-3">{{ $registro->empleado?->nombre_completo ?? '-' }}</td>
                                <td class="p-3">{{ optional($registro->fecha)->format('d/m/Y') }}</td>
                                <td class="p-3">{{ substr($registro->hora_inicio, 0, 5) }} - {{ substr($registro->hora_fin, 0, 5) }}</td>
                                <td class="p-3 text-right">{{ number_format((float)$registro->total_horas, 2) }}</td>
                                <td class="p-3">{{ $registro->motivo }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded text-xs {{ $registro->estado === 'autorizado' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($registro->estado) }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    @if($registro->estado !== 'autorizado')
                                        <form method="POST" action="{{ route('giralda.horas-extras.autorizar', $registro) }}" class="inline">
                                            @csrf
                                            <button class="text-blue-600 hover:underline">Autorizar</button>
                                        </form>
                                    @else
                                        <span class="text-slate-400">{{ $registro->responsable_autoriza ?? $registro->autorizadoPor?->name }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-slate-400">Sin horas extra en el periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $horasExtras->links() }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b">
                <h2 class="font-semibold text-[#0B265A]">Empleados Giralda</h2>
            </div>
            <div class="divide-y">
                @forelse($empleados as $empleado)
                    <a href="{{ route('empleados.edit', ['empleado' => $empleado->id_Empleado]) }}" class="block p-4 hover:bg-slate-50">
                        <div class="font-medium">{{ $empleado->nombre_completo }}</div>
                        <div class="text-xs text-slate-500">{{ $empleado->Puesto ?? 'Sin puesto' }} · {{ $empleado->areaRef?->nombre ?? 'Giralda' }}</div>
                    </a>
                @empty
                    <div class="p-6 text-center text-slate-400">No hay empleados asignados a Giralda.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b flex justify-between">
                <h2 class="font-semibold text-[#0B265A]">OC recientes Giralda</h2>
                <a href="{{ route('ordenes_compra.index', ['area_codigo' => 'GL']) }}" class="text-sm text-blue-600">Ver todas</a>
            </div>
            <div class="divide-y">
                @forelse($ordenesCompra as $oc)
                    <a href="{{ route('ordenes_compra.edit', $oc->id) }}" class="block p-4 hover:bg-slate-50">
                        <div class="flex justify-between gap-3">
                            <span class="font-medium">{{ $oc->folio }}</span>
                            <span class="text-sm">{{ optional($oc->fecha)->format('d/m/Y') }}</span>
                        </div>
                        <div class="text-xs text-slate-500">{{ $oc->proveedor?->nombre ?? 'Sin proveedor' }} · {{ ucfirst($oc->estado_normalizado) }}</div>
                    </a>
                @empty
                    <div class="p-6 text-center text-slate-400">Aun no hay OC con area Giralda.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
