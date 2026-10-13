@extends('layouts.admin')

@section('title', 'Nueva requisicion')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Obra / Requisiciones</div>
            <h1 class="mt-1 text-2xl font-bold text-[#0B265A]">Nueva requisicion</h1>
            <p class="text-sm text-slate-500">
                {{ $obra->nombre }} · <span class="font-mono">{{ $obra->clave_obra }}</span>
            </p>
        </div>
        <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'requisiciones']) }}"
           class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[#0B265A] shadow-sm transition hover:bg-slate-50">
            Volver a requisiciones
        </a>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <div class="font-semibold">Revisa la captura.</div>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('obras.requisiciones.store', $obra) }}"
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
          data-catalogo-url="{{ route('obras.requisiciones.catalogo', $obra) }}">
        @csrf

        <div class="border-b border-slate-200 pb-4">
            <h2 class="text-lg font-semibold text-slate-800">Captura de requisicion</h2>
            <p class="mt-1 text-sm text-slate-500">Selecciona el tipo de requisicion para cargar el catalogo correspondiente.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 py-6 md:grid-cols-3">
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Obra fija</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $obra->nombre }}</p>
                <p class="text-xs text-slate-500">{{ $obra->clave_obra }}</p>
            </div>
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Folio</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">Se genera al guardar</p>
                <p class="text-xs text-slate-500">Formato: REQ-YY-CAT-0001</p>
            </div>
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado inicial</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">Pendiente de revision</p>
                <p class="text-xs text-slate-500">Sin borrador en el MVP.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 pb-6 md:grid-cols-2">
            <div>
                <label for="tipo_requisicion_id" class="block text-sm font-semibold text-slate-700">Tipo de requisicion</label>
                <select id="tipo_requisicion_id"
                        name="tipo_requisicion_id"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A]"
                        required>
                    <option value="">Selecciona una partida</option>
                    @foreach($tiposRequisicion as $tipo)
                        <option value="{{ $tipo->id }}" @selected(old('tipo_requisicion_id') == $tipo->id)>
                            {{ $tipo->numero_catalogo }} - {{ $tipo->nombre }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Este catalogo viene de Empresa Config y define el bloque CAT del folio.</p>
            </div>

            <div>
                <label for="fecha_requerida" class="block text-sm font-semibold text-slate-700">Fecha requerida</label>
                <input id="fecha_requerida"
                       type="date"
                       name="fecha_requerida"
                       value="{{ old('fecha_requerida') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                <p class="mt-1 text-xs text-slate-500">Opcional por ahora.</p>
            </div>
        </div>

        <div id="catalogo-panel" class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-col gap-3 border-b border-slate-200 pb-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-800">Catalogo disponible</p>
                    <p id="catalogo-resumen" class="text-xs text-slate-500">Selecciona un tipo de requisicion para cargar opciones.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div id="catalogo-buscador-wrap" class="hidden min-w-0 sm:w-80">
                        <label for="catalogo-buscador" class="sr-only">Buscar en catalogo</label>
                        <input id="catalogo-buscador"
                               type="search"
                               autocomplete="off"
                               placeholder="Buscar producto, SKU o unidad"
                               class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                    </div>
                    <span id="catalogo-contador" class="inline-flex w-fit items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">0 items</span>
                </div>
            </div>

            <div id="catalogo-estado" class="py-8 text-center text-sm text-slate-500">
                Sin catalogo cargado.
            </div>

            <div id="catalogo-lista" class="hidden max-h-96 divide-y divide-slate-200 overflow-y-auto"></div>
            <div id="selected-items-hidden"></div>
        </div>

        <div class="pb-6">
            <label for="observaciones" class="block text-sm font-semibold text-slate-700">Observaciones</label>
            <textarea id="observaciones"
                      name="observaciones"
                      rows="4"
                      class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A]"
                      placeholder="Contexto general de la requisicion">{{ old('observaciones') }}</textarea>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
            <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'requisiciones']) }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900">
                Crear requisicion
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[data-catalogo-url]');
        const tipoSelect = document.getElementById('tipo_requisicion_id');
        const resumen = document.getElementById('catalogo-resumen');
        const contador = document.getElementById('catalogo-contador');
        const estado = document.getElementById('catalogo-estado');
        const lista = document.getElementById('catalogo-lista');
        const buscadorWrap = document.getElementById('catalogo-buscador-wrap');
        const buscador = document.getElementById('catalogo-buscador');
        const selectedItemsHidden = document.getElementById('selected-items-hidden');

        if (!form || !tipoSelect || !resumen || !contador || !estado || !lista) {
            return;
        }

        const catalogoUrl = form.dataset.catalogoUrl;
        let activeRequest = 0;
        let currentTipo = null;
        let searchTimer = null;
        const selectedItems = new Map();

        const badgeClass = function (key) {
            if (key === 'asignado_esta_obra') {
                return 'bg-blue-50 text-blue-700 ring-blue-200';
            }

            if (key === 'asignado_otra_obra' || key === 'stock_bajo') {
                return 'bg-amber-50 text-amber-700 ring-amber-200';
            }

            if (key === 'stock_cero') {
                return 'bg-rose-50 text-rose-700 ring-rose-200';
            }

            return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
        };

        const estadoClass = function (estado) {
            if (estado === 'operativa') {
                return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
            }

            if (estado === 'fuera_servicio' || estado === 'en_reparacion') {
                return 'bg-amber-50 text-amber-700 ring-amber-200';
            }

            if (estado === 'baja_definitiva') {
                return 'bg-rose-50 text-rose-700 ring-rose-200';
            }

            return 'bg-slate-50 text-slate-700 ring-slate-200';
        };

        const escapeHtml = function (value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const setEmpty = function (message) {
            lista.classList.add('hidden');
            lista.innerHTML = '';
            estado.classList.remove('hidden');
            estado.textContent = message;
            contador.textContent = '0 items';
        };

        const syncSelectedInputs = function () {
            if (!selectedItemsHidden) {
                return;
            }

            selectedItemsHidden.innerHTML = Array.from(selectedItems.keys()).map(function (id) {
                return `<input type="hidden" name="selected_items[]" value="${escapeHtml(id)}">`;
            }).join('');
        };
        const renderItems = function (items, tipo, options = {}) {
            const itemType = tipo?.item_type || items[0]?.item_type || 'item';
            const isProducto = itemType === 'producto';

            if (!items.length) {
                setEmpty(isProducto && options.q ? 'Sin coincidencias para la busqueda.' : 'No hay elementos disponibles para este tipo de requisicion.');
                contador.textContent = isProducto ? '0 resultados' : '0 items';
                return;
            }

            estado.classList.add('hidden');
            lista.classList.remove('hidden');
            contador.textContent = isProducto
                ? `${items.length} ${items.length === 1 ? 'resultado' : 'resultados'}`
                : `${items.length} ${items.length === 1 ? 'item' : 'items'}`;

            const isMaquina = itemType === 'maquina';
            const headerMain = isMaquina ? 'Maquina' : (isProducto ? 'Producto' : 'Nombre');
            const headerSecondary = isMaquina ? 'Num. eco / tipo' : (isProducto ? 'SKU / unidad' : 'Puesto');
            const headerAvailability = isProducto ? 'Existencia' : 'Disponibilidad';
            const gridStyle = 'display: grid; grid-template-columns: 32px minmax(0, 1.15fr) minmax(190px, 0.9fr) minmax(210px, 0.95fr); align-items: center; column-gap: 12px;';

            const rows = items.map(function (item) {
                const secondary = item.subtitle || (isMaquina ? 'Sin tipo' : (isProducto ? 'Sin SKU / unidad' : 'Sin puesto'));
                const badgeText = item.asignacion?.obra_nombre || item.badge || 'Disponible';
                const obraClave = item.asignacion?.obra_clave
                    ? `<span class="block text-[11px] font-normal text-slate-500">${escapeHtml(item.asignacion.obra_clave)}</span>`
                    : '';
                const estadoBadge = isMaquina && item.estado_label
                    ? `<span class="mt-1 inline-flex w-fit rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ${estadoClass(item.estado)}">${escapeHtml(item.estado_label)}</span>`
                    : '';

                return `
                    <label class="cursor-pointer px-3 py-3 hover:bg-white" style="${gridStyle}">
                        <span class="flex justify-center">
                            <input type="checkbox" value="${escapeHtml(item.id)}" data-item-id="${escapeHtml(item.id)}" data-item-label="${escapeHtml(item.label)}" ${selectedItems.has(String(item.id)) ? 'checked' : ''} class="catalogo-item-checkbox rounded border-slate-300 text-[#0B265A] focus:ring-[#0B265A]">
                        </span>
                        <span class="min-w-0 text-sm font-semibold text-slate-800">
                            ${escapeHtml(item.label)}
                        </span>
                        <span class="min-w-0 text-sm font-medium text-slate-500">
                            <span class="block">${escapeHtml(secondary)}</span>
                            ${estadoBadge}
                        </span>
                        <span class="min-w-0 text-right">
                            <span class="inline-flex max-w-full flex-col rounded-full px-2.5 py-1 text-xs font-semibold leading-tight ring-1 ${badgeClass(item.badge_key)}">
                                <span class="truncate">${escapeHtml(badgeText)}</span>
                                ${obraClave}
                            </span>
                        </span>
                    </label>
                `;
            }).join('');

            lista.innerHTML = `
                <div class="border-b border-[#0B265A]/20 bg-[#0B265A] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white" style="${gridStyle}">
                    <span></span>
                    <span>${headerMain}</span>
                    <span>${headerSecondary}</span>
                    <span class="text-right">${headerAvailability}</span>
                </div>
                ${rows}
            `;

            lista.querySelectorAll('.catalogo-item-checkbox').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    const id = String(checkbox.dataset.itemId || checkbox.value);

                    if (checkbox.checked) {
                        selectedItems.set(id, checkbox.dataset.itemLabel || id);
                    } else {
                        selectedItems.delete(id);
                    }

                    syncSelectedInputs();
                });
            });
        };

        const loadCatalogo = async function (options = {}) {
            const tipoId = tipoSelect.value;
            const query = String(options.q ?? '').trim();
            activeRequest += 1;
            const requestId = activeRequest;

            if (!tipoId) {
                resumen.textContent = 'Selecciona un tipo de requisicion para cargar opciones.';
                setEmpty('Sin catalogo cargado.');
                return;
            }

            resumen.textContent = 'Cargando catalogo...';
            setEmpty('Cargando opciones...');

            try {
                const url = new URL(catalogoUrl, window.location.origin);
                url.searchParams.set('tipo_id', tipoId);
                if (query) {
                    url.searchParams.set('q', query);
                }
                url.searchParams.set('limit', options.limit ?? 50);
                url.searchParams.set('page', options.page ?? 1);

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('No se pudo cargar el catalogo.');
                }

                const payload = await response.json();

                if (requestId !== activeRequest) {
                    return;
                }

                const tipo = payload.tipo || {};
                const items = Array.isArray(payload.items) ? payload.items : [];
                currentTipo = tipo;
                const isProducto = tipo.item_type === 'producto';

                if (buscadorWrap) {
                    buscadorWrap.classList.toggle('hidden', !isProducto);
                }

                const origen = tipo.origen_catalogo ? `Origen: ${tipo.origen_catalogo}` : 'Origen no definido';
                const almacen = tipo.almacen?.nombre ? ` · Almacen: ${tipo.almacen.nombre}` : '';

                resumen.textContent = `${tipo.nombre || 'Tipo seleccionado'} · ${origen}${almacen}`;

                if (isProducto && !query) {
                    setEmpty('Escribe al menos 2 caracteres para buscar materiales.');
                    contador.textContent = '0 resultados';
                    return;
                }

                renderItems(items, tipo, { q: query });
            } catch (error) {
                if (requestId !== activeRequest) {
                    return;
                }

                resumen.textContent = 'No se pudo cargar el catalogo.';
                setEmpty('Intenta seleccionar el tipo de requisicion nuevamente.');
            }
        };

        tipoSelect.addEventListener('change', function () {
            if (searchTimer) {
                clearTimeout(searchTimer);
            }

            if (buscador) {
                buscador.value = '';
            }

            loadCatalogo();
        });

        if (buscador) {
            buscador.addEventListener('input', function () {
                if (!currentTipo || currentTipo.item_type !== 'producto') {
                    return;
                }

                if (searchTimer) {
                    clearTimeout(searchTimer);
                }

                const query = buscador.value.trim();

                searchTimer = setTimeout(function () {
                    if (query.length < 2) {
                        activeRequest += 1;
                        setEmpty('Escribe al menos 2 caracteres para buscar materiales.');
                        contador.textContent = '0 resultados';
                        return;
                    }

                    loadCatalogo({ q: query, limit: 50, page: 1 });
                }, 300);
            });
        }

        if (tipoSelect.value) {
            loadCatalogo();
        }
    });
</script>
@endpush
















