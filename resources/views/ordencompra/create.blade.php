@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-4xl">
    <h1 class="text-xl font-semibold mb-4">Nueva orden de compra</h1>

    <form method="POST" action="{{ route('ordenes_compra.store') }}" class="space-y-4" data-loading-form data-loading-message="Creando orden de compra...">
        @csrf

        <div class="flex flex-wrap items-center gap-3">
            <label class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">
                <input type="checkbox" name="es_caja_chica" value="1" class="rounded border-amber-300" @checked(old('es_caja_chica'))>
                Orden de caja chica
            </label>

            <label class="inline-flex items-center gap-2 rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-sm font-medium text-purple-800">
                <input type="checkbox" name="gastos_sin_factura" value="1" class="rounded border-purple-300" @checked(old('gastos_sin_factura'))>
                Gastos sin factura
            </label>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="relative">
    <label class="block text-sm font-medium mb-1">Proveedor</label>

    {{-- ID real que se envía en el form --}}
    <input type="hidden" name="proveedor_id" id="proveedor_id" value="{{ old('proveedor_id') }}">
    <input type="hidden" name="proveedor_texto" id="proveedor_texto" value="{{ old('proveedor_texto') }}">

    {{-- Input visible --}}
    <input
        type="text"
        id="proveedor_busqueda"
        class="w-full border p-2 rounded"
        placeholder="Proveedor opcional si es caja chica / sin factura..."
        autocomplete="off"
        value="{{ old('proveedor_texto') }}"
    >

    {{-- Lista de resultados --}}
    <div
        id="proveedor_resultados"
        class="absolute left-0 top-full z-50 mt-1 w-full bg-white border rounded shadow hidden max-h-64 overflow-auto"
    ></div>

    @error('proveedor_id')
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>




            <div>
                <label>Área</label>
                <select name="area_id" class="w-full border p-2">
                    @foreach($areas as $a)
                        <option value="{{ $a->id }}" {{ (old('area_id', $selectedAreaId ?? null) == $a->id) ? 'selected' : '' }}>{{ $a->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- <div>
                <label>Obra</label>
                <select name="obra_id" class="w-full border p-2">
                    <option value="">Compra general</option>
                    @foreach($obras as $o)
                        <option value="{{ $o->id }}">{{ $o->nombre }}</option>
                    @endforeach
                </select>
            </div> -->
            {{-- Hidden para planeacion_gasto_id --}}
<input type="hidden" name="planeacion_gasto_id" id="planeacion_gasto_id" value="{{ old('planeacion_gasto_id') }}">
<input type="hidden" name="civil_partida_id" id="civil_partida_id" value="{{ old('civil_partida_id') }}">
 
<div>
    <label class="block text-sm font-medium mb-1">Obra</label>
    <select
        name="obra_id"
        id="obra_id"
        class="w-full border p-2 rounded"
        data-partidas-url="{{ route('ordenes_compra.partidas_obra', ['obra_id' => '__ID__']) }}"
        data-material-requests-url="{{ route('ordenes_compra.solicitudes_material_obra', ['obra' => '__ID__']) }}"
    >
        <option value="">Compra general</option>
        @foreach($obras as $o)
            <option value="{{ $o->id }}" {{ old('obra_id') == $o->id ? 'selected' : '' }}>
                {{ $o->nombre }}
            </option>
        @endforeach
    </select>
</div>

<div id="material_request_wrapper" class="col-span-2 hidden rounded-xl border border-blue-100 bg-blue-50/40 p-4">
    <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
        <div>
            <label class="block text-sm font-semibold text-slate-800">Materiales aprobados pendientes de OC</label>
            <p class="text-xs text-slate-500">Selecciona solo los renglones que iran con este proveedor. Puedes ajustar la cantidad a cargar.</p>
        </div>
        <span id="material_request_selected_count" class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700 shadow-sm">0 seleccionados</span>
    </div>

    <p id="material_requests_cargando" class="text-xs text-slate-400 mt-1 hidden">Cargando materiales aprobados…</p>
    <p id="material_requests_sin_datos" class="text-xs text-slate-400 mt-1 hidden">Esta obra no tiene materiales aprobados pendientes de OC.</p>

    <div id="material_request_table_wrapper" class="hidden overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-3 py-2 text-left">Usar</th>
                    <th class="px-3 py-2 text-left">Solicitud</th>
                    <th class="px-3 py-2 text-left">Codigo</th>
                    <th class="px-3 py-2 text-left">Material</th>
                    <th class="px-3 py-2 text-right">Autorizado</th>
                    <th class="px-3 py-2 text-right">OC borrador</th>
                    <th class="px-3 py-2 text-right">Pendiente</th>
                    <th class="px-3 py-2 text-right">A cargar</th>
                </tr>
            </thead>
            <tbody id="material_request_items_body" class="divide-y divide-slate-100"></tbody>
        </table>
    </div>

    @error('obra_civil_material_request_items')
        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
    @enderror
    @error('obra_civil_material_request_items.*.quantity')
        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
    @enderror
</div>
 
{{-- Select de partidas — se muestra solo cuando hay obra seleccionada --}}
<div>
    <label class="block text-sm font-medium mb-1">Centro de costo</label>
    <select name="centro_costo_id" id="centro_costo_id" class="w-full border p-2 rounded">
        <option value="">Sin centro de costo</option>
        @foreach($centrosCosto as $centro)
            <option value="{{ $centro->id }}" {{ old('centro_costo_id') == $centro->id ? 'selected' : '' }}>
                {{ $centro->codigo ? $centro->codigo . ' - ' : '' }}{{ $centro->nombre }}
            </option>
        @endforeach
    </select>
    @error('centro_costo_id')
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>

<div id="partidas_wrapper" class="{{ old('obra_id') ? '' : 'hidden' }}">
    <label class="block text-sm font-medium mb-1">Partida presupuestal</label>
    <select
        name="_partida_display"
        id="partida_select"
        class="w-full border p-2 rounded"
    >
        <option value="">— Selecciona una partida —</option>
    </select>
    <p id="partidas_cargando" class="text-xs text-slate-400 mt-1 hidden">Cargando partidas…</p>
    <p id="partidas_sin_datos" class="text-xs text-slate-400 mt-1 hidden">Esta obra no tiene partidas disponibles.</p>
</div>
 
{{-- ─────────────────────────────────────────────────────────────────────────
     Script — agregar dentro del @push('scripts') existente,
     DESPUÉS del script del buscador de proveedores.
     ───────────────────────────────────────────────────────────────────────── --}}
<script>
(function () {
    const obraSelect      = document.getElementById('obra_id');
    const partidasWrapper = document.getElementById('partidas_wrapper');
    const partidaSelect   = document.getElementById('partida_select');
    const hiddenPartida   = document.getElementById('planeacion_gasto_id');
    const hiddenCivilPartida = document.getElementById('civil_partida_id');
    const centroCostoSelect = document.getElementById('centro_costo_id');
    const msgCargando     = document.getElementById('partidas_cargando');
    const msgSinDatos     = document.getElementById('partidas_sin_datos');
 
    if (!obraSelect) return;
 
    // URL base: reemplazamos el placeholder __ID__ con el id real
    const urlBase = obraSelect.dataset.partidasUrl;
 
    function formatMonto(n) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2
        }).format(n);
    }
 
    function limpiarPartidas() {
        partidaSelect.innerHTML = '<option value="">— Selecciona una partida —</option>';
        hiddenPartida.value = '';
        if (hiddenCivilPartida) hiddenCivilPartida.value = '';
    }
 
    async function cargarPartidas(obraId) {
        limpiarPartidas();
        msgSinDatos.classList.add('hidden');
        msgCargando.classList.remove('hidden');
        partidasWrapper.classList.remove('hidden');
 
        try {
            const url = urlBase.replace('__ID__', obraId);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
 
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
 
            const partidas = await res.json();
 
            msgCargando.classList.add('hidden');
 
            if (!partidas.length) {
                msgSinDatos.classList.remove('hidden');
                return;
            }
 
            // Agrupamos por partida para el optgroup
            const grupos = {};
            partidas.forEach(p => {
                if (!grupos[p.partida]) grupos[p.partida] = [];
                grupos[p.partida].push(p);
            });
 
            Object.entries(grupos).forEach(([grupo, items]) => {
                const og = document.createElement('optgroup');
                og.label = grupo;
 
                items.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.dataset.disponible = p.disponible;
                    opt.dataset.source = p.source || 'planeacion';
 
                    const disponibleStr = formatMonto(p.disponible);
                    const topeStr       = formatMonto(p.tope);
                    const agotado       = p.disponible <= 0;
 
                    opt.textContent = `${p.concepto} | Disponible: ${disponibleStr} / Tope: ${topeStr}${agotado ? ' ⚠️ AGOTADO' : ''}`;
                    opt.disabled    = false; // dejamos seleccionar aunque esté agotado, el bloqueo es al autorizar
 
                    og.appendChild(opt);
                });
 
                partidaSelect.appendChild(og);
            });
 
            // Si venía un valor previo (old input), restaurarlo
            const oldPlaneacionVal = '{{ old('planeacion_gasto_id') }}';
            const oldCivilVal = '{{ old('civil_partida_id') }}';
            if (oldPlaneacionVal) {
                partidaSelect.value = oldPlaneacionVal;
                hiddenPartida.value = oldPlaneacionVal;
            } else if (oldCivilVal) {
                partidaSelect.value = oldCivilVal;
                if (hiddenCivilPartida) hiddenCivilPartida.value = oldCivilVal;
            }
 
        } catch (e) {
            msgCargando.classList.add('hidden');
            console.error('Error cargando partidas:', e);
        }
    }
 
    // Al cambiar la obra
    obraSelect.addEventListener('change', function () {
        const obraId = this.value;
        if (obraId && centroCostoSelect) {
            centroCostoSelect.value = '';
        }
 
        if (!obraId) {
            partidasWrapper.classList.add('hidden');
            limpiarPartidas();
            return;
        }
 
        cargarPartidas(obraId);
    });
 
    // Al cambiar la partida seleccionada
    partidaSelect.addEventListener('change', function () {
        const selectedOption = this.selectedOptions?.[0];
        const source = selectedOption?.dataset?.source || 'planeacion';

        hiddenPartida.value = source === 'planeacion' ? (this.value || '') : '';
        if (hiddenCivilPartida) {
            hiddenCivilPartida.value = source === 'civil' ? (this.value || '') : '';
        }
    });

    centroCostoSelect?.addEventListener('change', function () {
        if (!this.value) return;
        obraSelect.value = '';
        partidasWrapper.classList.add('hidden');
        limpiarPartidas();
    });
 
    // Si al cargar la página ya hay una obra seleccionada (old input / edit)
    if (obraSelect.value) {
        cargarPartidas(obraSelect.value);
    }
})();
</script>

