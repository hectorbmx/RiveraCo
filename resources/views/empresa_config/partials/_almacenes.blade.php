{{-- ======================
     ALMACENES
======================= --}}
<div x-show="tab === 'almacenes'" x-cloak class="space-y-6"
     x-data="almacenesTab()">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Almacenes</h2>
            <p class="text-sm text-gray-600">Ubicaciones de inventario y gasto relacionadas con areas.</p>
        </div>
    </div>

    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Nuevo almacen</h3>
        <form method="POST" action="{{ route('empresa-config.almacenes.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            @csrf

            <div>
                <label class="block text-xs text-slate-600 mb-1">Nombre del almacen</label>
                <input type="text" name="nombre" class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500" placeholder="Ej: AL-GIRALDA" required>
            </div>

            <div>
                <label class="block text-xs text-slate-600 mb-1">Area relacionada</label>
                <select name="area_id" class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500">
                    <option value="">Sin area por ahora</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->codigo }} - {{ $area->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex md:justify-end">
                <button type="submit" class="px-4 py-2 rounded-xl text-sm bg-gray-900 text-white hover:bg-gray-800">
                    Guardar almacen
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white border rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left font-semibold px-4 py-3">Nombre</th>
                        <th class="text-left font-semibold px-4 py-3">Area relacionada</th>
                        <th class="text-left font-semibold px-4 py-3">Tipo</th>
                        <th class="text-left font-semibold px-4 py-3">Estatus</th>
                        <th class="text-right font-semibold px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($almacenes as $almacen)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $almacen->nombre }}</div>
                            @if($almacen->codigo)
                                <div class="text-xs font-mono text-slate-500">{{ $almacen->codigo }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            @if($almacen->area)
                                <div class="font-medium text-slate-900">{{ $almacen->area->nombre }}</div>
                                @if($almacen->area->codigo)
                                    <div class="text-xs font-mono text-slate-500">{{ $almacen->area->codigo }}</div>
                                @endif
                            @else
                                <span class="text-xs text-slate-400">Sin area</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-700">
                                {{ ucfirst($almacen->tipo ?: 'general') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($almacen->activo)
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Activo</span>
                            @else
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-200 text-slate-700">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        @click="openEdit(@js($almacen))"
                                        class="px-3 py-1.5 rounded-lg text-xs bg-slate-100 text-slate-800 hover:bg-slate-200">
                                    Editar
                                </button>

                                <form method="POST"
                                      action="{{ route('empresa-config.almacenes.toggle', $almacen->id) }}"
                                      onsubmit="return confirm('¿Cambiar estatus del almacen?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs {{ $almacen->activo ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-green-100 text-green-800 hover:bg-green-200' }}">
                                        {{ $almacen->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                            No hay almacenes registrados.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="close()"></div>

        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-xl border">
            <div class="p-5 border-b flex items-center justify-between">
                <div>
                    <div class="text-base font-semibold text-slate-900" x-text="isEdit ? 'Editar almacén' : 'Agregar almacén'"></div>
                    <div class="text-xs text-slate-500">Actualiza la asignación y el estatus del almacén.</div>
                </div>
                <button type="button" @click="close()" class="p-2 rounded-lg hover:bg-slate-100">×</button>
            </div>

            <form :action="formAction" method="POST" class="p-5 space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PATCH">
                </template>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Nombre</label>
                        <input type="text" name="nombre" x-model="form.nombre"
                               class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500"
                               placeholder="Ej: AL-GIRALDA" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Tipo</label>
                            <select name="tipo" x-model="form.tipo" class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500">
                                <option value="general">General</option>
                                <option value="obra">Obra</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Área relacionada</label>
                            <select name="area_id" x-model="form.area_id" class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500">
                                <option value="">Sin area</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->codigo }} - {{ $area->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="activo" value="1" x-model="form.activo" class="rounded border-slate-300">
                        Activo
                    </label>

                    <div class="flex gap-2">
                        <button type="button" @click="close()" class="px-4 py-2 rounded-xl text-sm bg-slate-100 text-slate-800 hover:bg-slate-200">Cancelar</button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-sm bg-gray-900 text-white hover:bg-gray-800">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function almacenesTab() {
    return {
        modalOpen: false,
        isEdit: false,
        formAction: @js(route('empresa-config.almacenes.store')),
        form: {
            id: null,
            nombre: '',
            tipo: 'general',
            area_id: '',
            activo: true,
        },

        openEdit(almacen) {
            this.isEdit = true;
            this.formAction = @js(url('/empresa-config/almacenes')) + '/' + almacen.id;
            this.form = {
                id: almacen.id ?? null,
                nombre: almacen.nombre ?? '',
                tipo: almacen.tipo ?? 'general',
                area_id: almacen.area_id ? String(almacen.area_id) : '',
                activo: !!almacen.activo,
            };
            this.modalOpen = true;
        },

        close() {
            this.modalOpen = false;
        }
    }
}
</script>

