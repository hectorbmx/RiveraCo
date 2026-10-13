@extends('layouts.admin')

@section('title', $modo === 'edit' ? 'Editar requisicion' : 'Requisicion')

@section('content')
@php
    use App\Models\ObraRequisicion;

    $estadoLabel = match ((string) $requisicion->estado) {
        ObraRequisicion::ESTADO_PENDIENTE_REVISION => 'Pendiente de revision',
        ObraRequisicion::ESTADO_AUTORIZADA => 'Autorizada',
        ObraRequisicion::ESTADO_RECHAZADA => 'Rechazada',
        ObraRequisicion::ESTADO_EN_SURTIDO => 'En surtido',
        ObraRequisicion::ESTADO_SURTIDA_PARCIAL => 'Surtida parcial',
        ObraRequisicion::ESTADO_SURTIDA => 'Surtida',
        ObraRequisicion::ESTADO_RECIBIDA => 'Recibida',
        ObraRequisicion::ESTADO_CANCELADA => 'Cancelada',
        default => str_replace('_', ' ', ucfirst((string) $requisicion->estado)),
    };

    $catalogoItems = $catalogoItems ?? collect();
    $tipoItem = $requisicion->tipo?->item_type;
    $esMaquina = $tipoItem === 'maquina';
    $esProducto = $tipoItem === 'producto';
    $agregarLabel = $esMaquina ? 'Agregar maquina' : ($esProducto ? 'Agregar material' : 'Agregar empleado');
    $buscarPlaceholder = $esMaquina
        ? 'Buscar por maquina, numero economico, tipo u obra'
        : ($esProducto ? 'Buscar por producto, SKU o unidad' : 'Buscar por nombre, puesto u obra');
    $columnaPrincipalLabel = $esMaquina ? 'Maquina' : ($esProducto ? 'Producto' : 'Nombre');
    $columnaSecundariaLabel = $esMaquina ? 'Num. eco / tipo' : ($esProducto ? 'SKU / unidad' : 'Puesto');
    $columnaDisponibilidadLabel = $esProducto ? 'Existencia' : 'Disponibilidad';
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Obra / Requisiciones</div>
            <h1 class="mt-1 text-2xl font-bold text-[#0B265A]">{{ $requisicion->folio }}</h1>
            <p class="text-sm text-slate-500">
                {{ $obra->nombre }} · <span class="font-mono">{{ $obra->clave_obra }}</span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('obras.requisiciones.imprimir', [$obra, $requisicion]) }}"
               target="_blank"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[#0B265A] shadow-sm transition hover:bg-slate-50">
                Imprimir
            </a>
            @if($modo !== 'edit')
                <a href="{{ route('obras.requisiciones.edit', [$obra, $requisicion]) }}"
                   class="inline-flex items-center justify-center rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900">
                    Editar
                </a>
            @endif
            <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'requisiciones']) }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[#0B265A] shadow-sm transition hover:bg-slate-50">
                Volver a requisiciones
            </a>
        </div>
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

    @if($modo === 'edit' && ! $editable)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Esta requisicion ya no esta pendiente de revision, por eso solo se puede consultar.
        </div>
    @endif

    @if($editable)
        <form method="POST" action="{{ route('obras.requisiciones.update', [$obra, $requisicion]) }}" class="space-y-6">
            @csrf
            @method('PATCH')
    @else
        <div class="space-y-6">
    @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $requisicion->tipo?->nombre ?? '-' }}</p>
                <p class="text-xs text-slate-500">Catalogo {{ $requisicion->numero_catalogo }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $estadoLabel }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha solicitud</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ optional($requisicion->fecha_solicitud)->format('d/m/Y') ?? '-' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Solicita</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $requisicion->solicitadoPor?->name ?? '-' }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-[220px_minmax(0,1fr)] md:items-start">
                <div>
                    <label for="fecha_requerida" class="block text-sm font-semibold text-slate-700">Fecha requerida</label>
                    @if($editable)
                        <input id="fecha_requerida"
                               type="date"
                               name="fecha_requerida"
                               value="{{ old('fecha_requerida', optional($requisicion->fecha_requerida)->format('Y-m-d')) }}"
                               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                    @else
                        <p class="mt-2 text-sm text-slate-700">{{ optional($requisicion->fecha_requerida)->format('d/m/Y') ?? '-' }}</p>
                    @endif
                </div>
                <div>
                    <label for="observaciones" class="block text-sm font-semibold text-slate-700">Observaciones</label>
                    @if($editable)
                        <textarea id="observaciones"
                                  name="observaciones"
                                  rows="2"
                                  class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A]">{{ old('observaciones', $requisicion->observaciones) }}</textarea>
                    @else
                        <p class="mt-2 min-h-10 whitespace-pre-line rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">{{ $requisicion->observaciones ?: '-' }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if($editable)
            <div id="agregar-empleados-panel" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <label for="buscar-empleado-requisicion" class="block text-sm font-semibold text-slate-700">{{ $agregarLabel }}</label>
                <input id="buscar-empleado-requisicion"
                       type="search"
                       autocomplete="off"
                       placeholder="{{ $buscarPlaceholder }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A]">

                <div id="catalogo-empleados-results" class="mt-3 hidden overflow-hidden rounded-xl border border-slate-200">
                    <div class="grid items-center gap-3 bg-[#0B265A] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white" style="grid-template-columns: minmax(0, 1.1fr) minmax(150px, 0.85fr) minmax(170px, 0.9fr);">
                        <span>{{ $columnaPrincipalLabel }}</span>
                        <span>{{ $columnaSecundariaLabel }}</span>
                        <span class="text-right">{{ $columnaDisponibilidadLabel }}</span>
                    </div>
                    <div id="catalogo-empleados-edit" class="max-h-80 divide-y divide-slate-200 overflow-y-auto">
                        @forelse($catalogoItems as $item)
                            @php
                                $obraAsignada = $item['asignacion'] ?? null;
                                $badgeClass = match ($item['badge_key'] ?? 'disponible') {
                                    'asignado_esta_obra' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                    'asignado_otra_obra', 'stock_bajo' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                    'stock_cero' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                    default => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                };
                                $estadoClass = match ($item['estado'] ?? null) {
                                    'operativa' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                    'fuera_servicio', 'en_reparacion' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                    'baja_definitiva' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                    default => 'bg-slate-50 text-slate-700 ring-slate-200',
                                };
                                $badgeText = $obraAsignada['obra_nombre'] ?? ($item['badge'] ?? 'Disponible');
                                $searchText = strtolower(trim(($item['label'] ?? '') . ' ' . ($item['subtitle'] ?? '') . ' ' . ($obraAsignada['obra_nombre'] ?? '') . ' ' . ($obraAsignada['obra_clave'] ?? '')));
                            @endphp
                            <button type="button"
                                    class="catalogo-empleado-row hidden w-full cursor-pointer items-center gap-3 px-3 py-3 text-left hover:bg-slate-50"
                                    style="grid-template-columns: minmax(0, 1.1fr) minmax(150px, 0.85fr) minmax(170px, 0.9fr);"
                                    data-search="{{ e($searchText) }}"
                                    data-item-id="{{ $item['id'] }}"
                                    title="Doble click para agregar">
                                <span class="min-w-0 text-sm font-semibold text-slate-800">{{ $item['label'] }}</span>
                                                                <span class="min-w-0 text-sm font-medium text-slate-500">
                                    <span class="block">{{ $item['subtitle'] ?: ($esMaquina ? 'Sin tipo' : ($esProducto ? 'Sin SKU / unidad' : 'Sin puesto')) }}</span>
                                    @if($esMaquina && ! empty($item['estado_label']))
                                        <span class="mt-1 inline-flex w-fit rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $estadoClass }}">{{ $item['estado_label'] }}</span>
                                    @endif
                                </span>
                                <span class="min-w-0 text-right">
                                    <span class="inline-flex max-w-full flex-col rounded-full px-2.5 py-1 text-xs font-semibold leading-tight ring-1 {{ $badgeClass }}">
                                        <span class="truncate">{{ $badgeText }}</span>
                                        @if($obraAsignada)
                                            <span class="block text-[11px] font-normal text-slate-500">{{ $obraAsignada['obra_clave'] ?? '' }}</span>
                                        @endif
                                    </span>
                                </span>
                            </button>
                        @empty
                            <div class="px-4 py-6 text-center text-sm text-slate-500">No hay empleados disponibles para agregar.</div>
                        @endforelse
                    </div>
                    <div id="catalogo-empleados-empty" class="hidden px-4 py-6 text-center text-sm text-slate-500">No hay coincidencias para la busqueda.</div>
                </div>
                <p id="catalogo-empleados-help" class="mt-2 text-xs text-slate-500">Escribe al menos 2 caracteres y haz doble click sobre la partida para agregarla.</p>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#0B265A] text-left text-white">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Partida</th>
                            <th class="px-4 py-3 font-semibold">Tipo</th>
                            <th class="px-4 py-3 font-semibold">Unidad</th>
                            <th class="px-4 py-3 font-semibold">Cantidad</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 font-semibold">Observaciones</th>
                            @if($editable)
                                <th class="px-4 py-3 font-semibold text-center">Eliminar</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requisicion->detalles as $detalle)
                            <tr class="border-t border-slate-200 hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ $detalle->descripcion }}</div>
                                    <div class="text-xs text-slate-500">{{ $detalle->descripcion_snapshot }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $detalle->item_type ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $detalle->unidad ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if($editable)
                                        <input type="number"
                                               min="0.001"
                                               step="0.001"
                                               name="detalles[{{ $detalle->id }}][cantidad_solicitada]"
                                               value="{{ old("detalles.{$detalle->id}.cantidad_solicitada", $detalle->cantidad_solicitada) }}"
                                               class="w-28 rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                    @else
                                        {{ number_format((float) $detalle->cantidad_solicitada, 3) }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ ucfirst(str_replace('_', ' ', $detalle->estado)) }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if($editable)
                                        <input type="text"
                                               name="detalles[{{ $detalle->id }}][observaciones]"
                                               value="{{ old("detalles.{$detalle->id}.observaciones", $detalle->observaciones) }}"
                                               class="w-full min-w-48 rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                                    @else
                                        {{ $detalle->observaciones ?: '-' }}
                                    @endif
                                </td>
                                @if($editable)
                                    <td class="px-4 py-3 text-center">
                                        <button type="submit"
                                                form="delete-detalle-{{ $detalle->id }}"
                                                title="Eliminar partida requerida"
                                                aria-label="Eliminar partida requerida"
                                                onclick="return confirm('{{ $requisicion->detalles->count() <= 1 ? 'Si borras la ultima partida se borrara la requisicion completa. ¿Deseas continuar?' : '¿Deseas eliminar esta partida requerida de la requisicion?' }}')"
                                                class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-700 transition hover:bg-red-100"
                                                style="height: 36px; width: 36px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="M19 6l-1 14H6L5 6" />
                                                <path d="M10 11v5" />
                                                <path d="M14 11v5" />
                                            </svg>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $editable ? 7 : 6 }}" class="px-4 py-8 text-center text-sm text-slate-500">Esta requisicion no tiene partidas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'requisiciones']) }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Volver
            </a>
            @if($editable)
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900">
                    Guardar cambios
                </button>
            @endif
        </div>

    @if($editable)
        </form>

        <form id="add-detalles-form" method="POST" action="{{ route('obras.requisiciones.detalles.store', [$obra, $requisicion]) }}" class="hidden">
            @csrf
            <input type="hidden" id="add-detalle-selected-item" name="selected_items[]" value="">
        </form>

        @foreach($requisicion->detalles as $detalle)
            <form id="delete-detalle-{{ $detalle->id }}" method="POST" action="{{ route('obras.requisiciones.detalles.destroy', [$obra, $requisicion, $detalle]) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @else
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('buscar-empleado-requisicion');
        const results = document.getElementById('catalogo-empleados-results');
        const selectedInput = document.getElementById('add-detalle-selected-item');
        const addForm = document.getElementById('add-detalles-form');
        const rows = Array.from(document.querySelectorAll('.catalogo-empleado-row'));
        const empty = document.getElementById('catalogo-empleados-empty');

        if (!input || !results || !selectedInput || !addForm || !rows.length) {
            return;
        }

        const filterRows = function () {
            const query = input.value.trim().toLocaleLowerCase('es-MX');
            let visible = 0;

            results.classList.toggle('hidden', query.length < 2);

            rows.forEach(function (row) {
                const search = (row.dataset.search || '').toLocaleLowerCase('es-MX');
                const matches = query.length >= 2 && search.includes(query);
                row.classList.toggle('hidden', !matches);
                row.style.display = matches ? 'grid' : 'none';

                if (matches) {
                    visible += 1;
                }
            });

            if (empty) {
                empty.classList.toggle('hidden', query.length < 2 || visible > 0);
            }
        };

        input.addEventListener('input', filterRows);

        rows.forEach(function (row) {
            row.addEventListener('dblclick', function () {
                selectedInput.value = row.dataset.itemId;
                addForm.submit();
            });
        });
    });
</script>
@endpush











