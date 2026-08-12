@extends('layouts.admin')

@section('title', 'Horas extras - ' . $empleado->nombre_completo)

@section('content')
@php
    $puedeEditarHe = auth()->user()?->can('giralda.horas_extras.edit.access');
    $puedeEliminarHe = auth()->user()?->can('giralda.horas_extras.delete.access');
    $mostrarAccionesHe = $puedeEditarHe || $puedeEliminarHe;
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('giralda.empleados', ['tab' => 'horas_extras', 'estatus' => 'activo', 'semana' => $semana]) }}" class="inline-flex items-center rounded border px-3 py-2 text-sm font-semibold text-[#0B265A] hover:bg-slate-50">Regresar a lista</a>
            <h1 class="mt-2 text-2xl font-bold text-[#0B265A]">Horas extras</h1>
            <p class="text-sm text-slate-500">{{ $empleado->nombre_completo }} - ID {{ $empleado->id_Empleado }}</p>
        </div>
        <div class="text-right">
            <div class="text-xs uppercase tracking-wide text-slate-400">Semana seleccionada</div>
            <div class="text-sm font-semibold text-[#0B265A]">{{ $semanaTitulo }}</div>
            <div class="mt-1 text-xs {{ $esSemanaActual ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $esSemanaActual ? 'Semana editable' : 'Solo lectura: semana cerrada' }}
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-400">Total horas semana</div>
            <div class="text-3xl font-bold text-[#0B265A]">{{ number_format($totalHoras, 2) }}</div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('giralda.empleados.horas-extras', ['empleado' => $empleado->id_Empleado, 'semana' => $semanaAnterior]) }}" class="px-3 py-2 rounded border text-sm">Anterior</a>
            <a href="{{ route('giralda.empleados.horas-extras', ['empleado' => $empleado->id_Empleado, 'semana' => $semanaActual]) }}" class="px-3 py-2 rounded border text-sm">Semana actual</a>
            <a href="{{ route('giralda.empleados.horas-extras', ['empleado' => $empleado->id_Empleado, 'semana' => $semanaSiguiente]) }}" class="px-3 py-2 rounded border text-sm">Siguiente</a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h2 class="font-semibold text-[#0B265A]">Registros de la semana</h2>
            <p class="text-xs text-slate-500">{{ $registros->count() }} registros encontrados</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left p-3">Fecha</th>
                        <th class="text-left p-3">Horario</th>
                        <th class="text-right p-3">Horas</th>
                        <th class="text-left p-3">Motivo</th>
                        <th class="text-left p-3">Solicita</th>
                        <th class="text-left p-3">Autoriza</th>
                        <th class="text-left p-3">Estado</th>
                        @if($mostrarAccionesHe)
                            <th class="text-right p-3">Accion</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($registros as $registro)
                        <tr>
                            <td class="p-3">{{ optional($registro->fecha)->format('d/m/Y') }}</td>
                            <td class="p-3">{{ substr($registro->hora_inicio, 0, 5) }} - {{ substr($registro->hora_fin, 0, 5) }}</td>
                            <td class="p-3 text-right font-semibold text-[#0B265A]">{{ number_format((float) $registro->total_horas, 2) }}</td>
                            <td class="p-3">{{ $registro->motivo }}</td>
                            <td class="p-3">{{ $registro->responsable_solicita }}</td>
                            <td class="p-3">{{ $registro->responsable_autoriza ?: '-' }}</td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs {{ $registro->estado === 'autorizado' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ ucfirst($registro->estado) }}
                                </span>
                            </td>
                            @if($mostrarAccionesHe)
                                <td class="p-3 text-right">
                                    @if($esSemanaActual)
                                        <div class="flex justify-end gap-3" x-data="{ editOpen: false }">
                                            @if($puedeEditarHe)
                                                <button type="button" @click="editOpen = true" class="text-blue-600 hover:underline">Editar</button>

                                                <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 text-left">
                                                    <div @click.away="editOpen = false" class="w-full max-w-xl rounded-lg bg-white shadow-xl">
                                                        <div class="flex items-center justify-between border-b p-4">
                                                            <div>
                                                                <h3 class="font-semibold text-[#0B265A]">Editar horas extra</h3>
                                                                <p class="text-xs text-slate-500">{{ $empleado->nombre_completo }}</p>
                                                            </div>
                                                            <button type="button" @click="editOpen = false" class="text-slate-400 hover:text-slate-700">X</button>
                                                        </div>

                                                        <form method="POST" action="{{ route('giralda.horas-extras.update', ['horaExtra' => $registro, 'semana' => $semana]) }}" class="space-y-3 p-4" x-data="horasExtraEditModal({ inicio: @js(substr($registro->hora_inicio, 0, 5)), fin: @js(substr($registro->hora_fin, 0, 5)), total: @js(number_format((float) $registro->total_horas, 2, '.', '')) })">
                                                            @csrf
                                                            @method('PUT')

                                                            <div class="grid grid-cols-3 gap-2">
                                                                <div>
                                                                    <label class="mb-1 block text-sm font-medium">Fecha</label>
                                                                    <input type="date" name="fecha" value="{{ optional($registro->fecha)->toDateString() }}" class="w-full rounded border p-2" required>
                                                                </div>
                                                                <div>
                                                                    <label class="mb-1 block text-sm font-medium">Inicio</label>
                                                                    <input type="time" name="hora_inicio" x-model="inicio" @input="recalcular()" class="w-full rounded border p-2" required>
                                                                </div>
                                                                <div>
                                                                    <label class="mb-1 block text-sm font-medium">Fin</label>
                                                                    <input type="time" name="hora_fin" x-model="fin" @input="recalcular()" class="w-full rounded border p-2" required>
                                                                </div>
                                                            </div>

                                                            <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                                                                <div>
                                                                    <label class="mb-1 block text-sm font-medium">Horas extra</label>
                                                                    <input type="number" name="total_horas" x-model="total" @input="actualizarFinDesdeTotal()" min="0" max="24" step="0.25" class="w-full rounded border p-2" required>
                                                                </div>
                                                            </div>

                                                            <div>
                                                                <label class="mb-1 block text-sm font-medium">Motivo</label>
                                                                <input name="motivo" value="{{ $registro->motivo }}" class="w-full rounded border p-2" required>
                                                            </div>

                                                            <div class="grid grid-cols-2 gap-2">
                                                                <div>
                                                                    <label class="mb-1 block text-sm font-medium">Solicita</label>
                                                                    <input name="responsable_solicita" value="{{ $registro->responsable_solicita }}" class="w-full rounded border p-2" required>
                                                                </div>
                                                                <div>
                                                                    <label class="mb-1 block text-sm font-medium">Autoriza</label>
                                                                    <input name="responsable_autoriza" value="{{ $registro->responsable_autoriza }}" class="w-full rounded border p-2">
                                                                </div>
                                                            </div>

                                                            <div>
                                                                <label class="mb-1 block text-sm font-medium">Observaciones</label>
                                                                <textarea name="observaciones" rows="3" class="w-full rounded border p-2">{{ $registro->observaciones }}</textarea>
                                                            </div>

                                                            <div class="flex justify-end gap-2 pt-2">
                                                                <button type="button" @click="editOpen = false" class="rounded bg-slate-200 px-4 py-2">Cancelar</button>
                                                                <button class="rounded bg-[#0B265A] px-4 py-2 text-white">Guardar</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($puedeEliminarHe)
                                                <form method="POST" action="{{ route('giralda.horas-extras.destroy', ['horaExtra' => $registro, 'semana' => $semana]) }}" class="inline" onsubmit="return confirm('Eliminar este registro de horas extras?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-red-600 hover:underline">Eliminar</button>
                                                </form>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">Cerrado</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $mostrarAccionesHe ? 8 : 7 }}" class="p-6 text-center text-slate-400">No hay horas extras registradas en esta semana.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@once
<script>
function horasExtraEditModal(defaults) {
    return {
        inicio: defaults.inicio ?? '',
        fin: defaults.fin ?? '',
        total: defaults.total ?? '0.00',

        recalcular() {
            if (!this.inicio || !this.fin) {
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
            const inicio = this.minutos(this.inicio);
            const total = Number(String(this.total).replace(',', '.'));

            if (inicio === null || !Number.isFinite(total) || total < 0) {
                return;
            }

            this.fin = this.formatoHora(inicio + Math.round(total * 60));
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

        formatoHora(minutosTotales) {
            const minutosDia = ((minutosTotales % 1440) + 1440) % 1440;
            const horas = Math.floor(minutosDia / 60);
            const minutos = minutosDia % 60;

            return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}`;
        },
    };
}
</script>
@endonce
@endsection