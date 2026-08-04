@extends('layouts.admin')

@section('title', 'GIRALDA - Empleados')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Empleados Giralda</h1>
            <p class="text-sm text-slate-500">Listado filtrado, EPP y horas extras del personal de Giralda.</p>
        </div>
        <a href="{{ route('giralda.index') }}" class="text-sm text-slate-500 hover:text-slate-800">Volver a GIRALDA</a>
    </div>

    @if(!$areaGiralda)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded p-4 text-sm">
            No encontre un area con codigo GL o nombre Giralda. Crea/activa esa area para ver empleados.
        </div>
    @endif

    <div class="border-b flex gap-6 text-sm">
        @php
            $tabs = [
                'listado' => 'Listado',
                'epp' => 'EPP',
                'horas_extras' => 'Horas extras',
            ];
        @endphp
        @foreach($tabs as $key => $label)
            <a href="{{ route('giralda.empleados', array_merge(request()->except(['epp_page', 'horas_page']), ['tab' => $key])) }}"
               class="pb-2 border-b-2 transition-all {{ $tab === $key ? 'border-[#FFC107] text-[#0B265A] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('giralda.empleados') }}" class="grid md:grid-cols-5 gap-3 items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
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
                <label class="block text-sm font-medium mb-1">Estatus</label>
                <select name="estatus" class="w-full border rounded p-2">
                    <option value="activo" @selected($estatus === 'activo')>Activos</option>
                    <option value="baja" @selected($estatus === 'baja')>Baja</option>
                    <option value="todos" @selected($estatus === 'todos')>Todos</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button class="px-4 py-2 rounded bg-[#0B265A] text-white">Filtrar</button>
                <a href="{{ route('giralda.empleados', ['tab' => $tab]) }}" class="px-4 py-2 rounded bg-slate-200">Limpiar</a>
            </div>
        </form>
    </div>

    @if($tab === 'listado')
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b flex justify-between">
                <h2 class="font-semibold text-[#0B265A]">Listado filtrado</h2>
                <span class="text-sm text-slate-500">{{ $empleados->count() }} empleados</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left p-3">Empleado</th>
                            <th class="text-left p-3">Puesto</th>
                            <th class="text-left p-3">Area</th>
                            <th class="text-left p-3">Estatus</th>
                            <th class="text-right p-3">EPP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($empleados as $empleado)
                            <tr>
                                <td class="p-3 font-medium">{{ $empleado->nombre_completo }}</td>
                                <td class="p-3">{{ $empleado->Puesto ?? '-' }}</td>
                                <td class="p-3">{{ $empleado->areaRef?->nombre ?? 'Giralda' }}</td>
                                <td class="p-3">{{ (int)$empleado->Estatus === 2 ? 'Baja' : 'Activo' }}</td>
                                <td class="p-3 text-right">{{ $empleado->eppEntregas->count() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-slate-400">No hay empleados asignados a Giralda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($tab === 'epp')
        <div class="grid xl:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="font-semibold text-[#0B265A] mb-3">Registrar entrega de EPP</h2>
                <form method="POST" action="{{ route('empleados.epp.store', ['empleado' => '__EMPLEADO__']) }}" class="space-y-3" id="giralda-epp-form">
                    @csrf
                    <input type="hidden" name="redirect_to" value="giralda.empleados">
                    <div>
                        <label class="block text-sm font-medium mb-1">Empleado</label>
                        <select name="empleado_selector" id="giralda-epp-empleado" class="w-full border rounded p-2" required>
                            <option value="">Selecciona empleado</option>
                            @foreach($empleados->where('Estatus', 1) as $empleado)
                                <option value="{{ $empleado->id_Empleado }}">{{ $empleado->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Articulo</label>
                            <input name="articulo" list="articulos_epp_giralda" class="w-full border rounded p-2" required>
                            <datalist id="articulos_epp_giralda">
                                <option value="Botas">
                                <option value="Casco">
                                <option value="Chaleco reflejante">
                                <option value="Guantes">
                                <option value="Lentes">
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Cantidad</label>
                            <input type="number" step="0.01" min="0.01" name="cantidad" value="1" class="w-full border rounded p-2" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Talla</label>
                            <input name="talla" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Fecha</label>
                            <input type="date" name="fecha_entrega" value="{{ now()->toDateString() }}" class="w-full border rounded p-2" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Condicion</label>
                        <select name="condicion" class="w-full border rounded p-2" required>
                            <option value="nuevo">Nuevo</option>
                            <option value="bueno">Bueno</option>
                            <option value="reposicion">Reposicion</option>
                            <option value="usado">Usado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Obra o area</label>
                        <input name="obra_area" value="{{ $areaGiralda?->nombre ?? 'Giralda' }}" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Observaciones</label>
                        <textarea name="observaciones" rows="3" class="w-full border rounded p-2"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="confirmado_por_empleado" value="1" class="rounded">
                        Confirmado por empleado
                    </label>
                    <button class="w-full px-4 py-2 rounded bg-[#0B265A] text-white">Registrar EPP</button>
                </form>
            </div>

            <div class="xl:col-span-2 bg-white rounded-lg shadow overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="font-semibold text-[#0B265A]">Historial EPP</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="text-left p-3">Empleado</th>
                                <th class="text-left p-3">Fecha</th>
                                <th class="text-left p-3">Articulo</th>
                                <th class="text-right p-3">Cantidad</th>
                                <th class="text-left p-3">Talla</th>
                                <th class="text-left p-3">Entrega</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($eppEntregas as $entrega)
                                <tr>
                                    <td class="p-3">{{ $entrega->empleado?->nombre_completo }}</td>
                                    <td class="p-3">{{ optional($entrega->fecha_entrega)->format('d/m/Y') }}</td>
                                    <td class="p-3">{{ $entrega->articulo }}</td>
                                    <td class="p-3 text-right">{{ number_format((float)$entrega->cantidad, 2) }}</td>
                                    <td class="p-3">{{ $entrega->talla ?: '-' }}</td>
                                    <td class="p-3">{{ $entrega->entregadoPor?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-slate-400">Sin entregas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $eppEntregas->links() }}</div>
            </div>
        </div>
    @endif

    @if($tab === 'horas_extras')
        <div class="grid xl:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow p-4">
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
                    <div>
                        <label class="block text-sm font-medium mb-1">Solicita</label>
                        <input name="responsable_solicita" value="{{ auth()->user()->name ?? '' }}" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Autoriza</label>
                        <input name="responsable_autoriza" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Observaciones</label>
                        <textarea name="observaciones" rows="3" class="w-full border rounded p-2"></textarea>
                    </div>
                    <button class="w-full px-4 py-2 rounded bg-[#0B265A] text-white">Guardar horas</button>
                </form>
            </div>
            <div class="xl:col-span-2 bg-white rounded-lg shadow overflow-hidden">
                <div class="p-4 border-b flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold text-[#0B265A]">Historial horas extra</h2>
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
                                    <td class="p-3">{{ ucfirst($registro->estado) }}</td>
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
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('giralda-epp-form');
    const select = document.getElementById('giralda-epp-empleado');
    if (!form || !select) return;

    const template = form.getAttribute('action');
    form.addEventListener('submit', function (event) {
        if (!select.value) {
            event.preventDefault();
            return;
        }
        form.setAttribute('action', template.replace('__EMPLEADO__', select.value));
    });
});
</script>
@endpush
@endsection
