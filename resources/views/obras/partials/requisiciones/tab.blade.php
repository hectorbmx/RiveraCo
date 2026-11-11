@php
    use App\Models\ObraRequisicion;

    $requisiciones = $requisicionesObra ?? collect();
    $stats = $requisicionesStats ?? [
        'total' => 0,
        'pendientes' => 0,
        'autorizadas' => 0,
        'en_surtido' => 0,
    ];

    $estadoLabel = function ($estado) {
        return match ((string) $estado) {
            ObraRequisicion::ESTADO_PENDIENTE_REVISION => 'Pendiente de revision',
            ObraRequisicion::ESTADO_AUTORIZADA => 'Autorizada',
            ObraRequisicion::ESTADO_RECHAZADA => 'Rechazada',
            ObraRequisicion::ESTADO_EN_SURTIDO => 'En surtido',
            ObraRequisicion::ESTADO_SURTIDA_PARCIAL => 'Surtida parcial',
            ObraRequisicion::ESTADO_SURTIDA => 'Surtida',
            ObraRequisicion::ESTADO_RECIBIDA => 'Recibida',
            ObraRequisicion::ESTADO_CANCELADA => 'Cancelada',
            default => str_replace('_', ' ', ucfirst((string) $estado)),
        };
    };

    $estadoBadgeClass = function ($estado) {
        return match ((string) $estado) {
            ObraRequisicion::ESTADO_AUTORIZADA => 'inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800 ring-1 ring-inset ring-green-200',
            ObraRequisicion::ESTADO_PENDIENTE_REVISION => 'inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200',
            ObraRequisicion::ESTADO_RECHAZADA => 'inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800 ring-1 ring-inset ring-red-200',
            ObraRequisicion::ESTADO_EN_SURTIDO, ObraRequisicion::ESTADO_SURTIDA_PARCIAL, ObraRequisicion::ESTADO_SURTIDA, ObraRequisicion::ESTADO_RECIBIDA => 'inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800 ring-1 ring-inset ring-blue-200',
            ObraRequisicion::ESTADO_CANCELADA => 'inline-flex items-center rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-300',
            default => 'inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200',
        };
    };
@endphp

<div class="space-y-5">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Requisiciones de obra</h2>
            <p class="text-sm text-slate-500">Solicitudes de personal, maquinaria, herramientas, materiales u otros recursos para esta obra.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('obras.requisiciones.index', $obra) }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[#0B265A] shadow-sm transition hover:bg-slate-50">
                Ver bandeja
            </a>
            <a href="{{ route('obras.requisiciones.create', $obra) }}"
               class="inline-flex items-center justify-center rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900">
                Nueva requisicion
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $stats['total'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pendientes</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $stats['pendientes'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Autorizadas</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $stats['autorizadas'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">En surtido</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $stats['en_surtido'] ?? 0 }}</p>
        </div>
    </div>

    @if($requisiciones->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <p class="text-sm font-semibold text-slate-700">Aun no hay requisiciones para esta obra.</p>
            <p class="mt-1 text-sm text-slate-500">Crea la primera cabecera para empezar a capturar partidas.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#0B265A] text-left text-white">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Folio</th>
                            <th class="px-4 py-3 font-semibold">Tipo</th>
                            <th class="px-4 py-3 font-semibold">Fecha de creación</th>
                            <th class="px-4 py-3 font-semibold">Fecha requerida</th>
                            <th class="px-4 py-3 font-semibold">Partidas</th>
                            <th class="px-4 py-3 font-semibold">Solicita</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requisiciones as $requisicion)
                            <tr class="border-t border-slate-200 hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('obras.requisiciones.show', [$obra, $requisicion]) }}" class="text-[#0B265A] underline-offset-2 hover:underline">{{ $requisicion->folio }}</a></td>
                                <td class="px-4 py-3 text-slate-700">
                                    <span tabindex="0" class="cursor-help border-b border-dotted border-slate-400"
                                          title="{{ $requisicion->detalles->map(fn ($detalle) => $detalle->descripcion . ' — ' . number_format((float) $detalle->cantidad_solicitada, 3) . ' ' . ($detalle->unidad ?? ''))->implode("\n") ?: 'Sin partidas capturadas.' }}">{{ $requisicion->tipo?->nombre ?? '-' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ optional($requisicion->created_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($requisicion->fecha_requerida)->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $requisicion->detalles_count ?? $requisicion->detalles->count() }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $requisicion->solicitadoPor?->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="{{ $estadoBadgeClass($requisicion->estado) }}">{{ $estadoLabel($requisicion->estado) }}</span>
                                </td>
                                <td class="px-4 py-3">@include('obras.partials.requisiciones.acciones', ['obra' => $obra, 'requisicion' => $requisicion])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>





