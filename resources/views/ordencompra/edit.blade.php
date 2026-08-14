@extends('layouts.admin')

@section('content')
@php
  $bloqueado = in_array($oc->estado_normalizado, ['autorizada','verificada','cancelada']);
  $esObraCivil = in_array(strtoupper((string) ($oc->obra->tipo_obra ?? '')), ['OBRA_CIVIL', 'CIVIL'], true);
@endphp

<div class="p-6">
    <div class="flex justify-between mb-4">
        <h1 class="text-xl font-semibold">
            Orden {{ $oc->folio }}
            @if($oc->es_caja_chica)
                <span class="ml-2 align-middle rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">Caja chica</span>
            @endif
        </h1>

        <div class="space-x-2">
            <!-- @if(!$bloqueado)
                <form method="POST" action="{{ route('ordenes_compra.autorizar',$oc->id) }}" class="inline">
                    @csrf
                    <button class="bg-green-600 text-white px-3 py-1 rounded">Autorizar</button>
                </form>
            @endif -->
            @unless($esObraCivil)
            <button type="button"
                  id="btnModalProducto"
                  class="px-3 py-2 rounded-xl text-sm border border-slate-200 text-slate-700 hover:bg-slate-50">
              + Producto
          </button>
            @endunless

            @if(!$bloqueado)
                <form method="POST" action="{{ route('ordenes_compra.update', $oc->id) }}" id="formEncabezadoOc" class="inline" data-loading-form data-loading-message="Guardando orden de compra...">
                    @csrf
                    @method('PUT')

                    {{-- si tienes campos editables del encabezado, se envían aquí --}}
                    {{-- si no, puedes mandar algo mínimo o quitar el form y solo redirigir --}}
                    <button class="bg-blue-600 text-white px-3 py-1 rounded">
                        Guardar
                    </button>
                </form>
            @endif


            @canany(['ordenes_compra.print.access', 'ordenes_compra.imprimir'])
                <a href="{{ route('ordenes_compra.print', $oc->id) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 rounded bg-slate-900 px-3 py-1 text-white hover:bg-slate-700"
                   title="Imprimir OC">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z" />
                    </svg>
                    <span>Imprimir</span>
                </a>
            @endcanany

            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('ordenes_compra.index') }}"
               class="inline-flex items-center gap-2 rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-700"
               title="Regresar">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Regresar</span>
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
        window.mostrarCargaOc?.('Creando producto...');

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
            window.ocultarCargaOc?.();
            errorBox.innerText = err.message || 'Error desconocido.';
            errorBox.classList.remove('hidden');
        } finally {
            window.ocultarCargaOc?.();
            btnSave.disabled = false;
        }
    });
})();
</script>


    <div class="bg-white border rounded-xl p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <label class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                <input form="formEncabezadoOc" type="checkbox" name="es_caja_chica" value="1" class="rounded border-amber-300" @checked(old('es_caja_chica', $oc->es_caja_chica)) @disabled($bloqueado)>
                Caja chica
            </label>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Proveedor</label>
            <select form="formEncabezadoOc" name="proveedor_id" id="oc_proveedor_id" class="w-full border p-2 rounded" @disabled($bloqueado)>
                <option value="" @selected(! old('proveedor_id', $oc->proveedor_id))>Sin proveedor</option>
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
        <div>Subtotal: ${{ number_format($oc->subtotal_calc,2) }}</div>
        <div>IVA: ${{ number_format($oc->iva_monto_calc,2) }}</div>
        <div>Otros: ${{ number_format($oc->otros_monto_calc,2) }}</div>
        <div class="font-semibold">Total: ${{ number_format($oc->total_calc,2) }}</div>
    </div>

    {{-- Agregar detalle --}}
    @if(!$bloqueado)
    <form method="POST" action="{{ route('ordenes_compra.detalles.store',$oc->id) }}"
          class="grid grid-cols-1 md:grid-cols-8 gap-2 mb-4" data-loading-form data-loading-message="Agregando producto a la orden...">
        @csrf
        
  <!-- <input id="descProducto" name="descripcion"class="border p-2 col-span-2" placeholder="Descripción / buscar producto..."  autocomplete="off"> -->
   <div class="col-span-2">
        <input id="descProducto" name="descripcion" class="w-full border p-2 rounded" placeholder="{{ $esObraCivil ? 'Descripcion / buscar concepto civil...' : 'Descripcion / buscar producto...' }}" autocomplete="off">
        <span class="text-[10px] text-slate-400 block mt-1 ml-1 uppercase font-bold">{{ $esObraCivil ? 'Concepto civil' : 'Descripcion del producto' }}</span>
        
        <div id="producto_meta" class="text-[11px] text-slate-400 mt-1 ml-1 leading-tight"></div>
        <div id="sugerenciasProductos" class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow hidden max-h-60 overflow-auto"></div>
    </div>

  <input type="hidden" name="producto_id" id="producto_id">
  <input type="hidden" name="civil_concept_id" id="civil_concept_id">
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
        <input name="cantidad" id="cantidad" type="number" step="0.001" placeholder="0.000" class="w-full border p-2 rounded">
        <span class="text-[10px] text-slate-400 block mt-1 ml-1 uppercase font-bold">Cantidad</span>
        <span id="civil_cantidad_alerta" class="hidden text-[11px] text-amber-600 block mt-1 ml-1 leading-tight"></span>
    </div>

    <!-- Precio -->
    <div>
        <input name="precio_unitario" id="precio_unitario" type="number" step="0.0001" placeholder="0.00" class="w-full border p-2 rounded">
        <span class="text-[10px] text-slate-400 block mt-1 ml-1 uppercase font-bold">Precio Unit.</span>
    </div>

    <!-- Descuento -->
    <div>
        <input name="descuento_porcentaje" type="number" step="0.01" min="0" max="100" placeholder="0.00" class="w-full border p-2 rounded">
        <span class="text-[10px] text-slate-400 block mt-1 ml-1 uppercase font-bold">% Desc.</span>
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
    {{-- Retención --}}
