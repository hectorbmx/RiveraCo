@extends('layouts.admin')

@section('title', 'Costos - Hijos comerciales')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <a href="{{ route('costos.materiales.index') }}" class="text-sm font-semibold text-[#0B265A] hover:underline">← Volver al catalogo</a>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Familia resoluble</p>
            <h1 class="text-2xl font-bold text-[#0B265A]">{{ $group->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $group->code }} · {{ $group->family }} · {{ $familyRow->gradeText() }}</p>
        </div>

        <span class="inline-flex rounded-full px-3 py-2 text-sm font-semibold {{ $familyRow->statusClass }}">
            {{ $familyRow->statusLabel }}
        </span>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Unidad base</div>
            <div class="mt-2 text-xl font-bold text-slate-900">{{ $familyRow->budgetUnitsText() }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Hijos</div>
            <div class="mt-2 text-xl font-bold text-slate-900">{{ number_format($familyRow->childrenCount) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Activos</div>
            <div class="mt-2 text-xl font-bold text-emerald-700">{{ number_format($familyRow->activeChildrenCount) }}</div>
        </div>
        <div class="rounded-lg border border-orange-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-orange-700">Pendientes</div>
            <div class="mt-2 text-xl font-bold text-orange-800">{{ number_format($familyRow->pendingValidationCount) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Estado familia</div>
            <div class="mt-2 text-xl font-bold {{ $group->is_active ? 'text-emerald-700' : 'text-slate-600' }}">{{ $group->is_active ? 'Activa' : 'Inactiva' }}</div>
        </div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Reglas de resolucion actuales</h2>
            <p class="mt-1 text-sm text-slate-500">Estas reglas se leen desde el JSON actual de la familia. La edicion queda para una fase posterior.</p>
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
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Buscar hijo</label>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="SKU, descripcion, medida o calibre"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0B265A] focus:outline-none focus:ring-1 focus:ring-[#0B265A]">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Categoria</label>
                <select name="category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0B265A] focus:outline-none focus:ring-1 focus:ring-[#0B265A]">
                    <option value="">Todas</option>
                    @foreach($categoriesCatalog as $category)
                        <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Estado</label>
                <select name="state" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0B265A] focus:outline-none focus:ring-1 focus:ring-[#0B265A]">
                    @foreach($childStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['state'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Validacion</label>
                <select name="validation_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0B265A] focus:outline-none focus:ring-1 focus:ring-[#0B265A]">
                    <option value="">Todas</option>
                    @foreach($validationStatuses as $status)
                        <option value="{{ $status }}" @selected($filters['validation_status'] === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-5">
                <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#123675]">Filtrar</button>
                <a href="{{ route('costos.materiales.show', $group) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Limpiar</a>
            </div>
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($children as $child)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-[#0B265A]">{{ $child->sku }}</td>
                            <td class="px-5 py-3">
                                <div class="font-semibold text-slate-900">{{ $child->descripcion }}</div>
                                <div class="text-xs text-slate-500">ID {{ $child->id }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $child->categoryText() }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $child->specsText() }}</td>
                            <td class="px-5 py-3">
                                <div class="font-semibold text-slate-900">{{ $child->unidadCompra }}</div>
                                <div class="text-xs text-slate-500">{{ $child->conversionType }}</div>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $child->weightText() }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex max-w-xs rounded-full px-2 py-1 text-xs font-semibold {{ $child->validationClass }}">
                                    {{ $child->validationStatus ?: 'Sin validar' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $child->statusClass }}">
                                    {{ $child->statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">No se encontraron hijos con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div>
        {{ $children->links() }}
    </div>
</div>
@endsection
