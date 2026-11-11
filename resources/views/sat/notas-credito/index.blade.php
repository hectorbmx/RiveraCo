@extends('layouts.admin')

@section('title', 'Notas de crédito')

@section('content')
<div x-data="{
    modalAbierto: {{ $errors->any() ? 'true' : 'false' }}, motivo: {{ Illuminate\Support\Js::from(old('motivo', '')) }}, empresaId: {{ Illuminate\Support\Js::from((string) old('sat_empresa_id', '')) }}, clienteSeleccionado: {{ Illuminate\Support\Js::from((string) old('cliente_id', '')) }}, uuidFactura: {{ Illuminate\Support\Js::from(old('uuid_relacionado', '')) }}, moneda: {{ Illuminate\Support\Js::from(old('moneda', 'MXN')) }},
    borradorId: {{ Illuminate\Support\Js::from((string) old('borrador_id', '')) }}, tipoRelacion: {{ Illuminate\Support\Js::from(old('tipo_relacion', '')) }},
    urlGuardar: {{ Illuminate\Support\Js::from(old('borrador_id') ? route('sat.notas-credito.borradores.update', old('borrador_id')) : route('sat.notas-credito.borradores.store')) }},
    consultaFactura: '', facturas: [], buscandoFacturas: false, errorFacturas: '', secuenciaFactura: 0,
    subtotal: {{ Illuminate\Support\Js::from((string) old('subtotal', '')) }}, tipoIva: {{ Illuminate\Support\Js::from(old('tipo_iva', '0.16')) }}, importeEnEdicion: false,
    dinero(valor) {
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(valor) || 0);
    },
    actualizarSubtotal(evento) {
        let limpio = evento.target.value.replace(/[^0-9.]/g, '');
        const partes = limpio.split('.');
        limpio = partes[0] + (partes.length > 1 ? '.' + partes.slice(1).join('').slice(0, 2) : '');
        evento.target.value = limpio;
        this.subtotal = limpio;
    },
    get iva() {
        const subtotalCentavos = Math.round((Number(this.subtotal) || 0) * 100);
        return ((this.tipoIva === '0.16' ? Math.round(subtotalCentavos * 0.16) : 0) / 100).toFixed(2);
    },
    get total() {
        const subtotalCentavos = Math.round((Number(this.subtotal) || 0) * 100);
        const ivaCentavos = Math.round((Number(this.iva) || 0) * 100);
        return ((subtotalCentavos + ivaCentavos) / 100).toFixed(2);
    },
    async buscarFacturas() {
        const secuencia = ++this.secuenciaFactura;
        this.facturas = []; this.errorFacturas = '';
        if (!this.empresaId || !this.clienteSeleccionado) { this.buscandoFacturas = false; return; }
        this.buscandoFacturas = true;
        const params = new URLSearchParams({ sat_empresa_id: this.empresaId, cliente_id: this.clienteSeleccionado });
        const abortador = new AbortController();
        const tiempoLimite = setTimeout(() => abortador.abort(), 15000);
        try {
            const respuesta = await fetch('{{ route('sat.notas-credito.facturas') }}?' + params.toString(), { headers: { Accept: 'application/json' }, signal: abortador.signal });
            if (!respuesta.ok) throw new Error();
            const resultado = await respuesta.json();
            if (secuencia === this.secuenciaFactura) this.facturas = resultado.data;
        } catch (error) {
            if (secuencia === this.secuenciaFactura) this.errorFacturas = 'No se pudieron consultar las facturas. Intenta nuevamente.';
        } finally {
            clearTimeout(tiempoLimite);
            if (secuencia === this.secuenciaFactura) this.buscandoFacturas = false;
        }
    },
    cambiarCliente(id) { this.clienteSeleccionado = id; this.uuidFactura = ''; this.buscarFacturas(); },
    seleccionarFactura(factura) { this.uuidFactura = factura.uuid; this.moneda = factura.moneda; },
    nuevoBorrador() {
        this.borradorId = ''; this.urlGuardar = {{ Illuminate\Support\Js::from(route('sat.notas-credito.borradores.store')) }};
        this.empresaId = ''; this.clienteSeleccionado = ''; this.uuidFactura = ''; this.motivo = ''; this.tipoRelacion = '';
        this.subtotal = ''; this.tipoIva = '0.16'; this.moneda = 'MXN'; this.consultaFactura = ''; this.facturas = [];
        this.$dispatch('nota-cliente-establecer', { id: '', nombre: '' });
        this.modalAbierto = true;
    },
    editarBorrador(datos) {
        this.borradorId = String(datos.id); this.urlGuardar = datos.url;
        this.empresaId = String(datos.empresa_id || ''); this.clienteSeleccionado = String(datos.cliente_id || '');
        this.uuidFactura = datos.uuid || ''; this.motivo = datos.motivo || ''; this.tipoRelacion = datos.tipo_relacion || '';
        this.subtotal = String(datos.subtotal || ''); this.tipoIva = datos.tipo_iva || ''; this.moneda = datos.moneda || 'MXN';
        this.consultaFactura = ''; this.$dispatch('nota-cliente-establecer', { id: this.clienteSeleccionado, nombre: datos.cliente || '' });
        this.buscarFacturas(); this.modalAbierto = true;
    },
    facturasFiltradas() {
        const q = this.consultaFactura.trim().toLowerCase();
        return this.facturas.filter(factura => [factura.uuid, factura.serie, factura.folio].some(valor => (valor || '').toLowerCase().includes(q)));
    }
}" x-init="if (empresaId && clienteSeleccionado) buscarFacturas()" @cliente-cambiado="cambiarCliente($event.detail)" @keydown.escape.window="modalAbierto = false" class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Notas de crédito</h1>
            <p class="text-sm text-slate-500">Consulta de comprobantes de egreso emitidos en SIRICO.</p>
        </div>
        <button type="button" @click="nuevoBorrador()"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            <span>+</span> Nueva nota de crédito
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <form action="{{ route('sat.notas-credito.index') }}" method="GET" class="mb-4 rounded-2xl bg-[#0B265A] p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-12 md:items-end">
            <div class="md:col-span-7">
                <label for="notas-q" class="mb-1 block text-sm font-medium text-white">Buscar</label>
                <input id="notas-q" type="search" name="q" value="{{ $busqueda }}" placeholder="Folio, UUID, cliente, RFC u obra"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
            </div>
            <div class="md:col-span-3">
                <label for="notas-estado" class="mb-1 block text-sm font-medium text-white">Estado</label>
                <select id="notas-estado" name="estado" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                    <option value="">Todos</option>
                    @foreach($estados as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($estado === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 md:col-span-2">
                <button type="submit" class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-amber-300">Filtrar</button>
                <a href="{{ route('sat.notas-credito.index') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#0B265A] text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-5 py-4 text-left">Folio / UUID</th>
                        <th class="px-5 py-4 text-left">Fecha</th>
                        <th class="px-5 py-4 text-left">Cliente / RFC</th>
                        <th class="px-5 py-4 text-left">Obra</th>
                        <th class="px-5 py-4 text-left">CFDI relacionados</th>
                        <th class="px-5 py-4 text-right">Total</th>
                        <th class="px-5 py-4 text-left">Estado</th>
                        <th class="px-5 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($notas as $nota)
                        @php
                            $esBorrador = $nota instanceof \App\Models\SatFacturaBorrador;
                            $datosBorrador = $esBorrador ? ($nota->payload ?? []) : [];
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">{{ $esBorrador ? 'Borrador #' . $nota->id : (trim(($nota->serie ? $nota->serie . '-' : '') . ($nota->folio ?: '')) ?: 'Sin folio') }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $esBorrador ? 'Sin UUID' : ($nota->uuid ?: 'Sin UUID') }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">{{ ($esBorrador ? $nota->created_at : ($nota->fecha_emision ?: $nota->created_at))?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <div>{{ $nota->receptor_nombre ?: ($nota->cliente?->razon_social ?: ($nota->cliente?->nombre_comercial ?: 'Sin cliente')) }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $nota->receptor_rfc ?: ($nota->cliente?->rfc ?: '—') }}</div>
                            </td>
                            <td class="px-5 py-4">{{ $nota->obra?->nombre ?: '—' }}</td>
                            <td class="px-5 py-4">
                                @if($esBorrador)
                                    @php $relacionUuid = $datosBorrador['uuid_relacionado'] ?? ''; $origen = $foliosRelacionados->get(strtoupper($relacionUuid)); @endphp
                                    <div class="font-semibold text-slate-800">{{ $origen ? (trim(($origen->serie ? $origen->serie.'-' : '').($origen->folio ?: '')) ?: 'Sin folio') : 'Folio no disponible' }}</div>
                                    <div class="text-xs text-slate-500">{{ $relacionUuid ?: '—' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ ['anticipo' => 'Aplicación de anticipo', 'descuento' => 'Descuento o bonificación', 'devolucion' => 'Devolución'][$datosBorrador['motivo'] ?? ''] ?? '' }}</div>
                                @else
                                @forelse($relaciones->get($nota->id, collect()) as $relacion)
                                    @php $origen = $foliosRelacionados->get(strtoupper($relacion->uuid_relacionado)); @endphp
                                    <div class="font-semibold text-slate-800">{{ $origen ? (trim(($origen->serie ? $origen->serie.'-' : '').($origen->folio ?: '')) ?: 'Sin folio') : 'Folio no disponible' }}</div>
                                    <div class="text-xs text-slate-500">{{ $relacion->uuid_relacionado }}</div>
                                @empty
                                    <span class="text-slate-400">Sin relación registrada</span>
                                @endforelse
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap font-semibold">
                                @if($esBorrador && !isset($datosBorrador['total']))
                                    <span class="text-xs font-normal text-slate-500">IVA pendiente</span>
                                @else
                                    ${{ number_format((float) ($esBorrador ? $datosBorrador['total'] : $nota->total), 2) }} <span class="text-xs font-normal text-slate-500">{{ $esBorrador ? ($datosBorrador['moneda'] ?? '') : $nota->moneda }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $estados[$nota->estado] ?? ucfirst(str_replace('_', ' ', $nota->estado)) }}</span></td>
                            <td class="px-5 py-4">
                                @if($esBorrador && $nota->estado === 'borrador' && (auth()->user()?->hasRole('super-admin') || $nota->user_id === null || (int) $nota->user_id === (int) auth()->id()))
                                    <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                        <button type="button" class="font-semibold text-blue-700 hover:underline"
                                            @click="editarBorrador({{ Illuminate\Support\Js::from([
                                                'id' => $nota->id, 'url' => route('sat.notas-credito.borradores.update', $nota),
                                                'empresa_id' => $nota->sat_empresa_id, 'cliente_id' => $nota->cliente_id,
                                                'cliente' => $nota->cliente?->razon_social ?: $nota->cliente?->nombre_comercial,
                                                'uuid' => $datosBorrador['uuid_relacionado'] ?? '',
                                                'motivo' => $datosBorrador['motivo'] ?? '', 'tipo_relacion' => $datosBorrador['tipo_relacion'] ?? '',
                                                'subtotal' => $datosBorrador['subtotal'] ?? '', 'tipo_iva' => $datosBorrador['tipo_iva'] ?? '',
                                                'moneda' => $datosBorrador['moneda'] ?? 'MXN',
                                            ]) }})">Editar</button>
                                        <a href="{{ route('sat.notas-credito.borradores.preview', $nota) }}" class="font-semibold text-blue-700 hover:underline">Previsualizar</a>
                                        <form method="POST" action="{{ route('sat.notas-credito.borradores.destroy', $nota) }}" onsubmit="return confirm('¿Eliminar este borrador de nota de crédito? Esta acción no se puede deshacer.');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="font-semibold text-red-700 hover:underline">Borrar</button>
                                        </form>
                                        @can('sat.cfdi_autoriza.access')
                                            <form method="POST" action="{{ route('sat.notas-credito.borradores.timbrar', $nota) }}" onsubmit="return confirm('¿Timbrar esta nota de crédito en Facturapi? Revisa la previsualización antes de continuar.');">
                                                @csrf
                                                <button type="submit" class="font-semibold text-emerald-700 hover:underline">Timbrar</button>
                                            </form>
                                        @endcan
                                    </div>
                                @elseif($esBorrador && $nota->estado === 'pendiente_facturapi' && (auth()->user()?->hasRole('super-admin') || $nota->user_id === null || (int) $nota->user_id === (int) auth()->id()))
                                    @can('sat.cfdi_autoriza.access')
                                        <form method="POST" action="{{ route('sat.notas-credito.borradores.consultar', $nota) }}" class="text-right">
                                            @csrf
                                            <button type="submit" class="font-semibold text-blue-700 hover:underline">Consultar estado</button>
                                        </form>
                                    @endcan
                                @elseif(!$esBorrador)
                                    <div class="flex justify-end gap-2">
                                    <a href="{{ route('sat.notas-credito.show', $nota) }}" title="Detalles" aria-label="Detalles de la nota de crédito" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-700 hover:bg-blue-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    @if($nota->estado === 'timbrada')
                                    <a href="{{ Illuminate\Support\Facades\URL::temporarySignedRoute('sat.notas-credito.zip', now()->addMinutes(30), ['factura' => $nota->id]) }}" title="Descargar ZIP" aria-label="Descargar XML y PDF en ZIP" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-indigo-700 hover:bg-indigo-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3"/></svg>
                                    </a>
                                    @endif
                                    </div>
                                @else
                                    <span class="block text-right text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">No se encontraron notas de crédito.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($notas->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $notas->links() }}</div>
        @endif
    </div>

    <div x-cloak x-show="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4"
        role="dialog" aria-modal="true" aria-labelledby="nota-credito-modal-titulo" @click.self="modalAbierto = false">
        <div class="my-auto w-full max-w-3xl rounded-2xl bg-white shadow-xl">
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 id="nota-credito-modal-titulo" class="text-lg font-bold text-slate-900" x-text="borradorId ? 'Editar nota de crédito' : 'Nueva nota de crédito'"></h2>
                    <p class="mt-1 text-sm text-slate-500">Datos del comprobante de egreso.</p>
                </div>
                <button type="button" @click="modalAbierto = false" aria-label="Cerrar" class="rounded-lg px-2 text-2xl text-slate-500 hover:bg-slate-100">&times;</button>
            </div>
            <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
                @if($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <form id="nota-credito-form" method="POST" :action="urlGuardar" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @csrf
                    <input type="hidden" name="_method" :value="borradorId ? 'PUT' : 'POST'">
                    <input type="hidden" name="borrador_id" :value="borradorId">
                    <div>
                        <label for="nota-empresa" class="mb-1 block text-sm font-medium text-slate-700">Empresa emisora</label>
                        <select id="nota-empresa" name="sat_empresa_id" x-model="empresaId" @change="uuidFactura = ''; buscarFacturas()" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Selecciona una empresa</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}">{{ $empresa->nombre }} · {{ $empresa->rfc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative" x-data="{
                        clienteId: {{ Illuminate\Support\Js::from((string) old('cliente_id', '')) }},
                        busqueda: {{ Illuminate\Support\Js::from(($clientes->firstWhere('id', old('cliente_id'))?->razon_social ?? $clientes->firstWhere('id', old('cliente_id'))?->nombre_comercial) ?: '') }},
                        abierto: false,
                        clientes: {{ Illuminate\Support\Js::from($clientes->map(fn ($cliente) => [
                            'id' => (string) $cliente->id,
                            'nombre' => $cliente->razon_social ?: $cliente->nombre_comercial ?: 'Sin nombre',
                            'comercial' => $cliente->nombre_comercial ?: '',
                            'rfc' => $cliente->rfc ?: '',
                        ])) }},
                        normalizar(texto) { return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); },
                        resultados() {
                            const termino = this.normalizar(this.busqueda.trim());
                            return this.clientes.filter(cliente => this.normalizar([cliente.nombre, cliente.comercial, cliente.rfc].join(' ')).includes(termino)).slice(0, 20);
                        },
                        seleccionar(cliente) {
                            this.clienteId = cliente.id;
                            this.busqueda = cliente.nombre + (cliente.rfc ? ' · ' + cliente.rfc : '');
                            this.abierto = false;
                            this.$dispatch('cliente-cambiado', cliente.id);
                        }
                    }" @nota-cliente-establecer.window="clienteId = $event.detail.id; busqueda = $event.detail.nombre; abierto = false" @click.outside="abierto = false" @keydown.escape.stop="abierto = false">
                        <label for="nota-cliente" class="mb-1 block text-sm font-medium text-slate-700">Cliente</label>
                        <input type="hidden" name="cliente_id" x-model="clienteId">
                        <input id="nota-cliente" type="search" x-model="busqueda" @focus="abierto = true"
                            @input="clienteId = ''; abierto = true; $dispatch('cliente-cambiado', '')" autocomplete="off"
                            placeholder="Buscar por razón social, nombre o RFC"
                            :aria-expanded="abierto.toString()" aria-controls="nota-clientes-resultados"
                            class="w-full rounded-lg border-slate-300 text-sm">
                        <div id="nota-clientes-resultados" x-cloak x-show="abierto"
                            class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                            <template x-for="cliente in resultados()" :key="cliente.id">
                                <button type="button" @click="seleccionar(cliente)" class="block w-full px-4 py-3 text-left text-sm hover:bg-slate-50 focus:bg-slate-50">
                                    <span class="block font-medium text-slate-900" x-text="cliente.nombre"></span>
                                    <span class="block text-xs text-slate-500" x-text="cliente.rfc"></span>
                                </button>
                            </template>
                            <div x-show="resultados().length === 0" class="px-4 py-3 text-sm text-slate-500">Sin coincidencias</div>
                        </div>
                    </div>
                    <div>
                        <label for="nota-motivo" class="mb-1 block text-sm font-medium text-slate-700">Motivo</label>
                        <select id="nota-motivo" name="motivo" x-model="motivo" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Selecciona un motivo</option>
                            <option value="anticipo">Aplicación de anticipo</option>
                            <option value="descuento">Descuento o bonificación</option>
                            <option value="devolucion">Devolución</option>
                        </select>
                    </div>
                    <div>
                        <label for="nota-relacion" class="mb-1 block text-sm font-medium text-slate-700">Tipo de relación SAT</label>
                        <select id="nota-relacion" name="tipo_relacion" x-model="tipoRelacion" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Selecciona una relación</option>
                            <option value="07" @selected(old('tipo_relacion') === '07')>07 · Aplicación de anticipo</option>
                            <option value="01" @selected(old('tipo_relacion') === '01')>01 · Nota de crédito de los documentos relacionados</option>
                            <option value="03" @selected(old('tipo_relacion') === '03')>03 · Devolución de mercancía</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="nota-uuid" class="mb-1 block text-sm font-medium text-slate-700">UUID de la factura relacionada</label>
                        <input id="nota-uuid" name="uuid_relacionado" type="text" x-model="uuidFactura" readonly placeholder="Selecciona una factura de la tabla" class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm">
                        <p x-show="motivo === 'anticipo'" class="mt-1 text-xs text-slate-500">Para aplicación de anticipo, corresponde a la factura de la estimación.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="buscar-factura-nota" class="mb-1 block text-sm font-medium text-slate-700">Buscar factura de ingreso</label>
                        <input id="buscar-factura-nota" type="search" x-model="consultaFactura"
                            placeholder="Folio o UUID" class="w-full rounded-lg border-slate-300 text-sm">
                        <p class="mt-1 text-xs text-slate-500">Selecciona empresa y cliente; haz doble clic en la factura o usa el botón Seleccionar.</p>
                        <div class="mt-2 max-h-52 overflow-auto rounded-xl border border-slate-200">
                            <p x-show="!empresaId || !clienteSeleccionado" class="px-4 py-4 text-sm text-slate-500">Selecciona una empresa y un cliente para consultar sus facturas.</p>
                            <p x-show="buscandoFacturas" x-cloak class="px-4 py-4 text-sm text-slate-500">Buscando facturas...</p>
                            <p x-show="errorFacturas" x-cloak x-text="errorFacturas" class="px-4 py-4 text-sm text-red-700"></p>
                            <p x-show="empresaId && clienteSeleccionado && !buscandoFacturas && !errorFacturas && facturasFiltradas().length === 0" x-cloak class="px-4 py-4 text-sm text-slate-500">No se encontraron facturas de ingreso vigentes.</p>
                            <table x-show="!buscandoFacturas && facturasFiltradas().length > 0" x-cloak class="w-full text-left text-sm">
                                <thead class="sticky top-0 bg-[#0B265A] text-xs uppercase text-white"><tr>
                                    <th class="px-3 py-2">Factura / fecha</th><th class="px-3 py-2">UUID</th><th class="px-3 py-2 text-right">Total</th><th class="px-3 py-2"><span class="sr-only">Acción</span></th>
                                </tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="factura in facturasFiltradas()" :key="factura.uuid">
                                        <tr @dblclick="seleccionarFactura(factura)" :class="uuidFactura === factura.uuid ? 'bg-blue-50' : 'hover:bg-slate-50'" class="cursor-pointer">
                                            <td class="px-3 py-2"><span class="font-semibold" x-text="[factura.serie, factura.folio].filter(Boolean).join('-') || 'Sin folio'"></span><span class="block text-xs text-slate-500" x-text="(factura.fecha_formateada || '—') + ' · ' + factura.source_label"></span></td>
                                            <td class="px-3 py-2 text-xs" x-text="factura.uuid"></td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap" x-text="Number(factura.total).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + factura.moneda"></td>
                                            <td class="px-3 py-2"><button type="button" @click="seleccionarFactura(factura)" class="font-semibold text-indigo-700 hover:underline" x-text="uuidFactura === factura.uuid ? 'Seleccionada' : 'Seleccionar'"></button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Se muestran facturas de ingreso timbradas en SIRICO para la empresa y cliente seleccionados.</p>
                    </div>
                    <div class="sm:col-span-2 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="nota-importe" class="mb-1 block text-sm font-medium text-slate-700">Importe antes de impuestos</label>
                        <input type="hidden" name="subtotal" :value="subtotal">
                        <input id="nota-importe" type="text" inputmode="decimal" autocomplete="off" required
                            :value="importeEnEdicion || subtotal === '' ? subtotal : dinero(subtotal)"
                            @focus="importeEnEdicion = true" @blur="importeEnEdicion = false" @input="actualizarSubtotal($event)"
                            placeholder="$0.00" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label for="nota-tipo-iva" class="mb-1 block text-sm font-medium text-slate-700">IVA</label>
                        <select id="nota-tipo-iva" name="tipo_iva" x-model="tipoIva" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Selecciona el IVA</option>
                            <option value="0.16">IVA 16%</option>
                            <option value="exento">Exento (sin traslado)</option>
                            <option value="sin_iva">Sin IVA (no objeto)</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Importe de IVA: <span x-text="dinero(iva)"></span></p>
                    </div>
                    <div>
                        <label for="nota-total" class="mb-1 block text-sm font-medium text-slate-700">Total</label>
                        <input id="nota-total" type="text" :value="dinero(total)" readonly class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm">
                    </div>
                    <div>
                        <label for="nota-moneda" class="mb-1 block text-sm font-medium text-slate-700">Moneda</label>
                        <input id="nota-moneda" name="moneda" type="text" maxlength="3" x-model="moneda" readonly class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm">
                    </div>
                    </div>
                </form>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
                <button type="button" @click="modalAbierto = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cerrar</button>
                <button type="submit" form="nota-credito-form" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Guardar borrador</button>
            </div>
        </div>
    </div>
</div>
@endsection