<script>
(function () {
    const obraSelect = document.getElementById('obra_id');
    const wrapper = document.getElementById('material_request_wrapper');
    const body = document.getElementById('material_request_items_body');
    const tableWrapper = document.getElementById('material_request_table_wrapper');
    const msgCargando = document.getElementById('material_requests_cargando');
    const msgSinDatos = document.getElementById('material_requests_sin_datos');
    const selectedCount = document.getElementById('material_request_selected_count');
    const centroCostoSelect = document.getElementById('centro_costo_id');

    if (!obraSelect || !wrapper || !body) return;

    const urlBase = obraSelect.dataset.materialRequestsUrl;

    function numberFmt(value) {
        return Number(value || 0).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 4
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
    function commercialMetrics(item) {
        const request = item.commercial_request;
        const lines = Array.isArray(request?.items) ? request.items : [];
        const commercialTotal = Number(request?.total_commercial_quantity || lines.reduce((sum, line) => sum + Number(line.commercial_quantity || 0), 0));
        const convertedTotal = Number(request?.converted_quantity || item.approved_quantity || 0);
        const units = [...new Set(lines.map((line) => line.unidad_compra || 'PZA').filter(Boolean))];
        const commercialUnit = units.length === 1 ? units[0] : 'PZA';
        const hasCommercial = lines.length > 0 && commercialTotal > 0 && convertedTotal > 0;
        const toCommercial = (quantity) => hasCommercial ? (Number(quantity || 0) / convertedTotal) * commercialTotal : Number(quantity || 0);
        const toBase = (quantity) => hasCommercial ? (Number(quantity || 0) / commercialTotal) * convertedTotal : Number(quantity || 0);

        return { request, lines, commercialTotal, convertedTotal, commercialUnit, hasCommercial, toCommercial, toBase };
    }

    function commercialRequestSummary(item) {
        const metrics = commercialMetrics(item);

        if (!metrics.hasCommercial) return '';

        const lineText = metrics.lines.map((line) => {
            const qty = numberFmt(line.commercial_quantity);
            const buyUnit = escapeHtml(line.unidad_compra || 'PZA');
            const description = escapeHtml(line.descripcion || line.sku || 'Material comercial');

            return `${qty} ${buyUnit} ${description}`;
        }).join('<br>');

        return `<div class="mt-2 rounded-md border border-emerald-100 bg-emerald-50 px-2 py-1 text-[11px] leading-snug text-emerald-800">${lineText}</div>`;
    }

    function syncCommercialQuantity(row) {
        const commercialInput = row.querySelector('.js-material-request-commercial-quantity');
        const quantity = row.querySelector('.js-material-request-quantity');
        if (!commercialInput || !quantity) return;

        const commercialTotal = Number(commercialInput.dataset.commercialTotal || 0);
        const convertedTotal = Number(commercialInput.dataset.convertedTotal || 0);
        const value = Number(commercialInput.value || 0);
        const clamped = Math.min(Math.max(value, 0), commercialTotal);
        const converted = commercialTotal > 0 ? (clamped / commercialTotal) * convertedTotal : 0;

        quantity.value = converted.toFixed(4);
    }

    function syncIndexes() {
        let index = 0;
        body.querySelectorAll('tr[data-request-item-id]').forEach((row) => {
            const checkbox = row.querySelector('.js-material-request-check');
            const hiddenId = row.querySelector('.js-material-request-id');
            const quantity = row.querySelector('.js-material-request-quantity');
            const commercialQuantity = row.querySelector('.js-material-request-commercial-quantity');

            if (!checkbox?.checked) {
                hiddenId.disabled = true;
                quantity.disabled = true;
                quantity.removeAttribute('name');
                commercialQuantity?.setAttribute('disabled', 'disabled');
                row.classList.remove('bg-blue-50');
                return;
            }

            syncCommercialQuantity(row);
            hiddenId.disabled = false;
            quantity.disabled = false;
            commercialQuantity?.removeAttribute('disabled');
            hiddenId.name = `obra_civil_material_request_items[${index}][id]`;
            quantity.name = `obra_civil_material_request_items[${index}][quantity]`;
            row.classList.add('bg-blue-50');
            index++;
        });

        selectedCount.textContent = `${index} seleccionado${index === 1 ? '' : 's'}`;
    }

    function renderQuantityCell(value, unit, isCommercial = false) {
        const tone = isCommercial ? 'text-emerald-700' : 'text-slate-900';
        return `${numberFmt(value)}<div class="text-xs text-slate-400">${escapeHtml(unit)}</div>`;
    }

    function renderItems(items) {
        body.innerHTML = items.map((item) => {
            const requestItemId = Number(item.request_item_id || 0);
            const maxQuantity = Number(item.available_to_load_quantity || 0);
            const draftQuantity = Number(item.draft_quantity || 0);
            const unit = item.unidad || '';
            const metrics = commercialMetrics(item);
            const visibleUnit = metrics.hasCommercial ? metrics.commercialUnit : unit;
            const approvedVisible = metrics.hasCommercial ? metrics.toCommercial(item.approved_quantity) : Number(item.approved_quantity || 0);
            const draftVisible = metrics.hasCommercial ? metrics.toCommercial(draftQuantity) : draftQuantity;
            const maxVisible = metrics.hasCommercial ? metrics.toCommercial(maxQuantity) : maxQuantity;
            const draftBadge = draftVisible > 0
                ? `<div class="text-[11px] text-amber-600">Hay ${numberFmt(draftVisible)} ${escapeHtml(visibleUnit)} en borrador</div>`
                : '';
            const commercialSummary = commercialRequestSummary(item);
            const quantityInput = metrics.hasCommercial
                ? `<input type="number" min="0.0001" step="0.0001" max="${maxVisible}" value="${maxVisible}" class="js-material-request-commercial-quantity w-28 rounded border border-slate-300 p-2 text-right" data-commercial-total="${metrics.commercialTotal}" data-converted-total="${metrics.convertedTotal}" disabled>
                   <input type="hidden" class="js-material-request-quantity" max="${maxQuantity}" value="${maxQuantity}" disabled>
                   <div class="mt-1 text-xs text-slate-400">${escapeHtml(visibleUnit)}</div>`
                : `<input type="number" min="0.0001" step="0.0001" max="${maxQuantity}" value="${maxQuantity}" class="js-material-request-quantity w-28 rounded border border-slate-300 p-2 text-right" disabled>`;

            return `
                <tr data-request-item-id="${requestItemId}">
                    <td class="px-3 py-3 align-top">
                        <input type="checkbox" class="js-material-request-check rounded border-slate-300">
                        <input type="hidden" class="js-material-request-id" value="${requestItemId}" disabled>
                    </td>
                    <td class="px-3 py-3 align-top font-semibold text-blue-700">${escapeHtml(item.request_folio || '-')}</td>
                    <td class="px-3 py-3 align-top">${escapeHtml(item.codigo || '-')}</td>
                    <td class="px-3 py-3 align-top">
                        <div class="font-medium text-slate-800">${escapeHtml(item.concepto || 'Material')}</div>
                        <div class="text-xs text-slate-500">${escapeHtml(item.solicitante || 'Residente')}</div>
                        ${commercialSummary}
                        ${item.resident_notes ? `<div class="mt-1 text-xs text-slate-400">${escapeHtml(item.resident_notes)}</div>` : ''}
                    </td>
                    <td class="px-3 py-3 text-right align-top">${renderQuantityCell(approvedVisible, visibleUnit, metrics.hasCommercial)}</td>
                    <td class="px-3 py-3 text-right align-top">${renderQuantityCell(draftVisible, visibleUnit, metrics.hasCommercial)}${draftBadge}</td>
                    <td class="px-3 py-3 text-right align-top font-semibold text-emerald-700">${renderQuantityCell(maxVisible, visibleUnit, metrics.hasCommercial)}</td>
                    <td class="px-3 py-3 text-right align-top">
                        ${quantityInput}
                    </td>
                </tr>
            `;
        }).join('');

        body.querySelectorAll('.js-material-request-check').forEach((checkbox) => {
            checkbox.addEventListener('change', syncIndexes);
        });
        body.querySelectorAll('.js-material-request-quantity, .js-material-request-commercial-quantity').forEach((input) => {
            input.addEventListener('input', () => {
                const max = Number(input.max || 0);
                const value = Number(input.value || 0);
                if (value > max) input.value = max;
                syncIndexes();
            });
        });

        syncIndexes();
    }
    async function cargarSolicitudes(obraId) {
        resetSolicitudes();

        if (!obraId || !urlBase) {
            wrapper.classList.add('hidden');
            return;
        }

        wrapper.classList.remove('hidden');
        msgCargando?.classList.remove('hidden');

        try {
            const url = urlBase.replace('__ID__', obraId);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const payload = await res.json();
            const items = Array.isArray(payload.data) ? payload.data : [];

            msgCargando?.classList.add('hidden');

            if (!items.length) {
                msgSinDatos?.classList.remove('hidden');
                return;
            }

            tableWrapper?.classList.remove('hidden');
            renderItems(items);
        } catch (error) {
            msgCargando?.classList.add('hidden');
            msgSinDatos?.classList.remove('hidden');
            console.error('Error cargando materiales aprobados:', error);
        }
    }

    obraSelect.addEventListener('change', () => cargarSolicitudes(obraSelect.value));
    centroCostoSelect?.addEventListener('change', () => {
        if (!centroCostoSelect.value) return;
        resetSolicitudes();
        wrapper.classList.add('hidden');
    });

    if (obraSelect.value) {
        cargarSolicitudes(obraSelect.value);
    }
})();
</script>

            <div>
                <label>Fecha</label>
                <input type="date" name="fecha" class="w-full border p-2" value="{{ date('Y-m-d') }}">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Moneda</label>
                <select name="moneda" id="moneda_select" class="w-full border p-2 rounded">
                    <option value="MXN" @selected(old('moneda', 'MXN') === 'MXN')>MXN (Pesos)</option>
                    <option value="USD" @selected(old('moneda') === 'USD')>USD (Dólares)</option>
                    <option value="EUR" @selected(old('moneda') === 'EUR')>EUR (Euros)</option>
                </select>
                @error('moneda')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">IVA base (%)</label>
                <select name="iva" class="w-full border p-2 rounded">
                    @foreach($tiposIva as $tipo)
                        <option value="{{ (float) $tipo->porcentaje }}" @selected(old('iva', $tipo->default ? $tipo->porcentaje : null) == $tipo->porcentaje)>
                            {{ $tipo->nombre }} ({{ number_format((float) $tipo->porcentaje, 2) }}%)
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">
                    Tipo de cambio <span id="tc_required_label" class="text-xs text-red-600 font-bold {{ old('moneda', 'MXN') === 'MXN' ? 'hidden' : '' }}">* (Obligatorio para USD/EUR)</span>
                </label>
                <input type="number" step="0.0001" min="0.0001" name="tipo_cambio" id="tipo_cambio_input" class="w-full border p-2 rounded" value="{{ old('tipo_cambio', old('moneda', 'MXN') === 'MXN' ? '1' : '') }}" placeholder="Ej. 18.50">
                @error('tipo_cambio')
                    <p class="text-sm text-red-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label>Tipo de pago</label>
                <select name="tipo_pago" class="w-full border p-2">
                    <option value="">Selecciona metodo</option>
                    <option value="PUE">PUE - Pago en una sola exhibicion</option>
                    <option value="PPD">PPD - Pago en parcialidades o diferido</option>
                </select>
            </div>

            <div>
                <label>Forma de pago</label>
                <select name="forma_pago" class="w-full border p-2">
                    <option value="">Selecciona forma</option>
                    <option value="01">01 - Efectivo</option>
                    <option value="02">02 - Cheque nominativo</option>
                    <option value="03">03 - Transferencia electronica de fondos</option>
                    <option value="04">04 - Tarjeta de credito</option>
                    <option value="28">28 - Tarjeta de debito</option>
                    <option value="99">99 - Por definir</option>
                </select>
            </div>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Crear orden
        </button>
    </form>
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

    const monedaSelect = document.getElementById('moneda_select');
    const tcInput = document.getElementById('tipo_cambio_input');
    const tcLabel = document.getElementById('tc_required_label');

    function syncTipoCambio() {
        if (!monedaSelect || !tcInput) return;
        const isMxn = monedaSelect.value === 'MXN';
        if (!isMxn) {
            tcLabel?.classList.remove('hidden');
            tcInput.setAttribute('required', 'required');
            tcInput.classList.add('border-amber-400', 'bg-amber-50');
            if (tcInput.value === '1' || tcInput.value === '1.0000') {
                tcInput.value = '';
            }
        } else {
            tcLabel?.classList.add('hidden');
            tcInput.removeAttribute('required');
            tcInput.classList.remove('border-amber-400', 'bg-amber-50');
            if (!tcInput.value || parseFloat(tcInput.value) <= 0) {
                tcInput.value = '1';
            }
        }
    }

    monedaSelect?.addEventListener('change', syncTipoCambio);
    syncTipoCambio();

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
        form.addEventListener('submit', (e) => {
            if (monedaSelect && tcInput && monedaSelect.value !== 'MXN') {
                const val = parseFloat(tcInput.value);
                if (isNaN(val) || val <= 0) {
                    e.preventDefault();
                    alert('Debes ingresar un tipo de cambio válido mayor a 0 cuando la moneda es ' + monedaSelect.value + '.');
                    tcInput.focus();
                    return false;
                }
            }
            window.mostrarCargaOc(form.dataset.loadingMessage || 'Guardando...');
            form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
                button.disabled = true;
                button.classList.add('opacity-70', 'cursor-not-allowed');
            });
        });
    });
})();
</script>
@stack('scripts')

