@extends('layouts.admin')

@section('title', 'Detalle obra civil')

@section('content')
@php
    $estimationConcepts = collect();

    if ($activeImport) {
        $estimationConcepts = $activeImport->buildings
            ->flatMap(function ($building) {
                return $building->partidas->flatMap(function ($partida) use ($building) {
                    return $partida->concepts->map(function ($concept) use ($building, $partida) {
                        return [
                            'id' => $concept->id,
                            'building' => $building->name,
                            'partida_code' => $partida->code,
                            'partida_name' => $partida->name,
                            'excel_code' => $concept->excel_code,
                            'description' => $concept->description,
                            'unit' => $concept->unit,
                            'budget_quantity' => (float) $concept->budget_quantity,
                            'unit_price' => (float) $concept->unit_price,
                            'budget_amount' => (float) $concept->budget_amount,
                            'excel_row' => $concept->excel_row,
                        ];
                    });
                });
            })
            ->values();
    }
@endphp

<div class="max-w-8xl mx-auto space-y-6" x-data="obraCivilEstimacionModal(@js($estimationConcepts))">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? '-' }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">{{ $obra->nombre }}</h1>
            <p class="text-sm text-slate-500">Catalogo civil, partidas, conceptos y saldos de la obra.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($activeImport)
                <button type="button"
                        class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]"
                        @click="open()">
                    Generar estimacion
                </button>
                <a href="{{ route('obra_civil.estimations.index', $obra) }}"
                   class="rounded-lg border border-[#0B265A] px-4 py-2 text-sm font-semibold text-[#0B265A] hover:bg-blue-50">
                    Ver estimaciones
                </a>
                <a href="{{ route('obra_civil.work-reports.index', $obra) }}"
                   class="rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                    Avances reportados
                </a>
            @endif
            <a href="{{ route('obra_civil.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if($drafts->isNotEmpty())
        <section class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4">
            <h2 class="font-bold text-amber-900">Catalogos pendientes de guardar</h2>
            <div class="mt-3 space-y-2">
                @foreach($drafts as $draft)
                    <div class="flex items-center justify-between gap-3 text-sm text-amber-900">
                        <span>{{ $draft->filename }} / {{ $draft->created_at->format('d/m/Y H:i') }}</span>
                        <a href="{{ route('obra_civil.catalog.preview', [$obra, $draft]) }}" class="font-semibold underline">Continuar preview</a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Explosion de insumos</h2>
                <p class="text-sm text-slate-500">Insumos/materiales cargados para preparar el flujo de ordenes de compra.</p>
            </div>
            <a href="{{ route('obra_civil.insumos.index', $obra) }}"
               class="inline-flex items-center justify-center rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]">
                Ver insumos
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-5">
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Insumos activos</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($insumoStats['total'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Materiales</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($insumoStats['materiales'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Mano de obra</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($insumoStats['mano_obra'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Equipo</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($insumoStats['equipo_herramienta'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Importe materiales</div>
                <div class="mt-1 text-xl font-bold text-slate-900">${{ number_format((float) ($insumoStats['importe_materiales'] ?? 0), 2) }}</div>
            </div>
        </div>

        @if($activeInsumoImport)
            <div class="border-t border-slate-100 px-5 py-3 text-sm text-slate-600">
                Ultima carga: <span class="font-semibold text-slate-900">{{ $activeInsumoImport->filename }}</span>
                / hoja {{ $activeInsumoImport->sheet_name ?: '-' }}
                / {{ $activeInsumoImport->created_at->format('d/m/Y H:i') }}
            </div>
        @endif
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Catalogo activo</h2>
        </div>

        @if(!$activeImport)
            <div class="px-5 py-12 text-center text-sm text-slate-500">
                Esta obra civil todavia no tiene un catalogo guardado.
            </div>
        @else

            <div class="grid grid-cols-1 gap-4 border-b border-slate-100 p-5 md:grid-cols-3 xl:grid-cols-6">
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">Archivo</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $activeImport->filename }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">Edificios</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ number_format($activeImport->total_buildings) }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">Conceptos</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ number_format($activeImport->total_concepts) }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">Importe</div>
                    <div class="mt-1 font-semibold text-slate-900">${{ number_format((float) $activeImport->total_amount, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">IVA</div>
                    <div class="mt-1 font-semibold text-slate-900">${{ number_format(((float) $activeImport->total_amount) * 0.16, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">Total</div>
                    <div class="mt-1 font-semibold text-slate-900">${{ number_format(((float) $activeImport->total_amount) * 1.16, 2) }}</div>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($activeImport->buildings as $building)
                    <div class="p-5">
                        <h3 class="font-bold text-[#0B265A]">{{ $building->name }}</h3>
                        <div class="mt-4 space-y-4">
                            @foreach($building->partidas as $partida)
                                <div class="rounded-lg border border-slate-200">
                                    <div class="flex flex-col gap-1 border-b border-slate-100 bg-slate-50 px-4 py-3 md:flex-row md:items-center md:justify-between">
                                        <div class="font-semibold text-slate-800">{{ $partida->code }} {{ $partida->name }}</div>
                                        <div class="text-xs font-semibold text-slate-500">
                                            {{ number_format($partida->concepts->count()) }} conceptos / ${{ number_format((float) $partida->budget_amount, 2) }}
                                        </div>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="text-xs uppercase text-slate-500">
                                                <tr>
                                                    <th class="px-4 py-2 text-left">Clave</th>
                                                    <th class="px-4 py-2 text-left">Descripcion</th>
                                                    <th class="px-4 py-2 text-left">Unidad</th>
                                                    <th class="px-4 py-2 text-right">Cantidad</th>
                                                    <th class="px-4 py-2 text-right">Precio</th>
                                                    <th class="px-4 py-2 text-right">Importe</th>
                                                    <th class="px-4 py-2 text-right">Reportado</th>
                                                    <th class="px-4 py-2 text-right">Estimado</th>
                                                    <th class="px-4 py-2 text-right">Disponible</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <?php foreach ($partida->concepts as $concept): ?>
                                                    <?php
                                                        $balance = $balances->get($concept->id, []);
                                                        $reportedQuantity = (float) ($reportedQuantities->get($concept->id, 0) ?? 0);
                                                        $usedQuantity = (float) ($balance['used_quantity'] ?? 0);
                                                        $usedAmount = (float) ($balance['used_amount'] ?? 0);
                                                        $availableQuantity = (float) ($balance['available_quantity'] ?? $concept->budget_quantity);
                                                        $availableAmount = (float) ($balance['available_amount'] ?? $concept->budget_amount);
                                                        $isExceeded = $availableQuantity < 0 || $availableAmount < 0;
                                                    ?>
                                                    <tr class="align-top hover:bg-slate-50 {{ $isExceeded ? 'bg-amber-50/60' : '' }}">
                                                        <td class="px-4 py-2 font-mono text-xs text-slate-600">{{ $concept->excel_code }}</td>
                                                        <td class="px-4 py-2 text-slate-800">{{ $concept->description }}</td>
                                                        <td class="px-4 py-2 text-slate-600">{{ $concept->unit }}</td>
                                                        <td class="px-4 py-2 text-right tabular-nums">{{ number_format((float) $concept->budget_quantity, 4) }}</td>
                                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format((float) $concept->unit_price, 4) }}</td>
                                                        <td class="px-4 py-2 text-right tabular-nums font-semibold">${{ number_format((float) $concept->budget_amount, 2) }}</td>
                                                        <td class="px-4 py-2 text-right tabular-nums {{ $reportedQuantity > 0 ? 'text-blue-700' : 'text-slate-400' }}">
                                                            @if($reportedQuantity > 0)
                                                                <a href="{{ route('obra_civil.concept.reports', [$obra, $concept]) }}" class="inline-block rounded-md px-2 py-1 font-semibold hover:bg-blue-50 hover:underline">
                                                                    <div>{{ number_format($reportedQuantity, 4) }}</div>
                                                                    <div class="text-xs font-semibold">Ver reportes</div>
                                                                </a>
                                                            @else
                                                                <div>{{ number_format($reportedQuantity, 4) }}</div>
                                                                <div class="text-xs font-semibold">Campo</div>
                                                            @endif
                                                        </td>

                                                        <td class="px-4 py-2 text-right tabular-nums text-slate-600">
                                                            <div>{{ number_format($usedQuantity, 4) }}</div>
                                                            <div class="text-xs">${{ number_format($usedAmount, 2) }}</div>
                                                        </td>
                                                        <td class="px-4 py-2 text-right tabular-nums font-semibold {{ $isExceeded ? 'text-red-700' : 'text-slate-800' }}">
                                                            <div>{{ number_format($availableQuantity, 4) }}</div>
                                                            <div class="text-xs">${{ number_format($availableAmount, 2) }}</div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
<section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Historial de cargas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Archivo</th>
                        <th class="px-5 py-3 text-left">Estado</th>
                        <th class="px-5 py-3 text-left">Usuario</th>
                        <th class="px-5 py-3 text-right">Conceptos</th>
                        <th class="px-5 py-3 text-right">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($imports as $import)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $import->filename }}</td>
                            <td class="px-5 py-3">{{ $import->status }}</td>
                            <td class="px-5 py-3">{{ $import->importedBy->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($import->total_concepts) }}</td>
                            <td class="px-5 py-3 text-right">{{ $import->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">Sin cargas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div x-show="show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4 py-6">
        <div class="flex max-h-[92vh] w-full max-w-7xl flex-col rounded-lg bg-white shadow-xl" @click.away="close()">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Generar estimacion</h2>
                    <p class="text-sm text-slate-500" x-text="mode === 'preview' ? 'Revisa el borrador antes de guardar.' : 'Selecciona conceptos y captura cantidades.'"></p>
                </div>
                <button type="button" class="rounded-lg px-2 py-1 text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-600" @click="close()">
                    &times;
                </button>
            </div>

            <div class="grid grid-cols-1 gap-3 border-b border-slate-100 px-5 py-4 md:grid-cols-4">
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-xs font-semibold uppercase text-slate-500">Conceptos</div>
                    <div class="mt-1 text-lg font-bold text-slate-900" x-text="selectedItems().length"></div>
                </div>
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-xs font-semibold uppercase text-slate-500">Cantidad</div>
                    <div class="mt-1 text-lg font-bold text-slate-900" x-text="formatQuantity(totalQuantity())"></div>
                </div>
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-xs font-semibold uppercase text-slate-500">Subtotal</div>
                    <div class="mt-1 text-lg font-bold text-slate-900" x-text="money(totalAmount())"></div>
                </div>
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="text-xs font-semibold uppercase text-slate-500">Estado</div>
                    <div class="mt-1 text-lg font-bold text-amber-700">Preview</div>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                <template x-if="mode === 'select'">
                    <div class="space-y-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <input type="search"
                                   x-model.debounce.200ms="search"
                                   placeholder="Buscar por clave, partida o descripcion..."
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm md:max-w-md">
                            <div class="text-sm font-semibold text-slate-500">
                                <span x-text="filteredConcepts().length"></span> conceptos visibles
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="w-full min-w-[1100px] text-sm">
                                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Sel.</th>
                                        <th class="px-3 py-2 text-left">Clave</th>
                                        <th class="px-3 py-2 text-left">Partida</th>
                                        <th class="px-3 py-2 text-left">Descripcion</th>
                                        <th class="px-3 py-2 text-left">Unidad</th>
                                        <th class="px-3 py-2 text-right">Cantidad base</th>
                                        <th class="px-3 py-2 text-right">Cantidad a estimar</th>
                                        <th class="px-3 py-2 text-right">Precio</th>
                                        <th class="px-3 py-2 text-right">Importe</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="concept in filteredConcepts()" :key="concept.id">
                                        <tr class="align-top hover:bg-slate-50">
                                            <td class="px-3 py-2">
                                                <input type="checkbox"
                                                       class="rounded border-slate-300 text-[#0B265A]"
                                                       :checked="isSelected(concept.id)"
                                                       @change="toggleConcept(concept)">
                                            </td>
                                            <td class="px-3 py-2 font-mono text-xs text-slate-600" x-text="concept.excel_code || '-'"></td>
                                            <td class="px-3 py-2 text-slate-700">
                                                <div class="font-semibold" x-text="concept.partida_code || '-'"></div>
                                                <div class="text-xs text-slate-500" x-text="concept.partida_name"></div>
                                            </td>
                                            <td class="px-3 py-2 text-slate-800" x-text="concept.description"></td>
                                            <td class="px-3 py-2 text-slate-600" x-text="concept.unit || '-'"></td>
                                            <td class="px-3 py-2 text-right tabular-nums" x-text="formatQuantity(concept.budget_quantity)"></td>
                                            <td class="px-3 py-2 text-right">
                                                <input type="number"
                                                       min="0"
                                                       step="0.0001"
                                                       class="w-28 rounded-md border border-slate-300 px-2 py-1 text-right text-sm disabled:bg-slate-100"
                                                       :disabled="!isSelected(concept.id)"
                                                       :max="concept.budget_quantity"
                                                       x-model.number="quantities[concept.id]">
                                            </td>
                                            <td class="px-3 py-2 text-right tabular-nums" x-text="money(concept.unit_price)"></td>
                                            <td class="px-3 py-2 text-right tabular-nums font-semibold" x-text="money(lineAmount(concept))"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <template x-if="mode === 'preview'">
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full min-w-[900px] text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Clave</th>
                                    <th class="px-3 py-2 text-left">Partida</th>
                                    <th class="px-3 py-2 text-left">Descripcion</th>
                                    <th class="px-3 py-2 text-left">Unidad</th>
                                    <th class="px-3 py-2 text-right">Cantidad</th>
                                    <th class="px-3 py-2 text-right">Precio</th>
                                    <th class="px-3 py-2 text-right">Importe</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="concept in selectedItems()" :key="concept.id">
                                    <tr>
                                        <td class="px-3 py-2 font-mono text-xs" x-text="concept.excel_code || '-'"></td>
                                        <td class="px-3 py-2" x-text="concept.partida_code || '-'"></td>
                                        <td class="px-3 py-2" x-text="concept.description"></td>
                                        <td class="px-3 py-2" x-text="concept.unit || '-'"></td>
                                        <td class="px-3 py-2 text-right tabular-nums" x-text="formatQuantity(quantityFor(concept.id))"></td>
                                        <td class="px-3 py-2 text-right tabular-nums" x-text="money(concept.unit_price)"></td>
                                        <td class="px-3 py-2 text-right tabular-nums font-semibold" x-text="money(lineAmount(concept))"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-slate-50 font-bold text-slate-900">
                                <tr>
                                    <td colspan="6" class="px-3 py-3 text-right">Total estimado</td>
                                    <td class="px-3 py-3 text-right tabular-nums" x-text="money(totalAmount())"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </template>
            </div>

            <form x-ref="estimationForm" method="POST" action="{{ route('obra_civil.estimations.store', $obra) }}" class="hidden">
                @csrf
                <template x-for="(concept, index) in selectedItems()" :key="`form-${concept.id}`">
                    <div>
                        <input type="hidden" :name="`items[${index}][concept_id]`" :value="concept.id">
                        <input type="hidden" :name="`items[${index}][quantity]`" :value="quantityFor(concept.id)">
                    </div>
                </template>
            </form>
            <div class="flex flex-col gap-2 border-t border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
                <div class="text-sm font-semibold text-red-600" x-text="error"></div>
                <div class="flex justify-end gap-2">
                    <button type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            @click="mode === 'preview' ? mode = 'select' : close()"
                            x-text="mode === 'preview' ? 'Editar seleccion' : 'Cancelar'"></button>
                    <button type="button"
                            x-show="mode === 'select'"
                            class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]"
                            @click="preview()">
                        Previsualizar
                    </button>
                    <button type="button"
                            x-show="mode === 'preview'"
                            class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-70"
                            :disabled="saving"
                            @click="submit()"
                            x-text="saving ? 'Guardando...' : 'Confirmar y guardar'"></button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function obraCivilEstimacionModal(concepts) {
    return {
        show: false,
        mode: 'select',
        search: '',
        concepts: concepts || [],
        selected: {},
        quantities: {},
        error: '',
        saving: false,
        open() {
            this.show = true;
            this.mode = 'select';
            this.error = '';
            this.saving = false;
        },
        close() {
            this.show = false;
            this.mode = 'select';
            this.search = '';
            this.error = '';
            this.saving = false;
        },
        filteredConcepts() {
            const term = this.search.trim().toLowerCase();

            if (!term) {
                return this.concepts;
            }

            return this.concepts.filter((concept) => [
                concept.excel_code,
                concept.partida_code,
                concept.partida_name,
                concept.description,
                concept.unit,
            ].filter(Boolean).join(' ').toLowerCase().includes(term));
        },
        isSelected(id) {
            return Boolean(this.selected[id]);
        },
        toggleConcept(concept) {
            if (this.isSelected(concept.id)) {
                delete this.selected[concept.id];
                delete this.quantities[concept.id];
                return;
            }

            this.selected[concept.id] = concept;
            this.quantities[concept.id] = this.quantities[concept.id] || 0;
        },
        quantityFor(id) {
            const quantity = Number(this.quantities[id] || 0);
            return Number.isFinite(quantity) ? quantity : 0;
        },
        lineAmount(concept) {
            return this.quantityFor(concept.id) * Number(concept.unit_price || 0);
        },
        selectedItems() {
            return Object.values(this.selected);
        },
        totalQuantity() {
            return this.selectedItems().reduce((sum, concept) => sum + this.quantityFor(concept.id), 0);
        },
        totalAmount() {
            return this.selectedItems().reduce((sum, concept) => sum + this.lineAmount(concept), 0);
        },
        preview() {
            this.error = '';

            if (this.selectedItems().length === 0) {
                this.error = 'Selecciona al menos un concepto.';
                return;
            }

            const invalid = this.selectedItems().find((concept) => {
                const quantity = this.quantityFor(concept.id);
                return quantity <= 0 || quantity > Number(concept.budget_quantity || 0);
            });

            if (invalid) {
                this.error = 'Revisa las cantidades: deben ser mayores a 0 y no exceder la cantidad base.';
                return;
            }

            this.mode = 'preview';
        },
        submit() {
            this.preview();

            if (this.error) {
                return;
            }

            this.saving = true;
            this.$nextTick(() => this.$refs.estimationForm.submit());
        },
        money(value) {
            return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value || 0));
        },
        formatQuantity(value) {
            return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(Number(value || 0));
        },
    };
}
</script>
@endpush



