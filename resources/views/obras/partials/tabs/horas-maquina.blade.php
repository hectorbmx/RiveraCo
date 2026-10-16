@if($tab === 'horas-maquina')

<div class="space-y-4">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Horas máquina</h2>
            <p class="text-sm text-slate-600">
                Historial de registros por tramo. Cada fila corresponde a una captura.
            </p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="text-sm font-medium text-slate-900 mb-3">Registrar horas</div>

        @if($maquinasAsignadasActivas->count())
            <div class="flex flex-wrap says gap-2">
                @foreach($maquinasAsignadasActivas as $asignacion)
                    <button type="button"
                        class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700"
                        x-data
                        @click="$dispatch('open-horas-modal', {
                            obraMaquinaId: {{ $asignacion->id }},
                            maquinaNombre: @js($asignacion->maquina->nombre ?? 'Máquina'),
                            horometroSugerido: {{ (float)($asignacion->horometro_actual ?? $asignacion->horometro_inicio ?? 0) }},
                            fechaUltimaLectura: @js($asignacion->fecha_ultima_lectura ?? null)
                        })">
                        {{ $asignacion->maquina->nombre ?? 'Máquina' }} · Registrar
                    </button>
                @endforeach
            </div>
        @else
            <div class="text-sm text-slate-500">No hay máquinas activas asignadas a esta obra.</div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @php
            $ultimoRegistroPorAsignacion = [];
            $horometroAnteriorPorRegistro = [];

            foreach ($registrosHorasMaquina->groupBy('obra_maquina_id') as $obraMaquinaId => $registros) {
                $ordenados = $registros->sortBy([
                    [fn ($registro) => optional($registro->inicio)->timestamp ?? 0, 'asc'],
                    [fn ($registro) => optional($registro->created_at)->timestamp ?? 0, 'asc'],
                    ['id', 'asc'],
                ])->values();

                $asignacion = $ordenados->first()?->asignacion;
                $horometroAnterior = max([
                    (float) ($asignacion?->horometro_inicio ?? 0),
                    (float) ($asignacion?->maquina?->horometro_base ?? 0),
                ]);

                foreach ($ordenados as $registro) {
                    $horometroAnteriorPorRegistro[$registro->id] = $horometroAnterior;
                    $horometroAnterior = max($horometroAnterior, (float) $registro->horometro_fin);
                }

                $ultimoRegistroPorAsignacion[$obraMaquinaId] = $ordenados->last()?->id;
            }
        @endphp
        <table class="min-w-full text-sm">
            <thead class="bg-[#0B265A] border-b border-[#0B265A]">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-white">Fecha</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-white">Máquina</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-white">Horómetro inicio</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-white">Horómetro fin</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-white">Horas</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-white">Notas</th>
                    @if(auth()->user()?->can('editar_horas_maquina.access') || auth()->user()?->can('borrar_horas_maquina.access'))
                        <th class="px-4 py-2 text-center text-xs font-semibold text-white">Acciones</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @forelse($registrosHorasMaquina as $r)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-2 text-slate-700">
                            {{ optional($r->inicio)->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-2 text-slate-700">
                            {{ $r->asignacion?->maquina?->nombre ?? '—' }}
                        </td>
                        <td class="px-4 py-2 text-right text-slate-700">
                            {{ number_format((float)$r->horometro_inicio, 2) }}
                        </td>
                        <td class="px-4 py-2 text-right text-slate-700">
                            {{ number_format((float)$r->horometro_fin, 2) }}
                        </td>
                        <td class="px-4 py-2 text-right font-semibold text-slate-900">
                            {{ number_format((float)($r->horas ?? ((float)$r->horometro_fin - (float)$r->horometro_inicio)), 2) }}
                        </td>
                        <td class="px-4 py-2 text-slate-500">
                            {{ $r->notas ?: '—' }}
                        </td>

                        @if(auth()->user()?->can('editar_horas_maquina.access') || auth()->user()?->can('borrar_horas_maquina.access'))
                            <td class="px-4 py-2 text-center">
                                @if(($ultimoRegistroPorAsignacion[$r->obra_maquina_id] ?? null) === $r->id)
                                    @can('editar_horas_maquina.access')
                                        <button type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 text-blue-700 hover:bg-blue-50"
                                                title="Editar último registro"
                                                aria-label="Editar último registro"
                                                data-action="{{ route('obras.horas_maquina.update', [$obra->id, $r->obra_maquina_id, $r->id]) }}"
                                                data-fecha="{{ optional($r->inicio)->format('Y-m-d') }}"
                                                data-horometro="{{ (float) $r->horometro_fin }}"
                                                data-horometro-anterior="{{ (float) ($horometroAnteriorPorRegistro[$r->id] ?? $r->horometro_inicio) }}"
                                                data-notas="{{ e($r->notas ?? '') }}"
                                                onclick="window.openEditHorasModal(this)">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 3.487 3.651 3.651M5.25 18.75l.747-3.735a2.25 2.25 0 0 1 .637-1.145L15.5 4.999a2.25 2.25 0 0 1 3.182 0l.319.319a2.25 2.25 0 0 1 0 3.182l-8.871 8.871a2.25 2.25 0 0 1-1.145.637L5.25 18.75Z" />
                                            </svg>
                                        </button>
                                    @endcan

                                    @can('borrar_horas_maquina.access')
                                        <form method="POST"
                                              action="{{ route('obras.horas_maquina.destroy', [$obra->id, $r->obra_maquina_id, $r->id]) }}"
                                              class="inline"
                                              onsubmit="return confirm('¿Deseas eliminar el último registro de horas? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50"
                                                    title="Eliminar último registro"
                                                    aria-label="Eliminar último registro">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16m-10 4v6m4-6v6M9 7V4h6v3m-9 0 1 13h8l1-13" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endcan
                                @else
                                    <span class="text-xs text-slate-400" title="Solo se puede editar el último registro">—</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ (auth()->user()?->can('editar_horas_maquina.access') || auth()->user()?->can('borrar_horas_maquina.access')) ? 7 : 6 }}" class="px-4 py-6 text-center text-slate-400">
                            Aún no hay registros de horas para esta obra.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="registro-horas-modal"
    data-registro-horas-modal
    x-data="{
        registroOpen: false,
        obraMaquinaId: null,
        maquinaNombre: '',
        horometroSugerido: 0,
        fechaRegistro: '{{ now()->toDateString() }}',
        fechaUltimaLectura: '',
        horometroFin: '',
        errorMessage: '',
        hasError: false,
        validarCaptura() {
            const nuevo = Number(this.horometroFin);
            const actual = Number(this.horometroSugerido);
            let error = '';

            if (this.horometroFin !== '' && Number.isFinite(nuevo) && nuevo <= actual) {
                error = `El horómetro debe ser mayor a ${actual.toFixed(2)} h.`;
            } else if (this.fechaRegistro && this.fechaUltimaLectura && this.fechaRegistro < this.fechaUltimaLectura) {
                error = `La fecha no puede ser anterior al ${this.fechaUltimaLectura.split('-').reverse().join('/')}.`;
            }

            this.errorMessage = error;
            this.hasError = Boolean(error);

            return !this.hasError;
        },
    }"
    @open-horas-modal.window="
        registroOpen = true;
        obraMaquinaId = $event.detail.obraMaquinaId;
        maquinaNombre = $event.detail.maquinaNombre;
        horometroSugerido = $event.detail.horometroSugerido;
        fechaUltimaLectura = $event.detail.fechaUltimaLectura || '';
        horometroFin = (Number(horometroSugerido) + 0.01).toFixed(2);
        errorMessage = '';
        hasError = false;
        $nextTick(() => $refs.horometroFin.focus());
    "
