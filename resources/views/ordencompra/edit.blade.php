@extends('layouts.admin')

@section('content')
@php
  $bloqueado = in_array($oc->estado_normalizado, ['autorizada','cancelada']);
@endphp

<div class="p-6">
    <div class="flex justify-between mb-4">
        <h1 class="text-xl font-semibold">
            Orden {{ $oc->folio }}
        </h1>

        <div class="space-x-2">
            <!-- @if(!$bloqueado)
                <form method="POST" action="{{ route('ordenes_compra.autorizar',$oc->id) }}" class="inline">
                    @csrf
                    <button class="bg-green-600 text-white px-3 py-1 rounded">Autorizar</button>
                </form>
            @endif -->
            <button type="button"
                  id="btnModalProducto"
                  class="px-3 py-2 rounded-xl text-sm border border-slate-200 text-slate-700 hover:bg-slate-50">
              + Producto
          </button>

            @if(!$bloqueado)
                <form method="POST" action="{{ route('ordenes_compra.update', $oc->id) }}" id="formEncabezadoOc" class="inline">
                    @csrf
                    @method('PUT')

                    {{-- si tienes campos editables del encabezado, se envían aquí --}}
                    {{-- si no, puedes mandar algo mínimo o quitar el form y solo redirigir --}}
                    <button class="bg-blue-600 text-white px-3 py-1 rounded">
                        Guardar
                    </button>
                </form>
            @endif


            <a href="{{ route('ordenes_compra.index') }}"
               class="bg-gray-600 text-white px-3 py-1 rounded">
                Ver
            </a>
        </div>
    </div>

{{-- Modal: Crear producto rápido --}}
<div id="modalProductoBackdrop" class="fixed inset-0 bg-black/40 hidden z-50"></div>