@endsection
@push('scripts')
<script>
(function () {
    console.log('=== INICIO DEBUG BUSCADOR PROVEEDORES ===');

    const input = document.getElementById('proveedor_busqueda');
    const hiddenId = document.getElementById('proveedor_id');
    const hiddenTxt = document.getElementById('proveedor_texto');
    const box = document.getElementById('proveedor_resultados');

    console.log('Elementos encontrados:', {
        input: !!input,
        hiddenId: !!hiddenId,
        hiddenTxt: !!hiddenTxt,
        box: !!box
    });

    if (!input || !hiddenId || !box) {
        console.error('❌ FALTAN ELEMENTOS DEL DOM');
        return;
    }

    console.log('✅ Todos los elementos encontrados');

    let timer = null;
    let lastFetchController = null;

    function closeBox() {
        console.log('🔒 Cerrando dropdown');
        box.classList.add('hidden');
        box.innerHTML = '';
    }

    function openBox() {
        console.log('🔓 Abriendo dropdown');
        box.classList.remove('hidden');
    }

    function setSelected(id, nombre) {
        console.log('✅ Proveedor seleccionado:', { id, nombre });
        hiddenId.value = id;
        input.value = nombre;
        if (hiddenTxt) hiddenTxt.value = nombre;
        closeBox();
    }

    function render(items) {
        console.log('🎨 Renderizando items:', items);

        if (!items || !items.length) {
            box.innerHTML = `<div class="p-2 text-sm text-slate-500">Sin resultados</div>`;
            openBox();
            return;
        }

        box.innerHTML = items.map(i => `
            <button type="button"
                class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b last:border-b-0"
                data-id="${i.id}"
                data-nombre="${String(i.nombre || '').replace(/"/g, '&quot;')}"
            >
                <div class="font-medium">${i.nombre ?? ''}</div>
                ${i.rfc ? `<div class="text-xs text-slate-500">RFC: ${i.rfc}</div>` : ``}
            </button>
        `).join('');

        openBox();

        box.querySelectorAll('button[data-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                setSelected(btn.dataset.id, btn.dataset.nombre);
            });
        });
    }

    async function search(q) {
        console.log('🔍 Iniciando búsqueda para:', q);

        if (q.length < 3) {
            console.log('⚠️ Búsqueda cancelada: menos de 3 caracteres');
            hiddenId.value = '';
            closeBox();
            return;
        }

        if (lastFetchController) {
            console.log('⏹️ Abortando petición anterior');
            lastFetchController.abort();
        }
        lastFetchController = new AbortController();

        try {
            const url = `{{ route('proveedores.buscar') }}?q=${encodeURIComponent(q)}`;
            console.log('📡 Haciendo fetch a:', url);

            const res = await fetch(url, {
                signal: lastFetchController.signal,
                headers: { 'Accept': 'application/json' }
            });

            console.log('📥 Respuesta HTTP:', res.status, res.statusText);

            if (!res.ok) {
                const errorText = await res.text();
                console.error('❌ Error en respuesta:', errorText);
                throw new Error(`HTTP ${res.status}: ${errorText}`);
            }

            const data = await res.json();
            console.log('✅ Datos recibidos:', data);

            render(Array.isArray(data) ? data : []);
        } catch (e) {
            if (e.name === 'AbortError') {
                console.log('⏹️ Petición abortada');
                return;
            }
            console.error('❌ Error en búsqueda:', e);
            box.innerHTML = `<div class="p-2 text-sm text-red-600">Error: ${e.message}</div>`;
            openBox();
        }
    }

    input.addEventListener('input', (e) => {
        const q = e.target.value.trim();
        console.log('⌨️ Input evento:', q, '(length:', q.length, ')');

        hiddenId.value = '';
        if (hiddenTxt) hiddenTxt.value = q;

        clearTimeout(timer);
        console.log('⏱️ Timer reiniciado, esperando 250ms...');
        timer = setTimeout(() => {
            console.log('⏰ Timer ejecutado, llamando search()');
            search(q);
        }, 250);
    });

    document.addEventListener('click', (e) => {
        if (!box.contains(e.target) && e.target !== input) {
            console.log('👆 Click fuera, cerrando dropdown');
            closeBox();
        }
    });

    console.log('=== BUSCADOR INICIALIZADO CORRECTAMENTE ===');
})();
</script>
@endpush

