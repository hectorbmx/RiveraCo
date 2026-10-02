{{-- ======================
     AREAS
======================= --}}
<div x-show="tab === 'areas'" x-cloak class="space-y-6"
     x-data="areasTab()">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Áreas</h2>
            <p class="text-sm text-gray-600">Áreas del sistema</p>
        </div>

        <button type="button"
                @click="openCreate()"
                class="px-4 py-2 rounded-xl text-sm bg-gray-900 text-white hover:bg-gray-800">
            + Agregar área
        </button>
    </div>

    {{-- Tabla --}}
    <div class="bg-white border rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left font-semibold px-4 py-3">Código</th>
                        <th class="text-left font-semibold px-4 py-3">Nombre</th>
                        <th class="text-left font-semibold px-4 py-3">Descripción</th>
                        <th class="text-left font-semibold px-4 py-3">Horario base</th>
                        <th class="text-left font-semibold px-4 py-3">Almacen relacionado</th>
                        <th class="text-left font-semibold px-4 py-3">Estatus</th>
                        <th class="text-right font-semibold px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($areas as $a)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $a->codigo }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $a->nombre }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $a->descripcion ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            @if($a->horarioActivo)
                                <div class="font-medium text-slate-900">{{ substr($a->horarioActivo->hora_entrada, 0, 5) }} - {{ substr($a->horarioActivo->hora_salida, 0, 5) }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ collect($a->horarioActivo->dias_laborables ?? [])->map(fn ($dia) => ucfirst(substr($dia, 0, 3)))->implode(', ') ?: 'Sin dias' }}
                                </div>
                                <div class="text-xs text-slate-500">Comida {{ $a->horarioActivo->minutos_comida }} min - Tol. {{ $a->horarioActivo->minutos_tolerancia }} min</div>
                            @else
                                <span class="text-xs text-slate-400">Sin horario</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            @if($a->almacen)
                                <div class="font-medium text-slate-900">{{ $a->almacen->nombre }}</div>
                            @else
                                <span class="text-xs text-slate-400">Sin almacen</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($a->activo)
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Activa</span>
                            @else
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-slate-200 text-slate-700">Inactiva</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        @click="openEdit(@js($a))"
                                        class="px-3 py-1.5 rounded-lg text-xs bg-slate-100 text-slate-800 hover:bg-slate-200">
                                    Editar
                                </button>

                                <form method="POST"
                                      action="{{ route('empresa-config.areas.toggle', $a->id) }}"
                                      onsubmit="return confirm('¿Cambiar estatus del área?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs {{ $a->activo ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-green-100 text-green-800 hover:bg-green-200' }}">
                                        {{ $a->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>

                                <form method="POST"
                                      action="{{ route('empresa-config.areas.destroy', $a->id) }}"
                                      onsubmit="return confirm('¿Eliminar esta área?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs bg-red-100 text-red-700 hover:bg-red-200">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                            No hay áreas registradas.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="close()"></div>

        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border">
            <div class="p-5 border-b flex items-center justify-between">
                <div>
                    <div class="text-base font-semibold text-slate-900" x-text="isEdit ? 'Editar área' : 'Agregar área'"></div>
                    <div class="text-xs text-slate-500">Configura código, nombre, descripción y estatus.</div>
                </div>
                <button type="button" @click="close()"
                        class="p-2 rounded-lg hover:bg-slate-100">
                    ×
                </button>
            </div>

            <form :action="formAction" method="POST" class="p-5 space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PATCH">
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Código</label>
                        <input type="text" name="codigo" x-model="form.codigo"
                               class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500"
                               placeholder="EJ: ADM, OBR, RH">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Nombre</label>
                        <input type="text" name="nombre" x-model="form.nombre"
                               class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500"
                               placeholder="Ej: Administración">
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-slate-600 mb-1">Descripción</label>
                    <textarea name="descripcion" x-model="form.descripcion" rows="3"
                              class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500"
                              placeholder="Opcional"></textarea>
                </div>

                <div>
                    <label class="block text-xs text-slate-600 mb-1">Almacen relacionado</label>
                    <select name="almacen_id" x-model="form.almacen_id"
                            class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500">
                        <option value="">Sin almacen</option>
                        @foreach($almacenes as $almacen)
                            <option value="{{ $almacen->id }}">{{ $almacen->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4">
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Horario base</div>
                        <div class="text-xs text-slate-500">Los empleados heredan este horario desde su area.</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Nombre horario</label>
                            <input type="text" name="horario_nombre" x-model="form.horario_nombre"
                                   class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500"
                                   placeholder="Horario base">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Entrada</label>
                            <input type="time" name="horario_hora_entrada" x-model="form.horario_hora_entrada"
                                   class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Salida</label>
                            <input type="time" name="horario_hora_salida" x-model="form.horario_hora_salida"
                                   class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-600 mb-2">Días laborables</label>
                            <div class="grid grid-cols-2 gap-2 text-xs text-slate-700 sm:grid-cols-4">
                                <template x-for="dia in diasSemana" :key="dia.value">
                                    <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-1.5">
                                        <input type="checkbox" name="horario_dias_laborables[]" :value="dia.value" :checked="form.horario_dias_laborables.includes(dia.value)" @change="toggleDia(dia.value, $event.target.checked)" class="rounded border-slate-300">
                                        <span x-text="dia.label"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-slate-600 mb-1">Comida min.</label>
                                <input type="number" min="0" max="600" step="1" name="horario_minutos_comida" x-model="form.horario_minutos_comida"
                                       class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500"
                                       placeholder="0">
                            </div>

                            <div>
                                <label class="block text-xs text-slate-600 mb-1">Tolerancia min.</label>
                                <input type="number" min="0" max="240" step="1" name="horario_minutos_tolerancia" x-model="form.horario_minutos_tolerancia"
                                       class="w-full rounded-xl border-slate-300 focus:ring-0 focus:border-slate-500"
                                       placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="activo" value="1" x-model="form.activo"
                               class="rounded border-slate-300">
                        Activa
                    </label>

                    <div class="flex gap-2">
                        <button type="button" @click="close()"
                                class="px-4 py-2 rounded-xl text-sm bg-slate-100 text-slate-800 hover:bg-slate-200">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-xl text-sm bg-gray-900 text-white hover:bg-gray-800">
                            Guardar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function areasTab() {
    return {
        modalOpen: false,
        isEdit: false,
        formAction: @js(route('empresa-config.areas.store')),
        diasSemana: [
            { value: 'lunes', label: 'Lun' },
            { value: 'martes', label: 'Mar' },
            { value: 'miercoles', label: 'Mie' },
            { value: 'jueves', label: 'Jue' },
            { value: 'viernes', label: 'Vie' },
            { value: 'sabado', label: 'Sab' },
            { value: 'domingo', label: 'Dom' },
        ],
        form: {},

        normalizeDias(dias) {
            if (Array.isArray(dias)) {
                return dias.map((dia) => typeof dia === 'string' ? dia : dia?.value).filter(Boolean);
            }

            if (typeof dias === 'string' && dias.trim() !== '') {
                try {
                    return this.normalizeDias(JSON.parse(dias));
                } catch (error) {
                    return dias.split(',').map((dia) => dia.trim()).filter(Boolean);
                }
            }

            return [];
        },

        toggleDia(dia, checked) {
            const dias = this.normalizeDias(this.form.horario_dias_laborables);

            this.form.horario_dias_laborables = checked
                ? [...new Set([...dias, dia])]
                : dias.filter((actual) => actual !== dia);
        },

        horarioForm(horario = null) {
            return {
                horario_nombre: horario?.nombre ?? '',
                horario_hora_entrada: horario?.hora_entrada ? String(horario.hora_entrada).slice(0, 5) : '',
                horario_hora_salida: horario?.hora_salida ? String(horario.hora_salida).slice(0, 5) : '',
                horario_dias_laborables: this.normalizeDias(horario?.dias_laborables),
                horario_minutos_comida: horario?.minutos_comida ?? 0,
                horario_minutos_tolerancia: horario?.minutos_tolerancia ?? 0,
            };
        },

        areaForm(area = null) {
            return {
                id: area?.id ?? null,
                codigo: area?.codigo ?? '',
                nombre: area?.nombre ?? '',
                descripcion: area?.descripcion ?? '',
                activo: area ? !!area.activo : true,
                almacen_id: area?.almacen?.id ? String(area.almacen.id) : '',
                ...this.horarioForm(area?.horario_activo ?? null),
            };
        },

        openCreate() {
            this.isEdit = false;
            this.formAction = @js(route('empresa-config.areas.store'));
            this.form = this.areaForm();
            this.modalOpen = true;
        },

        openEdit(area) {
            this.isEdit = true;
            this.formAction = @js(url('/empresa-config/areas')) + '/' + area.id;
            this.form = this.areaForm(area);
            this.modalOpen = true;
        },

        close() {
            this.modalOpen = false;
        }
    }
}
</script>

{{--  TERMINA :AREAS --}}