>
    <div x-show="registroOpen" x-cloak x-transition class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/40" @click="registroOpen=false"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-[#0B265A] flex items-center justify-between">
                    <div>
                        <div class="text-sm text-white/80">Registrar horas</div>
                        <div class="font-semibold text-white" x-text="maquinaNombre"></div>
                    </div>
                    <button type="button" class="text-white/80 hover:text-white" aria-label="Cerrar" @click="registroOpen=false">✕</button>
                </div>

                <form method="POST"
                      :action="`/obras/{{ $obra->id }}/maquinaria/${obraMaquinaId}/horas`"
                      class="p-5 space-y-4"
                      @submit="if (!validarCaptura()) $event.preventDefault()">
                    @csrf

                    <div class="text-xs text-slate-500">
                        Último horómetro registrado:
                        <span class="font-medium" x-text="Number(horometroSugerido).toFixed(2)"></span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Fecha del registro</label>
                        <input type="date" name="fecha" x-model="fechaRegistro"
                               @blur="validarCaptura()"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                               required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Horómetro final</label>
                        <input type="number" step="0.01" min="0.01"
                               name="horometro_fin"
                               x-ref="horometroFin"
                               :min="(Number(horometroSugerido) + 0.01).toFixed(2)"
                               x-model="horometroFin"
                               @blur="validarCaptura()"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                               required>
                        <p x-show="errorMessage" x-cloak class="mt-1 rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700" x-text="errorMessage"></p>
                        <p class="text-xs text-slate-500 mt-1">
                            Se calcula automáticamente la diferencia contra el último horómetro.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notas (opcional)</label>
                        <textarea name="notas" rows="2"
                                  class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button"
                                class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50"
                                @click="registroOpen=false">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="hasError">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div
    id="edit-horas-modal"
    data-edit-horas-modal
    x-data="{
        edicionOpen: false,
        action: '',
        fecha: '',
        horometro: '',
        horometroAnterior: 0,
        notas: '',
        errorMessage: '',
        hasError: false,
        validarEdicion() {
            const nuevo = Number(this.horometro);
            const anterior = Number(this.horometroAnterior);

            this.errorMessage = (this.horometro !== '' && Number.isFinite(nuevo) && nuevo <= anterior)
                ? `El horómetro debe ser mayor a ${anterior.toFixed(2)} h.`
                : '';
            this.hasError = Boolean(this.errorMessage);

            return !this.hasError;
        }
    }"
    @open-edit-horas-modal.window="
        edicionOpen = true;
        action = $event.detail.action;
        fecha = $event.detail.fecha || '';
        horometro = $event.detail.horometro;
        horometroAnterior = $event.detail.horometroAnterior || 0;
        notas = $event.detail.notas || '';
        errorMessage = '';
        hasError = false;
    "
