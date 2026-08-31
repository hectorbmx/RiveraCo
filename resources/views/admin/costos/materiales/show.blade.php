@extends('layouts.admin')

@section('title', 'Costos - Hijos comerciales')

@section('content')
@php
    $fieldClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0B265A] focus:outline-none focus:ring-1 focus:ring-[#0B265A]';
    $labelClass = 'mb-1 block text-xs font-semibold uppercase text-slate-500';
@endphp

<div x-data="{ editFamilyOpen: false, createChildOpen: false, editingChild: null, loading: false }" class="max-w-7xl mx-auto space-y-6">
    <div x-show="loading" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40">
        <div class="rounded-lg bg-white px-6 py-5 text-center shadow-xl">
            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-[#0B265A]"></div>
            <div class="mt-3 text-sm font-semibold text-slate-800">Procesando cambios...</div>
        </div>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <a href="{{ route('costos.materiales.index') }}" class="text-sm font-semibold text-[#0B265A] hover:underline">Volver al catalogo</a>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Familia resoluble</p>
            <h1 class="text-2xl font-bold text-[#0B265A]">{{ $group->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $group->code }} · {{ $group->family }} · {{ $familyRow->gradeText() }}</p>
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <button type="button" @click="editFamilyOpen = true" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Editar familia</button>
            <button type="button" @click="createChildOpen = true" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#123675]">Nuevo hijo</button>
            <form method="POST" action="{{ route('costos.materiales.familias.estado', $group) }}" class="costos-write-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $group->is_active ? 0 : 1 }}">
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ $group->is_active ? 'Inactivar familia' : 'Activar familia' }}</button>
            </form>
            <span class="inline-flex rounded-full px-3 py-2 text-sm font-semibold {{ $familyRow->statusClass }}">{{ $familyRow->statusLabel }}</span>
        </div>
    </div>

    @if (isset($errors) && $errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <div class="font-semibold">Revisa la captura</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase text-slate-500">Unidad base</div><div class="mt-2 text-xl font-bold text-slate-900">{{ $familyRow->budgetUnitsText() }}</div></div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase text-slate-500">Hijos</div><div class="mt-2 text-xl font-bold text-slate-900">{{ number_format($familyRow->childrenCount) }}</div></div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase text-slate-500">Activos</div><div class="mt-2 text-xl font-bold text-emerald-700">{{ number_format($familyRow->activeChildrenCount) }}</div></div>
        <div class="rounded-lg border border-orange-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase text-orange-700">Pendientes</div><div class="mt-2 text-xl font-bold text-orange-800">{{ number_format($familyRow->pendingValidationCount) }}</div></div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase text-slate-500">Estado familia</div><div class="mt-2 text-xl font-bold {{ $group->is_active ? 'text-emerald-700' : 'text-slate-600' }}">{{ $group->is_active ? 'Activa' : 'Inactiva' }}</div></div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Reglas de resolucion actuales</h2>
            <p class="mt-1 text-sm text-slate-500">Estas reglas identifican conceptos de explosion para esta familia.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
            @foreach($rules as $label => $values)
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">{{ str_replace('_', ' ', $label) }}</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse($values as $value)
                            <span class="rounded-full bg-white px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $value }}</span>
                        @empty
                            <span class="text-sm text-slate-400">Sin datos</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Materiales comerciales hijos</h2>
            <p class="mt-1 text-sm text-slate-500">Opciones que el residente puede pedir y compras puede comprar para esta familia.</p>
        </div>

        <form method="GET" action="{{ route('costos.materiales.show', $group) }}" class="grid grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:grid-cols-5">
            <div class="md:col-span-2"><label class="{{ $labelClass }}">Buscar hijo</label><input type="search" name="q" value="{{ $filters['q'] }}" placeholder="SKU, descripcion, medida o calibre" class="{{ $fieldClass }}"></div>
            <div><label class="{{ $labelClass }}">Categoria</label><select name="category" class="{{ $fieldClass }}"><option value="">Todas</option>@foreach($categoriesCatalog as $category)<option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>@endforeach</select></div>
            <div><label class="{{ $labelClass }}">Estado</label><select name="state" class="{{ $fieldClass }}">@foreach($childStatusOptions as $value => $label)<option value="{{ $value }}" @selected($filters['state'] === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="{{ $labelClass }}">Validacion</label><select name="validation_status" class="{{ $fieldClass }}"><option value="">Todas</option>@foreach($validationStatuses as $status)<option value="{{ $status }}" @selected($filters['validation_status'] === $status)>{{ $status }}</option>@endforeach</select></div>
            <div class="flex items-end gap-2 md:col-span-5"><button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#123675]">Filtrar</button><a href="{{ route('costos.materiales.show', $group) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Limpiar</a></div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">SKU</th>
                        <th class="px-5 py-3 text-left">Descripcion</th>
                        <th class="px-5 py-3 text-left">Categoria</th>
                        <th class="px-5 py-3 text-left">Especificacion</th>
                        <th class="px-5 py-3 text-left">Compra</th>
                        <th class="px-5 py-3 text-left">Peso/factor</th>
                        <th class="px-5 py-3 text-left">Validacion</th>
                        <th class="px-5 py-3 text-left">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($children as $child)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-[#0B265A]">{{ $child->sku }}</td>
                            <td class="px-5 py-3"><div class="font-semibold text-slate-900">{{ $child->descripcion }}</div><div class="text-xs text-slate-500">ID {{ $child->id }}</div></td>
                            <td class="px-5 py-3 text-slate-700">{{ $child->categoryText() }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $child->specsText() }}</td>
                            <td class="px-5 py-3"><div class="font-semibold text-slate-900">{{ $child->unidadCompra }}</div><div class="text-xs text-slate-500">{{ $child->conversionType }}</div></td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $child->weightText() }}</td>
                            <td class="px-5 py-3"><span class="inline-flex max-w-xs rounded-full px-2 py-1 text-xs font-semibold {{ $child->validationClass }}">{{ $child->validationStatus ?: 'Sin validar' }}</span></td>
                            <td class="px-5 py-3"><span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $child->statusClass }}">{{ $child->statusLabel }}</span></td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="editingChild = {{ $child->id }}" class="rounded-lg bg-[#0B265A] px-3 py-2 text-xs font-semibold text-white hover:bg-[#123675]">Editar</button>
                                    <form method="POST" action="{{ route('costos.materiales.hijos.estado', [$group, $child->id]) }}" class="costos-write-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $child->isActive ? 0 : 1 }}">
                                        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ $child->isActive ? 'Inactivar' : 'Activar' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-10 text-center text-sm text-slate-500">No se encontraron hijos con los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div>{{ $children->links() }}</div>

    <div x-show="editFamilyOpen" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4">
        <div @click.outside="editFamilyOpen = false" class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-lg bg-white shadow-xl">
            <form method="POST" action="{{ route('costos.materiales.familias.update', $group) }}" class="costos-write-form">
                @csrf
                @method('PUT')
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="text-lg font-semibold text-slate-900">Editar familia</h3></div>
                <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                    <div><label class="{{ $labelClass }}">Codigo</label><input name="code" value="{{ $group->code }}" required class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Grupo tecnico</label><input name="family" value="{{ $group->family }}" required class="{{ $fieldClass }}"></div>
                    <div class="md:col-span-2"><label class="{{ $labelClass }}">Nombre</label><input name="name" value="{{ $group->name }}" required class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Grado</label><input name="grade" value="{{ $group->grade }}" class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Unidades base</label><input name="budget_units_text" value="{{ implode(', ', $group->budget_units ?? []) }}" class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Codigos explosion</label><textarea name="source_codes_text" rows="4" class="{{ $fieldClass }}">{{ implode("\n", $group->source_codes ?? []) }}</textarea></div>
                    <div><label class="{{ $labelClass }}">Keywords</label><textarea name="keywords_text" rows="4" class="{{ $fieldClass }}">{{ implode("\n", $group->keywords ?? []) }}</textarea></div>
                    <div class="md:col-span-2"><label class="{{ $labelClass }}">Reglas JSON</label><textarea name="match_rules_json" rows="8" class="{{ $fieldClass }} font-mono">{{ json_encode($group->match_rules ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea></div>
                    <input type="hidden" name="is_active" value="{{ $group->is_active ? 1 : 0 }}">
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4"><button type="button" @click="editFamilyOpen = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button><button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#123675]">Guardar familia</button></div>
            </form>
        </div>
    </div>

    <div x-show="createChildOpen" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4">
        <div @click.outside="createChildOpen = false" class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-lg bg-white shadow-xl">
            <form method="POST" action="{{ route('costos.materiales.hijos.store', $group) }}" class="costos-write-form">
                @csrf
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="text-lg font-semibold text-slate-900">Nuevo material hijo</h3></div>
                <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-3">
                    <div><label class="{{ $labelClass }}">SKU</label><input name="sku" required class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Categoria</label><input name="category" class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Subcategoria</label><input name="subcategory" class="{{ $fieldClass }}"></div>
                    <div class="md:col-span-3"><label class="{{ $labelClass }}">Descripcion</label><input name="descripcion" required class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Grado</label><input name="grade" class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Medida</label><input name="medida" class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Diametro</label><input name="diametro" class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Calibre / espesor</label><input name="calibre_espesor" class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Longitud</label><input type="number" step="0.0001" min="0" name="longitud" class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Unidad compra</label><input name="unidad_compra" value="PZA" required class="{{ $fieldClass }}"></div>
                    <input type="hidden" name="conversion_type" value="fixed_weight_per_piece">
                    <div><label class="{{ $labelClass }}">Peso por pieza</label><input type="number" step="0.000001" min="0" name="peso_por_pieza" class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Factor conversion</label><input type="number" step="0.000001" min="0" name="factor_conversion" class="{{ $fieldClass }}"></div>
                    <div><label class="{{ $labelClass }}">Validacion</label><input name="validation_status" value="Validar con Ingenieria" class="{{ $fieldClass }}"></div>
                    <input type="hidden" name="is_active" value="1">
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4"><button type="button" @click="createChildOpen = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button><button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#123675]">Guardar hijo</button></div>
            </form>
        </div>
    </div>

    @foreach($children as $child)
        <div x-show="editingChild === {{ $child->id }}" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4">
            <div @click.outside="editingChild = null" class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-lg bg-white shadow-xl">
                <form method="POST" action="{{ route('costos.materiales.hijos.update', [$group, $child->id]) }}" class="costos-write-form">
                    @csrf
                    @method('PUT')
                    <div class="border-b border-slate-200 px-5 py-4"><h3 class="text-lg font-semibold text-slate-900">Editar material hijo</h3></div>
                    <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-3">
                        <div><label class="{{ $labelClass }}">SKU</label><input name="sku" value="{{ $child->sku }}" required class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Categoria</label><input name="category" value="{{ $child->category }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Subcategoria</label><input name="subcategory" value="{{ $child->subcategory }}" class="{{ $fieldClass }}"></div>
                        <div class="md:col-span-3"><label class="{{ $labelClass }}">Descripcion</label><input name="descripcion" value="{{ $child->descripcion }}" required class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Grado</label><input name="grade" value="{{ $child->grade }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Medida</label><input name="medida" value="{{ $child->medida }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Diametro</label><input name="diametro" value="{{ $child->diametro }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Calibre / espesor</label><input name="calibre_espesor" value="{{ $child->calibreEspesor }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Longitud</label><input type="number" step="0.0001" min="0" name="longitud" value="{{ $child->longitud }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Unidad compra</label><input name="unidad_compra" value="{{ $child->unidadCompra }}" required class="{{ $fieldClass }}"></div>
                        <input type="hidden" name="conversion_type" value="{{ $child->conversionType }}">
                        <div><label class="{{ $labelClass }}">Peso por metro</label><input type="number" step="0.000001" min="0" name="peso_por_metro" value="{{ $child->pesoPorMetro }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Peso por pieza</label><input type="number" step="0.000001" min="0" name="peso_por_pieza" value="{{ $child->pesoPorPieza }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Peso por m2</label><input type="number" step="0.000001" min="0" name="peso_por_m2" value="{{ $child->pesoPorM2 }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Peso por rollo</label><input type="number" step="0.000001" min="0" name="peso_por_rollo" value="{{ $child->pesoPorRollo }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Factor conversion</label><input type="number" step="0.000001" min="0" name="factor_conversion" value="{{ $child->factorConversion }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Tolerancia</label><input name="tolerance" value="{{ $child->tolerance }}" class="{{ $fieldClass }}"></div>
                        <div class="md:col-span-2"><label class="{{ $labelClass }}">Validacion</label><input name="validation_status" value="{{ $child->validationStatus }}" class="{{ $fieldClass }}"></div>
                        <div><label class="{{ $labelClass }}">Activo</label><select name="is_active" class="{{ $fieldClass }}"><option value="1" @selected($child->isActive)>Activo</option><option value="0" @selected(!$child->isActive)>Inactivo</option></select></div>
                        <div class="md:col-span-3"><label class="{{ $labelClass }}">Fuente tecnica</label><textarea name="technical_source" rows="3" class="{{ $fieldClass }}">{{ $child->technicalSource }}</textarea></div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4"><button type="button" @click="editingChild = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button><button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#123675]">Guardar hijo</button></div>
                </form>
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<script>
    document.addEventListener('submit', function (event) {
        if (event.target.classList.contains('costos-write-form')) {
            const root = event.target.closest('[x-data]');
            if (root && window.Alpine) {
                Alpine.$data(root).loading = true;
            }
        }
    });
</script>
@endpush
@endsection

