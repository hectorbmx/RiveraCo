@extends('layouts.admin')

@section('title', 'Nueva Factura')

@section('content')
<div x-data="facturaForm()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- ALERTAS --}}
@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 whitespace-pre-line">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
        <div class="font-semibold mb-2">
            Errores de validación:
        </div>

        <ul class="list-disc pl-5 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-6">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Nueva Factura CFDI
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                Generación de factura electrónica SAT.
            </p>
        </div>

        <a href="{{ route('sat.facturacion.index') }}"
           class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Volver
        </a>

    </div>

    <form method="POST"
          action="{{ route('sat.facturacion.store') }}"  @submit="loadingTimbrar = true">

        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            {{-- COLUMNA IZQUIERDA --}}
            <div class="xl:col-span-2 space-y-6">

                {{-- DATOS CFDI --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

                    <h2 class="text-lg font-semibold text-slate-900 mb-5">
                        Datos CFDI
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- EMPRESA --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Empresa emisora
                            </label>

                            <select name="sat_empresa_id"
                                    class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">

                                <option value="">
                                    Seleccionar empresa
                                </option>

                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}">
                                        {{ $empresa->nombre }} — {{ $empresa->rfc }}
                                    </option>
                                @endforeach

                            </select>
                        </div>
                        {{-- CLIENTE --}}
                        <div class="relative" @click.outside="clienteOpen = false">
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Cliente
                            </label>

                            <input type="hidden" name="cliente_id" x-model="clienteId">
                            <input type="text"
                                   x-model="clienteSearch"
                                   @focus="clienteOpen = true"
                                   @input="clienteId = ''; clienteOpen = true"
                                   placeholder="Buscar por razon social, nombre o RFC"
                                   autocomplete="off"
                                   class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">

                            <div x-show="clienteOpen"
                                 x-cloak
                                 class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                                <template x-for="cliente in clientesFiltrados()" :key="cliente.id">
                                    <button type="button"
                                            @click="selectCliente(cliente)"
                                            class="block w-full px-4 py-3 text-left text-sm hover:bg-slate-50">
                                        <div class="font-medium text-slate-900" x-text="cliente.nombre"></div>
                                        <div class="text-xs text-slate-500" x-text="cliente.rfc"></div>
                                    </button>
                                </template>
                                <div x-show="clientesFiltrados().length === 0" class="px-4 py-3 text-sm text-slate-500">
                                    Sin coincidencias
                                </div>
                            </div>
                        </div>

                        {{-- OBRA --}}
                        <div class="relative" @click.outside="obraOpen = false">
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Obra (opcional)
                            </label>

                            <input type="hidden" name="obra_id" x-model="obraId">
                            <input type="text"
                                   x-model="obraSearch"
                                   @focus="obraOpen = true"
                                   @input="obraId = ''; obraOpen = true"
                                   placeholder="Buscar obra"
                                   autocomplete="off"
                                   class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">

                            <div class="mt-1 flex justify-end">
                                <button type="button"
                                        @click="clearObra()"
                                        class="text-xs font-medium text-slate-500 hover:text-slate-700">
                                    Sin obra
                                </button>
                            </div>

                            <div x-show="obraOpen"
                                 x-cloak
                                 class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                                <template x-for="obra in obrasFiltradas()" :key="obra.id">
                                    <button type="button"
                                            @click="selectObra(obra)"
                                            class="block w-full px-4 py-3 text-left text-sm hover:bg-slate-50">
                                        <div class="font-medium text-slate-900" x-text="obra.nombre"></div>
                                    </button>
                                </template>
                                <div x-show="obrasFiltradas().length === 0" class="px-4 py-3 text-sm text-slate-500">
                                    Sin coincidencias
                                </div>
                            </div>
                        </div>
    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- USO CFDI --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Uso CFDI
                </label>

                <select name="uso_cfdi"
                        class="w-full rounded-xl border-slate-300">

                    <option value="G01">G01 - Adquisición de mercancías</option>
                    <option value="G02">G02 - Devoluciones, descuentos o bonificaciones</option>
                    <option value="G03" selected>G03 - Gastos en general</option>

                    <option value="I01">I01 - Construcciones</option>
                    <option value="I02">I02 - Mobiliario y equipo de oficina por inversiones</option>
                    <option value="I03">I03 - Equipo de transporte</option>
                    <option value="I04">I04 - Equipo de cómputo y accesorios</option>
                    <option value="I05">I05 - Dados, troqueles, moldes, matrices y herramental</option>
                    <option value="I06">I06 - Comunicaciones telefónicas</option>
                    <option value="I07">I07 - Comunicaciones satelitales</option>
                    <option value="I08">I08 - Otra maquinaria y equipo</option>

                    <option value="D01">D01 - Honorarios médicos, dentales y gastos hospitalarios</option>
                    <option value="D02">D02 - Gastos médicos por incapacidad o discapacidad</option>
                    <option value="D03">D03 - Gastos funerales</option>
                    <option value="D04">D04 - Donativos</option>
                    <option value="D05">D05 - Intereses reales efectivamente pagados por créditos hipotecarios</option>
                    <option value="D06">D06 - Aportaciones voluntarias al SAR</option>
                    <option value="D07">D07 - Primas por seguros de gastos médicos</option>
                    <option value="D08">D08 - Gastos de transportación escolar obligatoria</option>
                    <option value="D09">D09 - Depósitos en cuentas para el ahorro / pensiones</option>
                    <option value="D10">D10 - Pagos por servicios educativos</option>

                    <option value="S01">S01 - Sin efectos fiscales</option>
                    <option value="CP01">CP01 - Pagos</option>
                    <option value="CN01">CN01 - Nómina</option>
                </select>
            </div>

            {{-- MÉTODO DE PAGO --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Método de pago
                </label>

                <select name="metodo_pago"
                        class="w-full rounded-xl border-slate-300">

                    <option value="PUE">PUE - Pago en una sola exhibición</option>
                    <option value="PPD">PPD - Pago en parcialidades</option>

                </select>
            </div>

            {{-- FORMA DE PAGO --}}
            <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Forma de pago
                    </label>

                    <select name="forma_pago"
                            class="w-full rounded-xl border-slate-300">

                        <option value="03">03 - Transferencia electrónica</option>
                        <option value="01">01 - Efectivo</option>
                        <option value="02">02 - Cheque nominativo</option>
                        <option value="04">04 - Tarjeta de crédito</option>
                        <option value="28">28 - Tarjeta de débito</option>
                        <option value="99">99 - Por definir</option>

                    </select>
                </div>
                
            </div>
              {{-- IVA GLOBAL --}}
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
            IVA
        </label>
        <select name="tipo_iva" class="w-full rounded-xl border-slate-300">
            <option value="0.16">IVA 16%</option>
            <option value="0.08">IVA 8% (Zona fronteriza)</option>
            <option value="0">IVA 0% (Tasa cero)</option>
            <option value="exento">Exento (sin traslado)</option>
            <option value="sin_iva">Sin IVA (no objeto)</option>
        </select>
        <p class="text-xs text-slate-400 mt-1">Se aplica a todos los conceptos</p>
    </div>
    

     </div>
                    {{-- Complemento Servicios Parciales de Construcción --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mt-6"
     x-data="{ usarComplementoConstruccion: false }">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-900">
                Complemento Servicios Parciales de Construcción
            </h3>
            <p class="text-sm text-slate-500">
                Úsalo solo cuando el cliente requiera este complemento en el XML.
            </p>
        </div>

        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox"
                   name="usar_complemento_construccion"
                   value="1"
                   x-model="usarComplementoConstruccion"
                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Agregar complemento
        </label>
    </div>

    <div x-show="usarComplementoConstruccion"
         x-cloak
         class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">

        <div class="md:col-span-3">
            <label class="block text-sm font-medium text-slate-700">
                Número de permiso, licencia o autorización
            </label>
            <input type="text"
                   name="complemento_construccion[num_per_lico_aut]"
                   value="{{ old('complemento_construccion.num_per_lico_aut') }}"
                   class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   placeholder="Ej. DEUR-1698/24">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Calle del inmueble</label>
            <input type="text" name="complemento_construccion[calle]"
                   value="{{ old('complemento_construccion.calle') }}"
                   class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Código postal</label>
            <input type="text" name="complemento_construccion[codigo_postal]"
                   value="{{ old('complemento_construccion.codigo_postal') }}"
                   maxlength="5"
                   class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">No. exterior</label>
            <input type="text" name="complemento_construccion[no_exterior]"
                   value="{{ old('complemento_construccion.no_exterior', '.') }}"
                   class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">No. interior</label>
            <input type="text" name="complemento_construccion[no_interior]"
                   value="{{ old('complemento_construccion.no_interior', '.') }}"
                   class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Colonia</label>
            <input type="text" name="complemento_construccion[colonia]"
                   value="{{ old('complemento_construccion.colonia', '.') }}"
                   class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Localidad</label>
            <input type="text" name="complemento_construccion[localidad]"
                   value="{{ old('complemento_construccion.localidad') }}"
                   class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Municipio</label>
            <input type="text" name="complemento_construccion[municipio]"
                   value="{{ old('complemento_construccion.municipio') }}"
                   class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Estado</label>
            <input type="text" name="complemento_construccion[estado]"
                   value="{{ old('complemento_construccion.estado') }}"
                   maxlength="2"
                   placeholder="Ej. 18"
                   class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div class="md:col-span-3">
            <label class="block text-sm font-medium text-slate-700">Referencia</label>
            <input type="text" name="complemento_construccion[referencia]"
                   value="{{ old('complemento_construccion.referencia', '.') }}"
                   class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
    </div>
</div>

                </div>
                

                {{-- CONCEPTOS --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

                    <div class="flex items-center justify-between mb-5">

                        <h2 class="text-lg font-semibold text-slate-900">
                            Conceptos
                        </h2>

                      <button type="button"
                            @click="openConceptos = true"
                            class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                        + Agregar concepto
                    </button>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="w-full text-sm">

                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 text-left">Descripción</th>
                                    <th class="px-4 py-3 text-right">Cant.</th>
                                    <th class="px-4 py-3 text-right">P.U.</th>
                                    <th class="px-4 py-3 text-right">IVA</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                    <th class="px-4 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
    <template x-if="conceptosSeleccionados.length === 0">
        <tr>
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                Aún no hay conceptos.
            </td>
        </tr>
    </template>

    <template x-for="(item, index) in conceptosSeleccionados" :key="index">
        <tr class="border-b border-slate-100">
            <td class="px-4 py-3">
                <input type="hidden" :name="`conceptos[${index}][sat_concepto_id]`" :value="item.id">
                <input type="hidden" :name="`conceptos[${index}][descripcion]`" :value="item.descripcion">
                <input type="hidden" :name="`conceptos[${index}][clave_producto_servicio]`" :value="item.clave_producto_servicio">
                <input type="hidden" :name="`conceptos[${index}][clave_unidad]`" :value="item.clave_unidad">
                <input type="hidden" :name="`conceptos[${index}][unidad]`" :value="item.unidad">
                <input type="hidden" :name="`conceptos[${index}][iva_tasa]`" :value="item.iva_tasa">
                <input type="hidden" :name="`conceptos[${index}][incluye_iva]`" :value="item.incluye_iva ? 1 : 0">

                <div class="font-medium text-slate-900" x-text="item.descripcion"></div>
                <div class="text-xs text-slate-500">
                    SAT: <span x-text="item.clave_producto_servicio"></span> /
                    Unidad: <span x-text="item.clave_unidad"></span>
                </div>
            </td>

            <td class="px-4 py-3 text-right">
                <input type="number"
                       min="0.000001"
                       step="0.000001"
                       :name="`conceptos[${index}][cantidad]`"
                       x-model.number="item.cantidad"
                       class="w-24 rounded-lg border-slate-300 text-right">
            </td>

            <td class="px-4 py-3 text-right">
                <input type="number"
                       min="0"
                       step="0.01"
                       :name="`conceptos[${index}][precio_unitario]`"
                       x-model.number="item.precio_unitario"
                       class="w-28 rounded-lg border-slate-300 text-right">
            </td>

            <td class="px-4 py-3 text-right">
                <span x-text="money(ivaItem(item))"></span>
            </td>

            <td class="px-4 py-3 text-right font-semibold">
                <span x-text="money(totalItem(item))"></span>
            </td>

            <td class="px-4 py-3 text-right">
                <button type="button"
                        @click="removeConcepto(index)"
                        class="text-red-600 hover:text-red-800 text-sm">
                    Quitar
                </button>
            </td>
        </tr>
    </template>
</tbody>

                        </table>

                    </div>

                </div>

            </div>

            {{-- SIDEBAR --}}
            <div class="space-y-6">

                {{-- RESUMEN --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

                    <h2 class="text-lg font-semibold text-slate-900 mb-5">
                        Resumen
                    </h2>

                    <div class="space-y-3 text-sm">

                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="font-medium">$0.00</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">IVA</span>
                            <span class="font-medium">$0.00</span>
                        </div>

                        <div class="border-t border-slate-200 pt-3 flex items-center justify-between">
                            <span class="font-semibold text-slate-900">
                                Total
                            </span>

                            <span class="text-lg font-bold text-slate-900">
                                $0.00
                            </span>
                        </div>

                    </div>

                      <button type="submit"
        :disabled="loadingTimbrar"
        class="w-full mt-6 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">

    <span x-show="!loadingTimbrar">
        Timbrar CFDI
    </span>

    <span x-show="loadingTimbrar" class="flex items-center gap-2">
        <svg class="animate-spin h-5 w-5 text-white"
             xmlns="http://www.w3.org/2000/svg"
             fill="none"
             viewBox="0 0 24 24">
            <circle class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"></circle>

            <path class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>

        Timbrando...
    </span>
</button>

                </div>

            </div>

        </div>

    </form>
    
{{-- MODAL SELECCIONAR CONCEPTO --}}
<div x-show="openConceptos"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">

    <div @click.away="openConceptos = false"
         class="w-full max-w-4xl rounded-2xl bg-white shadow-xl border border-slate-200">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Seleccionar concepto
                </h2>
                <p class="text-sm text-slate-500">
                    Elige el concepto que deseas agregar a la factura.
                </p>
            </div>

            <button type="button"
                    @click="openConceptos = false"
                    class="text-slate-400 hover:text-slate-600">
                ✕
            </button>
        </div>

        <div class="p-6">

            <input type="text"
                   x-model="searchConcepto"
                   placeholder="Buscar por descripción, código o clave SAT..."
                   class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 mb-4">

            <div class="max-h-[420px] overflow-y-auto border border-slate-200 rounded-xl">

                <table class="w-full text-sm">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr class="text-xs uppercase text-slate-500">
                            <th class="px-4 py-3 text-left">Código</th>
                            <th class="px-4 py-3 text-left">Descripción</th>
                            <th class="px-4 py-3 text-left">Clave SAT</th>
                            <th class="px-4 py-3 text-right">Precio</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <template x-for="concepto in conceptosFiltrados()" :key="concepto.id">
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3" x-text="concepto.codigo || '—'"></td>

                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900" x-text="concepto.descripcion"></div>
                                    <div class="text-xs text-slate-500">
                                        Unidad:
                                        <span x-text="concepto.unidad || concepto.clave_unidad"></span>
                                    </div>
                                </td>

                                <td class="px-4 py-3" x-text="concepto.clave_producto_servicio"></td>

                                <td class="px-4 py-3 text-right" x-text="money(concepto.precio_unitario)"></td>

                                <td class="px-4 py-3 text-right">
                                    <button type="button"
                                            @click="addConcepto(concepto)"
                                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                        Agregar
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

            </div>

        </div>

    </div>
    
</div>
<div x-show="loadingTimbrar"
     x-transition.opacity
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm">

    <div class="rounded-2xl bg-white px-8 py-6 shadow-2xl flex flex-col items-center gap-4">

        <svg class="animate-spin h-10 w-10 text-indigo-600"
             xmlns="http://www.w3.org/2000/svg"
             fill="none"
             viewBox="0 0 24 24">
            <circle class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"></circle>

            <path class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>

        <div class="text-sm font-semibold text-slate-700">
            Timbrando CFDI...
        </div>

        <div class="text-xs text-slate-500">
            Esto puede tardar unos segundos
        </div>
    </div>
</div>
</div>

@php
    $clientesBuscador = $clientes->map(function ($cliente) {
        $nombre = $cliente->razon_social ?: $cliente->nombre_comercial;

        return [
            'id' => (string) $cliente->id,
            'nombre' => $nombre,
            'rfc' => $cliente->rfc,
            'search' => trim($nombre . ' ' . $cliente->rfc),
        ];
    })->values();

    $obrasBuscador = $obras->map(function ($obra) {
        return [
            'id' => (string) $obra->id,
            'nombre' => $obra->nombre ?? $obra->Nombre ?? ('Obra #' . $obra->id),
        ];
    })->values();
@endphp
<script>
function facturaForm() {
    return {
        openConceptos: false,
        searchConcepto: '',
        loadingTimbrar: false,
        clienteId: @json((string) old('cliente_id', '')),
        clienteSearch: '',
        clienteOpen: false,
        clientes: @json($clientesBuscador),
        obraId: @json((string) old('obra_id', '')),
        obraSearch: '',
        obraOpen: false,
        obras: @json($obrasBuscador),
        catalogoConceptos: @json($conceptos),

        conceptosSeleccionados: [],

        init() {
            const cliente = this.clientes.find((item) => item.id === this.clienteId);
            if (cliente) {
                this.clienteSearch = `${cliente.nombre} - ${cliente.rfc}`;
            }

            const obra = this.obras.find((item) => item.id === this.obraId);
            if (obra) {
                this.obraSearch = obra.nombre;
            }
        },

        clientesFiltrados() {
            const q = this.clienteSearch.toLowerCase().trim();

            if (!q) {
                return this.clientes.slice(0, 20);
            }

            return this.clientes
                .filter((cliente) => String(cliente.search || '').toLowerCase().includes(q))
                .slice(0, 20);
        },

        selectCliente(cliente) {
            this.clienteId = cliente.id;
            this.clienteSearch = `${cliente.nombre} - ${cliente.rfc}`;
            this.clienteOpen = false;
        },

        obrasFiltradas() {
            const q = this.obraSearch.toLowerCase().trim();

            if (!q) {
                return this.obras.slice(0, 20);
            }

            return this.obras
                .filter((obra) => String(obra.nombre || '').toLowerCase().includes(q))
                .slice(0, 20);
        },

        selectObra(obra) {
            this.obraId = obra.id;
            this.obraSearch = obra.nombre;
            this.obraOpen = false;
        },

        clearObra() {
            this.obraId = '';
            this.obraSearch = '';
            this.obraOpen = false;
        },

        conceptosFiltrados() {
            const q = this.searchConcepto.toLowerCase().trim();

            if (!q) {
                return this.catalogoConceptos;
            }

            return this.catalogoConceptos.filter(c => {
                return String(c.codigo || '').toLowerCase().includes(q)
                    || String(c.descripcion || '').toLowerCase().includes(q)
                    || String(c.clave_producto_servicio || '').toLowerCase().includes(q)
                    || String(c.clave_unidad || '').toLowerCase().includes(q);
            });
        },

        addConcepto(concepto) {
            this.conceptosSeleccionados.push({
                id: concepto.id,
                codigo: concepto.codigo,
                descripcion: concepto.descripcion,
                clave_producto_servicio: concepto.clave_producto_servicio,
                clave_unidad: concepto.clave_unidad,
                unidad: concepto.unidad,
                objeto_impuesto: concepto.objeto_impuesto,
                iva_tasa: parseFloat(concepto.iva_tasa || 0),
                incluye_iva: concepto.incluye_iva == 1 || concepto.incluye_iva === true,
                cantidad: 1,
                precio_unitario: parseFloat(concepto.precio_unitario || 0),
            });

            this.openConceptos = false;
            this.searchConcepto = '';
        },

        removeConcepto(index) {
            this.conceptosSeleccionados.splice(index, 1);
        },

        subtotalItem(item) {
            const cantidad = parseFloat(item.cantidad || 0);
            const precio = parseFloat(item.precio_unitario || 0);

            if (item.incluye_iva && item.iva_tasa > 0) {
                return (cantidad * precio) / (1 + item.iva_tasa);
            }

            return cantidad * precio;
        },

        ivaItem(item) {
            if (item.objeto_impuesto === '01') {
                return 0;
            }

            return this.subtotalItem(item) * parseFloat(item.iva_tasa || 0);
        },

        totalItem(item) {
            if (item.incluye_iva) {
                return parseFloat(item.cantidad || 0) * parseFloat(item.precio_unitario || 0);
            }

            return this.subtotalItem(item) + this.ivaItem(item);
        },

        subtotal() {
            return this.conceptosSeleccionados.reduce((sum, item) => sum + this.subtotalItem(item), 0);
        },

        iva() {
            return this.conceptosSeleccionados.reduce((sum, item) => sum + this.ivaItem(item), 0);
        },

        total() {
            return this.conceptosSeleccionados.reduce((sum, item) => sum + this.totalItem(item), 0);
        },

        money(value) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(value || 0);
        },
    }
}
</script>
@endsection