>
    <div x-show="edicionOpen" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4 py-6">
        <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl" @click.outside="edicionOpen = false">
            <div class="flex items-start justify-between bg-[#0B265A] px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-white">Editar registro de horas</h2>
                    <p class="text-xs text-white/75">Solo se puede modificar el último registro.</p>
                </div>
                <button type="button" class="text-white/80 hover:text-white" aria-label="Cerrar" @click="edicionOpen = false">&times;</button>
            </div>

            <form method="POST" :action="action" class="space-y-4 px-5 py-5" @submit="if (!validarEdicion()) $event.preventDefault()">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Fecha del registro</label>
                    <input type="date" name="fecha" x-model="fecha" required
                           class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Horómetro final</label>
                    <input type="number" name="horometro_fin" x-model="horometro" @blur="validarEdicion()"
                           min="0" step="0.01" required
                           class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    <p x-show="errorMessage" class="mt-1 rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700" x-text="errorMessage"></p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Notas</label>
                    <textarea name="notas" rows="3" maxlength="500" x-model="notas"
                              class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="edicionOpen = false">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 disabled:cursor-not-allowed disabled:opacity-50" :disabled="hasError">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.openEditHorasModal = function (button) {
        const modal = document.getElementById('edit-horas-modal');
        const state = window.Alpine?.$data(modal);

        if (!state) {
            return;
        }

        state.action = button.dataset.action || '';
        state.fecha = button.dataset.fecha || '';
        state.horometro = Number(button.dataset.horometro || 0);
        state.horometroAnterior = Number(button.dataset.horometroAnterior || 0);
        state.notas = button.dataset.notas || '';
        state.errorMessage = '';
        state.hasError = false;
        state.edicionOpen = true;
    };
</script>
@endpush

@endif
