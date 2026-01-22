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
                <form method="POST" action="{{ route('ordenes_compra.update', $oc->id) }}" class="inline">
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
        
  <input id="descProducto" name="descripcion"class="border p-2 col-span-2" placeholder="Descripción / buscar producto..."  autocomplete="off">

  <input type="hidden" name="producto_id" id="producto_id">
  <input type="hidden" name="legacy_prod_id" id="legacy_prod_id">
  <input type="hidden" name="unidad" id="unidad">

  <div id="sugerenciasProductos"
       class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow hidden max-h-60 overflow-auto">
  </div>


        <!-- <input name="descripcion" placeholder="Descripción" class="border p-2 col-span-2" required> -->
        <input name="cantidad" type="number" step="0.001" placeholder="Cant." class="border p-2">
        <input name="precio_unitario" type="number" step="0.0001" placeholder="Precio" class="border p-2">
        <input name="iva" type="number" step="0.01" placeholder="IVA" class="border p-2" value="">
        <button class="bg-blue-600 text-white px-3 py-2 rounded">Agregar</button>
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
                <td class="p-2 text-center">{{ $d->descripcion }}</td>
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

  const productoId = document.getElementById('producto_id');
  const legacyId   = document.getElementById('legacy_prod_id');
  const unidad     = document.getElementById('unidad');

  // Si no existe el input/box en esta vista, no hacemos nada (evita el crash)
  if (!input || !box || !productoId || !legacyId || !unidad) {
    console.warn('Autocomplete productos: faltan elementos en DOM', {
      input: !!input, box: !!box, productoId: !!productoId, legacyId: !!legacyId, unidad: !!unidad
    });
    return;
  }

  let timer = null;

  input.addEventListener('input', () => {
    clearTimeout(timer);

    const q = input.value.trim();
    if (q.length < 2) {
      box.classList.add('hidden');
      box.innerHTML = '';
      productoId.value = '';
      legacyId.value = '';
      return;
    }

    timer = setTimeout(async () => {
      const res = await fetch(`/productos/buscar?q=${encodeURIComponent(q)}`, {
        headers: { 'Accept': 'application/json' }
      });

      if (!res.ok) return;

      const data = await res.json();

      if (!data.length) {
        box.classList.add('hidden');
        box.innerHTML = '';
        return;
      }

      box.innerHTML = data.map(p => `
        <div class="px-3 py-2 hover:bg-slate-50 cursor-pointer"
             data-id="${p.id}"
             data-legacy="${p.legacy_prod_id ?? ''}"
             data-nombre="${(p.nombre ?? '').replace(/"/g,'&quot;')}"
             data-unidad="${p.unidad ?? ''}">
          <div class="font-semibold">${p.nombre}</div>
          <div class="text-xs text-slate-500">SKU: ${p.sku ?? '-'} · Unidad: ${p.unidad ?? '-'}</div>
        </div>
      `).join('');

      box.classList.remove('hidden');

      box.querySelectorAll('[data-id]').forEach(item => {
        item.addEventListener('dblclick', () => {
          productoId.value = item.dataset.id;
          legacyId.value   = item.dataset.legacy || '';
          unidad.value     = item.dataset.unidad || '';
          input.value      = item.dataset.nombre || input.value;

          box.classList.add('hidden');
          box.innerHTML = '';
        });
      });

    }, 250);
  });
});

</script>
