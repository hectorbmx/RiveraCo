<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-[#0B265A]">Entrega de equipo de proteccion personal</h2>
        <p class="text-sm text-slate-500">Historial de botas, cascos, chalecos, guantes, lentes y otros equipos configurables.</p>
    </div>

    <form method="POST" action="{{ route('empleados.epp.store', $empleado->id_Empleado) }}" class="grid md:grid-cols-4 gap-3 bg-slate-50 rounded-lg p-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Articulo</label>
            <input name="articulo" list="articulos_epp" class="w-full border rounded p-2" required>
            <datalist id="articulos_epp">
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
        <div>
            <label class="block text-sm font-medium mb-1">Talla</label>
            <input name="talla" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Fecha entrega</label>
            <input type="date" name="fecha_entrega" value="{{ now()->toDateString() }}" class="w-full border rounded p-2" required>
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
            <input name="obra_area" value="{{ $empleado->areaRef?->nombre }}" class="w-full border rounded p-2">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Observaciones</label>
            <input name="observaciones" class="w-full border rounded p-2">
        </div>
        <label class="flex items-center gap-2 text-sm md:col-span-2">
            <input type="checkbox" name="confirmado_por_empleado" value="1" class="rounded">
            Confirmado por empleado
        </label>
        <div class="md:col-span-2 flex justify-end">
            <button class="px-4 py-2 rounded bg-[#0B265A] text-white">Registrar entrega</button>
        </div>
    </form>

    <div class="overflow-x-auto border rounded-lg">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left p-3">Fecha</th>
                    <th class="text-left p-3">Articulo</th>
                    <th class="text-right p-3">Cantidad</th>
                    <th class="text-left p-3">Talla</th>
                    <th class="text-left p-3">Condicion</th>
                    <th class="text-left p-3">Obra/area</th>
                    <th class="text-left p-3">Entrega</th>
                    <th class="text-left p-3">Confirmacion</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($entregas as $entrega)
                    <tr>
                        <td class="p-3">{{ optional($entrega->fecha_entrega)->format('d/m/Y') }}</td>
                        <td class="p-3">{{ $entrega->articulo }}</td>
                        <td class="p-3 text-right">{{ number_format((float)$entrega->cantidad, 2) }}</td>
                        <td class="p-3">{{ $entrega->talla ?: '-' }}</td>
                        <td class="p-3">{{ ucfirst($entrega->condicion) }}</td>
                        <td class="p-3">{{ $entrega->obra_area ?: '-' }}</td>
                        <td class="p-3">{{ $entrega->entregadoPor?->name ?? '-' }}</td>
                        <td class="p-3">
                            @if($entrega->confirmado_por_empleado)
                                <span class="text-green-700">Confirmado</span>
                            @else
                                <span class="text-slate-400">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                    @if($entrega->observaciones)
                        <tr>
                            <td></td>
                            <td colspan="7" class="px-3 pb-3 text-xs text-slate-500">{{ $entrega->observaciones }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="p-6 text-center text-slate-400">Sin entregas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
