@php
    $isEdit = ($modo ?? 'crear') === 'editar';
    $action = $isEdit
        ? route('huentitan.herramientas.update', $herramienta)
        : route('huentitan.herramientas.store');
    $vidaUtil = old('vida_util_piezas', $herramienta->vida_util_piezas);
    $costo = (float) old('costo', $herramienta->costo ?? 0);
    $residual = (float) old('costo_residual', $herramienta->costo_residual ?? 0);
    $sugerido = $vidaUtil ? max(($costo - $residual) / max((int) $vidaUtil, 1), 0) : null;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($isEdit)
        @method('PATCH')
    @endif

    <div class="bg-white border rounded-lg p-6 shadow-sm space-y-5">
        @if($isEdit)
            <div class="rounded-md border bg-gray-50 p-3">
                <div class="text-xs text-gray-500">Codigo</div>
                <div class="mt-1 font-mono text-sm font-semibold text-gray-900">{{ $herramienta->codigo }}</div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <label class="block md:col-span-2">
                <span class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></span>
                <input type="text" name="nombre" value="{{ old('nombre', $herramienta->nombre) }}" required maxlength="150" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('nombre')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block md:col-span-2">
                <span class="block text-sm font-medium text-gray-700 mb-1">Descripcion</span>
                <textarea name="descripcion" rows="3" maxlength="1000" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('descripcion', $herramienta->descripcion) }}</textarea>
                @error('descripcion')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Costo</span>
                <input type="number" step="0.01" min="0" name="costo" value="{{ old('costo', $herramienta->costo ?? 0) }}" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('costo')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Costo residual</span>
                <input type="number" step="0.01" min="0" name="costo_residual" value="{{ old('costo_residual', $herramienta->costo_residual ?? 0) }}" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('costo_residual')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Vida util en piezas</span>
                <input type="number" step="1" min="1" name="vida_util_piezas" value="{{ old('vida_util_piezas', $herramienta->vida_util_piezas) }}" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('vida_util_piezas')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <div class="rounded-md border bg-yellow-50 p-3">
                <div class="text-xs font-medium text-yellow-800">Costo sugerido por pieza</div>
                <div class="mt-1 text-lg font-semibold text-yellow-900">
                    {{ $sugerido === null ? 'Pendiente' : '$' . number_format($sugerido, 2) }}
                </div>
            </div>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Estado <span class="text-red-500">*</span></span>
                <select name="estado" required class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="activa" @selected(old('estado', $herramienta->estado ?? 'activa') === 'activa')>Activa</option>
                    <option value="en_mantenimiento" @selected(old('estado', $herramienta->estado) === 'en_mantenimiento')>En mantenimiento</option>
                    <option value="baja" @selected(old('estado', $herramienta->estado) === 'baja')>Baja</option>
                </select>
                @error('estado')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Fecha compra</span>
                <input type="date" name="fecha_compra" value="{{ old('fecha_compra', optional($herramienta->fecha_compra)->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('fecha_compra')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Fecha registro</span>
                <input type="date" name="fecha_registro" value="{{ old('fecha_registro', optional($herramienta->fecha_registro)->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('fecha_registro')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <div class="block relative" data-proveedor-search data-search-url="{{ route('proveedores.buscar') }}">
                <label for="herramienta_proveedor_busqueda" class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                <input type="hidden" name="proveedor_id" id="herramienta_proveedor_id" value="{{ old('proveedor_id', $herramienta->proveedor_id) }}">
                <input type="hidden" name="proveedor_nombre" id="herramienta_proveedor_nombre" value="{{ old('proveedor_nombre', $herramienta->proveedor_nombre ?? $herramienta->proveedor?->nombre) }}">
                <input type="text" id="herramienta_proveedor_busqueda" value="{{ old('proveedor_nombre', $herramienta->proveedor?->nombre ?? $herramienta->proveedor_nombre) }}" maxlength="150" autocomplete="off" placeholder="Buscar proveedor por nombre o RFC" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                <div id="herramienta_proveedor_resultados" class="hidden absolute z-30 mt-1 w-full max-h-64 overflow-y-auto rounded-md border bg-white shadow-lg"></div>
                @error('proveedor_id')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                @error('proveedor_nombre')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </div>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Marca</span>
                <input type="text" name="marca" value="{{ old('marca', $herramienta->marca) }}" maxlength="100" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('marca')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Modelo</span>
                <input type="text" name="modelo" value="{{ old('modelo', $herramienta->modelo) }}" maxlength="100" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('modelo')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Numero de serie</span>
                <input type="text" name="numero_serie" value="{{ old('numero_serie', $herramienta->numero_serie) }}" maxlength="100" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('numero_serie')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
        </div>

        <label class="flex items-start gap-3 rounded-md border border-emerald-100 bg-emerald-50 p-3">
            <input type="checkbox" name="activo" value="1" @checked(old('activo', $herramienta->activo ?? true)) class="mt-1 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-700">
            <span>
                <span class="block text-sm font-semibold text-emerald-800">Activo</span>
                <span class="block text-xs text-emerald-700">Disponible para integrarse al precio unitario de productos HUENTITAN.</span>
            </span>
        </label>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
        <a href="{{ route('huentitan.herramientas.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Cancelar</a>
        <button class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-[#0B265A] text-white text-sm font-semibold hover:bg-[#12336f]">
            {{ $isEdit ? 'Guardar cambios' : 'Crear herramienta' }}
        </button>
    </div>
</form>
@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-proveedor-search]');
    if (!root) return;

    const input = document.getElementById('herramienta_proveedor_busqueda');
    const hiddenId = document.getElementById('herramienta_proveedor_id');
    const hiddenName = document.getElementById('herramienta_proveedor_nombre');
    const box = document.getElementById('herramienta_proveedor_resultados');
    const searchUrl = root.dataset.searchUrl;

    if (!input || !hiddenId || !hiddenName || !box || !searchUrl) return;

    let timer = null;
    let lastFetchController = null;

    function closeBox() {
        box.classList.add('hidden');
        box.innerHTML = '';
    }

    function openBox() {
        box.classList.remove('hidden');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setSelected(id, nombre) {
        hiddenId.value = id;
        hiddenName.value = nombre;
        input.value = nombre;
        closeBox();
    }

    function render(items) {
        if (!items || !items.length) {
            box.innerHTML = '<div class="p-2 text-sm text-slate-500">Sin resultados</div>';
            openBox();
            return;
        }

        box.innerHTML = items.map((item) => {
            const nombre = escapeHtml(item.nombre || '');
            const rfc = item.rfc ? `<div class="text-xs text-slate-500">RFC: ${escapeHtml(item.rfc)}</div>` : '';
            return `
                <button type="button" class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b last:border-b-0" data-id="${escapeHtml(item.id)}" data-nombre="${nombre}">
                    <div class="font-medium text-slate-900">${nombre}</div>
                    ${rfc}
                </button>
            `;
        }).join('');

        openBox();

        box.querySelectorAll('button[data-id]').forEach((button) => {
            button.addEventListener('click', () => setSelected(button.dataset.id, button.dataset.nombre));
        });
    }

    async function search(q) {
        if (q.length < 3) {
            hiddenId.value = '';
            closeBox();
            return;
        }

        if (lastFetchController) {
            lastFetchController.abort();
        }
        lastFetchController = new AbortController();

        try {
            const response = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                signal: lastFetchController.signal,
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) throw new Error('No se pudo buscar proveedores.');
            const data = await response.json();
            render(Array.isArray(data) ? data : []);
        } catch (error) {
            if (error.name === 'AbortError') return;
            box.innerHTML = '<div class="p-2 text-sm text-red-600">Error buscando proveedores</div>';
            openBox();
        }
    }

    input.addEventListener('input', (event) => {
        const q = event.target.value.trim();
        hiddenId.value = '';
        hiddenName.value = q;
        clearTimeout(timer);
        timer = setTimeout(() => search(q), 250);
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            closeBox();
        }
    });
})();
</script>
@endpush

