@extends('layouts.admin')

@section('title', 'Captura de caja chica')

@section('content')
<div class="max-w-[98%] mx-auto space-y-5" x-data="reposicionCajaChicaExcel()">

    {{-- HEADER --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Captura de caja chica</h1>
            <p class="text-sm text-slate-500">Arrastra tus archivos XML para importar automáticamente o agrega gastos sin factura en la hoja de captura.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reposicion-caja-chica.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver a la bandeja
            </a>
        </div>
    </div>

    {{-- ALERTAS DE ERRORES / FEEDBACK --}}
    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 space-y-1">
            <p class="font-semibold">Revisa los siguientes campos antes de guardar:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <template x-if="xmlErrores.length > 0">
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-semibold">Algunos archivos no se pudieron procesar:</p>
            <ul class="list-disc pl-5 mt-1 space-y-0.5">
                <template x-for="(err, i) in xmlErrores" :key="i">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>
    </template>

    {{-- PASO 1: SELECCIÓN PREVIA DE OBRA O ALMACÉN --}}
    <div class="rounded-xl border-2 border-[#0B265A]/20 bg-white p-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#0B265A] text-xs font-bold text-white">1</span>
                    <h2 class="text-base font-bold text-[#0B265A]">Selecciona la Obra o Almacén objetivo</h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Todos los XMLs y gastos manuales que cargues se asignarán a esta obra/almacén. Al terminar el lote, podrás avanzar a la siguiente.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Destino</label>
                    <select x-model="targetDestino" @change="onTargetDestinoChange()" class="rounded-lg border-slate-300 text-sm font-semibold text-slate-800">
                        <option value="obra">Obra</option>
                        <option value="almacen">Almacén</option>
                    </select>
                </div>

                <div x-show="targetDestino === 'obra'" class="min-w-[260px]">
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Obra destino (activas) <span class="text-red-500">*</span></label>
                    <select x-model="targetObraId" @change="syncTargetToAllRows()" class="w-full rounded-lg border-blue-300 bg-blue-50/50 text-sm font-bold text-slate-900 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Selecciona una Obra activa --</option>
                        <template x-for="obra in obras" :key="obra.id">
                            <option :value="obra.id" x-text="obra.nombre + (obra.estatus_nuevo == 2 ? ' (En ejecución)' : ' (Planeación)')"></option>
                        </template>
                    </select>
                </div>

                <div x-show="targetDestino === 'almacen'" class="min-w-[260px]">
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Almacén destino <span class="text-red-500">*</span></label>
                    <select x-model="targetAlmacenId" @change="syncTargetToAllRows()" class="w-full rounded-lg border-blue-300 bg-blue-50/50 text-sm font-bold text-slate-900 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Selecciona un Almacén --</option>
                        <template x-for="alm in almacenes" :key="alm.id">
                            <option :value="alm.id" x-text="alm.nombre"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- PASO 2: ZONA DE CARGA XML Y ACCIONES RÁPIDAS --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
        
        {{-- DROPZONE XML --}}
        <div class="lg:col-span-8 rounded-xl border-2 border-dashed border-blue-300 bg-blue-50/40 p-5 text-center transition-colors hover:border-blue-500 hover:bg-blue-50/70"
             @dragover.prevent="dragover = true"
             @dragleave.prevent="dragover = false"
             @drop.prevent="dragover = false; handleXmlDrop($event)"
             :class="{ 'border-blue-600 bg-blue-100/60': dragover }">
            
            <input type="file" x-ref="xmlInput" @change="handleXmlSelect($event)" multiple accept=".xml,text/xml" class="hidden">
            
            <div class="flex flex-col items-center justify-center gap-2">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-2xl font-bold">
                    📄
                </div>
                <div>
                    <button type="button" @click="$refs.xmlInput.click()" class="font-bold text-blue-700 hover:underline">
                        Haz clic aquí para seleccionar archivos XML
                    </button>
                    <span class="text-slate-600"> o arrástralos directamente a este recuadro</span>
                </div>
                <p class="text-xs text-slate-500">Puedes cargar varios CFDI (3.3 y 4.0) de esta obra a la vez. Se extraerán proveedor, RFC, fecha, conceptos y total al instante.</p>
                
                <div x-show="loadingXml" class="mt-2 flex items-center gap-2 text-sm font-semibold text-blue-800">
                    <svg class="h-5 w-5 animate-spin text-blue-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Extrayendo datos de los archivos XML...</span>
                </div>
            </div>
        </div>

        {{-- BOTONES DE ACCIÓN RÁPIDA --}}
        <div class="lg:col-span-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-center gap-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Gastos sin factura para esta obra</h3>
            <p class="text-xs text-slate-500">Agrega renglones para comprobantes que no tienen XML fiscal:</p>
            <div class="flex flex-col gap-2">
                <button type="button" @click="addManualRow('sin_factura_viaticos')" class="rounded-lg border border-purple-300 bg-purple-50 px-3 py-2 text-xs font-bold text-purple-700 hover:bg-purple-100 text-left flex items-center justify-between">
                    <span>➕ Gasto sin factura (Viáticos)</span>
                    <span class="text-[10px] bg-purple-200 text-purple-800 rounded px-1.5 py-0.5">Viáticos</span>
                </button>
                <button type="button" @click="addManualRow('sin_factura_reembolso')" class="rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100 text-left flex items-center justify-between">
                    <span>➕ Gasto sin factura (Reembolso)</span>
                    <span class="text-[10px] bg-indigo-200 text-indigo-800 rounded px-1.5 py-0.5">Reembolso</span>
                </button>
            </div>
        </div>
    </div>

    {{-- FORMULARIO PRINCIPAL / HOJA TIPO EXCEL --}}
    <form method="POST" action="{{ route('reposicion-caja-chica.store') }}" enctype="multipart/form-data" @submit="isSubmitting = true">
        @csrf
        <input type="hidden" name="action" x-model="formAction">
        <input type="hidden" name="target_destino" x-model="targetDestino">
        <input type="hidden" name="target_obra_id" x-model="targetObraId">
        <input type="hidden" name="target_almacen_id" x-model="targetAlmacenId">

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            
            {{-- ENCABEZADO DE LA HOJA --}}
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/80 px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="font-bold text-slate-800 text-sm">Hoja de Gastos</span>
                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800" x-text="rows.length + ' renglones'"></span>
                </div>
                <template x-if="rows.length > 0">
                    <button type="button" @click="rows = []" class="text-xs font-semibold text-red-600 hover:underline">
                        Limpiar todos los renglones
                    </button>
                </template>
            </div>

            {{-- TABLA GRID EXCEL --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-100/90 text-slate-700 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="w-10 px-3 py-2.5 text-center">#</th>
                            <th class="w-44 px-3 py-2.5 text-left">Tipo comprobación</th>
                            <th class="w-32 px-3 py-2.5 text-left">Fecha</th>
                            <th class="w-56 px-3 py-2.5 text-left">Proveedor</th>
                            <th class="w-36 px-3 py-2.5 text-left">RFC</th>
                            <th class="min-w-[340px] px-3 py-2.5 text-left">Concepto</th>
                            <th class="w-44 px-3 py-2.5 text-left">Categoría</th>
                            <th class="w-32 px-3 py-2.5 text-left">Forma pago</th>
                            <th class="w-32 px-3 py-2.5 text-right">Importe ($)</th>
                            <th class="w-44 px-3 py-2.5 text-center">Evidencia / Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        
                        <template x-for="(row, index) in rows" :key="row.uid">
                            <tr class="hover:bg-blue-50/30 transition-colors" :class="{ 'bg-purple-50/20': !row.requiere_factura }">
                                
                                {{-- # / ÍNDICE --}}
                                <td class="px-3 py-2 text-center font-bold text-slate-400">
                                    <span x-text="index + 1"></span>
                                    <input type="hidden" :name="'gastos[' + index + '][id]'" :value="row.id || ''">
                                </td>

                                {{-- TIPO DE COMPROBACIÓN --}}
                                <td class="px-2 py-2">
                                    <select :name="'gastos[' + index + '][categoria_id]'" 
                                            x-model="row.categoria_id" 
                                            @change="onCategoriaChange(row)"
                                            class="w-full rounded border-slate-300 text-xs py-1 px-1.5 focus:ring-1 focus:ring-blue-500">
                                        <template x-for="cat in categorias" :key="cat.id">
                                            <option :value="String(cat.id)" x-text="cat.nombre" :selected="row.categoria_id == cat.id"></option>
                                        </template>
                                    </select>
                                </td>

                                {{-- FECHA --}}
                                <td class="px-2 py-2">
                                    <input type="date" :name="'gastos[' + index + '][fecha_gasto]'" 
                                           x-model="row.fecha_gasto" 
                                           class="w-full rounded border-slate-300 text-xs py-1 px-1.5 focus:ring-1 focus:ring-blue-500">
                                </td>

                                {{-- PROVEEDOR --}}
                                <td class="px-2 py-2">
                                    <input type="text" :name="'gastos[' + index + '][proveedor_nombre]'" 
                                           x-model="row.proveedor_nombre" 
                                           placeholder="Nombre o razón social" 
                                           required
                                           class="w-full rounded border-slate-300 text-xs py-1 px-1.5 font-semibold text-slate-800 focus:ring-1 focus:ring-blue-500">
                                </td>

                                {{-- RFC --}}
                                <td class="px-2 py-2">
                                    <input type="text" :name="'gastos[' + index + '][proveedor_rfc]'" 
                                           x-model="row.proveedor_rfc" 
                                           placeholder="RFC opcional" 
                                           class="w-full rounded border-slate-300 text-xs py-1 px-1.5 uppercase text-slate-700 focus:ring-1 focus:ring-blue-500">
                                </td>

                                {{-- CONCEPTO Y MOTIVO --}}
                                <td class="px-2 py-2">
                                    <input type="text" :name="'gastos[' + index + '][concepto]'" 
                                           x-model="row.concepto" 
                                           placeholder="Descripción del gasto / detalle operativo" 
                                           required
                                           class="w-full rounded border-slate-300 text-xs py-1 px-1.5 focus:ring-1 focus:ring-blue-500">
                                    
                                    {{--
                                    Motivo sin factura temporalmente oculto para mantener el renglón alineado.
                                    Reactivar cuando el flujo operativo vuelva a requerir este dato separado del concepto.
                                    <div x-show="!row.requiere_factura" class="mt-1">
                                        <input type="text" :name="'gastos[' + index + '][motivo_sin_factura]'"
                                               x-model="row.motivo_sin_factura"
                                               placeholder="* Motivo por el que no hay factura"
                                               class="w-full rounded border-amber-300 bg-amber-50 text-[11px] py-0.5 px-1.5 text-amber-900 placeholder:text-amber-600">
                                    </div>
                                    --}}
                                </td>

                                {{-- CATEGORÍA / SUBCATEGORÍA --}}
                                <td class="px-2 py-2">
                                    <select :name="'gastos[' + index + '][subcategoria_id]'" 
                                            x-model="row.subcategoria_id" 
                                            class="w-full rounded border-slate-300 text-xs py-1 px-1.5">
                                        <option value="">Selecciona...</option>
                                        <template x-for="sub in getSubcategorias(row.categoria_id)" :key="sub.id">
                                            <option :value="sub.id" x-text="sub.nombre" :selected="row.subcategoria_id == sub.id"></option>
                                        </template>
                                    </select>
                                </td>

                                {{-- FORMA DE PAGO --}}
                                <td class="px-2 py-2">
                                    <select :name="'gastos[' + index + '][forma_pago]'" 
                                            x-model="row.forma_pago" 
                                            class="w-full rounded border-slate-300 text-xs py-1 px-1.5">
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta</option>
                                    </select>
                                </td>

                                {{-- IMPORTE REGISTRADO --}}
                                <td class="px-2 py-2 text-right">
                                    <div class="relative">
                                        <span class="absolute left-2 top-1 text-slate-400 font-bold">$</span>
                                        <input type="number" step="0.01" min="0.01" 
                                               :name="'gastos[' + index + '][importe_registrado]'" 
                                               x-model.number="row.importe_registrado" 
                                               required
                                               class="w-full rounded border-slate-300 text-xs py-1 pl-5 pr-1.5 text-right font-bold text-slate-900 focus:ring-1 focus:ring-blue-500">
                                    </div>
                                </td>

                                {{-- EVIDENCIA / ACCIONES --}}
                                <td class="px-2 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1" :title="row.xml_file_name ? 'XML: ' + row.xml_file_name : ''">
                                        <label class="cursor-pointer rounded border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50 inline-flex items-center gap-1 shadow-sm">
                                            <span>📎 Adjuntar</span>
                                            <input type="file" :name="'evidencias[' + index + '][]'" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" class="hidden" @change="onEvidenciaChange($event, row)">
                                        </label>

                                        <template x-if="row.evidencia_count > 0">
                                            <span class="rounded bg-green-50 px-1.5 py-1 text-[10px] font-bold text-green-700" x-text="row.evidencia_count"></span>
                                        </template>

                                        <button type="button" @click="removeRow(index)" class="rounded border border-transparent p-1 text-slate-400 hover:border-red-200 hover:bg-red-50 hover:text-red-600 transition-colors" title="Eliminar renglón">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        {{-- ESTADO VACÍO --}}
                        <template x-if="rows.length === 0">
                            <tr>
                                <td colspan="10" class="px-4 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="text-4xl">📊</span>
                                        <p class="font-semibold text-slate-600">No hay gastos en la hoja de captura.</p>
                                        <p class="text-xs text-slate-400 max-w-md">Arrastra tus archivos XML en el recuadro superior o haz clic en "Agregar gasto sin factura" para comenzar a capturar.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- FOOTER / TOTALES EN VIVO --}}
            <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                
                {{-- MÉTRICAS EN VIVO --}}
                <div class="flex flex-wrap items-center gap-6">
                    <div>
                        <span class="text-xs uppercase font-bold text-slate-400">Gastos capturados</span>
                        <p class="text-lg font-bold text-slate-800" x-text="rows.length"></p>
                    </div>
                    <div class="border-l border-slate-200 pl-6">
                        <span class="text-xs uppercase font-bold text-green-600">Con factura</span>
                        <p class="text-lg font-bold text-green-700" x-text="'$' + formatMoney(totalConFactura)"></p>
                    </div>
                    <div class="border-l border-slate-200 pl-6">
                        <span class="text-xs uppercase font-bold text-purple-600">Sin factura</span>
                        <p class="text-lg font-bold text-purple-700" x-text="'$' + formatMoney(totalSinFactura)"></p>
                    </div>
                    <div class="border-l border-slate-200 pl-6">
                        <span class="text-xs uppercase font-bold text-[#0B265A]">Total General</span>
                        <p class="text-2xl font-black text-[#0B265A]" x-text="'$' + formatMoney(totalGeneral)"></p>
                    </div>
                </div>

                {{-- BOTONES DE GUARDADO / ENVÍO --}}
                <div class="flex items-center gap-3">
                    <button type="submit" 
                            @click="formAction = 'borrador'" 
                            :disabled="rows.length === 0 || isSubmitting"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 transition-all">
                        Guardar borradores
                    </button>

                    <button type="submit" 
                            @click="formAction = 'enviar'" 
                            :disabled="rows.length === 0 || isSubmitting"
                            class="rounded-lg bg-[#0B265A] px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-900 disabled:opacity-50 transition-all flex items-center gap-2">
                        <span>🚀 Guardar y Enviar a revisión</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- CONTENEDOR OCULTO PARA SUBIR LOS ARCHIVOS XML RECTIFICADOS --}}
        <div x-ref="hiddenXmlContainer" class="hidden"></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function reposicionCajaChicaExcel() {
    return {
        categorias: {!! json_encode($categorias->map(fn($c) => [
            'id' => $c->id,
            'codigo' => $c->codigo,
            'nombre' => $c->nombre,
            'requiere_factura' => (bool) $c->requiere_factura,
            'forma_pago_base' => $c->forma_pago_base ?: 'efectivo',
        ])->values()) !!},
        
        subcategorias: {!! json_encode($subcategorias->map(fn($s) => [
            'id' => $s->id,
            'categoria_id' => $s->categoria_id,
            'nombre' => $s->nombre,
        ])->values()) !!},

        obras: {!! json_encode($obras->map(fn($o) => ['id' => $o->id, 'nombre' => $o->nombre, 'estatus_nuevo' => (int) $o->estatus_nuevo])->values()) !!},
        almacenes: {!! json_encode($almacenes->map(fn($a) => ['id' => $a->id, 'nombre' => $a->nombre])->values()) !!},

        rows: [],
        dragover: false,
        loadingXml: false,
        xmlErrores: [],
        targetDestino: @json(old('target_destino', 'obra')),
        targetObraId: @json(old('target_obra_id', '')),
        targetAlmacenId: @json(old('target_almacen_id', '')),
        formAction: 'borrador',
        isSubmitting: false,

        init() {
            if (!this.targetObraId && this.obras.length > 0) {
                this.targetObraId = this.obras[0].id;
            }
            if (!this.targetAlmacenId && this.almacenes.length > 0) {
                this.targetAlmacenId = this.almacenes[0].id;
            }

            // Si hay datos anteriores de sesión (old input tras error de validación)
            @if(old('gastos'))
                const oldGastos = {!! json_encode(old('gastos')) !!};
                if (Array.isArray(oldGastos)) {
                    oldGastos.forEach(g => {
                        const cat = this.categorias.find(c => c.id == g.categoria_id) || this.categorias[0];
                        this.rows.push({
                            uid: 'row_' + Math.random().toString(36).substr(2, 9),
                            id: g.id || '',
                            categoria_id: g.categoria_id ? String(g.categoria_id) : String(cat.id),
                            requiere_factura: cat ? cat.requiere_factura : false,
                            subcategoria_id: g.subcategoria_id || '',
                            destino: g.destino || this.targetDestino,
                            obra_id: g.obra_id || (this.targetDestino === 'obra' ? this.targetObraId : ''),
                            almacen_id: g.almacen_id || (this.targetDestino === 'almacen' ? this.targetAlmacenId : ''),
                            fecha_gasto: g.fecha_gasto || new Date().toISOString().slice(0, 10),
                            proveedor_nombre: g.proveedor_nombre || '',
                            proveedor_rfc: g.proveedor_rfc || '',
                            concepto: g.concepto || '',
                            forma_pago: g.forma_pago || (cat ? cat.forma_pago_base : 'efectivo'),
                            importe_registrado: parseFloat(g.importe_registrado) || 0,
                            motivo_sin_factura: g.motivo_sin_factura || '',
                            xml_file_name: '',
                            evidencia_count: 0,
                        });
                    });
                }
            @endif
        },

        getSubcategorias(categoriaId) {
            return this.subcategorias.filter(s => s.categoria_id == categoriaId);
        },

        onCategoriaChange(row) {
            const cat = this.categorias.find(c => c.id == row.categoria_id);
            if (cat) {
                row.requiere_factura = cat.requiere_factura;
                if (cat.forma_pago_base && !row.forma_pago) {
                    row.forma_pago = cat.forma_pago_base;
                }
                const subs = this.getSubcategorias(cat.id);
                if (subs.length > 0 && !subs.some(s => s.id == row.subcategoria_id)) {
                    row.subcategoria_id = subs[0].id;
                }
            }
        },

        onTargetDestinoChange() {
            this.syncTargetToAllRows();
        },

        syncTargetToAllRows() {
            this.rows.forEach(r => {
                r.destino = this.targetDestino;
                if (this.targetDestino === 'obra') {
                    r.obra_id = this.targetObraId;
                    r.almacen_id = '';
                } else {
                    r.almacen_id = this.targetAlmacenId;
                    r.obra_id = '';
                }
            });
        },

        addManualRow(codigoTipo = 'sin_factura_viaticos') {
            const cat = this.categorias.find(c => c.codigo === codigoTipo) || this.categorias.find(c => !c.requiere_factura) || this.categorias[0];
            const subs = cat ? this.getSubcategorias(cat.id) : [];
            
            this.rows.push({
                uid: 'row_' + Math.random().toString(36).substr(2, 9),
                id: '',
                categoria_id: cat ? String(cat.id) : '',
                requiere_factura: cat ? cat.requiere_factura : false,
                subcategoria_id: subs.length > 0 ? subs[0].id : '',
                destino: this.targetDestino,
                obra_id: this.targetDestino === 'obra' ? this.targetObraId : '',
                almacen_id: this.targetDestino === 'almacen' ? this.targetAlmacenId : '',
                fecha_gasto: new Date().toISOString().slice(0, 10),
                proveedor_nombre: '',
                proveedor_rfc: '',
                concepto: '',
                forma_pago: cat ? cat.forma_pago_base : 'efectivo',
                importe_registrado: 0,
                motivo_sin_factura: '',
                xml_file_name: '',
                evidencia_count: 0,
            });
        },

        removeRow(index) {
            this.rows.splice(index, 1);
        },

        handleXmlDrop(e) {
            const files = Array.from(e.dataTransfer.files).filter(f => f.name.toLowerCase().endsWith('.xml'));
            if (files.length > 0) {
                this.uploadAndParseXmls(files);
            }
        },

        handleXmlSelect(e) {
            const files = Array.from(e.target.files);
            if (files.length > 0) {
                this.uploadAndParseXmls(files);
            }
            e.target.value = '';
        },

        async uploadAndParseXmls(files) {
            this.loadingXml = true;
            this.xmlErrores = [];

            const formData = new FormData();
            files.forEach(file => {
                formData.append('xml_files[]', file);
            });

            try {
                const response = await fetch('{{ route("reposicion-caja-chica.parse-xml") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const res = await response.json();

                if (res.errores && res.errores.length > 0) {
                    this.xmlErrores = res.errores;
                }

                if (res.ok && res.data) {
                    res.data.forEach((cfdi, i) => {
                        const sourceFile = files[cfdi.file_index ?? i];
                        const formaPagoSat = String(cfdi.forma_pago_sat || '').padStart(2, '0');
                        const categoriasPorFormaPago = {
                            efectivo: 'efectivo_factura',
                            tarjeta: 'tarjeta_factura',
                        };

                        if (cfdi.forma_pago === 'transferencia' || formaPagoSat === '03') {
                            this.xmlErrores.push((cfdi.filename || sourceFile?.name || 'XML') + ': Forma de pago SAT 03 transferencia. Este comprobante corresponde a orden de compra / pago a proveedores, no a caja chica.');
                            return;
                        }

                        const codigoCat = categoriasPorFormaPago[cfdi.forma_pago];
                        if (!codigoCat) {
                            this.xmlErrores.push((cfdi.filename || sourceFile?.name || 'XML') + ': Forma de pago SAT no permitida o no reconocida para caja chica. Solo se aceptan efectivo y tarjeta.');
                            return;
                        }

                        const cat = this.categorias.find(c => c.codigo === codigoCat);
                        if (!cat) {
                            this.xmlErrores.push((cfdi.filename || sourceFile?.name || 'XML') + ': No existe una categoría activa para ' + codigoCat + '.');
                            return;
                        }

                        const subs = this.getSubcategorias(cat.id);

                        const newRowIndex = this.rows.length;

                        this.rows.push({
                            uid: 'row_' + Math.random().toString(36).substr(2, 9),
                            id: '',
                            categoria_id: cat ? String(cat.id) : '',
                            requiere_factura: true,
                            subcategoria_id: subs.length > 0 ? subs[0].id : '',
                            destino: this.targetDestino,
                            obra_id: this.targetDestino === 'obra' ? this.targetObraId : '',
                            almacen_id: this.targetDestino === 'almacen' ? this.targetAlmacenId : '',
                            fecha_gasto: cfdi.fecha || new Date().toISOString().slice(0, 10),
                            proveedor_nombre: cfdi.emisor_nombre || '',
                            proveedor_rfc: cfdi.emisor_rfc || '',
                            concepto: cfdi.concepto || 'Gasto con factura',
                            forma_pago: cfdi.forma_pago || 'efectivo',
                            importe_registrado: parseFloat(cfdi.total) || 0,
                            motivo_sin_factura: '',
                            xml_file_name: cfdi.filename || sourceFile?.name || 'factura.xml',
                            evidencia_count: 0,
                        });

                        // Adjuntar el archivo XML real al form
                        if (sourceFile) {
                            this.attachXmlToForm(sourceFile, newRowIndex);
                        }
                    });
                }
            } catch (err) {
                this.xmlErrores.push('Error al conectar con el servidor: ' + err.message);
            } finally {
                this.loadingXml = false;
            }
        },

        attachXmlToForm(file, rowIndex) {
            const container = this.$refs.hiddenXmlContainer;
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.name = `xml_files[${rowIndex}]`;
            fileInput.files = dataTransfer.files;
            container.appendChild(fileInput);
        },

        onEvidenciaChange(e, row) {
            row.evidencia_count = e.target.files.length;
        },

        get totalGeneral() {
            return this.rows.reduce((sum, r) => sum + (parseFloat(r.importe_registrado) || 0), 0);
        },

        get totalConFactura() {
            return this.rows
                .filter(r => r.requiere_factura)
                .reduce((sum, r) => sum + (parseFloat(r.importe_registrado) || 0), 0);
        },

        get totalSinFactura() {
            return this.rows
                .filter(r => !r.requiere_factura)
                .reduce((sum, r) => sum + (parseFloat(r.importe_registrado) || 0), 0);
        },

        formatMoney(amount) {
            return (parseFloat(amount) || 0).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }
    };
}
</script>
@endpush








