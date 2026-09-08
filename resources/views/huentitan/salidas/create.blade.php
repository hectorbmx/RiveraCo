@extends('layouts.admin')

@section('title', 'Nueva salida - HUENTITAN')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6"
    x-data="huentitanSalidaCreate({ productos: @js($productos), oldDetalles: @js(old('detalles', [])) })">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <a href="{{ route('huentitan.index') }}" class="hover:underline">HUENTITAN</a>
                <span>/</span>
                <a href="{{ route('huentitan.salidas.index') }}" class="hover:underline">Salidas</a>
            </div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Nueva salida a obra</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $almacen->nombre }}</p>
        </div>
        <a href="{{ route('huentitan.salidas.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Regresar</a>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
            <div class="font-semibold">Revisa la informacion capturada.</div>
            <ul class="mt-2 list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('huentitan.salidas.store') }}" class="space-y-6" @submit="validarAntesDeEnviar($event)">
        @csrf

        <div class="bg-white border rounded-lg overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900">Datos base</h2>
            </div>
            <div class="p-4 grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div class="lg:col-span-5">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Obra destino</label>
                    <select name="obra_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" required>
                        <option value="">Selecciona obra</option>
                        @foreach($obras as $obra)
                            <option value="{{ $obra->id }}" @selected((string) old('obra_id') === (string) $obra->id)>
                                {{ $obra->clave_obra ? $obra->clave_obra . ' - ' : '' }}{{ $obra->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ old('fecha', now()->format('Y-m-d')) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" required>
                </div>
                <div class="lg:col-span-5">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Observaciones</label>
                    <input type="text" name="observaciones" value="{{ old('observaciones') }}" placeholder="Notas generales de la entrega..." class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">
                <div class="lg:col-span-6 relative">
                    <label class="block text-xs font-medium text-blue-900 mb-1">Buscar producto HUENTITAN</label>
                    <input type="text" x-model="busqueda" @focus="buscando = true" @keydown.escape="buscando = false" placeholder="Codigo o nombre del producto..." class="w-full rounded-md border border-blue-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
                    <div x-show="buscando && busqueda.length > 0" @click.outside="buscando = false" class="absolute z-20 mt-1 w-full bg-white border border-blue-100 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                        <template x-for="producto in productosFiltrados" :key="producto.id">
                            <button type="button" @click="agregarProducto(producto)" class="w-full text-left px-3 py-2 hover:bg-blue-50 border-b last:border-b-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900" x-text="producto.nombre"></div>
                                        <div class="text-xs text-gray-500"><span x-text="producto.sku"></span> · <span x-text="producto.unidad"></span> · <span x-text="tipoLabel(producto.tipo_inventario)"></span></div>
                                    </div>
                                    <div class="text-right text-xs whitespace-nowrap">
                                        <div class="font-semibold" :class="producto.stock_disponible > 0 ? 'text-green-700' : 'text-red-600'" x-text="numero(producto.stock_disponible, 3)"></div>
                                        <div class="text-gray-400">disp.</div>
                                    </div>
                                </div>
                            </button>
                        </template>
                        <div x-show="productosFiltrados.length === 0" class="px-3 py-4 text-sm text-gray-500 text-center">Sin coincidencias</div>
                    </div>
                </div>
                <div class="lg:col-span-3">
                    <div class="text-xs font-medium text-blue-900 mb-1">Productos disponibles</div>
                    <div class="rounded-md bg-white border border-blue-100 px-3 py-2 text-sm text-gray-700">
                        <span class="font-semibold text-gray-900" x-text="productos.length"></span> en catalogo HUENTITAN
                    </div>
                </div>
                <div class="lg:col-span-3">
                    <div class="text-xs font-medium text-blue-900 mb-1">Partidas agregadas</div>
                    <div class="rounded-md bg-white border border-blue-100 px-3 py-2 text-sm text-gray-700">
                        <span class="font-semibold text-gray-900" x-text="detalles.length"></span> partidas
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-lg overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Materiales a entregar</h2>
                    <p class="text-xs text-gray-500">El stock solo se descontara cuando la salida se aplique al inventario.</p>
                </div>
                <div class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="tieneFaltantes ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'" x-text="tieneFaltantes ? 'Con faltantes' : 'Stock suficiente'"></div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Producto</th>
                            <th class="px-4 py-3 text-left font-semibold">Tipo</th>
                            <th class="px-4 py-3 text-right font-semibold">Disponible</th>
                            <th class="px-4 py-3 text-right font-semibold">Cantidad</th>
                            <th class="px-4 py-3 text-left font-semibold">Unidad</th>
                            <th class="px-4 py-3 text-right font-semibold">Costo prom.</th>
                            <th class="px-4 py-3 text-right font-semibold">Importe</th>
                            <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-800">
                        <template x-for="(detalle, index) in detalles" :key="detalle.producto_id">
                            <tr :class="detalle.cantidad > detalle.stock_disponible ? 'bg-red-50' : ''">
                                <td class="px-4 py-3 min-w-[280px]">
                                    <input type="hidden" :name="`detalles[${index}][producto_id]`" :value="detalle.producto_id">
                                    <input type="hidden" :name="`detalles[${index}][cantidad]`" :value="detalle.cantidad">
                                    <input type="hidden" :name="`detalles[${index}][unidad]`" :value="detalle.unidad">
                                    <div class="font-semibold text-gray-900" x-text="detalle.nombre"></div>
                                    <div class="text-xs text-gray-500" x-text="detalle.sku"></div>
                                    <div x-show="detalle.cantidad > detalle.stock_disponible" class="mt-1 text-xs font-medium text-red-600">La cantidad excede el disponible.</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="tipoLabel(detalle.tipo_inventario)"></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="font-semibold" :class="detalle.stock_disponible > 0 ? 'text-green-700' : 'text-red-600'" x-text="numero(detalle.stock_disponible, 3)"></div>
                                    <div class="text-xs text-gray-400">Actual: <span x-text="numero(detalle.stock_actual, 3)"></span></div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <input type="number" min="0.001" step="0.001" x-model.number="detalle.cantidad" class="w-28 rounded-md border border-gray-300 px-3 py-2 text-sm text-right focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="detalle.unidad"></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap" x-text="moneda(detalle.costo_unitario)"></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap font-semibold" x-text="moneda(detalle.cantidad * detalle.costo_unitario)"></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button type="button" @click="quitarProducto(index)" class="text-xs font-semibold text-red-600 hover:text-red-700 hover:underline">Quitar</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="detalles.length === 0">
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-500">
                                Busca un producto HUENTITAN para agregarlo a la salida.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="text-xs text-gray-500">
                    Total estimado: <span class="font-semibold text-gray-900" x-text="moneda(totalEstimado)"></span>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('huentitan.salidas.index') }}" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-100 transition">Cancelar</a>
                    <button type="submit" class="px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 transition">
                        Guardar borrador
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function huentitanSalidaCreate(config) {
    return {
        productos: config.productos || [],
        detalles: [],
        busqueda: '',
        buscando: false,
        init() {
            (config.oldDetalles || []).forEach((detalle) => {
                const producto = this.productos.find((item) => String(item.id) === String(detalle.producto_id));
                if (producto) {
                    this.agregarProducto(producto, parseFloat(detalle.cantidad || 0));
                }
            });
        },
        get productosFiltrados() {
            const texto = this.busqueda.trim().toLowerCase();
            if (!texto) return [];

            return this.productos
                .filter((producto) => !this.detalles.some((detalle) => detalle.producto_id === producto.id))
                .filter((producto) => {
                    return producto.nombre.toLowerCase().includes(texto)
                        || producto.sku.toLowerCase().includes(texto);
                })
                .slice(0, 20);
        },
        get tieneFaltantes() {
            return this.detalles.some((detalle) => Number(detalle.cantidad || 0) > Number(detalle.stock_disponible || 0));
        },
        get totalEstimado() {
            return this.detalles.reduce((total, detalle) => total + (Number(detalle.cantidad || 0) * Number(detalle.costo_unitario || 0)), 0);
        },
        agregarProducto(producto, cantidad = 1) {
            if (this.detalles.some((detalle) => detalle.producto_id === producto.id)) {
                this.busqueda = '';
                this.buscando = false;
                return;
            }

            this.detalles.push({
                producto_id: producto.id,
                sku: producto.sku,
                nombre: producto.nombre,
                unidad: producto.unidad || 'PZA',
                tipo_inventario: producto.tipo_inventario || 'sin_clasificar',
                stock_actual: Number(producto.stock_actual || 0),
                stock_reservado: Number(producto.stock_reservado || 0),
                stock_disponible: Number(producto.stock_disponible || 0),
                costo_unitario: Number(producto.costo_promedio || 0),
                cantidad: cantidad > 0 ? cantidad : 1,
            });

            this.busqueda = '';
            this.buscando = false;
        },
        quitarProducto(index) {
            this.detalles.splice(index, 1);
        },
        validarAntesDeEnviar(event) {
            if (this.detalles.length === 0) {
                event.preventDefault();
                alert('Agrega al menos un producto a la salida.');
                return;
            }

            const cantidadInvalida = this.detalles.some((detalle) => Number(detalle.cantidad || 0) <= 0);
            if (cantidadInvalida) {
                event.preventDefault();
                alert('Todas las cantidades deben ser mayores a cero.');
                return;
            }
        },
        tipoLabel(tipo) {
            const labels = {
                materia_prima: 'Materia prima',
                producto_terminado: 'Producto terminado',
                subensamble: 'Subensamble',
                sin_clasificar: 'Sin clasificar',
            };

            return labels[tipo] || String(tipo || '-').replaceAll('_', ' ');
        },
        numero(valor, decimales = 2) {
            return new Intl.NumberFormat('es-MX', {
                minimumFractionDigits: decimales,
                maximumFractionDigits: decimales,
            }).format(Number(valor || 0));
        },
        moneda(valor) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN',
            }).format(Number(valor || 0));
        },
    };
}
</script>
@endsection
