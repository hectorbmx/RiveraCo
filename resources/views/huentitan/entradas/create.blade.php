@extends('layouts.admin')

@section('title', 'Nueva Entrada - HUENTITAN')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6" x-data="nuevaEntrada({{ $selectedOcId ?? 'null' }})">
    {{-- Migas de pan --}}
    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
        <a href="{{ route('huentitan.index') }}" class="hover:underline">HUENTITAN</a>
        <span>/</span>
        <a href="{{ route('huentitan.entradas.index') }}" class="hover:underline">Entradas</a>
        <span>/</span>
        <span class="text-gray-900">Nueva entrada</span>
    </div>

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Registrar Entrada de Material</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $almacen->nombre }} &bull; Recepción física con base en orden de compra autorizada.</p>
        </div>
        <a href="{{ route('huentitan.entradas.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900">
            &larr; Volver al listado
        </a>
    </div>

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('huentitan.entradas.store') }}" @submit="validarFormulario($event)">
        @csrf

        {{-- Panel Superior: Datos de Cabecera --}}
        <div class="bg-white border rounded-lg p-6 shadow-sm space-y-6">
            <h2 class="text-base font-semibold text-gray-900 border-b pb-2">1. Datos Generales de la Recepción</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Selector de Orden de Compra --}}
                <div class="md:col-span-2">
                    <label for="orden_compra_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Orden de Compra Autorizada <span class="text-red-500">*</span>
                    </label>
                    <select 
                        name="orden_compra_id" 
                        id="orden_compra_id" 
                        x-model="ordenCompraId" 
                        @change="cargarDetalles()" 
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white"
                    >
                        <option value="">-- Selecciona una Orden de Compra pendiente --</option>
                        @forelse($ordenesCompra as $oc)
                            <option value="{{ $oc->id }}" @selected(($selectedOcId ?? 0) === $oc->id)>
                                {{ $oc->folio }} &bull; {{ $oc->proveedor?->nombre ?? 'Sin proveedor' }} &bull; {{ $oc->fecha ? $oc->fecha->format('d/m/Y') : '' }} &bull; Total: ${{ number_format($oc->total, 2) }}
                            </option>
                        @empty
                            <option value="" disabled>No hay órdenes de compra autorizadas con saldo pendiente para HUENTITAN</option>
                        @endforelse
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Solo se muestran órdenes de compra autorizadas de HUENTITAN con saldo pendiente de recibir.
                    </p>
                </div>

                {{-- Fecha de recepción --}}
                <div>
                    <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha y Hora de Recepción <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="datetime-local" 
                        name="fecha" 
                        id="fecha" 
                        value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}" 
                        required 
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                </div>
            </div>

            {{-- Ficha informativa de la OC seleccionada --}}
            <template x-if="ordenCompraInfo">
                <div class="rounded-lg border border-blue-100 bg-blue-50/60 p-4 transition">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">
                        <div>
                            <span class="text-xs uppercase font-semibold text-blue-800 tracking-wider">Proveedor</span>
                            <div class="font-medium text-gray-900" x-text="ordenCompraInfo.proveedor"></div>
                            <div class="text-xs text-gray-500" x-text="ordenCompraInfo.proveedor_rfc ? 'RFC: ' + ordenCompraInfo.proveedor_rfc : ''"></div>
                        </div>
                        <div class="sm:text-right">
                            <span class="text-xs uppercase font-semibold text-blue-800 tracking-wider">Orden Compra</span>
                            <div class="font-semibold text-blue-900" x-text="ordenCompraInfo.folio"></div>
                            <div class="text-xs text-gray-600" x-text="'Fecha OC: ' + ordenCompraInfo.fecha"></div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Observaciones generales --}}
            <div>
                <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">
                    Observaciones generales de la entrada (opcional)
                </label>
                <textarea 
                    name="observaciones" 
                    id="observaciones" 
                    rows="2" 
                    placeholder="Ej. Entregado por paquetería / chofer de proveedor, factura o remisión adjunta..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >{{ old('observaciones') }}</textarea>
            </div>
        </div>

        {{-- Panel Inferior: Partidas de la Orden de Compra --}}
        <div class="mt-6 bg-white border rounded-lg overflow-hidden shadow-sm">
            <div class="p-4 border-b bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">2. Productos y Cantidades a Recibir</h2>
                    <p class="text-xs text-gray-500">Ajusta la cantidad recibida si se trata de una entrega parcial.</p>
                </div>
                <template x-if="partidas.length > 0">
                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="seleccionarTodas(true)" 
                            class="text-xs font-medium text-blue-600 hover:text-blue-800"
                        >
                            Seleccionar todas
                        </button>
                        <span class="text-gray-300">|</span>
                        <button 
                            type="button" 
                            @click="seleccionarTodas(false)" 
                            class="text-xs font-medium text-gray-600 hover:text-gray-800"
                        >
                            Deseleccionar todas
                        </button>
                    </div>
                </template>
            </div>

            {{-- Estado de carga --}}
            <div x-show="cargando" class="py-12 text-center text-gray-500" x-cloak>
                <svg class="animate-spin h-6 w-6 text-blue-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm">Cargando partidas pendientes de la orden de compra...</p>
            </div>

            {{-- Mensaje inicial cuando no hay OC seleccionada --}}
            <div x-show="!cargando && !ordenCompraId" class="py-12 text-center text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <p class="text-sm font-medium text-gray-600">Ninguna orden de compra seleccionada</p>
                <p class="text-xs text-gray-400 mt-1">Elige una orden de compra autorizada en el selector de arriba para desplegar sus partidas pendientes.</p>
            </div>

            {{-- Mensaje cuando la OC no tiene partidas pendientes --}}
            <div x-show="!cargando && ordenCompraId && partidas.length === 0" class="py-12 text-center text-gray-500" x-cloak>
                <p class="text-sm font-medium text-amber-700">Esta orden de compra no tiene productos pendientes de recibir.</p>
            </div>

            {{-- Tabla de Partidas --}}
            <div x-show="!cargando && partidas.length > 0" class="overflow-x-auto" x-cloak>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-center w-12 font-semibold">
                                <input 
                                    type="checkbox" 
                                    :checked="todasSeleccionadas" 
                                    @change="seleccionarTodas($event.target.checked)" 
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                            </th>
                            <th class="px-4 py-3 text-left font-semibold">Producto / Descripción</th>
                            <th class="px-4 py-3 text-center font-semibold">Unidad</th>
                            <th class="px-4 py-3 text-right font-semibold">Comprado</th>
                            <th class="px-4 py-3 text-right font-semibold">Ya Recibido</th>
                            <th class="px-4 py-3 text-right font-semibold text-blue-900 bg-blue-50/50">Pendiente</th>
                            <th class="px-4 py-3 text-right font-semibold w-40">Cant. a Recibir</th>
                            <th class="px-4 py-3 text-right font-semibold">Costo Unit.</th>
                            <th class="px-4 py-3 text-right font-semibold">Importe</th>
                            <th class="px-4 py-3 text-left font-semibold">Notas Partida</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-800">
                        <template x-for="(item, index) in partidas" :key="item.id">
                            <tr :class="item.seleccionado ? (item.excede_pendiente ? 'bg-red-50' : 'bg-white') : 'bg-gray-50 opacity-60'">
                                {{-- Checkbox --}}
                                <td class="px-4 py-3 text-center">
                                    <input 
                                        type="checkbox" 
                                        x-model="item.seleccionado" 
                                        @change="recalcularTotales()"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    >
                                    {{-- Inputs ocultos para envío del formulario --}}
                                    <input type="hidden" :name="'partidas[' + index + '][orden_compra_detalle_id]'" :value="item.id" :disabled="!item.seleccionado">
                                    <input type="hidden" :name="'partidas[' + index + '][producto_id]'" :value="item.producto_id" :disabled="!item.seleccionado">
                                    <input type="hidden" :name="'partidas[' + index + '][descripcion]'" :value="item.descripcion" :disabled="!item.seleccionado">
                                    <input type="hidden" :name="'partidas[' + index + '][unidad]'" :value="item.unidad" :disabled="!item.seleccionado">
                                    <input type="hidden" :name="'partidas[' + index + '][cantidad_ordenada]'" :value="item.cantidad_ordenada" :disabled="!item.seleccionado">
                                    <input type="hidden" :name="'partidas[' + index + '][costo_unitario]'" :value="item.costo_unitario" :disabled="!item.seleccionado">
                                </td>

                                {{-- Producto --}}
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900" x-text="item.producto_nombre"></div>
                                    <div class="text-xs text-gray-500" x-show="item.producto_sku" x-text="'SKU: ' + item.producto_sku"></div>
                                    <div class="text-xs text-gray-400 italic" x-show="item.descripcion && item.descripcion !== item.producto_nombre" x-text="item.descripcion"></div>
                                </td>

                                {{-- Unidad --}}
                                <td class="px-4 py-3 text-center text-xs font-semibold text-gray-600" x-text="item.unidad"></td>

                                {{-- Cantidad comprada --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap text-gray-600" x-text="formatearDecimal(item.cantidad_ordenada, 3)"></td>

                                {{-- Cantidad ya recibida --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap text-gray-600" x-text="formatearDecimal(item.cantidad_recibida_previa, 3)"></td>

                                {{-- Cantidad pendiente --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-blue-800 bg-blue-50/50" x-text="formatearDecimal(item.cantidad_pendiente, 3)"></td>

                                {{-- Cantidad a recibir (Editable) --}}
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <input 
                                        type="number" 
                                        :name="'partidas[' + index + '][cantidad_recibida]'" 
                                        step="0.001" 
                                        min="0.001" 
                                        :max="item.cantidad_pendiente"
                                        x-model.number="item.cantidad_a_recibir" 
                                        @input="validarCantidad(item); recalcularTotales()"
                                        :disabled="!item.seleccionado"
                                        :class="item.excede_pendiente ? 'border-red-500 focus:ring-red-500 bg-red-50' : 'border-gray-300 focus:ring-blue-500'"
                                        class="w-32 text-right rounded-md px-2.5 py-1.5 text-sm font-semibold focus:outline-none focus:ring-1"
                                    >
                                    <div x-show="item.excede_pendiente" class="text-[11px] text-red-600 font-medium mt-0.5" x-cloak>
                                        Excede el saldo (<span x-text="formatearDecimal(item.cantidad_pendiente, 3)"></span>)
                                    </div>
                                </td>

                                {{-- Costo unitario --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap text-gray-600 text-xs" x-text="'$' + formatearDecimal(item.costo_unitario, 2)"></td>

                                {{-- Importe --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap font-medium text-gray-900" x-text="'$' + formatearDecimal(item.importe, 2)"></td>

                                {{-- Observaciones de partida --}}
                                <td class="px-4 py-3">
                                    <input 
                                        type="text" 
                                        :name="'partidas[' + index + '][observaciones]'" 
                                        x-model="item.observaciones" 
                                        :disabled="!item.seleccionado"
                                        placeholder="Nota / lote..." 
                                        class="w-full text-xs rounded border border-gray-300 px-2 py-1 focus:border-blue-500 focus:outline-none"
                                    >
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t font-semibold text-gray-900">
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-right text-xs uppercase tracking-wider text-gray-600">
                                Totales de la entrada:
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-blue-900" x-text="formatearDecimal(totalCantidad, 3)"></td>
                            <td></td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900" x-text="'$' + formatearDecimal(totalImporte, 2)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Pie con Botones de Acción --}}
            <div class="p-4 border-t bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="text-xs text-gray-500">
                    <span class="font-semibold text-gray-800" x-text="totalPartidasSeleccionadas"></span> partida(s) seleccionada(s) para recibir.
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('huentitan.entradas.index') }}" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-100 transition">
                        Cancelar
                    </a>
                    <button 
                        type="submit" 
                        :disabled="!puedeGuardar" 
                        :class="puedeGuardar ? 'bg-blue-600 hover:bg-blue-700 text-white cursor-pointer' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                        class="inline-flex items-center justify-center px-5 py-2 rounded-md text-sm font-semibold shadow-sm transition"
                    >
                        Guardar Entrada
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function nuevaEntrada(initialOcId = null) {
    return {
        ordenCompraId: initialOcId || '',
        ordenCompraInfo: null,
        partidas: [],
        cargando: false,
        totalPartidasSeleccionadas: 0,
        totalCantidad: 0,
        totalImporte: 0,
        hayExcesos: false,

        init() {
            if (this.ordenCompraId) {
                this.cargarDetalles();
            }
        },

        async cargarDetalles() {
            if (!this.ordenCompraId) {
                this.partidas = [];
                this.ordenCompraInfo = null;
                this.recalcularTotales();
                return;
            }

            this.cargando = true;
            try {
                const url = "{{ url('huentitan/entradas-oc-detalles') }}/" + this.ordenCompraId;
                const resp = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!resp.ok) {
                    throw new Error('Error al cargar partidas de la orden de compra');
                }

                const data = await resp.json();
                this.ordenCompraInfo = data.orden_compra;
                this.partidas = (data.detalles || []).map(d => ({
                    ...d,
                    seleccionado: true,
                    cantidad_a_recibir: Number(d.cantidad_pendiente),
                    excede_pendiente: false,
                    observaciones: '',
                    importe: Number(d.cantidad_pendiente) * Number(d.costo_unitario)
                }));

                this.recalcularTotales();
            } catch (err) {
                console.error(err);
                alert('No se pudieron obtener las partidas de la orden de compra seleccionada.');
            } finally {
                this.cargando = false;
            }
        },

        validarCantidad(item) {
            const pendiente = Number(item.cantidad_pendiente || 0);
            const aRecibir = Number(item.cantidad_a_recibir || 0);

            item.excede_pendiente = aRecibir > pendiente;
            item.importe = Math.max(0, aRecibir) * Number(item.costo_unitario || 0);
        },

        seleccionarTodas(valor) {
            this.partidas.forEach(item => {
                item.seleccionado = Boolean(valor);
            });
            this.recalcularTotales();
        },

        get todasSeleccionadas() {
            return this.partidas.length > 0 && this.partidas.every(i => i.seleccionado);
        },

        recalcularTotales() {
            let count = 0;
            let qty = 0;
            let totalImp = 0;
            let hasExceso = false;

            this.partidas.forEach(item => {
                if (item.seleccionado) {
                    count++;
                    const aRecibir = Number(item.cantidad_a_recibir || 0);
                    qty += aRecibir;
                    totalImp += (aRecibir * Number(item.costo_unitario || 0));
                    if (item.excede_pendiente || aRecibir <= 0) {
                        hasExceso = true;
                    }
                }
            });

            this.totalPartidasSeleccionadas = count;
            this.totalCantidad = qty;
            this.totalImporte = totalImp;
            this.hayExcesos = hasExceso;
        },

        get puedeGuardar() {
            return !this.cargando && 
                   this.totalPartidasSeleccionadas > 0 && 
                   this.totalCantidad > 0 && 
                   !this.hayExcesos;
        },

        validarFormulario(e) {
            this.recalcularTotales();

            if (!this.ordenCompraId) {
                e.preventDefault();
                alert('Debes seleccionar una orden de compra.');
                return;
            }

            if (this.totalPartidasSeleccionadas === 0) {
                e.preventDefault();
                alert('Debes seleccionar al menos una partida para recibir.');
                return;
            }

            if (this.hayExcesos) {
                e.preventDefault();
                alert('Existen partidas cuya cantidad a recibir supera el saldo pendiente o es menor o igual a cero. Corrige los valores antes de guardar.');
                return;
            }
        },

        formatearDecimal(val, dec = 2) {
            const num = Number(val || 0);
            return num.toLocaleString('es-MX', {
                minimumFractionDigits: dec,
                maximumFractionDigits: dec
            });
        }
    };
}
</script>
@endsection
