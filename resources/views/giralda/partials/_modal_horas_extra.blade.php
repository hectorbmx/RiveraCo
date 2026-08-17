@php
    $horarioBase = $areaGiralda?->horarioActivo;
    $horaEntradaBase = $horarioBase?->hora_entrada ? substr((string) $horarioBase->hora_entrada, 0, 5) : null;
    $horaSalidaBase = $horarioBase?->hora_salida ? substr((string) $horarioBase->hora_salida, 0, 5) : null;
    $semanaHorasExtraInicio = \Carbon\Carbon::parse($semana ?? now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString())->startOfWeek(\Carbon\Carbon::MONDAY);
    $fechaHorasExtraDefault = $semanaHorasExtraInicio->isSameWeek(now()) ? now()->toDateString() : $semanaHorasExtraInicio->toDateString();
    $fechaHorasExtraMin = now()->startOfWeek(\Carbon\Carbon::MONDAY)->subWeek()->toDateString();
    $fechaHorasExtraMax = now()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString();
@endphp

<div x-data="horasExtraModal({ inicio: @js(old('hora_inicio', $horaSalidaBase)), fin: @js(old('hora_fin', $horaSalidaBase)), total: @js(old('total_horas')) })" x-init="recalcular()" class="inline-block text-left">
    @if($esSemanaHorasExtrasEditable ?? true)
        <button type="button" @click="open = true" class="px-3 py-1.5 rounded bg-[#0B265A] text-white hover:bg-blue-900">
            Dar horas
        </button>
    @else
        <span class="px-3 py-1.5 rounded bg-slate-100 text-sm font-semibold text-slate-400">Cerrado</span>
    @endif

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div @click.away="open = false" class="w-full max-w-xl bg-white rounded-lg shadow-xl">
            <div class="p-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-[#0B265A]">Horas extra</h3>
                    <p class="text-xs text-slate-500">{{ $empleado->nombre_completo }}</p>
                </div>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700">X</button>
            </div>

            <form method="POST" action="{{ route('giralda.horas-extras.store', ['tab' => 'horas_extras']) }}" class="p-4 space-y-3">
                @csrf
                <input type="hidden" name="empleado_id" value="{{ $empleado->id_Empleado }}">
                <input type="hidden" name="semana" value="{{ $semana ?? now()->startOfWeek()->toDateString() }}">

                @if($horarioBase)
                    <div class="rounded bg-blue-50 px-3 py-2 text-xs text-blue-800">
                        Horario base: {{ $horaEntradaBase ?? '--:--' }} - {{ $horaSalidaBase ?? '--:--' }}
                    </div>
                @endif

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Fecha</label>
                        <input type="date" name="fecha" value="{{ old('fecha', $fechaHorasExtraDefault) }}" min="{{ $fechaHorasExtraMin }}" max="{{ $fechaHorasExtraMax }}" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Inicio</label>
                        <input type="time" name="hora_inicio" x-model="inicio" @input="activarCalculoAutomatico()" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Fin</label>
                        <input type="time" name="hora_fin" x-model="fin" @input="activarCalculoAutomatico()" class="w-full border rounded p-2" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Horas extra</label>
                        <input type="number" name="total_horas" x-model="total" @input="actualizarFinDesdeTotal()" min="0" step="0.25" class="w-full border rounded p-2" required>
                    </div>
                    <div class="md:col-span-2 flex items-end">
                        <div class="text-xs text-slate-500" x-text="manual ? 'Captura manual' : 'Calculado con inicio y fin'"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Motivo</label>
                    <input name="motivo" class="w-full border rounded p-2" required>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Solicita</label>
                        <input name="responsable_solicita" value="{{ auth()->user()->name ?? '' }}" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Autoriza</label>
                        <input name="responsable_autoriza" class="w-full border rounded p-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="3" class="w-full border rounded p-2"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded bg-slate-200">Cancelar</button>
                    <button class="px-4 py-2 rounded bg-[#0B265A] text-white">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
<script>
function horasExtraModal(defaults) {
    return {
        open: false,
        inicio: defaults.inicio ?? '',
        fin: defaults.fin ?? '',
        total: defaults.total ?? '0.00',
        manual: defaults.total !== null && defaults.total !== undefined && defaults.total !== '',

        activarCalculoAutomatico() {
            this.manual = false;
            this.recalcular();
        },

        recalcular() {
            if (this.manual || !this.inicio || !this.fin) {
                return;
            }

            const inicio = this.minutos(this.inicio);
            let fin = this.minutos(this.fin);

            if (inicio === null || fin === null) {
                return;
            }

            if (fin < inicio) {
                fin += 24 * 60;
            }

            this.total = ((fin - inicio) / 60).toFixed(2);
        },

        actualizarFinDesdeTotal() {
            this.manual = true;

            const inicio = this.minutos(this.inicio);
            const total = this.horasCapturadas();

            if (inicio === null || total === null) {
                return;
            }

            this.fin = this.formatoHora(inicio + Math.round(total * 60));
        },

        horasCapturadas() {
            const total = Number(String(this.total).replace(',', '.'));

            return Number.isFinite(total) && total >= 0 ? total : null;
        },

        formatoHora(minutosTotales) {
            const minutosDia = ((minutosTotales % 1440) + 1440) % 1440;
            const horas = Math.floor(minutosDia / 60);
            const minutos = minutosDia % 60;

            return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}`;
        },

        minutos(valor) {
            const partes = String(valor).split(':');

            if (partes.length !== 2) {
                return null;
            }

            const horas = Number(partes[0]);
            const minutos = Number(partes[1]);

            return Number.isFinite(horas) && Number.isFinite(minutos)
                ? (horas * 60) + minutos
                : null;
        },
    };
}
</script>
@endonce
