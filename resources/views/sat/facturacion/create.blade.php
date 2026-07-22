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

    @php
        $selectedUsoCfdi = old('uso_cfdi', $prefill['uso_cfdi'] ?? 'G03');
        $selectedMetodoPago = old('metodo_pago', $prefill['metodo_pago'] ?? 'PUE');
        $selectedFormaPago = old('forma_pago', $prefill['forma_pago'] ?? '03');
    @endphp

    <form method="POST"
          action="{{ route('sat.facturacion.store') }}"
          @submit="if ($event.submitter?.dataset.action === 'timbrar') loadingTimbrar = true">

        @csrf

        @if(!empty($prefill['cfdi_borrador_id']))
            <input type="hidden" name="cfdi_borrador_id" value="{{ old('cfdi_borrador_id', $prefill['cfdi_borrador_id']) }}">
        @endif

        @if(!empty($prefill['obra_factura_borrador_id']))
            <input type="hidden" name="obra_factura_borrador_id" value="{{ old('obra_factura_borrador_id', $prefill['obra_factura_borrador_id']) }}">
        @endif

        @if(!empty($borrador))
            <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Facturando borrador BF-{{ str_pad($borrador->id, 5, '0', STR_PAD_LEFT) }} de la obra {{ $borrador->obra?->nombre ?? $borrador->obra?->Nombre ?? ('#' . $borrador->obra_id) }}.
            </div>
        @endif

        @if(!empty($cfdiBorrador))
            <div class="mb-4 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                Editando borrador CFDI #{{ $cfdiBorrador->id }} guardado el {{ $cfdiBorrador->updated_at?->format('d/m/Y H:i') }}.
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

            {{-- COLUMNA IZQUIERDA --}}
            <div class="xl:col-span-8 space-y-6">

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
                                    x-model="satEmpresaId"
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

                    <option value="G01" @selected($selectedUsoCfdi === 'G01')>G01 - Adquisición de mercancías</option>
                    <option value="G02" @selected($selectedUsoCfdi === 'G02')>G02 - Devoluciones, descuentos o bonificaciones</option>
                    <option value="G03" @selected($selectedUsoCfdi === 'G03')>G03 - Gastos en general</option>

                    <option value="I01" @selected($selectedUsoCfdi === 'I01')>I01 - Construcciones</option>
                    <option value="I02" @selected($selectedUsoCfdi === 'I02')>I02 - Mobiliario y equipo de oficina por inversiones</option>
                    <option value="I03" @selected($selectedUsoCfdi === 'I03')>I03 - Equipo de transporte</option>
                    <option value="I04" @selected($selectedUsoCfdi === 'I04')>I04 - Equipo de cómputo y accesorios</option>
                    <option value="I05" @selected($selectedUsoCfdi === 'I05')>I05 - Dados, troqueles, moldes, matrices y herramental</option>
                    <option value="I06" @selected($selectedUsoCfdi === 'I06')>I06 - Comunicaciones telefónicas</option>
                    <option value="I07" @selected($selectedUsoCfdi === 'I07')>I07 - Comunicaciones satelitales</option>
                    <option value="I08" @selected($selectedUsoCfdi === 'I08')>I08 - Otra maquinaria y equipo</option>

                    <option value="D01" @selected($selectedUsoCfdi === 'D01')>D01 - Honorarios médicos, dentales y gastos hospitalarios</option>
                    <option value="D02" @selected($selectedUsoCfdi === 'D02')>D02 - Gastos médicos por incapacidad o discapacidad</option>
                    <option value="D03" @selected($selectedUsoCfdi === 'D03')>D03 - Gastos funerales</option>
                    <option value="D04" @selected($selectedUsoCfdi === 'D04')>D04 - Donativos</option>
                    <option value="D05" @selected($selectedUsoCfdi === 'D05')>D05 - Intereses reales efectivamente pagados por créditos hipotecarios</option>
                    <option value="D06" @selected($selectedUsoCfdi === 'D06')>D06 - Aportaciones voluntarias al SAR</option>
                    <option value="D07" @selected($selectedUsoCfdi === 'D07')>D07 - Primas por seguros de gastos médicos</option>
                    <option value="D08" @selected($selectedUsoCfdi === 'D08')>D08 - Gastos de transportación escolar obligatoria</option>
                    <option value="D09" @selected($selectedUsoCfdi === 'D09')>D09 - Depósitos en cuentas para el ahorro / pensiones</option>
                    <option value="D10" @selected($selectedUsoCfdi === 'D10')>D10 - Pagos por servicios educativos</option>

                    <option value="S01" @selected($selectedUsoCfdi === 'S01')>S01 - Sin efectos fiscales</option>
                    <option value="CP01" @selected($selectedUsoCfdi === 'CP01')>CP01 - Pagos</option>
                    <option value="CN01" @selected($selectedUsoCfdi === 'CN01')>CN01 - Nómina</option>
                </select>
            </div>

            {{-- MÉTODO DE PAGO --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Método de pago
                </label>

                <select name="metodo_pago"
                        class="w-full rounded-xl border-slate-300">

                    <option value="PUE" @selected($selectedMetodoPago === 'PUE')>PUE - Pago en una sola exhibicion</option>
                    <option value="PPD" @selected($selectedMetodoPago === 'PPD')>PPD - Pago en parcialidades</option>

                </select>
            </div>

            {{-- FORMA DE PAGO --}}
            <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Forma de pago
                    </label>

                    <select name="forma_pago"
                            class="w-full rounded-xl border-slate-300">

                        <option value="03" @selected($selectedFormaPago === '03')>03 - Transferencia electronica</option>
                        <option value="01" @selected($selectedFormaPago === '01')>01 - Efectivo</option>
                        <option value="02" @selected($selectedFormaPago === '02')>02 - Cheque nominativo</option>
                        <option value="04" @selected($selectedFormaPago === '04')>04 - Tarjeta de credito</option>
                        <option value="28" @selected($selectedFormaPago === '28')>28 - Tarjeta de debito</option>
                        <option value="99" @selected($selectedFormaPago === '99')>99 - Por definir</option>

                    </select>
                </div>
                
            </div>
              {{-- IVA GLOBAL --}}
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
            IVA
        </label>
        <select name="tipo_iva" x-model="tipoIva" class="w-full rounded-xl border-slate-300">
            <option value="0.16">IVA 16%</option>
            <option value="0.08">IVA 8% (Zona fronteriza)</option>
            <option value="0">IVA 0% (Tasa cero)</option>
            <option value="exento">Exento (sin traslado)</option>
            <option value="sin_iva">Sin IVA (no objeto)</option>
        </select>
        <p class="text-xs text-slate-400 mt-1">Se aplica a todos los conceptos</p>
    </div>
    

     </div>
      {{-- FACTURAS RELACIONADAS --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mt-6"
     >

    <div class="flex items-start justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-900">
                Facturas Relacionadas
            </h3>
            <p class="text-sm text-slate-500">
                Relaciona este CFDI con facturas emitidas anteriormente.
            </p>
        </div>

        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox"
                   name="usar_relacion"
                   value="1"
                   x-model="usarRelacion"
                   @change="if (usarRelacion) openRelacionModal(); else clearRelacionadas()"
                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Relacionar CFDI
        </label>
    </div>

    <div x-show="usarRelacion"
         x-cloak
         class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">

        <div>
            <label class="block text-sm font-medium text-slate-700">
                Tipo de Relación
            </label>
            <select name="relacion_tipo" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="04">04 - Sustitución de los CFDI previos</option>
                <option value="01">01 - Nota de crédito de los documentos relacionados</option>
                <option value="02">02 - Nota de débito de los documentos relacionados</option>
                <option value="03">03 - Devolución de mercancía sobre facturas o traslados previos</option>
                <option value="05">05 - Traslados de mercancias facturados previamente</option>
                <option value="06">06 - Factura generada por los traslados previos</option>
                <option value="07">07 - CFDI por aplicación de anticipo</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Facturas seleccionadas</label>
            <input type="hidden" name="relacion_uuids" :value="selectedRelacionUuids().join(',')">

            <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 p-3 min-h-[46px]">
                <div x-show="selectedRelacionadas.length === 0" class="text-sm text-slate-500">
                    Sin facturas relacionadas.
                </div>

                <div x-show="selectedRelacionadas.length > 0" class="flex flex-wrap gap-2">
                    <template x-for="factura in selectedRelacionadas" :key="factura.uuid">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white border border-slate-200 px-3 py-1 text-xs text-slate-700">
                            <span class="font-mono" x-text="shortUuid(factura.uuid)"></span>
                            <button type="button" @click="removeRelacionada(factura.uuid)" class="font-semibold text-slate-400 hover:text-red-600">x</button>
                        </span>
                    </template>
                </div>
            </div>

            <button type="button"
                    @click="openRelacionModal()"
                    class="mt-3 inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                Buscar facturas
            </button>
        </div>
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
                <input type="hidden" :name="`conceptos[${index}][clave_producto_servicio]`" :value="item.clave_producto_servicio">
                <input type="hidden" :name="`conceptos[${index}][clave_unidad]`" :value="item.clave_unidad">
                <input type="hidden" :name="`conceptos[${index}][unidad]`" :value="item.unidad">
                <input type="hidden" :name="`conceptos[${index}][iva_tasa]`" :value="item.iva_tasa">
                <input type="hidden" :name="`conceptos[${index}][incluye_iva]`" :value="item.incluye_iva ? 1 : 0">
                <input type="hidden" :name="`conceptos[${index}][descripcion]`" :value="item.descripcion">

                <div class="flex items-start justify-between">
                    <div>
                        <div class="font-medium text-slate-900" x-text="item.nombre_catalogo"></div>
                        <div class="text-sm text-slate-600 mt-1" x-show="item.descripcion !== item.nombre_catalogo" x-text="item.descripcion.length > 50 ? item.descripcion.substring(0, 50) + '...' : item.descripcion"></div>
                        <div class="text-xs text-slate-500 mt-1">
                            SAT: <span x-text="item.clave_producto_servicio"></span> /
                            Unidad: <span x-text="item.clave_unidad"></span>
                        </div>
                    </div>
                    <button type="button" @click="editDescripcion(index)" class="text-indigo-600 hover:text-indigo-800 p-1 bg-indigo-50 rounded hover:bg-indigo-100 transition-colors ml-2" title="Editar Detalles">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </button>
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
                       step="0.000001"
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

                {{-- AJUSTES --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-start justify-between gap-4 mb-5">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Ajustes</h2>
                            <p class="text-sm text-slate-500">Captura amortizacion, descuentos y retenciones aplicables a la factura.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Amortizacion</label>
                            <input type="number"
                                   min="0"
                                   step="0.01"
                                   name="amortizacion"
                                   x-model.number="amortizacion"
                                   class="w-full rounded-xl border-slate-300 text-right focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Descuentos</label>
                            <input type="number"
                                   min="0"
                                   step="0.01"
                                   name="descuento"
                                   x-model.number="descuento"
                                   class="w-full rounded-xl border-slate-300 text-right focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Retenciones</label>
                            <input type="number"
                                   min="0"
                                   step="0.01"
                                   name="retenciones"
                                   x-model.number="retenciones"
                                   class="w-full rounded-xl border-slate-300 text-right focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

            </div>

            {{-- SIDEBAR --}}
            <div class="xl:col-span-4 space-y-6 xl:sticky xl:top-6">

                {{-- RESUMEN --}}
                <div class="overflow-hidden rounded-2xl border border-slate-900 bg-slate-950 shadow-xl shadow-slate-900/10">

                    <div class="h-2 bg-amber-400"></div>

                    <div class="bg-slate-800 px-6 py-7">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-400/10 text-amber-300">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 7v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7M8 11h8M8 15h5" />
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-xl font-bold text-white">
                                    Resumen de Factura
                                </h2>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">
                                    Totales en MXN
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-7">
                        <div class="space-y-5 text-sm">

                            <div class="flex items-center justify-between gap-4">
                                <span class="font-bold uppercase tracking-wider text-slate-400">Subtotal</span>
                                <span class="text-lg font-bold tabular-nums text-white" x-text="money(subtotal())"></span>
                            </div>

                            <div class="border-t border-white/10"></div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="font-bold uppercase tracking-wider text-slate-400">IVA</span>
                                <span class="text-lg font-bold tabular-nums text-white" x-text="money(iva())"></span>
                            </div>

                            <div class="border-t border-white/10"></div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="font-medium text-slate-400">Amortizacion</span>
                                <span class="font-semibold tabular-nums text-amber-300">- <span x-text="money(amortizacion || 0)"></span></span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="font-medium text-slate-400">Descuentos</span>
                                <span class="font-semibold tabular-nums text-amber-300">- <span x-text="money(descuento || 0)"></span></span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="font-medium text-slate-400">Retenciones</span>
                                <span class="font-semibold tabular-nums text-amber-300">- <span x-text="money(retenciones || 0)"></span></span>
                            </div>

                            <div class="border-t border-white/10 pt-5">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <span class="block text-xs font-bold uppercase tracking-[0.24em] text-amber-300">
                                            Total neto
                                        </span>
                                        <span class="mt-1 block text-4xl font-black leading-none tabular-nums text-white" x-text="money(total())"></span>
                                    </div>

                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-400/10 text-amber-300">
                                        <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-7 space-y-3">
                            <button type="submit"
                                    data-action="timbrar"
                                    :disabled="loadingTimbrar"
                                    class="w-full inline-flex items-center justify-center rounded-lg bg-amber-400 px-5 py-4 text-sm font-bold uppercase tracking-[0.18em] text-slate-950 shadow-sm hover:bg-amber-300 disabled:opacity-50 disabled:cursor-not-allowed">

                                <span x-show="!loadingTimbrar" class="inline-flex items-center gap-2">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m13 10 4-7-9 9h6l-4 9 9-11h-6Z" />
                                    </svg>
                                    Timbrar CFDI
                                </span>

                                <span x-show="loadingTimbrar" class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-slate-950"
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

                            <button type="submit"
                                    formaction="{{ route('sat.facturacion.borradores.store') }}"
                                    formmethod="POST"
                                    formnovalidate
                                    :disabled="loadingTimbrar"
                                    class="w-full inline-flex items-center justify-center rounded-lg border border-amber-400/50 bg-slate-950 px-5 py-4 text-sm font-bold text-amber-300 shadow-sm hover:bg-slate-900 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="inline-flex items-center gap-2">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 3H7a2 2 0 0 0-2 2v14l7-3 7 3V5a2 2 0 0 0-2-2Z" />
                                    </svg>
                                    Guardar borrador
                                </span>
                            </button>

                            <button type="submit"
                                    formaction="{{ route('sat.facturacion.preview') }}"
                                    formmethod="POST"
                                    formtarget="_blank"
                                    :disabled="loadingTimbrar"
                                    class="w-full inline-flex items-center justify-center rounded-lg border border-slate-600 bg-slate-900 px-5 py-4 text-sm font-bold text-white shadow-sm hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="inline-flex items-center gap-2">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    Previsualizar PDF
                                </span>
                            </button>
                        </div>

                        <div class="mt-7 rounded-lg border border-white/10 bg-slate-900/70 p-4 text-xs leading-5 text-slate-400">
                            Al timbrar esta factura, se generara el archivo XML y PDF oficial ante el SAT. Asegurate de que los datos sean correctos.
                        </div>
                    </div>

                </div>

                @if(($cfdiBorradores ?? collect())->isNotEmpty())
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-700">Borradores</h3>
                            <p class="mt-1 text-xs text-slate-500">Capturas CFDI guardadas recientemente.</p>
                        </div>

                        <div class="space-y-3">
                            @foreach($cfdiBorradores as $draft)
                                <a href="{{ route('sat.facturacion.create', ['cfdi_borrador_id' => $draft->id]) }}"
                                   class="block rounded-xl border px-4 py-3 text-sm hover:bg-slate-50 {{ !empty($cfdiBorrador) && $cfdiBorrador->id === $draft->id ? 'border-indigo-200 bg-indigo-50' : 'border-slate-200 bg-white' }}">
                                    <div class="font-semibold text-slate-900">{{ $draft->titulo ?: 'Borrador CFDI #' . $draft->id }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $draft->updated_at?->format('d/m/Y H:i') }}
                                        @if($draft->cliente)
                                            · {{ $draft->cliente->razon_social ?: $draft->cliente->nombre_comercial }}
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

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

{{-- MODAL FACTURAS RELACIONADAS --}}
<div x-show="openRelacion"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">

    <div @click.away="closeRelacionModal()"
         class="w-full max-w-5xl rounded-2xl bg-white shadow-xl border border-slate-200">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Seleccionar facturas relacionadas
                </h2>
                <p class="text-sm text-slate-500">
                    Busca CFDI emitidos desde Facturapi o descargados del SAT.
                </p>
            </div>

            <button type="button"
                    @click="closeRelacionModal()"
                    class="text-slate-400 hover:text-slate-600">
                x
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div class="flex flex-col md:flex-row gap-3">
                <input type="text"
                       x-model="relacionSearch"
                       @input.debounce.350ms="fetchRelacionables()"
                       placeholder="Buscar por UUID, RFC, cliente, serie, folio..."
                       class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">

                <button type="button"
                        @click="fetchRelacionables()"
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Buscar
                </button>
            </div>

            <div class="min-h-[260px] max-h-[430px] overflow-y-auto border border-slate-200 rounded-xl">
                <div x-show="relacionLoading" class="px-4 py-6 text-sm text-slate-500">
                    Buscando facturas...
                </div>

                <div x-show="!relacionLoading && relacionResults.length === 0" class="px-4 py-6 text-sm text-slate-500">
                    Sin facturas disponibles para relacionar.
                </div>

                <table x-show="!relacionLoading && relacionResults.length > 0" class="w-full text-sm">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr class="text-xs uppercase text-slate-500">
                            <th class="px-4 py-3 text-left">Factura</th>
                            <th class="px-4 py-3 text-left">Receptor</th>
                            <th class="px-4 py-3 text-left">UUID</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Accion</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <template x-for="factura in relacionResults" :key="`${factura.source}-${factura.id}`">
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">
                                        <span x-text="factura.serie || 'S/S'"></span>-<span x-text="factura.folio || factura.id"></span>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <span x-text="factura.fecha_formateada || factura.fecha || '-'"></span>
                                        <span class="mx-1">/</span>
                                        <span x-text="factura.source_label"></span>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900" x-text="factura.receptor_nombre || '-'"></div>
                                    <div class="text-xs text-slate-500" x-text="factura.receptor_rfc || '-'"></div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-mono text-xs text-slate-600 break-all" x-text="factura.uuid"></div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="font-semibold text-slate-900" x-text="money(factura.total || 0)"></div>
                                    <div class="text-xs text-slate-500" x-text="factura.moneda || 'MXN'"></div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <button type="button"
                                            @click="toggleRelacionada(factura)"
                                            class="rounded-lg px-3 py-1.5 text-xs font-semibold"
                                            :class="isRelacionada(factura.uuid) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-600 text-white hover:bg-indigo-700'">
                                        <span x-text="isRelacionada(factura.uuid) ? 'Seleccionada' : 'Seleccionar'"></span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between gap-4">
                <div class="text-sm text-slate-500">
                    <span x-text="selectedRelacionadas.length"></span>
                    factura(s) seleccionada(s)
                </div>

                <div class="flex items-center gap-3">
                    <button type="button"
                            @click="clearRelacionadas()"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Limpiar
                    </button>
                    <button type="button"
                            @click="closeRelacionModal()"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Usar seleccionadas
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDITAR DESCRIPCION --}}
<div x-show="openEditDesc"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">

    <div @click.away="closeEditDesc()"
         class="w-full max-w-2xl rounded-2xl bg-white shadow-xl border border-slate-200 flex flex-col">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Detalles del Concepto
                </h2>
                <p class="text-sm text-slate-500">
                    Edita o agrega información a la descripción del concepto.
                </p>
            </div>

            <button type="button"
                    @click="closeEditDesc()"
                    class="text-slate-400 hover:text-slate-600">
                ✕
            </button>
        </div>

        <div class="p-6">
            <div class="mb-4">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Concepto Base:</span>
                <div class="text-sm text-slate-900 font-medium mt-1" x-text="descToEditName"></div>
            </div>

            <label class="block text-sm font-medium text-slate-700 mb-2">
                Descripción (Texto Plano)
            </label>
            <textarea x-model="descToEditText"
                      rows="8"
                      class="w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                      placeholder="Escribe los detalles aquí..."></textarea>
            
            <div class="mt-2 text-xs text-slate-500 flex items-center">
                <svg class="w-4 h-4 mr-1 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                Recuerda que no se permiten etiquetas de diseño (HTML). Usa saltos de línea normales.
            </div>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50 rounded-b-2xl">
            <button type="button"
                    @click="closeEditDesc()"
                    class="px-4 py-2 rounded-xl border border-slate-300 text-sm text-slate-700 hover:bg-white">
                Cancelar
            </button>
            <button type="button"
                    @click="saveEditDesc()"
                    class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold shadow hover:bg-indigo-700">
                Guardar Detalles
            </button>
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
        satEmpresaId: @json((string) old('sat_empresa_id', $prefill['sat_empresa_id'] ?? '')),
        clienteId: @json((string) old('cliente_id', $prefill['cliente_id'] ?? '')),
        clienteSearch: '',
        clienteOpen: false,
        clientes: @json($clientesBuscador),
        obraId: @json((string) old('obra_id', $prefill['obra_id'] ?? '')),
        obraSearch: '',
        obraOpen: false,
        obras: @json($obrasBuscador),
        catalogoConceptos: @json($conceptos),

        conceptosSeleccionados: @json(old('conceptos', $prefill['conceptos'] ?? [])),
        amortizacion: Number(@json(old('amortizacion', $prefill['amortizacion'] ?? 0))) || 0,
        descuento: Number(@json(old('descuento', $prefill['descuento'] ?? 0))) || 0,
        retenciones: Number(@json(old('retenciones', $prefill['retenciones'] ?? 0))) || 0,
        tipoIva: @json(old('tipo_iva', $prefill['tipo_iva'] ?? '0.16')),
        usarRelacion: @json((bool) old('usar_relacion', !empty($prefill['relacion_uuids'] ?? ''))),
        openRelacion: false,
        relacionSearch: '',
        relacionLoading: false,
        relacionResults: [],
        selectedRelacionadas: [],
        relacionablesUrl: @json(route('sat.facturacion.relacionables')),
        oldRelacionUuids: @json(old('relacion_uuids', $prefill['relacion_uuids'] ?? '')),

        openEditDesc: false,
        descToEditIndex: null,
        descToEditName: '',
        descToEditText: '',

        init() {
            this.conceptosSeleccionados = (this.conceptosSeleccionados || []).map((item) => ({
                id: item.id ?? item.sat_concepto_id ?? null,
                codigo: item.codigo ?? '',
                nombre_catalogo: item.nombre_catalogo ?? item.descripcion ?? '',
                descripcion: item.descripcion ?? '',
                clave_producto_servicio: item.clave_producto_servicio ?? '',
                clave_unidad: item.clave_unidad ?? '',
                unidad: item.unidad ?? '',
                objeto_impuesto: item.objeto_impuesto ?? '02',
                iva_tasa: parseFloat(item.iva_tasa || 0),
                incluye_iva: item.incluye_iva === true || item.incluye_iva === 1 || item.incluye_iva === '1',
                cantidad: parseFloat(item.cantidad || 1),
                precio_unitario: parseFloat(item.precio_unitario || 0),
            }));

            const cliente = this.clientes.find((item) => item.id === this.clienteId);
            if (cliente) {
                this.clienteSearch = `${cliente.nombre} - ${cliente.rfc}`;
            }

            const obra = this.obras.find((item) => item.id === this.obraId);
            if (obra) {
                this.obraSearch = obra.nombre;
            }

            this.selectedRelacionadas = String(this.oldRelacionUuids || '')
                .split(',')
                .map((uuid) => uuid.trim())
                .filter(Boolean)
                .map((uuid) => ({
                    uuid,
                    source: 'old',
                    source_label: 'Seleccionada',
                    total: 0,
                }));

            if (this.selectedRelacionadas.length > 0) {
                this.usarRelacion = true;
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

            if (this.openRelacion) {
                this.fetchRelacionables();
            }
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

        openRelacionModal() {
            this.usarRelacion = true;
            this.openRelacion = true;
            this.fetchRelacionables();
        },

        closeRelacionModal() {
            this.openRelacion = false;
        },

        selectedRelacionUuids() {
            return this.selectedRelacionadas
                .map((factura) => String(factura.uuid || '').trim())
                .filter(Boolean);
        },

        isRelacionada(uuid) {
            const normalized = String(uuid || '').toUpperCase();
            return this.selectedRelacionadas.some((factura) => String(factura.uuid || '').toUpperCase() === normalized);
        },

        toggleRelacionada(factura) {
            if (this.isRelacionada(factura.uuid)) {
                this.removeRelacionada(factura.uuid);
                return;
            }

            this.selectedRelacionadas.push(factura);
        },

        removeRelacionada(uuid) {
            const normalized = String(uuid || '').toUpperCase();
            this.selectedRelacionadas = this.selectedRelacionadas
                .filter((factura) => String(factura.uuid || '').toUpperCase() !== normalized);
        },

        clearRelacionadas() {
            this.selectedRelacionadas = [];
        },

        shortUuid(uuid) {
            const value = String(uuid || '');
            return value.length > 16 ? `...${value.slice(-12)}` : value;
        },

        async fetchRelacionables() {
            this.relacionLoading = true;

            const params = new URLSearchParams();

            if (this.relacionSearch.trim()) {
                params.set('q', this.relacionSearch.trim());
            }

            if (this.clienteId) {
                params.set('cliente_id', this.clienteId);
            }

            if (this.satEmpresaId) {
                params.set('sat_empresa_id', this.satEmpresaId);
            }

            try {
                const response = await fetch(`${this.relacionablesUrl}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('No se pudieron cargar las facturas.');
                }

                const payload = await response.json();
                this.relacionResults = payload.data || [];
            } catch (error) {
                this.relacionResults = [];
            } finally {
                this.relacionLoading = false;
            }
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
                nombre_catalogo: concepto.descripcion,
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

        editDescripcion(index) {
            this.descToEditIndex = index;
            this.descToEditName = this.conceptosSeleccionados[index].nombre_catalogo;
            this.descToEditText = this.conceptosSeleccionados[index].descripcion;
            this.openEditDesc = true;
        },

        closeEditDesc() {
            this.openEditDesc = false;
            this.descToEditIndex = null;
            this.descToEditName = '';
            this.descToEditText = '';
        },

        saveEditDesc() {
            if (this.descToEditIndex !== null) {
                this.conceptosSeleccionados[this.descToEditIndex].descripcion = this.descToEditText;
            }
            this.closeEditDesc();
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
            if (this.tipoIva === 'sin_iva' || item.objeto_impuesto === '01') {
                return 0;
            }

            return this.subtotalItem(item) * this.ivaTasa();
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

        baseGravable() {
            return Math.max(0, this.subtotal()
                - Math.max(0, parseFloat(this.amortizacion || 0))
                - Math.max(0, parseFloat(this.descuento || 0)));
        },

        ivaTasa() {
            return ['0.16', '0.08'].includes(this.tipoIva)
                ? parseFloat(this.tipoIva)
                : 0;
        },

        iva() {
            return this.baseGravable() * this.ivaTasa();
        },

        ajusteTotal() {
            return Math.max(0, parseFloat(this.amortizacion || 0))
                + Math.max(0, parseFloat(this.descuento || 0))
                + Math.max(0, parseFloat(this.retenciones || 0));
        },

        total() {
            return Math.max(0, this.baseGravable() + this.iva() - Math.max(0, parseFloat(this.retenciones || 0)));
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
