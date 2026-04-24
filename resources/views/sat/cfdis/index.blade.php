@extends('layouts.admin')

@section('title', 'CFDIs descargados')

@section('content')
<div x-data="cfdiModal()" x-cloak>
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Visor SAT</h1>
        <p class="text-sm text-gray-600 mt-1">
            CFDIs descargados y almacenados en la tabla <span class="font-medium">sat_cfdis</span>.
        </p>
    </div>

    {{-- Selector de empresa --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <form method="GET" action="{{ route('sat.cfdis.index') }}" id="empresaFilterForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Empresa SAT</label>
                <select name="sat_empresa_id"
                        id="sat_empresa_id"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        onchange="document.getElementById('empresaFilterForm').submit()">
                    <option value="">Selecciona una empresa para cargar CFDIs</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" @selected(request('sat_empresa_id') == $empresa->id)>
                            {{ $empresa->nombre }} — {{ $empresa->rfc }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($empresaSeleccionada)
                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                    <span class="font-medium text-gray-900">Empresa seleccionada:</span>
                    <span>{{ $empresaSeleccionada->nombre }}</span>
                    <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                        {{ $empresaSeleccionada->rfc }}
                    </span>
                </div>
            @endif
        </form>
    </div>

    @if(!$empresaSeleccionada)
        <div class="bg-white rounded-2xl border border-dashed border-gray-300 shadow-sm p-10 text-center">
            <div class="text-lg font-semibold text-gray-900">Selecciona una empresa SAT</div>
            <p class="text-sm text-gray-500 mt-2">
                Primero elige una empresa para cargar el resumen y el detalle de CFDIs.
            </p>
        </div>
    @else

        {{-- Filtros adicionales --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
            <form method="GET" action="{{ route('sat.cfdis.index') }}" class="flex flex-col gap-4">
                <input type="hidden" name="sat_empresa_id" value="{{ $empresaSeleccionada->id }}">

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">UUID</label>
                        <input type="text"
                               name="uuid"
                               value="{{ request('uuid') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Buscar UUID">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">RFC emisor</label>
                        <input type="text"
                               name="rfc_emisor"
                               value="{{ request('rfc_emisor') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="RFC emisor">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">RFC receptor</label>
                        <input type="text"
                               name="rfc_receptor"
                               value="{{ request('rfc_receptor') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="RFC receptor">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
                        <select name="tipo_comprobante"
                                class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="I" @selected(request('tipo_comprobante') === 'I')>Ingresos</option>
                            <option value="E" @selected(request('tipo_comprobante') === 'E')>Egresos</option>
                            <option value="P" @selected(request('tipo_comprobante') === 'P')>Pagos</option>
                            <option value="N" @selected(request('tipo_comprobante') === 'N')>Nóminas</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Fecha inicio</label>
                        <input type="date"
                               name="fecha_inicio"
                               value="{{ request('fecha_inicio') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Fecha fin</label>
                        <input type="date"
                               name="fecha_fin"
                               value="{{ request('fecha_fin') }}"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Filtrar
                        </button>

                        <a href="{{ route('sat.cfdis.index', ['sat_empresa_id' => $empresaSeleccionada->id]) }}"
                           class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Limpiar
                        </a>
                    </div>

                    <div class="text-sm text-gray-500">
                        Total de la empresa:
                        <span class="font-semibold text-gray-900">{{ number_format($totalGeneral) }}</span>
                    </div>
                </div>
            </form>
        </div>

        {{-- Resúmenes --}}
        <!-- <div class="grid grid-cols-1 lg:grid-cols-3 gap-4"> -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            <div class="rounded-2xl border border-indigo-300 bg-indigo-50 shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Ingresos</div>
                        <div class="text-sm text-gray-500 mt-1">CFDIs tipo I</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600 text-lg">
                        ⬈
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Registros</span>
                        <span class="font-semibold text-gray-900">{{ number_format($resumenIngresos) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-900">${{ number_format((float) $subtotalIngresos, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Egresos</div>
                        <div class="text-sm text-gray-500 mt-1">CFDIs tipo E</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-600 text-lg">
                        ⬋
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Registros</span>
                        <span class="font-semibold text-gray-900">{{ number_format($resumenEgresos) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-900">${{ number_format((float) $subtotalEgresos, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Pagos</div>
                        <div class="text-sm text-gray-500 mt-1">CFDIs tipo P</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-600 text-lg">
                        💳
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Registros</span>
                        <span class="font-semibold text-gray-900">{{ number_format($resumenPagos) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-900">${{ number_format((float) $subtotalPagos, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-amber-300 bg-amber-50 shadow-sm p-5">
    <div class="flex items-start justify-between">
        <div>
            <div class="text-lg font-semibold text-gray-900">Nóminas</div>
            <div class="text-sm text-gray-500 mt-1">CFDIs tipo N</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 text-lg">
            👥
        </div>
    </div>

    <div class="mt-5 space-y-3">
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Registros</span>
            <span class="font-semibold text-gray-900">{{ number_format($resumenNominas) }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Subtotal</span>
            <span class="font-semibold text-gray-900">${{ number_format((float) $subtotalNominas, 2) }}</span>
        </div>
    </div>
</div>
        </div>

        {{-- Encabezado de detalle --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">Detalle de CFDIs descargados</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Total de registros de la empresa antes de filtros:
                    <span class="font-semibold text-gray-900">{{ number_format($totalGeneral) }}</span>
                </p>
            </div>

            <div class="text-sm text-gray-600">
                Resultado actual:
                <span class="font-semibold text-gray-900">{{ number_format($totalFiltrado) }}</span>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">UUID</th>
                            <th class="px-4 py-3 text-left font-medium">Fecha de expedición</th>
                            <th class="px-4 py-3 text-left font-medium">Tipo</th>
                            <th class="px-4 py-3 text-left font-medium">RFC emisor</th>
                            <th class="px-4 py-3 text-left font-medium">RFC receptor</th>
                            <th class="px-4 py-3 text-left font-medium">Moneda</th>
                            <th class="px-4 py-3 text-left font-medium">Total MXN</th>
                            <th class="px-4 py-3 text-left font-medium">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($cfdis as $cfdi)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <span class="inline-flex max-w-[260px] truncate rounded-lg border border-indigo-300 bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700"
                                          title="{{ $cfdi->uuid }}">
                                        {{ $cfdi->uuid }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ optional($cfdi->fecha_emision)->format('d/m/Y H:i') }}
                                </td>

                                <td class="px-4 py-3">
                                    @if($cfdi->tipo_comprobante === 'I')
                                        <span class="inline-flex rounded-lg bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 border border-green-200">
                                            Ingreso
                                        </span>
                                    @elseif($cfdi->tipo_comprobante === 'E')
                                        <span class="inline-flex rounded-lg bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 border border-red-200">
                                            Egreso
                                        </span>
                                    @elseif($cfdi->tipo_comprobante === 'P')
                                        <span class="inline-flex rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 border border-blue-200">
                                            Pago
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-lg bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                                            {{ $cfdi->tipo_comprobante }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $cfdi->rfc_emisor }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $cfdi->rfc_receptor }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $cfdi->moneda }}
                                </td>

                                <td class="px-4 py-3 font-medium text-gray-900">
                                    ${{ number_format((float) $cfdi->total, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    <button type="button"
                                        class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 border border-blue-200 hover:bg-blue-100"
                                        @click="openCfdiModal('{{ route('sat.cfdis.detalle', $cfdi->id) }}')">
                                        Ver
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                    No hay CFDIs registrados para esta empresa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($cfdis instanceof \Illuminate\Contracts\Pagination\Paginator)
                <div class="px-4 py-4 border-t border-gray-200">
                    {{ $cfdis->links() }}
                </div>
            @endif
        </div>

    @endif

</div>
<!-- Modal CFDI -->
<!-- Modal CFDI -->
<div x-show="open"
     x-transition
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">

    <div class="bg-gray-50 w-full max-w-4xl rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 bg-blue-600 text-white">
            <h2 class="text-lg font-semibold text-white">
                Detalle CFDI
            </h2>

            <button @click="close()"
                    class="text-white/80 hover:text-white">
                ✕
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 max-h-[70vh] overflow-y-auto bg-white">

            <!-- Loader -->
            <div x-show="loading" class="text-center text-gray-500 py-10">
                Cargando...
            </div>

            <!-- Contenido -->
            <div x-show="!loading && data" class="space-y-5">

                <!-- Datos generales -->
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Datos generales
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="md:col-span-2">
                            <span class="text-gray-500">UUID</span>
                            <div class="font-medium break-all" x-text="data?.uuid || '-'"></div>
                        </div>

                        <div>
                            <span class="text-gray-500">Fecha</span>
                            <div class="font-medium" x-text="data?.fecha_emision || '-'"></div>
                        </div>

                        <div>
                            <span class="text-gray-500">Serie / Folio</span>
                            <div class="font-medium">
                                <span x-text="data?.serie || '-'"></span>
                                <span x-text="data?.folio || ''"></span>
                            </div>
                        </div>

                        <div>
                            <span class="text-gray-500">Tipo CFDI</span>
                            <div class="font-medium" x-text="data?.tipo_comprobante || '-'"></div>
                        </div>

                        <div>
                            <span class="text-gray-500">Total</span>
                            <div class="font-semibold text-green-700" x-text="data?.total || '-'"></div>
                        </div>
                    </div>
                </div>

                <!-- Pago -->
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Pago y expedición
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Forma de pago</span>
                            <div class="font-medium" x-text="data?.forma_pago || '-'"></div>
                        </div>

                        <div>
                            <span class="text-gray-500">Método de pago</span>
                            <div class="font-medium" x-text="data?.metodo_pago || '-'"></div>
                        </div>

                        <div>
                            <span class="text-gray-500">Lugar expedición</span>
                            <div class="font-medium" x-text="data?.lugar_expedicion || '-'"></div>
                        </div>
                    </div>
                </div>

                <!-- Emisor / Receptor -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Emisor -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">
                            Emisor
                        </h3>

                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-gray-500">RFC</span>
                                <div class="font-medium" x-text="data?.rfc_emisor || data?.emisor_rfc || '-'"></div>
                            </div>

                            <div>
                                <span class="text-gray-500">Nombre</span>
                                <div class="font-medium" x-text="data?.emisor_nombre || '-'"></div>
                            </div>

                            <div>
                                <span class="text-gray-500">Régimen fiscal</span>
                                <div class="font-medium" x-text="data?.emisor_regimen_fiscal || '-'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Receptor -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">
                            Receptor
                        </h3>

                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-gray-500">RFC</span>
                                <div class="font-medium" x-text="data?.rfc_receptor || data?.receptor_rfc || '-'"></div>
                            </div>

                            <div>
                                <span class="text-gray-500">Nombre</span>
                                <div class="font-medium" x-text="data?.receptor_nombre || '-'"></div>
                            </div>

                            <div>
                                <span class="text-gray-500">Uso CFDI</span>
                                <div class="font-medium" x-text="data?.receptor_uso_cfdi || '-'"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Conceptos -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Conceptos
                    </h3>

                    <div class="overflow-x-auto border rounded-xl">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left">Clave</th>
                                    <th class="px-3 py-2 text-left">Descripción</th>
                                    <th class="px-3 py-2 text-left">Cantidad</th>
                                    <th class="px-3 py-2 text-left">Unidad</th>
                                    <th class="px-3 py-2 text-left">Precio</th>
                                    <th class="px-3 py-2 text-left">Importe</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-for="c in (data?.conceptos || [])" :key="c.id">
                                    <tr class="border-t">
                                        <td class="px-3 py-2" x-text="c.clave_prod_serv"></td>
                                        <td class="px-3 py-2" x-text="c.descripcion"></td>
                                        <td class="px-3 py-2" x-text="c.cantidad"></td>
                                        <td class="px-3 py-2" x-text="c.unidad"></td>
                                        <td class="px-3 py-2" x-text="c.valor_unitario"></td>
                                        <td class="px-3 py-2" x-text="c.importe"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-3 border-t text-right">
            <button @click="close()"
                    class="px-4 py-2 text-sm rounded-lg border hover:bg-gray-50">
                Cerrar
            </button>
        </div>

    </div>
</div>
<script>
function cfdiModal() {
    return {
        open: false,
        loading: false,
        data: null,

        async openCfdiModal(url) {
            this.open = true;
            this.loading = true;
            this.data = null;

            try {
                const res = await fetch(url);
                const json = await res.json();

                this.data = json.cfdi;
            } catch (e) {
                console.error(e);
            }

            this.loading = false;
        },

        close() {
            this.open = false;
            this.data = null;
        }
    }
}
</script>
</div>

@endsection