<div>
    <select name="tipo_retencion_id" class="w-full border p-2 rounded">
        <option value="">Sin retención</option>

        @foreach($tiposRetencion as $retencion)
            <option value="{{ $retencion->id }}">
                {{ $retencion->nombre }}
                ({{ number_format((float) $retencion->porcentaje, 2) }}%)
            </option>
        @endforeach
    </select>

    <span class="text-[10px] text-slate-400 block mt-1 ml-1 uppercase font-bold">
        Retención
    </span>
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
            <th class="p-2 border">Desc.</th>
            <th class="p-2 border">IVA</th>
            <th class="p-2 border">Retencion</th>
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

        <td class="p-2 text-center">
            {{ $d->cantidad }}
        </td>

        <td class="p-2 text-center">
            ${{ number_format((float) $d->precio_unitario, 2) }}
        </td>

        <td class="p-2 text-center">
            <div>${{ number_format((float) $d->subtotal_bruto, 2) }}</div>
            @if((float) $d->descuento_calculado > 0)
                <div class="text-[11px] text-slate-500">Neto: ${{ number_format((float) $d->subtotal, 2) }}</div>
            @endif
        </td>

        <td class="p-2 text-center">
            @if((float) $d->descuento_calculado > 0)
                <div class="text-red-600">-${{ number_format((float) $d->descuento_calculado, 2) }}</div>
                <div class="text-xs text-slate-500">{{ number_format((float) $d->descuento_porcentaje, 2) }}%</div>
            @else
                <span class="text-slate-400">-</span>
            @endif
        </td>

        {{-- Ya no mostramos el porcentaje de IVA, solo el importe --}}
        <td class="p-2 text-center">
            ${{ number_format((float) $d->iva_calculado, 2) }}
        </td>

        {{-- Retención --}}
        <td class="p-2 text-center">
            @if($d->tipoRetencion)
                <div class="font-medium text-slate-700">
                    {{ $d->tipoRetencion->nombre }}
                </div>

                <div class="text-xs text-slate-500">
                    {{ number_format((float) $d->retencion_porcentaje, 2) }}%
                </div>

                <div class="text-sm text-red-600">
                    -${{ number_format((float) $d->retenciones, 2) }}
                </div>
            @else
                <span class="text-slate-400">Sin retención</span>
            @endif
        </td>

        <td class="p-2 text-center font-semibold">
            ${{ number_format((float) $d->total, 2) }}
        </td>

        <td class="p-2 text-center">
            @if(!$bloqueado)
                <form method="POST"
                      action="{{ route('ordenes_compra.detalles.destroy', [$oc->id, $d->id]) }}">
                    @csrf
                    @method('DELETE')

                    <button class="text-red-600">
                        Eliminar
                    </button>
                </form>
            @endif
        </td>
    </tr>
@endforeach
</tbody>
    </table>
</div>

