@extends('layouts.admin')

@section('title', 'Conceptos SAT')

@section('content')

<div x-data="catalogoConceptosSat()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-6">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Catálogo de Conceptos SAT
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                Conceptos reutilizables para facturación CFDI.
            </p>
        </div>

    <button type="button"
            @click="openCreate = true"
            class="inline-flex items-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
        + Nuevo Concepto
    </button>

    </div>

    {{-- TABLA --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-xs uppercase tracking-wide text-slate-500">

                        <th class="px-5 py-4 text-left">
                            Código
                        </th>

                        <th class="px-5 py-4 text-left">
                            Descripción
                        </th>

                        <th class="px-5 py-4 text-left">
                            Clave SAT
                        </th>

                        <th class="px-5 py-4 text-left">
                            Unidad
                        </th>

                        <th class="px-5 py-4 text-right">
                            Precio
                        </th>

                        <th class="px-5 py-4 text-center">
                            IVA
                        </th>

                        <th class="px-5 py-4 text-center">
                            Estado
                        </th>

                        <th class="px-5 py-4 text-right">
                            Acciones
                        </th>

                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse($conceptos as $concepto)

                        <tr class="hover:bg-slate-50">

                            <td class="px-5 py-4 font-medium text-slate-900">
                                {{ $concepto->codigo ?? '—' }}
                            </td>

                            <td class="px-5 py-4">
                                {{ $concepto->descripcion }}
                            </td>

                            <td class="px-5 py-4">
                                {{ $concepto->clave_producto_servicio }}
                            </td>

                            <td class="px-5 py-4">
                                {{ $concepto->clave_unidad }}
                            </td>

                            <td class="px-5 py-4 text-right">
                                ${{ number_format($concepto->precio_unitario, 2) }}
                            </td>

                            <td class="px-5 py-4 text-center">
                                {{ $concepto->iva_porcentaje }}%
                            </td>

                            <td class="px-5 py-4 text-center">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                                    {{ $concepto->activo
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : 'bg-red-50 text-red-700 border border-red-200' }}">

                                    {{ $concepto->activo ? 'Activo' : 'Inactivo' }}

                                </span>

                            </td>

                            <td class="px-5 py-4 text-right">

                                <button type="button"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                    Editar
                                </button>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8"
                                class="px-5 py-10 text-center text-slate-500">
                                Aún no hay conceptos registrados.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if($conceptos->hasPages())
            <div class="px-5 py-4 border-t border-slate-200">
                {{ $conceptos->links() }}
            </div>
        @endif

    </div>
{{-- MODAL NUEVO CONCEPTO --}}
<div x-show="openCreate"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">

    <div @click.away="openCreate = false"
         class="w-full max-w-2xl rounded-2xl bg-white shadow-xl border border-slate-200">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Nuevo concepto SAT
                </h2>
                <p class="text-sm text-slate-500">
                    Captura un concepto reutilizable para facturación CFDI.
                </p>
            </div>

            <button type="button"
                    @click="openCreate = false"
                    class="text-slate-400 hover:text-slate-600">
                ✕
            </button>
        </div>

        <form method="POST" action="{{ route('sat.catalogos.conceptos.store') }}">
            @csrf

            <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Código interno
                    </label>
                    <input type="text"
                           name="codigo"
                           class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Ej. SERV-001">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Precio unitario
                    </label>
                    <input type="number"
                           step="0.01"
                           name="precio_unitario"
                           value="0"
                           class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Descripción
                    </label>
                    <input type="text"
                           name="descripcion"
                           required
                           class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Ej. Servicio de construcción">
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Clave producto/servicio SAT
                    </label>
                    <input type="hidden"
                           name="clave_producto_servicio"
                           :value="claveProductoServicio">
                    <input type="text"
                           x-model="claveProductoSearch"
                           @input.debounce.350ms="buscarProductosSat()"
                           @focus="claveProductoOpen = productosSat.length > 0"
                           @keydown.escape="claveProductoOpen = false"
                           required
                           class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Buscar por nombre o clave">

                    <div x-show="claveProductoOpen"
                         x-cloak
                         @click.away="claveProductoOpen = false"
                         class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div x-show="buscandoProductosSat" class="px-4 py-3 text-sm text-slate-500">
                            Buscando...
                        </div>

                        <template x-if="!buscandoProductosSat && productosSat.length === 0 && claveProductoSearch.length >= 2">
                            <div class="px-4 py-3 text-sm text-slate-500">
                                Sin resultados
                            </div>
                        </template>

                        <template x-for="producto in productosSat" :key="producto.key">
                            <button type="button"
                                    @click="seleccionarProductoSat(producto)"
                                    class="block w-full px-4 py-3 text-left hover:bg-slate-50">
                                <span class="block text-sm font-semibold text-slate-900" x-text="producto.key"></span>
                                <span class="block text-xs text-slate-500" x-text="producto.description"></span>
                            </button>
                        </template>
                    </div>

                    <p x-show="errorProductosSat"
                       x-text="errorProductosSat"
                       class="mt-1 text-xs text-red-600"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Clave unidad SAT
                    </label>
                    <input type="text"
                           name="clave_unidad"
                           required
                           class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Ej. E48">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Unidad visible
                    </label>
                    <input type="text"
                           name="unidad"
                           class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Ej. Servicio">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Objeto de impuesto
                    </label>
                    <select name="objeto_impuesto"
                            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="02">02 - Sí objeto de impuesto</option>
                        <option value="01">01 - No objeto de impuesto</option>
                        <option value="03">03 - Sí objeto, no obligado al desglose</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        IVA tasa
                    </label>
                    <select name="iva_tasa"
                            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="0.160000">16%</option>
                        <option value="0.080000">8%</option>
                        <option value="0.000000">0%</option>
                    </select>
                </div>

                <div class="flex items-center gap-3 pt-6">
                    <input type="checkbox"
                           name="incluye_iva"
                           value="1"
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">

                    <span class="text-sm text-slate-700">
                        El precio ya incluye IVA
                    </span>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Observaciones
                    </label>
                    <textarea name="observaciones"
                              rows="3"
                              class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

            </div>

            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl">
                <button type="button"
                        @click="openCreate = false"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-white">
                    Cancelar
                </button>

                <button type="submit"
                        class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Guardar concepto
                </button>
            </div>

        </form>

    </div>
</div>
</div>

<script>
function catalogoConceptosSat() {
    return {
        openCreate: false,
        claveProductoSearch: '',
        claveProductoServicio: '',
        claveProductoOpen: false,
        buscandoProductosSat: false,
        productosSat: [],
        errorProductosSat: '',

        async buscarProductosSat() {
            const query = this.claveProductoSearch.trim();

            this.errorProductosSat = '';
            this.claveProductoServicio = /^\d+$/.test(query) ? query : '';

            if (query.length < 2) {
                this.productosSat = [];
                this.claveProductoOpen = false;
                return;
            }

            this.buscandoProductosSat = true;
            this.claveProductoOpen = true;

            try {
                const url = new URL(@json(route('sat.catalogos.productos-sat.buscar')));
                url.searchParams.set('q', query);

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const body = await response.json();

                if (!response.ok) {
                    throw new Error(body.message || 'No se pudo consultar el catalogo SAT.');
                }

                this.productosSat = body.data || [];
            } catch (error) {
                this.productosSat = [];
                this.errorProductosSat = error.message || 'No se pudo consultar el catalogo SAT.';
            } finally {
                this.buscandoProductosSat = false;
            }
        },

        seleccionarProductoSat(producto) {
            this.claveProductoServicio = producto.key;
            this.claveProductoSearch = producto.key;
            this.productosSat = [];
            this.claveProductoOpen = false;
            this.errorProductosSat = '';
        },
    };
}
</script>

@endsection
