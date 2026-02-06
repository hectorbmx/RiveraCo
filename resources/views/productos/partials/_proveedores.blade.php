{{-- Asignar proveedor --}}
<div class="mb-6">
    <h3 class="text-sm font-semibold text-slate-700 mb-3">Agregar proveedor a este producto</h3>

    <form method="POST" action="{{ route('productos.proveedores.attach', $producto->id) }}"
          class="grid md:grid-cols-6 gap-3 items-end">
        @csrf

        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Proveedor</label>
            <select name="proveedor_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                <option value="">-- Selecciona --</option>
                @foreach($proveedores as $prov)
                    <option value="{{ $prov->id }}">{{ $prov->nombre }} {{ $prov->rfc ? "({$prov->rfc})" : '' }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Precio</label>
            <input name="precio_lista" type="number" step="0.0001"
                   class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Moneda</label>
            <select name="moneda" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                <option value="MXN">MXN</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Entrega (días)</label>
            <input name="tiempo_entrega_dias" type="number" min="0"
                   class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
        </div>

        <div class="flex gap-2">
            <button class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl text-sm hover:opacity-90">
                Agregar
            </button>
        </div>

        <div class="md:col-span-6">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="activo" value="1" checked>
                Activo en este proveedor
            </label>
            <input name="notas" placeholder="Notas (opcional)"
                   class="mt-2 w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
        </div>
    </form>
</div>

{{-- Lista proveedores actuales --}}
<h3 class="text-sm font-semibold text-slate-700 mb-3">Proveedores que lo manejan</h3>

<div class="overflow-auto border border-slate-200 rounded-2xl">
    <table class="min-w-full text-sm">
    <thead class="bg-slate-50 border-b border-slate-200">
        <tr>
            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Proveedor</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Precio</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Moneda</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Entrega</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Activo</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Notas</th>
            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Acciones</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
        @forelse($producto->proveedores as $prov)
            <tr>
                <td class="px-4 py-2">
                    <div class="font-semibold text-slate-800">{{ $prov->nombre }}</div>
                    <div class="text-xs text-slate-500">{{ $prov->rfc ?? '-' }}</div>
                </td>

                <td class="px-4 py-2" colspan="6">
                    <form id="form-update-{{ $prov->id }}" method="POST"
                          action="{{ route('productos.proveedores.update', [$producto->id, $prov->id]) }}"
                          class="grid md:grid-cols-6 gap-2 items-center">
                        @csrf
                        @method('PUT')

                        <input name="precio_lista" type="number" step="0.0001"
                               value="{{ $prov->pivot->precio_lista }}"
                               class="border border-slate-200 rounded-xl px-2 py-1 text-sm">

                        <select name="moneda" class="border border-slate-200 rounded-xl px-2 py-1 text-sm">
                            @foreach(['MXN','USD','EUR'] as $m)
                                <option value="{{ $m }}" {{ ($prov->pivot->moneda===$m) ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>

                        <input name="tiempo_entrega_dias" type="number" min="0"
                               value="{{ $prov->pivot->tiempo_entrega_dias }}"
                               class="border border-slate-200 rounded-xl px-2 py-1 text-sm">

                        <select name="activo" class="border border-slate-200 rounded-xl px-2 py-1 text-sm">
                            <option value="1" {{ ((int)$prov->pivot->activo===1) ? 'selected' : '' }}>Sí</option>
                            <option value="0" {{ ((int)$prov->pivot->activo===0) ? 'selected' : '' }}>No</option>
                        </select>

                        <input name="notas" value="{{ $prov->pivot->notas }}"
                               class="border border-slate-200 rounded-xl px-2 py-1 text-sm"
                               placeholder="Notas">

                        <div class="flex justify-end gap-2">
                            <button type="submit" class="px-3 py-1 rounded-xl text-sm bg-[#0B265A] text-white hover:opacity-90">
                                Guardar
                            </button>

                            <button type="submit" 
                                    form="form-delete-{{ $prov->id }}" 
                                    class="px-3 py-1 rounded-xl text-sm border border-slate-200 text-slate-600 hover:bg-slate-50">
                                Quitar
                            </button>
                        </div>
                    </form>

                    <form id="form-delete-{{ $prov->id }}" method="POST"
                          action="{{ route('productos.proveedores.detach', [$producto->id, $prov->id]) }}"
                          onsubmit="return confirm('¿Quitar proveedor de este producto?')"
                          class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-6 text-center text-slate-500">
                    Este producto aún no está asignado a proveedores.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>