<div id="ocLoadingOverlay" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/45 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-2xl">
        <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-[#0B265A]"></div>
        <div id="ocLoadingMessage" class="text-sm font-semibold text-slate-800">Guardando...</div>
        <div class="mt-1 text-xs text-slate-500">Espera un momento.</div>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('ocLoadingOverlay');
    const message = document.getElementById('ocLoadingMessage');

    window.mostrarCargaOc = function (texto) {
        if (message) message.textContent = texto || 'Guardando...';
        overlay?.classList.remove('hidden');
        overlay?.classList.add('flex');
    };

    window.ocultarCargaOc = function () {
        overlay?.classList.add('hidden');
        overlay?.classList.remove('flex');
    };

    document.querySelectorAll('form[data-loading-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            window.mostrarCargaOc(form.dataset.loadingMessage || 'Guardando...');
            form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
                button.disabled = true;
                button.classList.add('opacity-70', 'cursor-not-allowed');
            });
        });
    });
})();
</script>
@endsection
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('descProducto');
    const box   = document.getElementById('sugerenciasProductos');

    const esObraCivil = @json($esObraCivil);
    const urlProductos = "{{ route('productos.buscar') }}";
    const urlConceptosCivil = "{{ route('ordenes_compra.conceptos_civiles.buscar', $oc->id) }}";

    const productoId = document.getElementById('detalle_producto_id') || document.getElementById('producto_id');
    const civilConceptId = document.getElementById('civil_concept_id');
    const legacyId   = document.getElementById('detalle_legacy_prod_id') || document.getElementById('legacy_prod_id');
    const unidad     = document.getElementById('detalle_unidad') || document.getElementById('unidad');
    const meta        = document.getElementById('producto_meta');
    const proveedor   = document.getElementById('oc_proveedor_id');
    const precioInput = document.getElementById('precio_unitario');
    const cantidadInput = document.getElementById('cantidad');
    const cantidadAlerta = document.getElementById('civil_cantidad_alerta');

    if (!input || !box || (!productoId && !civilConceptId)) {
        console.warn('Autocomplete: Faltan elementos esenciales en el DOM');
        return;
    }

    let timer = null;

    function limpiarSeleccion() {
        if (productoId) productoId.value = '';
        if (civilConceptId) civilConceptId.value = '';
        if (legacyId) legacyId.value = '';
        if (meta) meta.innerText = '';
        if (precioInput) precioInput.value = '';
        if (cantidadInput) cantidadInput.classList.remove('border-amber-500', 'bg-amber-50');
        if (cantidadAlerta) {
            cantidadAlerta.classList.add('hidden');
            cantidadAlerta.innerText = '';
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNumber(value, decimals = 2) {
        const number = Number(value ?? 0);
        return Number.isFinite(number) ? number.toLocaleString('es-MX', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) : '0.00';
    }

    function formatMoney(value) {
        const number = Number(value ?? 0);
        return Number.isFinite(number) ? number.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' }) : '$0.00';
    }

    function civilDisponibleTexto(data) {
        const unidadTexto = data.unidad ? ` ${data.unidad}` : '';
        const ordenes = Number(data.ordenes_count ?? 0);
        const ordenesTexto = ordenes > 0 ? ` - OCs: ${ordenes}` : '';
        return `Disponible: ${formatNumber(data.cantidad_disponible)}${unidadTexto} / ${formatMoney(data.importe_disponible)}${ordenesTexto}`;
    }

    function validarCantidadCivilDisponible() {
        if (!esObraCivil || !cantidadInput || !cantidadAlerta || !civilConceptId?.value) {
            return;
        }

        const cantidad = Number(cantidadInput.value || 0);
        const disponible = Number(cantidadInput.dataset.cantidadDisponible || 0);
        const unidadTexto = cantidadInput.dataset.unidad ? ` ${cantidadInput.dataset.unidad}` : '';

        if (cantidad > disponible) {
            cantidadInput.classList.add('border-amber-500', 'bg-amber-50');
            cantidadAlerta.classList.remove('hidden');
            cantidadAlerta.innerText = `Excede disponible: ${formatNumber(disponible)}${unidadTexto}. Se validara al autorizar.`;
            return;
        }

        cantidadInput.classList.remove('border-amber-500', 'bg-amber-50');
        cantidadAlerta.classList.add('hidden');
        cantidadAlerta.innerText = '';
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);

        const q = input.value.trim();

        if (q.length < 2) {
            box.classList.add('hidden');
            box.innerHTML = '';
            limpiarSeleccion();
            return;
        }

        timer = setTimeout(async () => {
            try {
                const urlBusqueda = esObraCivil ? urlConceptosCivil : urlProductos;
                const params = new URLSearchParams({ q });

                if (!esObraCivil && proveedor?.value) {
                    params.set('proveedor_id', proveedor.value);
                }

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

                box.innerHTML = data.map(p => {
                    const precio = p.ultimo_precio !== null && p.ultimo_precio !== undefined && p.ultimo_precio !== ''
                        ? Number(p.ultimo_precio).toFixed(4)
                        : '';
                    const metaLinea = esObraCivil
                        ? `Clave: ${escapeHtml(p.sku ?? '-')} - Unidad: ${escapeHtml(p.unidad ?? '-')} - P.U.: ${precio ? Number(precio).toFixed(2) : '-'}`
                        : `SKU: ${escapeHtml(p.sku ?? '-')} - Unidad: ${escapeHtml(p.unidad ?? '-')}${precio ? ` - Ultimo precio: ${Number(precio).toFixed(2)} ${escapeHtml(p.moneda_precio ?? '')}` : ''}`;
                    const disponibleLinea = esObraCivil ? civilDisponibleTexto(p) : '';
                    const extra = esObraCivil && p.descripcion
                        ? `<div class="text-xs text-slate-400">${escapeHtml(p.descripcion)}</div>`
                        : (p.descripcion ? `<div class="text-xs text-slate-400">${escapeHtml(p.descripcion)}</div>` : '');

                    return `
                    <div class="px-3 py-2 hover:bg-slate-100 cursor-pointer border-b border-slate-50 last:border-0"
                         data-id="${escapeHtml(p.id)}"
                         data-civil-id="${escapeHtml(p.civil_concept_id ?? '')}"
                         data-legacy="${escapeHtml(p.legacy_prod_id ?? '')}"
                         data-nombre="${escapeHtml(p.nombre ?? '')}"
                         data-unidad="${escapeHtml(p.unidad ?? '')}"
                         data-sku="${escapeHtml(p.sku ?? '')}"
                         data-descripcion="${escapeHtml(p.descripcion ?? '')}"
                         data-precio="${escapeHtml(precio)}"
                         data-moneda="${escapeHtml(p.moneda_precio ?? '')}"
                         data-cantidad-disponible="${escapeHtml(p.cantidad_disponible ?? '')}"
                         data-importe-disponible="${escapeHtml(p.importe_disponible ?? '')}"
                         data-ordenes-count="${escapeHtml(p.ordenes_count ?? 0)}">
                        <div class="font-semibold text-slate-800">${escapeHtml(p.nombre)}</div>
                        <div class="text-xs text-slate-500">${metaLinea}</div>
                        ${disponibleLinea ? `<div class="text-xs text-slate-400">${escapeHtml(disponibleLinea)}</div>` : ''}
                        ${extra}
                    </div>`;
                }).join('');

                box.classList.remove('hidden');

                box.querySelectorAll('[data-id]').forEach(item => {
                    item.addEventListener('click', () => {
                        if (esObraCivil) {
                            if (civilConceptId) civilConceptId.value = item.dataset.civilId || item.dataset.id || '';
                            if (productoId) productoId.value = '';
                            if (legacyId) legacyId.value = '';
                        } else {
                            if (productoId) productoId.value = item.dataset.id || '';
                            if (civilConceptId) civilConceptId.value = '';
                            if (legacyId) legacyId.value = item.dataset.legacy || '';
                        }

                        if (unidad) unidad.value = item.dataset.unidad || '';
                        if (precioInput) precioInput.value = item.dataset.precio !== '' ? Number(item.dataset.precio).toFixed(4) : '';
                        if (cantidadInput) {
                            cantidadInput.dataset.cantidadDisponible = item.dataset.cantidadDisponible || '0';
                            cantidadInput.dataset.unidad = item.dataset.unidad || '';
                            validarCantidadCivilDisponible();
                        }

                        input.value = item.dataset.nombre || input.value;
                        if (meta) {
                            const clave = item.dataset.sku || '-';
                            const descripcion = item.dataset.descripcion || '';
                            const precio = item.dataset.precio !== '' ? ` - P.U.: ${Number(item.dataset.precio).toFixed(2)} ${item.dataset.moneda || ''}` : '';
                            const disponible = esObraCivil ? civilDisponibleTexto({
                                unidad: item.dataset.unidad || '',
                                cantidad_disponible: item.dataset.cantidadDisponible || 0,
                                importe_disponible: item.dataset.importeDisponible || 0,
                                ordenes_count: item.dataset.ordenesCount || 0,
                            }) : '';
                            meta.innerText = esObraCivil
                                ? `Clave: ${clave}${descripcion ? ' - ' + descripcion : ''}${precio}${disponible ? ' | ' + disponible : ''}`
                                : (descripcion ? `SKU: ${clave} - ${descripcion}${precio}` : `SKU: ${clave}${precio}`);
                        }

                        box.classList.add('hidden');
                        box.innerHTML = '';
                    });
                });

            } catch (error) {
                console.error(esObraCivil ? 'Error en el fetch de conceptos civiles:' : 'Error en el fetch de productos:', error);
            }
        }, 300);
    });

    cantidadInput?.addEventListener('input', validarCantidadCivilDisponible);

    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !box.contains(e.target)) {
            box.classList.add('hidden');
        }
    });
});
</script>
