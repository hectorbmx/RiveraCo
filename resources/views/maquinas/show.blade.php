@extends('layouts.admin')

@section('title', 'Detalle de máquina')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">
                Máquina: {{ $maquina->nombre ?? '—' }}
            </h1>
            <p class="text-sm text-slate-500">
                Código: {{ $maquina->codigo ?? '—' }} ·
                Estado: <span class="font-medium text-slate-700">{{ $maquina->estado ?? '—' }}</span> ·
                Ubicación: <span class="font-medium text-slate-700">{{ $maquina->ubicacion ?? '—' }}</span>
            </p>

            @if($maquina->asignacionActiva && $maquina->asignacionActiva->obra)
                <p class="mt-1 text-sm text-slate-600">
                    Asignada a: <span class="font-semibold">{{ $maquina->asignacionActiva->obra->nombre ?? 'Obra' }}</span>
                </p>
            @endif
        </div>

        <div class="flex gap-2">
            <a href="{{ route('maquinas.index') }}"
               class="px-3 py-2 rounded-lg border text-sm bg-white hover:bg-slate-50 text-slate-700 border-slate-200">
                ← Volver
            </a>
        </div>
    </div>

    {{-- Tabs --}}
    @php
        $tabs = [
            'general'  => 'General',
            'servicios'=> 'Servicios',
            'obras'    => 'Obras',
            'seguros'  => 'Seguros',
            'kardex'   =>  'Kardex',
        ];
    @endphp

    <div class="border-b mb-4">
        <nav class="flex gap-2">
            @foreach($tabs as $key => $label)
                <a href="{{ route('maquinas.show', ['maquina' => $maquina->id, 'tab' => $key]) }}"
                   class="px-4 py-2 text-sm font-medium rounded-t-lg
                          {{ $tab === $key ? 'bg-white border border-b-0 text-[#0B265A]' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Tab content --}}
    @if($tab === 'general')
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-semibold mb-1">Revisa los datos generales:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl border bg-white p-4">
                <div class="mb-4">
                    <div class="text-sm font-semibold text-slate-800">Datos generales</div>
                    <div class="text-xs text-slate-500">Edita la información base del equipo sin entrar a configuración empresa.</div>
                </div>

                <form method="POST" action="{{ route('maquinas.updateGeneral', $maquina) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Nombre *</label>
                            <input name="nombre" required value="{{ old('nombre', $maquina->nombre) }}"
                                   class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Código</label>
                            <input name="codigo" value="{{ old('codigo', $maquina->codigo) }}"
                                   class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Tipo</label>
                            <input name="tipo" value="{{ old('tipo', $maquina->tipo) }}"
                                   class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Marca</label>
                            <input name="marca" value="{{ old('marca', $maquina->marca) }}"
                                   class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Modelo / Año</label>
                            <input name="modelo" value="{{ old('modelo', $maquina->modelo) }}"
                                   class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Número de serie</label>
                            <input name="numero_serie" value="{{ old('numero_serie', $maquina->numero_serie) }}"
                                   class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Placas</label>
                            <input name="placas" value="{{ old('placas', $maquina->placas) }}"
                                   class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Color</label>
                            <input name="color" value="{{ old('color', $maquina->color) }}"
                                   class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Horómetro base</label>
                            <input name="horometro_base" type="number" step="0.01" min="0"
                                   value="{{ old('horometro_base', $maquina->horometro_base) }}"
                                   class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Notas</label>
                        <textarea name="notas" rows="3"
                                  class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">{{ old('notas', $maquina->notas) }}</textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-[#0B265A] text-white hover:opacity-90">
                            Guardar datos generales
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border bg-white p-4">
                <div class="text-sm font-semibold text-slate-800 mb-3">Resumen</div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Estado</span>
                        <span class="font-medium text-slate-900">{{ $maquina->estado ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Ubicación</span>
                        <span class="font-medium text-slate-900">{{ $maquina->ubicacion ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Asignación activa</span>
                        <span class="font-medium text-slate-900">
                            {{ $maquina->asignacionActiva ? 'Sí' : 'No' }}
                        </span>
                    </div>
                </div>

                {{-- aquí después meteremos el toggle (por ahora no) --}}
                <div class="mt-4 text-xs text-slate-500">
                    {{-- Toggle servicio --}}
<div class="mt-4 pt-4 border-t">
    <div class="text-sm font-semibold text-slate-800 mb-2">Acciones</div>

    <form id="form_cambio_estado_maquina" method="POST" action="{{ route('maquinas.cambiarEstado', $maquina) }}" class="space-y-3">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-1">
            <label class="block text-xs text-slate-500 mb-1">Nuevo estado</label>

            @php
                $opciones = [];

                if ($maquina->estado === \App\Models\Maquina::ESTADO_OPERATIVA) {
                    $opciones = [\App\Models\Maquina::ESTADO_FUERA_SERVICIO => 'Fuera de servicio'];
                } elseif ($maquina->estado === \App\Models\Maquina::ESTADO_FUERA_SERVICIO) {
                    $opciones = [
                        \App\Models\Maquina::ESTADO_EN_REPARACION => 'En reparación',
                        \App\Models\Maquina::ESTADO_OPERATIVA     => 'Operativa',
                    ];
                } elseif ($maquina->estado === \App\Models\Maquina::ESTADO_EN_REPARACION) {
                    $opciones = [\App\Models\Maquina::ESTADO_OPERATIVA => 'Operativa'];
                }
            @endphp

            @if(empty($opciones))
                <select class="w-full rounded-lg border-slate-200 text-sm bg-slate-100" disabled>
                    <option>Estado no editable</option>
                </select>
            @else
                <select name="estado" class="w-full rounded-lg border-slate-200 text-sm">
                    @foreach($opciones as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="md:col-span-1">
            <label class="block text-xs text-slate-500 mb-1">Motivo (opcional)</label>
            <input type="text" name="motivo" class="w-full rounded-lg border-slate-200 text-sm"
                   placeholder="Ej. Falla hidráulica" value="{{ old('motivo') }}" />
        </div>

        <div class="md:col-span-1">
            <label class="block text-xs text-slate-500 mb-1">Notas (opcional)</label>
            <input type="text" name="notas" class="w-full rounded-lg border-slate-200 text-sm"
                   placeholder="Detalle breve" value="{{ old('notas') }}" />
        </div>
    </div>

    <button type="submit"
            id="btn_cambio_estado_maquina"
            class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium
                   bg-[#0B265A] text-white hover:opacity-90 disabled:bg-slate-200 disabled:text-slate-600"
            @disabled(empty($opciones))>
        Aplicar cambio de estado
    </button>
</form>

    <p class="mt-3 text-xs text-slate-500">
        * Las asignaciones cambian ubicación automáticamente. Aquí solo se cambia el estado (operativa / fuera de servicio).
    </p>
</div>
                </div>
            </div>
        </div>
    @endif

    @if($tab === 'servicios')
        <div class="rounded-xl border bg-white overflow-hidden">
            <div class="px-4 py-3 border-b flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-slate-800">Servicios realizados</div>
                <div class="text-xs text-slate-500">Mantenimientos ligados a esta máquina.</div>
                </div>
                <a href="{{ route('mantenimiento.mantenimientos.create', ['maquina_id' => $maquina->id]) }}"
                   class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                    + Programar servicio
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Folio</th>
                            <th class="text-left px-4 py-3">Tipo</th>
                            <th class="text-left px-4 py-3">Estatus</th>
                            <th class="text-left px-4 py-3">Fecha</th>
                            <th class="text-left px-4 py-3">Detalles</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($maquina->mantenimientos ?? [] as $mtto)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">{{ $mtto->folio ?? $mtto->id }}</td>
                                <td class="px-4 py-3">{{ $mtto->tipo ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $mtto->estatus ?? '—' }}</td>
                                <td class="px-4 py-3">{{ optional($mtto->fecha_programada)->format('Y-m-d') ?? ($mtto->created_at?->format('Y-m-d') ?? '—') }}</td>
                                <td class="px-4 py-3 text-slate-500">—</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Sin servicios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($tab === 'obras')
        <div class="rounded-xl border bg-white overflow-hidden">
            <div class="px-4 py-3 border-b">
                <div class="text-sm font-semibold text-slate-800">Historial de obras</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Obra</th>
                            <th class="text-left px-4 py-3">Inicio</th>
                            <th class="text-left px-4 py-3">Fin</th>
                            <th class="text-left px-4 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($maquina->asignaciones ?? [] as $a)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    {{ $a->obra->nombre ?? '—' }}
                                </td>
                                <td class="px-4 py-3">{{ $a->fecha_inicio?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $a->fecha_fin?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3">
    @php
        $obraCerrada = in_array((int) ($a->obra?->estatus_nuevo ?? 0), [\App\Models\Obra::ESTATUS_TERMINADA, \App\Models\Obra::ESTATUS_CANCELADA], true);
        $estadoVisible = ($a->estado === 'activa' && $obraCerrada) ? 'finalizada' : ($a->estado ?? '-');
    @endphp
    {{ $estadoVisible }}
    @if($a->estado === 'activa' && $obraCerrada)
        <div class="text-xs text-slate-400">Liberada por obra cerrada</div>
    @endif
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">Sin historial de obras.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($tab === 'seguros')
<div class="rounded-xl border bg-white overflow-hidden">
    <div class="px-4 py-3 border-b flex items-center justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-800">Historial de seguros</div>
        </div>

        <a 
    href="{{ route('maquinas.seguros.create', $maquina) }}"
    class="px-3 py-2 rounded-lg border text-sm bg-blue-600 text-white hover:bg-blue-700"
>
    + Agregar seguro
</a>
    </div>

    <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
        <tr>
            <th class="px-2 py-3"></th>
            <th class="text-left px-4 py-3">Aseguradora</th>
            <th class="text-left px-4 py-3">Póliza</th>
            <th class="text-left px-4 py-3">Inicio</th>
            <th class="text-left px-4 py-3">Fin</th>
            <th class="text-left px-4 py-3">Archivo</th>
            <th class="text-left px-4 py-3"></th>
        </tr>
    </thead>

    <tbody class="divide-y">
        @forelse($maquina->seguros ?? [] as $s)
           @php
    $hoy = \Carbon\Carbon::today();
    $fin = $s->vigencia_hasta;
    $dias = $fin ? $hoy->diffInDays($fin, false) : null;

    if ($fin && $fin->lt($hoy)) {
        $tooltip = 'Vencido hace ' . abs($dias) . ' días';
        $estado = 'rojo';
    } elseif ($dias !== null && $dias <= 60) {
        $tooltip = 'Vence en ' . $dias . ' días';
        $estado = 'amarillo';
    } else {
        $tooltip = 'Vigente';
        $estado = 'verde';
    }
@endphp

            <tr class="hover:bg-slate-50">
              <td class="px-2 py-3 align-middle">
                    <span
                        title="{{ $tooltip }}"
                        style="
                            display:inline-block;
                            width:12px;
                            height:12px;
                            border-radius:9999px;
                            background:
                            @if($estado === 'rojo')
                                #ef4444
                            @elseif($estado === 'amarillo')
                                #facc15
                            @else
                                #22c55e
                            @endif
                            ;
                        "
                    ></span>
                </td>

                <td class="px-4 py-3">{{ $s->aseguradora ?? '—' }}</td>
                <td class="px-4 py-3">{{ $s->poliza_numero ?? '—' }}</td>
                <td class="px-4 py-3">{{ $s->vigencia_desde?->format('Y-m-d') ?? '—' }}</td>
                <td class="px-4 py-3">{{ $s->vigencia_hasta?->format('Y-m-d') ?? '—' }}</td>

                <td class="px-4 py-3">
                    @if($s->documento_path)
                        <a href="{{ Storage::disk('public')->url(ltrim($s->documento_path, '/')) }}" target="_blank" class="text-blue-600 hover:underline">
                            Ver archivo
                        </a>
                        <!-- <a href="{{ Storage::disk('public')->url(ltrim($s->documento_path, '/')) }}" target="_blank" class="text-blue-600 hover:underline">
                            Ver archivo
                            </a> -->
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </td>

                <td class="px-4 py-3">
                    <a href="{{ route('maquinas.seguros.edit', [$maquina, $s]) }}" class="text-blue-600 hover:underline text-sm">
                        Editar
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-6 text-center text-slate-500">
                    Sin seguros registrados.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
    </div>
</div>
@endif
    @if($tab === 'kardex')
<div class="rounded-xl border bg-white overflow-hidden">
    <div class="px-4 py-3 border-b">
        <div class="text-sm font-semibold text-slate-800">Kardex / Historial de movimientos</div>
        <div class="text-xs text-slate-500">
            Registro completo de cambios de estado y ubicación.
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Fecha</th>
                    <th class="text-left px-4 py-3">Tipo</th>
                    <th class="text-left px-4 py-3">Ubicación</th>
                    <th class="text-left px-4 py-3">Estado</th>
                    <th class="text-left px-4 py-3">Obra</th>
                    <th class="text-left px-4 py-3">Usuario</th>
                    <th class="text-left px-4 py-3">Motivo</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($maquina->movimientos ?? [] as $mov)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $mov->fecha_evento?->format('Y-m-d H:i') ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ ucfirst(str_replace('_', ' ', $mov->tipo)) }}
                        </td>

                        <td class="px-4 py-3">
                            @if($mov->ubicacion_anterior || $mov->ubicacion_nueva)
                                <span class="text-slate-600 text-xs">
                                    {{ $mov->ubicacion_anterior ?? '—' }}
                                    →
                                    <strong>{{ $mov->ubicacion_nueva ?? '—' }}</strong>
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            @if($mov->estado_anterior || $mov->estado_nuevo)
                                <span class="text-slate-600 text-xs">
                                    {{ $mov->estado_anterior ?? '—' }}
                                    →
                                    <strong>{{ $mov->estado_nuevo ?? '—' }}</strong>
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            {{ $mov->obra->nombre ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $mov->user->name ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-slate-500">
                            {{ $mov->motivo ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">
                            Sin movimientos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<div id="modal_cargando_cambio_estado"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 px-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modal_cargando_cambio_estado_titulo">
    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
        <div class="flex items-center gap-4">
            <div class="h-11 w-11 flex-shrink-0 rounded-full border-4 border-slate-200 border-t-[#0B265A] animate-spin"></div>

            <div>
                <h2 id="modal_cargando_cambio_estado_titulo" class="text-sm font-semibold text-slate-900">
                    Aplicando cambio de estado
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Guardando movimiento de la maquina...
                </p>
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form_cambio_estado_maquina');
    const modal = document.getElementById('modal_cargando_cambio_estado');
    const button = document.getElementById('btn_cambio_estado_maquina');

    if (!form || !modal) {
        return;
    }

    form.addEventListener('submit', function () {
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (button) {
            button.disabled = true;
            button.textContent = 'Aplicando...';
        }
    });
});
</script>
@endpush

