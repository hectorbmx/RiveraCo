@extends('layouts.admin')

@section('title', 'Materiales del servicio preventivo')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6" x-data="machineMaterialsEditor({
    maquinas: @js($maquinas->map(fn ($maquina) => [
        'id' => $maquina->id,
        'nombre' => $maquina->nombre,
        'codigo' => $maquina->codigo,
        'numero_serie' => $maquina->numero_serie,
    ])->values()),
    productos: @js($productosCatalogo->map(fn ($producto) => [
        'id' => $producto->id,
        'nombre' => $producto->nombre,
        'sku' => $producto->sku,
        'unidad' => $producto->unidad,
    ])->values()),
    listarUrl: @js(route('empresa_config.servicios-preventivos.maquinas.materiales.index', [$tipo, '__MAQUINA__'])),
    guardarUrl: @js(route('empresa_config.servicios-preventivos.maquinas.materiales.store', [$tipo, '__MAQUINA__'])),
    actualizarUrl: @js(route('empresa_config.servicios-preventivos.materiales.update', '__MATERIAL__')),
    toggleUrl: @js(route('empresa_config.servicios-preventivos.materiales.toggle-activo', '__MATERIAL__')),
})">
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <a href="{{ route('empresa_config.edit', ['tab' => 'maquinaria']) }}" class="text-sm font-semibold text-blue-700 hover:underline">&larr; Regresar a maquinaria</a>
            <h1 class="mt-2 text-2xl font-semibold text-gray-900">Servicio preventivo</h1>
            <p class="text-sm text-gray-600">{{ $tipo->nombre }} · {{ number_format((float) $tipo->intervalo_valor, 0) }} horas</p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700 ring-1 ring-blue-200">
            Configuración por máquina
        </span>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-4">
            <h2 class="text-base font-semibold text-gray-900">Máquinas</h2>
            <p class="text-sm text-gray-600">Cada máquina puede tener productos distintos para este servicio.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-[#0B265A] text-white">
                    <tr>
                        <th class="px-4 py-3 text-left">Máquina</th>
                        <th class="px-4 py-3 text-left">Serie</th>
                        <th class="px-4 py-3 text-left">Configuración</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($maquinas as $maquina)
                        @php $cantidadMateriales = $materiales->get($maquina->id, collect())->count(); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $maquina->nombre }}
                                @if($maquina->codigo)<span class="block text-xs text-gray-500">{{ $maquina->codigo }}</span>@endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $maquina->numero_serie ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($cantidadMateriales)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">{{ $cantidadMateriales }} materiales</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-gray-200">Sin configurar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" @click="abrirMaquina({{ $maquina->id }})" title="Editar materiales de la máquina" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 text-blue-700 hover:bg-blue-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.232 5.232 3.536 3.536M4 20h4l10.5-10.5a2.5 2.5 0 0 0-3.536-3.536L4.5 16.5 4 20Z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay máquinas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="modalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4 py-6" @keydown.escape.window="cerrarModal()">
        <div class="relative w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl" @click.outside="cerrarModal()">
            <div x-show="guardando" x-cloak class="absolute inset-0 z-30 flex items-center justify-center bg-white/75">
                <div class="text-center">
                    <div class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-[#0B265A]"></div>
                    <p class="text-sm font-semibold text-[#0B265A]">Guardando cambios...</p>
                </div>
            </div>
            <div class="flex items-start justify-between bg-[#0B265A] px-5 py-4 text-white">
                <div>
                    <h2 class="text-base font-semibold">Materiales del servicio</h2>
                    <p class="text-sm text-blue-100" x-text="maquinaActual ? `${maquinaActual.nombre} · {{ $tipo->nombre }}` : ''"></p>
                </div>
                <button type="button" @click="cerrarModal()" class="text-2xl leading-none text-white/80 hover:text-white" title="Cerrar">&times;</button>
            </div>

            <div class="space-y-4 p-5">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                        <div class="relative md:col-span-6" @click.outside="resultadosAbiertos = false">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Buscar producto</label>
                            <input type="search" x-model="productoBusqueda" @focus="resultadosAbiertos = true" @input="resultadosAbiertos = true" placeholder="Nombre o SKU..." autocomplete="off" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <div x-show="resultadosAbiertos && productoBusqueda.trim().length >= 2" x-cloak class="absolute left-0 right-0 top-full z-20 mt-1 max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                                <template x-for="producto in productosFiltrados" :key="producto.id">
                                    <button type="button" @click="seleccionarProducto(producto)" class="block w-full rounded-md px-3 py-2 text-left text-sm hover:bg-blue-50">
                                        <span class="font-medium text-gray-900" x-text="producto.nombre"></span>
                                        <span class="block text-xs text-gray-500" x-text="`${producto.sku || 'Sin SKU'} · ${producto.unidad || 'Sin unidad'}`"></span>
                                    </button>
                                </template>
                                <div x-show="productosFiltrados.length === 0" class="px-3 py-3 text-sm text-gray-500">No se encontraron productos.</div>
                            </div>
                            <p x-show="form.producto_id" x-cloak class="mt-1 text-xs font-semibold text-blue-700">Producto seleccionado</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Cantidad</label>
                            <input type="number" min="0.001" step="0.001" x-model="form.cantidad_requerida" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Unidad</label>
                            <input type="text" maxlength="50" x-model="form.unidad" placeholder="Automática" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <button type="button" @click="guardarMaterial()" :disabled="guardando" class="w-full rounded-lg bg-[#0B265A] px-3 py-2 text-sm font-semibold text-white hover:bg-[#163875] disabled:opacity-60" x-text="editandoId ? 'Actualizar' : 'Agregar a la lista'"></button>
                        </div>
                    </div>
                    <p x-show="mensaje" x-text="mensaje" class="mt-2 text-sm text-red-600"></p>
                </div>

                <div x-show="cargando" class="py-8 text-center text-sm text-gray-500">Cargando materiales...</div>
                <div x-show="!cargando && materiales.length === 0" x-cloak class="py-8 text-center text-sm text-gray-500">Esta máquina todavía no tiene materiales configurados para este servicio.</div>
                <div x-show="materiales.length" x-cloak class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[#0B265A] text-white">
                            <tr><th class="px-4 py-3 text-left">Producto</th><th class="px-4 py-3 text-left">Cantidad</th><th class="px-4 py-3 text-left">Unidad</th><th class="px-4 py-3 text-left">Estado</th><th class="px-4 py-3 text-right">Acciones</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            <template x-for="material in materiales" :key="material.id">
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900"><span x-text="material.producto?.nombre || 'Producto no disponible'"></span><span class="block text-xs text-gray-500" x-text="material.producto?.sku || '—'"></span></td>
                                    <td class="px-4 py-3" x-text="material.cantidad_requerida"></td>
                                    <td class="px-4 py-3" x-text="material.unidad || material.producto?.unidad || '—'"></td>
                                    <td class="px-4 py-3"><span x-show="material.activo" class="text-xs font-semibold text-emerald-700">Activo</span><span x-show="!material.activo" class="text-xs font-semibold text-gray-500">Inactivo</span></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap"><button type="button" @click="editarMaterial(material)" class="mr-2 text-sm font-semibold text-blue-700 hover:underline">Editar</button><button type="button" @click="toggleMaterial(material)" class="text-sm font-semibold text-gray-700 hover:underline" x-text="material.activo ? 'Desactivar' : 'Activar'"></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 px-5 py-4">
                <button type="button" @click="cerrarModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="button" @click="guardarCambios()" :disabled="guardando" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#163875] disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="!guardando">Guardar cambios</span>
                    <span x-show="guardando" x-cloak class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        Guardando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function machineMaterialsEditor(config) {
    return {
        ...config,
        modalOpen: false,
        maquinaActual: null,
        materiales: [],
        cargando: false,
        guardando: false,
        mensaje: '',
        editandoId: null,
        pendientes: [],
        productoBusqueda: '',
        resultadosAbiertos: false,
        form: { producto_id: '', cantidad_requerida: '', unidad: '' },
        csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; },
        get productosFiltrados() {
            const termino = this.productoBusqueda.trim().toLowerCase();
            if (termino.length < 2) return [];
            return this.productos.filter((producto) => [producto.nombre, producto.sku, producto.unidad]
                .filter(Boolean)
                .some((valor) => String(valor).toLowerCase().includes(termino)))
                .slice(0, 20);
        },
        baseUrl(template) { return template.replace('__MAQUINA__', this.maquinaActual.id); },
        resetForm() { this.editandoId = null; this.form = { producto_id: '', cantidad_requerida: '', unidad: '' }; this.productoBusqueda = ''; this.resultadosAbiertos = false; },
        seleccionarProducto(producto) {
            this.form.producto_id = String(producto.id);
            this.form.unidad = this.form.unidad || producto.unidad || '';
            this.productoBusqueda = `${producto.nombre} - ${producto.sku || 'sin SKU'}`;
            this.resultadosAbiertos = false;
        },
        async abrirMaquina(id) {
            this.maquinaActual = this.maquinas.find((maquina) => Number(maquina.id) === Number(id));
            this.modalOpen = true; this.mensaje = ''; this.pendientes = []; this.resetForm(); this.cargando = true;
            try {
                const response = await fetch(this.baseUrl(this.listarUrl), { headers: { 'Accept': 'application/json' } });
                if (!response.ok) throw new Error('No se pudieron cargar los materiales.');
                this.materiales = await response.json();
            } catch (error) { this.mensaje = error.message; }
            finally { this.cargando = false; }
        },
        cerrarModal() {
            if (this.pendientes.length && !window.confirm('Hay materiales sin guardar. ¿Deseas salir sin guardar?')) return;
            this.modalOpen = false; this.maquinaActual = null; this.materiales = []; this.pendientes = []; this.resetForm();
        },
        editarMaterial(material) {
            this.editandoId = material.id;
            this.form = { producto_id: String(material.producto_id), cantidad_requerida: material.cantidad_requerida, unidad: material.unidad || '' };
            const producto = this.productos.find((item) => Number(item.id) === Number(material.producto_id));
            this.productoBusqueda = producto ? `${producto.nombre} - ${producto.sku || 'sin SKU'}` : '';
            this.resultadosAbiertos = false;
        },
        async guardarMaterial() {
            this.mensaje = '';
            if (!this.form.producto_id || !this.form.cantidad_requerida) { this.mensaje = 'Selecciona un producto e indica una cantidad mayor a cero.'; return; }

            const producto = this.productos.find((item) => Number(item.id) === Number(this.form.producto_id));
            const esPendiente = String(this.editandoId || '').startsWith('tmp-');

            if (!this.editandoId && this.materiales.some((item) => Number(item.producto_id) === Number(this.form.producto_id) && item.activo)) {
                this.mensaje = 'Ese producto ya está agregado para esta máquina y servicio.';
                return;
            }

            if (esPendiente) {
                this.materiales = this.materiales.map((item) => item.id === this.editandoId ? {
                    ...item,
                    producto_id: this.form.producto_id,
                    producto,
                    cantidad_requerida: this.form.cantidad_requerida,
                    unidad: this.form.unidad || producto?.unidad || '',
                } : item);
                this.pendientes = this.materiales.filter((item) => String(item.id).startsWith('tmp-'));
                this.resetForm();
                return;
            }

            if (!this.editandoId) {
                const provisional = {
                    id: `tmp-${Date.now()}`,
                    producto_id: this.form.producto_id,
                    producto,
                    cantidad_requerida: this.form.cantidad_requerida,
                    unidad: this.form.unidad || producto?.unidad || '',
                    activo: true,
                    pendiente: true,
                };
                this.materiales = [...this.materiales, provisional];
                this.pendientes = [...this.pendientes, provisional];
                this.resetForm();
                return;
            }

            this.guardando = true;
            const response = await fetch(this.actualizarUrl.replace('__MATERIAL__', this.editandoId), {
                method: 'PUT',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                body: JSON.stringify({ ...this.form, maquina_id: this.maquinaActual.id, activo: true }),
            });
            if (!response.ok) { const data = await response.json().catch(() => ({})); this.mensaje = data.message || Object.values(data.errors || {}).flat().join(' ') || 'No se pudo guardar el material.'; this.guardando = false; return; }
            const material = await response.json();
            this.materiales = this.materiales.map((item) => item.id === material.id ? material : item);
            this.resetForm(); this.guardando = false;
        },
        async guardarCambios() {
            if (!this.pendientes.length) { this.cerrarModal(); return; }
            this.guardando = true;
            try {
                const guardados = [];
                for (const pendiente of this.pendientes) {
                    const response = await fetch(this.baseUrl(this.guardarUrl), {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                        body: JSON.stringify({
                            producto_id: pendiente.producto_id,
                            cantidad_requerida: pendiente.cantidad_requerida,
                            unidad: pendiente.unidad,
                            maquina_id: this.maquinaActual.id,
                            activo: true,
                        }),
                    });
                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'No se pudo guardar un material.');
                    }
                    guardados.push(await response.json());
                }
                const provisionales = new Map(this.pendientes.map((item, index) => [item.id, guardados[index]]));
                this.materiales = this.materiales.map((item) => provisionales.get(item.id) || item);
                this.pendientes = [];
                // Recarga los contadores y estados del listado después de persistir todo.
                window.location.reload();
            } catch (error) {
                this.mensaje = error.message;
                this.guardando = false;
            }
        },
        async toggleMaterial(material) {
            if (String(material.id).startsWith('tmp-')) {
                material.activo = !material.activo;
                return;
            }
            const response = await fetch(this.toggleUrl.replace('__MATERIAL__', material.id), { method: 'PATCH', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() } });
            if (!response.ok) { this.mensaje = 'No se pudo cambiar el estado del material.'; return; }
            material.activo = (await response.json()).activo;
        },
    };
}
</script>
@endsection
