@extends('layouts.admin')

@section('title', 'Detalle de gasto')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">RCC-G-{{ str_pad($gasto->id, 5, '0', STR_PAD_LEFT) }}</h1>
            <p class="text-sm text-slate-500">Detalle del gasto y autorizacion individual.</p>
        </div>
        <a href="{{ route('reposicion-caja-chica.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Volver</a>
    </div>

    @if($modoEdicion ?? false)
        <form id="gasto-edicion-form" method="POST" action="{{ route('reposicion-caja-chica.update', $gasto) }}">
            @csrf
            @method('PATCH')
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200 md:col-span-2">
            <h2 class="mb-4 text-lg font-bold text-slate-900">Informacion registrada</h2>
            <dl class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Obra / almacen</dt><dd>{{ $gasto->obra->nombre ?? $gasto->almacen->nombre ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Tipo de comprobacion</dt><dd class="font-semibold text-slate-900">{{ $gasto->categoria->nombre ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Fecha</dt>@if($modoEdicion ?? false)<dd><input type="date" name="fecha_gasto" value="{{ optional($gasto->fecha_gasto)->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></dd>@else<dd>{{ optional($gasto->fecha_gasto)->format('d/m/Y') }}</dd>@endif</div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Forma de pago</dt>@if($modoEdicion ?? false)<dd><select name="forma_pago" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="efectivo" @selected($gasto->forma_pago === 'efectivo')>Efectivo</option><option value="tarjeta" @selected($gasto->forma_pago === 'tarjeta')>Tarjeta</option><option value="transferencia" @selected($gasto->forma_pago === 'transferencia')>Transferencia</option></select></dd>@else<dd>{{ $gasto->forma_pago ?? '-' }}</dd>@endif</div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">Proveedor / beneficiario</dt>
                    @if($modoEdicion ?? false)
                        <dd class="relative">
                            <input type="hidden" name="proveedor_id" id="proveedor_id" value="{{ $gasto->proveedor_id }}">
                            <input type="text" name="proveedor_nombre" id="proveedor_nombre" value="{{ $gasto->proveedor_nombre }}" autocomplete="off" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <div id="proveedor-resultados" class="absolute left-0 right-0 top-full z-50 mt-1 hidden max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl"></div>
                        </dd>
                    @else
                        <dd>{{ $gasto->proveedor_nombre }}</dd>
                    @endif
                </div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">RFC</dt>@if($modoEdicion ?? false)<dd><input type="text" name="proveedor_rfc" id="proveedor_rfc" value="{{ $gasto->proveedor_rfc }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></dd>@else<dd>{{ $gasto->proveedor_rfc ?? '-' }}</dd>@endif</div>
                <div class="md:col-span-2"><dt class="text-xs font-semibold uppercase text-slate-500">Concepto</dt>@if($modoEdicion ?? false)<dd><textarea name="concepto" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ $gasto->concepto }}</textarea></dd>@else<dd>{{ $gasto->concepto }}</dd>@endif</div>

                @php
                    $activoSeleccionado = $gasto->activo_operativo_tipo === 'indirecto'
                        ? 'indirecto'
                        : ($gasto->maquina_id
                            ? 'maquina:' . $gasto->maquina_id
                            : ($gasto->vehiculo_id ? 'vehiculo:' . $gasto->vehiculo_id : ''));
                @endphp
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">¿Hidrotamiz?</dt>
                    @if($modoEdicion ?? false)
                        <dd class="mt-1 flex min-h-[42px] items-center rounded-lg border border-slate-300 px-3 py-2">
                            <input type="hidden" name="es_para_hidrotamiz" value="0">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input type="checkbox" name="es_para_hidrotamiz" id="es_para_hidrotamiz" value="1" @checked($gasto->es_para_hidrotamiz) class="h-4 w-4 rounded border-slate-300 text-[#0B265A] focus:ring-[#0B265A]">
                                Sí, gasto para Hidrotamiz
                            </label>
                        </dd>
                    @else
                        <dd>{{ $gasto->es_para_hidrotamiz ? 'Sí' : 'No' }}</dd>
                    @endif
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">Activo</dt>
                    @if($modoEdicion ?? false)
                        <dd>
                            <select name="activo_operativo" id="activo_operativo" @disabled($gasto->es_para_hidrotamiz) class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" @selected($activoSeleccionado === '')>Sin activo</option>
                                <option value="indirecto" @selected($activoSeleccionado === 'indirecto')>Indirecto</option>
                                @foreach($maquinas ?? [] as $maquina)
                                    <option value="maquina:{{ $maquina->id }}" @selected($activoSeleccionado === 'maquina:' . $maquina->id)>
                                        Maquina - {{ trim(($maquina->codigo ? $maquina->codigo . ' - ' : '') . $maquina->nombre) }}
                                    </option>
                                @endforeach
                                @foreach($vehiculos ?? [] as $vehiculo)
                                    <option value="vehiculo:{{ $vehiculo->id }}" @selected($activoSeleccionado === 'vehiculo:' . $vehiculo->id)>
                                        Vehiculo - {{ trim(implode(' ', array_filter([$vehiculo->marca, $vehiculo->modelo, $vehiculo->placas]))) }}
                                    </option>
                                @endforeach
                            </select>
                        </dd>
                    @else
                        <dd>{{ $gasto->activo_operativo_label }}</dd>
                    @endif
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">Categoria</dt>
                    @if($modoEdicion ?? false)
                        <dd>
                            <select name="subcategoria_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Sin categoria</option>
                                @foreach($subcategorias ?? [] as $subcategoria)
                                    <option value="{{ $subcategoria->id }}" @selected((string) $gasto->subcategoria_id === (string) $subcategoria->id)>
                                        {{ $subcategoria->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </dd>
                    @else
                        <dd>{{ $gasto->subcategoria->nombre ?? '-' }}</dd>
                    @endif
                </div>
                <div class="md:col-span-2"><dt class="text-xs font-semibold uppercase text-slate-500">Motivo sin factura</dt>@if($modoEdicion ?? false)<dd><textarea name="motivo_sin_factura" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ $gasto->motivo_sin_factura }}</textarea></dd>@else<dd>{{ $gasto->motivo_sin_factura ?? '-' }}</dd>@endif</div>
                <div class="md:col-span-2"><dt class="text-xs font-semibold uppercase text-slate-500">Observaciones</dt>@if($modoEdicion ?? false)<dd><textarea name="observaciones" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ $gasto->observaciones }}</textarea></dd>@else<dd>{{ $gasto->observaciones ?? '-' }}</dd>@endif</div>
            </dl>
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <h2 class="mb-4 text-lg font-bold text-slate-900">Autorizacion</h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Estado</dt><dd class="font-semibold text-slate-900">{{ str_replace('_', ' ', $gasto->estado_autorizacion) }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Importe registrado</dt><dd class="text-lg font-bold text-[#0B265A]">${{ number_format((float) $gasto->importe_registrado, 2) }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Importe autorizado</dt><dd class="text-lg font-bold text-green-700">${{ number_format((float) ($gasto->importe_autorizado ?? 0), 2) }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Resuelto por</dt><dd>{{ $gasto->resueltoPor->name ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Fecha resolucion</dt><dd>{{ optional($gasto->resuelto_at)->format('d/m/Y H:i') ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Motivo rechazo</dt><dd>{{ $gasto->motivo_rechazo ?? '-' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
        <h2 class="mb-4 text-lg font-bold text-slate-900">Evidencias</h2>
        @forelse($gasto->archivos as $archivo)
            <a href="{{ $archivo->url }}" target="_blank" class="mb-2 block rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-slate-50">
                {{ $archivo->nombre_original ?? basename($archivo->path) }}
            </a>
        @empty
            <p class="text-sm text-slate-500">No hay evidencias cargadas.</p>
        @endforelse
    </div>
    @if($modoEdicion ?? false)
        <div class="flex items-center justify-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <a href="{{ route('reposicion-caja-chica.show', $gasto) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
            <button id="gasto-edicion-submit" type="submit" disabled class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#163b7a] disabled:cursor-not-allowed disabled:opacity-40">Guardar cambios</button>
        </div>
        </form>
    @endif
</div>
@endsection

@if($modoEdicion ?? false)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('gasto-edicion-form');
    const submit = document.getElementById('gasto-edicion-submit');
    const proveedorInput = document.getElementById('proveedor_nombre');
    const proveedorRfc = document.getElementById('proveedor_rfc');
    const proveedorId = document.getElementById('proveedor_id');
    const resultados = document.getElementById('proveedor-resultados');
    const hidrotamiz = document.getElementById('es_para_hidrotamiz');
    const activo = document.getElementById('activo_operativo');

    if (!form || !submit) return;

    const syncHidrotamiz = () => {
        if (!hidrotamiz || !activo) return;

        activo.disabled = hidrotamiz.checked;
        if (hidrotamiz.checked) {
            activo.value = '';
        }
    };

    hidrotamiz?.addEventListener('change', syncHidrotamiz);
    syncHidrotamiz();

    let proveedorTimer;
    proveedorInput?.addEventListener('input', () => {
        proveedorId.value = '';
        clearTimeout(proveedorTimer);
        const q = proveedorInput.value.trim();
        if (q.length < 3) {
            resultados.innerHTML = '';
            resultados.classList.add('hidden');
            return;
        }

        proveedorTimer = setTimeout(async () => {
            try {
                const response = await fetch(`{{ route('proveedores.buscar') }}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const proveedores = await response.json();
                resultados.innerHTML = '';

                proveedores.forEach((proveedor) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'block w-full border-b border-slate-100 px-3 py-2 text-left text-xs hover:bg-blue-50';
                    button.innerHTML = `<div class="font-semibold text-slate-800"></div><div class="text-[11px] text-slate-500"></div>`;
                    button.children[0].textContent = proveedor.nombre || '';
                    button.children[1].textContent = proveedor.rfc || 'Sin RFC';
                    button.addEventListener('click', () => {
                        proveedorId.value = proveedor.id || '';
                        proveedorInput.value = proveedor.nombre || '';
                        proveedorRfc.value = proveedor.rfc || '';
                        resultados.classList.add('hidden');
                        updateState();
                    });
                    resultados.appendChild(button);
                });

                resultados.classList.toggle('hidden', proveedores.length === 0);
            } catch (error) {
                resultados.innerHTML = '';
                resultados.classList.add('hidden');
            }
        }, 300);
    });

    document.addEventListener('click', (event) => {
        if (!resultados?.contains(event.target) && event.target !== proveedorInput) {
            resultados?.classList.add('hidden');
        }
    });

    const snapshot = () => JSON.stringify(Array.from(new FormData(form).entries()));
    const initialState = snapshot();
    let dirty = false;

    const updateState = () => {
        dirty = snapshot() !== initialState;
        submit.disabled = !dirty;
    };

    form.addEventListener('input', updateState);
    form.addEventListener('change', updateState);
    form.addEventListener('submit', () => {
        dirty = false;
        submit.disabled = true;
    });
    window.addEventListener('beforeunload', (event) => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });
});
</script>
@endpush
@endif