<div id="modalProducto" class="fixed inset-0 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold text-[#0B265A]">Crear producto</h3>
                <button type="button" id="btnCerrarModalProducto" class="text-slate-500 hover:text-slate-800">✕</button>
            </div>

            <div class="p-6">
                <div id="modalProductoError" class="hidden mb-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm"></div>

                <form id="formCrearProducto" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre *</label>
                        <input name="nombre" id="mp_nombre" required
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm"
                               placeholder="Ej. Cámara de seguridad">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">SKU</label>
                            <input name="sku" id="mp_sku"
                                   class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm"
                                   placeholder="Opcional">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Unidad</label>
                            <input name="unidad" id="mp_unidad"
                                   class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm"
                                   placeholder="pza, caja, m, kg...">
                        </div>
                    </div>

                    <input type="hidden" name="tipo" value="PRODUCTO">
                    <input type="hidden" name="activo" value="1">

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Descripción</label>
                        <textarea name="descripcion" id="mp_descripcion" rows="3"
                                  class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm"
                                  placeholder="Opcional"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button"
                                id="btnCancelarModalProducto"
                                class="px-4 py-2 rounded-xl text-sm border border-slate-200 text-slate-600 hover:bg-slate-50">
                            Cancelar
                        </button>

                        <button type="submit"
                                id="btnGuardarProducto"
                                class="bg-[#0B265A] text-white px-4 py-2 rounded-xl text-sm hover:opacity-90">
                            Crear y usar
                        </button>
                        
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const btnOpen   = document.getElementById('btnModalProducto');
    const modal     = document.getElementById('modalProducto');
    const backdrop  = document.getElementById('modalProductoBackdrop');
    const btnClose  = document.getElementById('btnCerrarModalProducto');
    const btnCancel = document.getElementById('btnCancelarModalProducto');
    const form      = document.getElementById('formCrearProducto');
    const errorBox  = document.getElementById('modalProductoError');

    const csrf = document.querySelector('input[name="_token"]')?.value;

    const inputDetalleProductoId = document.getElementById('detalle_producto_id');
    const inputDetalleLegacyId   = document.getElementById('detalle_legacy_prod_id');
    const inputDetalleDesc       = document.getElementById('detalle_descripcion');
    const inputDetalleUnidad     = document.getElementById('detalle_unidad');

    function openModal() {
        errorBox.classList.add('hidden');
        errorBox.innerText = '';
        backdrop.classList.remove('hidden');
        modal.classList.remove('hidden');

        // sugerencia: precargar nombre con lo que el usuario ya escribió en descripción
        if (inputDetalleDesc && !document.getElementById('mp_nombre').value) {
            document.getElementById('mp_nombre').value = inputDetalleDesc.value || '';
        }
    }

    function closeModal() {
        backdrop.classList.add('hidden');
        modal.classList.add('hidden');
    }

    btnOpen?.addEventListener('click', openModal);
    btnClose?.addEventListener('click', closeModal);
    btnCancel?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);

    form?.addEventListener('submit', async function (e) {
        e.preventDefault();

        errorBox.classList.add('hidden');
        errorBox.innerText = '';

        const btnSave = document.getElementById('btnGuardarProducto');
        btnSave.disabled = true;

        try {
            const payload = {
                nombre: document.getElementById('mp_nombre').value.trim(),
                sku: document.getElementById('mp_sku').value.trim(),
                unidad: document.getElementById('mp_unidad').value.trim(),
                tipo: 'PRODUCTO',
                activo: 1,
                descripcion: document.getElementById('mp_descripcion').value.trim(),
            };

            const res = await fetch("{{ route('productos.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (!res.ok || !data.ok) {
                let msg = 'No se pudo crear el producto.';
                if (data?.message) msg = data.message;
                if (data?.errors) {
                    msg = Object.values(data.errors).flat().join(' ');
                }
                throw new Error(msg);
            }

            const p = data.producto;

            // ✅ autollenar el detalle
            if (inputDetalleProductoId) inputDetalleProductoId.value = p.id;
            if (inputDetalleLegacyId) inputDetalleLegacyId.value = p.legacy_prod_id || '';

            if (inputDetalleDesc) inputDetalleDesc.value = p.nombre || '';
            if (inputDetalleUnidad) inputDetalleUnidad.value = p.unidad || '';

            closeModal();

        } catch (err) {
            errorBox.innerText = err.message || 'Error desconocido.';
            errorBox.classList.remove('hidden');
        } finally {
            btnSave.disabled = false;
        }
    });
})();
</script>


    <div class="bg-white border rounded-xl p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Proveedor</label>
            <select form="formEncabezadoOc" name="proveedor_id" id="oc_proveedor_id" class="w-full border p-2 rounded" @disabled($bloqueado)>
                @foreach($proveedores as $p)
                    <option value="{{ $p->id }}" @selected(old('proveedor_id', $oc->proveedor_id) == $p->id)>{{ $p->nombre }}</option>
                @endforeach
            </select>
            @error('proveedor_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Area</label>
            <select form="formEncabezadoOc" name="area_id" class="w-full border p-2 rounded" @disabled($bloqueado)>
                @foreach($areas as $a)
                    <option value="{{ $a->id }}" @selected(old('area_id', $oc->area_id) == $a->id)>{{ $a->nombre }}</option>
                @endforeach
            </select>
            @error('area_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Obra</label>
            <select form="formEncabezadoOc" name="obra_id" id="edit_obra_id" class="w-full border p-2 rounded" @disabled($bloqueado)>
                <option value="">Compra general</option>
                @foreach($obras as $o)
                    <option value="{{ $o->id }}" @selected(old('obra_id', $oc->obra_id) == $o->id)>{{ $o->nombre }}</option>
                @endforeach
            </select>
            @error('obra_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Centro de costo</label>
            <select form="formEncabezadoOc" name="centro_costo_id" id="edit_centro_costo_id" class="w-full border p-2 rounded" @disabled($bloqueado)>
                <option value="">Sin centro de costo</option>
                @foreach($centrosCosto as $centro)
                    <option value="{{ $centro->id }}" @selected(old('centro_costo_id', $oc->centro_costo_id) == $centro->id)>
                        {{ $centro->codigo ? $centro->codigo . ' - ' : '' }}{{ $centro->nombre }}
                    </option>
                @endforeach
            </select>
            @error('centro_costo_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha</label>
            <input form="formEncabezadoOc" type="date" name="fecha" value="{{ old('fecha', optional($oc->fecha)->format('Y-m-d')) }}" class="w-full border p-2 rounded" @disabled($bloqueado)>
            @error('fecha')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Moneda</label>
            <select form="formEncabezadoOc" name="moneda" class="w-full border p-2 rounded" @disabled($bloqueado)>
                @foreach(['MXN','USD','EUR'] as $moneda)
                    <option value="{{ $moneda }}" @selected(old('moneda', $oc->moneda ?? 'MXN') === $moneda)>{{ $moneda }}</option>
                @endforeach
            </select>
            @error('moneda')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo de cambio</label>
            <input form="formEncabezadoOc" type="number" step="0.0001" name="tipo_cambio" value="{{ old('tipo_cambio', $oc->tipo_cambio) }}" class="w-full border p-2 rounded" @disabled($bloqueado)>
            @error('tipo_cambio')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Cotizacion</label>
            <input form="formEncabezadoOc" name="cotizacion" value="{{ old('cotizacion', $oc->cotizacion) }}" class="w-full border p-2 rounded" @disabled($bloqueado)>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Atencion</label>
            <input form="formEncabezadoOc" name="atencion" value="{{ old('atencion', $oc->atencion) }}" class="w-full border p-2 rounded" @disabled($bloqueado)>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo pago</label>
            <select form="formEncabezadoOc" name="tipo_pago" class="w-full border p-2 rounded" @disabled($bloqueado)>
                <option value="">Selecciona metodo</option>
                <option value="PUE" @selected(old('tipo_pago', $oc->tipo_pago) === 'PUE')>PUE - Pago en una sola exhibicion</option>
                <option value="PPD" @selected(old('tipo_pago', $oc->tipo_pago) === 'PPD')>PPD - Pago en parcialidades o diferido</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Forma pago</label>
            <select form="formEncabezadoOc" name="forma_pago" class="w-full border p-2 rounded" @disabled($bloqueado)>
                <option value="">Selecciona forma</option>
                @foreach([
                    '01' => '01 - Efectivo',
                    '02' => '02 - Cheque nominativo',
                    '03' => '03 - Transferencia electronica de fondos',
                    '04' => '04 - Tarjeta de credito',
                    '28' => '28 - Tarjeta de debito',
                    '99' => '99 - Por definir',
                ] as $clave => $label)
                    <option value="{{ $clave }}" @selected(old('forma_pago', $oc->forma_pago) == $clave)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Comentarios</label>
            <textarea form="formEncabezadoOc" name="comentarios" rows="2" class="w-full border p-2 rounded" @disabled($bloqueado)>{{ old('comentarios', $oc->comentarios) }}</textarea>
        </div>

        <input form="formEncabezadoOc" type="hidden" name="planeacion_gasto_id" value="{{ old('planeacion_gasto_id', $oc->planeacion_gasto_id) }}">
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const obra = document.getElementById('edit_obra_id');
        const centro = document.getElementById('edit_centro_costo_id');
        obra?.addEventListener('change', () => {
            if (obra.value && centro) centro.value = '';
        });
        centro?.addEventListener('change', () => {
            if (centro.value && obra) obra.value = '';
        });
    });
    </script>

    {{-- Totales --}}
    <div class="grid grid-cols-4 gap-4 mb-4">
        <div>Subtotal: ${{ number_format($oc->subtotal,2) }}</div>
        <div>IVA: ${{ number_format($oc->iva,2) }}</div>
        <div>Otros: ${{ number_format($oc->otros_impuestos,2) }}</div>
        <div class="font-semibold">Total: ${{ number_format($oc->total,2) }}</div>
    </div>

    {{-- Agregar detalle --}}
    @if(!$bloqueado)
    <form method="POST" action="{{ route('ordenes_compra.detalles.store',$oc->id) }}"
          class="grid grid-cols-6 gap-2 mb-4">
        @csrf
        
  <!-- <input id="descProducto" name="descripcion"class="border p-2 col-span-2" placeholder="Descripción / buscar producto..."  autocomplete="off"> -->
   <div class="col-span-2">
        <input id="descProducto" name="descripcion" class="w-full border p-2 rounded" placeholder="Descripción / buscar producto..." autocomplete="off">
        <span class="text-[10px] text-slate-400 block mt-1 ml-1 uppercase font-bold">Descripción del Producto</span>
        
        <div id="producto_meta" class="text-[11px] text-slate-400 mt-1 ml-1 leading-tight"></div>
        <div id="sugerenciasProductos" class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow hidden max-h-60 overflow-auto"></div>
    </div>

  <input type="hidden" name="producto_id" id="producto_id">
  <input type="hidden" name="legacy_prod_id" id="legacy_prod_id">
  <input type="hidden" name="unidad" id="unidad">

  <div id="sugerenciasProductos"
       class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow hidden max-h-60 overflow-auto">
  </div>


        <!-- <input name="descripcion" placeholder="Descripción" class="border p-2 col-span-2" required> -->
        <!-- <input name="cantidad" type="number" step="0.001" placeholder="Cant." class="border p-2">
        <input name="precio_unitario" type="number" step="0.0001" placeholder="Precio" class="border p-2">
        <input name="iva" type="number" step="0.01" placeholder="IVA" class="border p-2" value=""> -->
        <!-- Cantidad -->
    <div>
        <input name="cantidad" type="number" step="0.001" placeholder="0.000" class="w-full border p-2 rounded">
        <span class="text-[10px] text-slate-400 block mt-1 ml-1 uppercase font-bold">Cantidad</span>
    </div>

    <!-- Precio -->
    <div>
        <input name="precio_unitario" id="precio_unitario" type="number" step="0.0001" placeholder="0.00" class="w-full border p-2 rounded">
        <span class="text-[10px] text-slate-400 block mt-1 ml-1 uppercase font-bold">Precio Unit.</span>
    </div>

    <!-- IVA -->
    <div>
        <select name="iva" class="w-full border p-2 rounded">
            @foreach($tiposIva as $tipo)
                <option value="{{ (float) $tipo->porcentaje }}" @selected($tipo->default)>
                    {{ $tipo->nombre }} ({{ number_format((float) $tipo->porcentaje, 2) }}%)
                </option>
            @endforeach
        </select>
        <span class="text-[10px] text-slate-400 block mt-1 ml-1 uppercase font-bold">% IVA</span>
    </div>
        <div class="flex flex-col">
        <button class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 transition-colors">
            Agregar
        </button>
        <span class="text-[10px] text-transparent mt-1 ml-1 select-none">-</span> <!-- Espaciador para alinear -->
    </div>
    </form>
    @endif

    {{-- Detalles --}}
    <table class="w-full text-sm border">
        <thead class="bg-gray-100">
        <tr>
            <th class="p-2 border">Descripción</th>
            <th class="p-2 border">Cant</th>
            <th class="p-2 border">Precio</th>
            <th class="p-2 border">SubTotal</th>
            <th class="p-2 border">%IVA</th>
            <th class="p-2 border">IVA</th>
            <th class="p-2 border">Importe</th>
            <th class="p-2 border"></th>
        </tr>
        </thead>
        <tbody>
        @foreach($oc->detalles as $d)
        
            <tr>
                <td class="p-2 text-center">
                    <div>{{ $d->descripcion }}</div>
                    @if($d->producto)
                        <div class="mt-1 text-[11px] text-slate-400">
                            SKU: {{ $d->producto->sku ?: '-' }}
                            @if($d->producto->descripcion)
                                · {{ $d->producto->descripcion }}
                            @endif
                        </div>
                    @endif
                </td>
                <td class="p-2 text-center">{{ $d->cantidad }}</td>
                <td class="p-2 text-center">${{ number_format($d->precio_unitario,2) }}</td>
                <td class="p-2 text-center">${{ number_format($d->precio_unitario*$d->cantidad,2) }}</td>
                <td class="p-2 text-center">%{{ number_format($d->iva) }}</td>
                <td class="p-2 text-center">${{ number_format($d->iva_calculado,2) }}</td>
                <td class="p-2 text-center">${{ number_format($d->total,2) }}</td>
                <td class="p-2 text-center">
                    @if(!$bloqueado)
                    <form method="POST"
                          action="{{ route('ordenes_compra.detalles.destroy',[$oc->id,$d->id]) }}">
                        @csrf @method('DELETE')
                        <button class="text-red-600">Eliminar</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('descProducto');
    const box   = document.getElementById('sugerenciasProductos');

    // IDs actualizados para coincidir con tu formulario de detalles
    const productoId = document.getElementById('detalle_producto_id') || document.getElementById('producto_id');
    const legacyId   = document.getElementById('detalle_legacy_prod_id') || document.getElementById('legacy_prod_id');
    const unidad     = document.getElementById('detalle_unidad') || document.getElementById('unidad');
    const meta        = document.getElementById('producto_meta');
    const proveedor   = document.getElementById('oc_proveedor_id');
    const precioInput = document.getElementById('precio_unitario');

    if (!input || !box || !productoId) {
        console.warn('Autocomplete: Faltan elementos esenciales en el DOM');
        return;
    }

    let timer = null;

    input.addEventListener('input', () => {
        clearTimeout(timer);

        const q = input.value.trim();
        
        // Limpiar campos si el texto es muy corto
        if (q.length < 2) {
            box.classList.add('hidden');
            box.innerHTML = '';
            productoId.value = '';
            if(legacyId) legacyId.value = '';
            if(meta) meta.innerText = '';
            if(precioInput) precioInput.value = '';
            return;
        }

        timer = setTimeout(async () => {
            try {
                /** 
                 * SOLUCIÓN A SUBDOMINIOS Y CARPETAS:
                 * Usamos el helper route() de Laravel para que la URL sea absoluta y correcta
                 * sin importar si estás en /v2/public/ o en la raíz.
                 */
                const urlBusqueda = "{{ route('productos.buscar') }}";
                
                const params = new URLSearchParams({ q });
                if (proveedor?.value) params.set('proveedor_id', proveedor.value);

                const res = await fetch(`${urlBusqueda}?${params.toString()}`, {
                    headers: { 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) throw new Error('Error en la respuesta del servidor');

                const data = await res.json();

                if (!data.length) {
                    box.classList.add('hidden');
                    box.innerHTML = '';
                    return;
                }

                // Generar lista de sugerencias
                box.innerHTML = data.map(p => `
                    <div class="px-3 py-2 hover:bg-slate-100 cursor-pointer border-b border-slate-50 last:border-0"
                         data-id="${p.id}"
                         data-legacy="${p.legacy_prod_id ?? ''}"
                         data-nombre="${(p.nombre ?? '').replace(/"/g,'&quot;')}"
                         data-unidad="${p.unidad ?? ''}"
                         data-sku="${(p.sku ?? '').replace(/"/g,'&quot;')}"
                         data-descripcion="${(p.descripcion ?? '').replace(/"/g,'&quot;')}"
                         data-precio="${p.ultimo_precio ?? ''}"
                         data-moneda="${p.moneda_precio ?? ''}">
                        <div class="font-semibold text-slate-800">${p.nombre}</div>
                        <div class="text-xs text-slate-500">
                            SKU: ${p.sku ?? '-'} - Unidad: ${p.unidad ?? '-'}${p.ultimo_precio !== null && p.ultimo_precio !== undefined ? ` - Ultimo precio: ${Number(p.ultimo_precio).toFixed(2)} ${p.moneda_precio ?? ''}` : ''}
                        </div>
                        ${p.descripcion ? `<div class="text-xs text-slate-400">${p.descripcion}</div>` : ``}
                    </div>
                `).join('');

                box.classList.remove('hidden');

                // Eventos para seleccionar producto
                box.querySelectorAll('[data-id]').forEach(item => {
                    // Cambiado a 'click' simple para mayor agilidad
                    item.addEventListener('click', () => {
                        productoId.value = item.dataset.id;
                        if(legacyId) legacyId.value = item.dataset.legacy || '';
                        if(unidad)   unidad.value   = item.dataset.unidad || '';
                        if(precioInput) precioInput.value = item.dataset.precio !== '' ? Number(item.dataset.precio).toFixed(4) : '';
                        
                        input.value = item.dataset.nombre || input.value;
                        if(meta) {
                            const sku = item.dataset.sku || '-';
                            const descripcion = item.dataset.descripcion || '';
                            const precio = item.dataset.precio !== '' ? ` - Ultimo precio: ${Number(item.dataset.precio).toFixed(2)} ${item.dataset.moneda || ''}` : '';
                            meta.innerText = descripcion ? `SKU: ${sku} - ${descripcion}${precio}` : `SKU: ${sku}${precio}`;
                        }

                        box.classList.add('hidden');
                        box.innerHTML = '';
                    });
                });

            } catch (error) {
                console.error('Error en el fetch de productos:', error);
            }
        }, 300);
    });

    // Cerrar el buscador si el usuario hace clic fuera de él
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !box.contains(e.target)) {
            box.classList.add('hidden');
        }
    });
});
</script>
