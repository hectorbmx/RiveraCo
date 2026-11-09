@php
    $modoEntrega = old('modalidad_entrega', $oc->modalidad_entrega ?? 'almacen');
    $almacenEntrega = old('almacen_destino_id', $oc->almacen_destino_id ?? '');
    $destinoBloqueado = ($bloqueado ?? false) || ($entregaBloqueada ?? false);
@endphp
<div>
    <label class="mb-1 block text-sm font-semibold text-slate-700" for="modalidad_entrega">Lugar de entrega</label>
    <select id="modalidad_entrega" name="modalidad_entrega" @if($formId ?? null) form="{{ $formId }}" @endif
            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm disabled:bg-slate-100" @disabled($destinoBloqueado)>
        <option value="almacen" @selected($modoEntrega === 'almacen')>Almacén</option>
        <option value="obra_directa" @selected($modoEntrega === 'obra_directa')>Entrega directa a obra</option>
    </select>
    @error('modalidad_entrega')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    <p class="mt-1 text-xs text-slate-500">La entrega directa utiliza la obra seleccionada en esta orden. El destino no cambia durante la recepción.</p>
</div>
<div>
    <label class="mb-1 block text-sm font-semibold text-slate-700" for="almacen_destino_id">Almacén responsable</label>
    <select id="almacen_destino_id" name="almacen_destino_id" @if($formId ?? null) form="{{ $formId }}" @endif
            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm disabled:bg-slate-100" @disabled($destinoBloqueado)>
        <option value="">Selecciona para entrega directa</option>
        @foreach($almacenesEntrega as $almacenEntregaOpcion)
            <option value="{{ $almacenEntregaOpcion->id }}" @selected((string) $almacenEntrega === (string) $almacenEntregaOpcion->id)>{{ $almacenEntregaOpcion->nombre }}</option>
        @endforeach
    </select>
    @error('almacen_destino_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
