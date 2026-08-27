@extends('layouts.admin')

@section('title', 'Costos - Catalogo de materiales')

@section('content')
@php
    $isChildrenView = $catalogView === 'hijos';
    $baseFilters = collect($filters)->except('vista')->filter(fn ($value) => $value !== '')->all();
    $familiesUrl = route('costos.materiales.index', array_merge($baseFilters, ['vista' => 'familias']));
    $childrenUrl = route('costos.materiales.index', array_merge($baseFilters, ['vista' => 'hijos']));
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modulo Costos</p>
            <h1 class="text-2xl font-bold text-[#0B265A]">Catalogo maestro de materiales</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                Familias resolubles y materiales comerciales hijos usados para traducir conceptos de explosion de insumos a piezas comprables.
            </p>
        </div>

        <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-semibold text-[#0B265A]">
            Modo lectura
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4 xl:grid-cols-7">
        <a href="{{ $familiesUrl }}" class="rounded-lg border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ !$isChildrenView ? 'border-[#0B265A] ring-2 ring-[#0B265A]/10' : 'border-slate-200' }}">
            <div class="text-xs font-semibold uppercase text-slate-500">Familias</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['families']) }}</div>
        </a>
        <a href="{{ route('costos.materiales.index', array_merge($baseFilters, ['vista' => 'familias', 'state' => 'active'])) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="text-xs font-semibold uppercase text-slate-500">Familias activas</div>
            <div class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($stats['active_families']) }}</div>
        </a>
        <a href="{{ $childrenUrl }}" class="rounded-lg border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $isChildrenView ? 'border-[#0B265A] ring-2 ring-[#0B265A]/10' : 'border-slate-200' }}">
            <div class="text-xs font-semibold uppercase text-slate-500">Hijos</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['children']) }}</div>
        </a>
        <a href="{{ route('costos.materiales.index', array_merge($baseFilters, ['vista' => 'hijos', 'state' => 'active'])) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="text-xs font-semibold uppercase text-slate-500">Hijos activos</div>
            <div class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($stats['active_children']) }}</div>
        </a>
        <a href="{{ route('costos.materiales.index', array_merge($baseFilters, ['vista' => 'hijos', 'state' => 'inactive'])) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="text-xs font-semibold uppercase text-slate-500">Hijos inactivos</div>
            <div class="mt-2 text-2xl font-bold text-slate-700">{{ number_format($stats['inactive_children']) }}</div>
        </a>
        <a href="{{ route('costos.materiales.index', array_merge($baseFilters, ['vista' => 'familias', 'state' => 'without_active_children'])) }}" class="rounded-lg border border-amber-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="text-xs font-semibold uppercase text-amber-700">Sin hijos activos</div>
            <div class="mt-2 text-2xl font-bold text-amber-800">{{ number_format($stats['without_active_children']) }}</div>
        </a>
        <a href="{{ route('costos.materiales.index', array_merge($baseFilters, ['vista' => $catalogView, 'state' => 'pending_validation'])) }}" class="rounded-lg border border-orange-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="text-xs font-semibold uppercase text-orange-700">Pendientes</div>
            <div class="mt-2 text-2xl font-bold text-orange-800">{{ number_format($stats['pending_validation']) }}</div>
        </a>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $isChildrenView ? 'Todos los hijos comerciales' : 'Familias resolubles' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $isChildrenView ? 'Vista global tipo Excel con todos los materiales hijos disponibles en el catalogo maestro.' : 'Primera lectura del catalogo actual. La edicion de familias, reglas y precios queda para fases posteriores.' }}
                    </p>
                </div>
                <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 text-sm font-semibold">
                    <a href="{{ $familiesUrl }}" class="rounded-md px-3 py-2 {{ !$isChildrenView ? 'bg-[#0B265A] text-white' : 'text-slate-600 hover:bg-white' }}">Familias</a>
                    <a href="{{ $childrenUrl }}" class="rounded-md px-3 py-2 {{ $isChildrenView ? 'bg-[#0B265A] text-white' : 'text-slate-600 hover:bg-white' }}">Hijos</a>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('costos.materiales.index') }}" class="grid grid-cols-1 gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:grid-cols-5">
            <input type="hidden" name="vista" value="{{ $catalogView }}">
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Buscar</label>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ $isChildrenView ? 'SKU, descripcion, medida, calibre o familia' : 'Codigo, familia, SKU o descripcion' }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0B265A] focus:outline-none focus:ring-1 focus:ring-[#0B265A]">
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Familia</label>
                <select name="family" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0B265A] focus:outline-none focus:ring-1 focus:ring-[#0B265A]">
                    <option value="">Todas</option>
                    @foreach($familiesCatalog as $family)
                        <option value="{{ $family }}" @selected($filters['family'] === $family)>{{ $family }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Categoria hijo</label>
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
                    @foreach(($isChildrenView ? $childStatusOptions : $statusOptions) as $value => $label)
                        <option value="{{ $value }}" @selected($filters['state'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Estado de validacion</label>
                <select name="validation_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0B265A] focus:outline-none focus:ring-1 focus:ring-[#0B265A]">
                    <option value="">Todos</option>
                    @foreach($validationStatuses as $status)
                        <option value="{{ $status }}" @selected($filters['validation_status'] === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2 md:col-span-2">
                <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#123675]">Filtrar</button>
                <a href="{{ route('costos.materiales.index', ['vista' => $catalogView]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Limpiar</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            @if(!$isChildrenView)
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Codigo</th>
                            <th class="px-5 py-3 text-left">Familia resoluble</th>
                            <th class="px-5 py-3 text-left">Grupo / grado</th>
                            <th class="px-5 py-3 text-left">Unidad base</th>
                            <th class="px-5 py-3 text-right">Hijos</th>
                            <th class="px-5 py-3 text-right">Activos</th>
                            <th class="px-5 py-3 text-right">Pendientes</th>
                            <th class="px-5 py-3 text-left">Reglas</th>
                            <th class="px-5 py-3 text-left">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($families as $family)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-mono text-xs font-semibold text-[#0B265A]">{{ $family->code }}</td>
                                <td class="px-5 py-3"><div class="font-semibold text-slate-900">{{ $family->name }}</div><div class="text-xs text-slate-500">ID {{ $family->id }}</div></td>
                                <td class="px-5 py-3"><div class="text-slate-700">{{ $family->family }}</div><div class="text-xs text-slate-500">{{ $family->gradeText() }}</div></td>
                                <td class="px-5 py-3 text-slate-700">{{ $family->budgetUnitsText() }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-slate-900">{{ number_format($family->childrenCount) }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-emerald-700">{{ number_format($family->activeChildrenCount) }}</td>
                                <td class="px-5 py-3 text-right font-semibold {{ $family->pendingValidationCount > 0 ? 'text-orange-700' : 'text-slate-400' }}">{{ number_format($family->pendingValidationCount) }}</td>
                                <td class="px-5 py-3 text-xs text-slate-600"><div>{{ $family->sourceCodesCount }} codigos</div><div>{{ $family->keywordsCount }} keywords</div></td>
                                <td class="px-5 py-3"><span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $family->statusClass }}">{{ $family->statusLabel }}</span></td>
                                <td class="px-5 py-3 text-right"><a href="{{ route('costos.materiales.show', $family->id) }}" class="inline-flex items-center rounded-lg bg-[#0B265A] px-3 py-2 text-xs font-semibold text-white hover:bg-[#123675]">Ver</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-5 py-10 text-center text-sm text-slate-500">No se encontraron familias con los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left">SKU</th>
                            <th class="px-5 py-3 text-left">Familia padre</th>
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
                                    <div class="font-semibold text-slate-900">{{ $child->parentName ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $child->parentCode ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-3"><div class="font-semibold text-slate-900">{{ $child->descripcion }}</div><div class="text-xs text-slate-500">ID {{ $child->id }}</div></td>
                                <td class="px-5 py-3 text-slate-700">{{ $child->categoryText() }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $child->specsText() }}</td>
                                <td class="px-5 py-3"><div class="font-semibold text-slate-900">{{ $child->unidadCompra }}</div><div class="text-xs text-slate-500">{{ $child->conversionType }}</div></td>
                                <td class="px-5 py-3 font-semibold text-slate-900">{{ $child->weightText() }}</td>
                                <td class="px-5 py-3"><span class="inline-flex max-w-xs rounded-full px-2 py-1 text-xs font-semibold {{ $child->validationClass }}">{{ $child->validationStatus ?: 'Sin validar' }}</span></td>
                                <td class="px-5 py-3"><span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $child->statusClass }}">{{ $child->statusLabel }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-5 py-10 text-center text-sm text-slate-500">No se encontraron hijos con los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <div>
        {{ ($isChildrenView ? $children : $families)->links() }}
    </div>
</div>
@endsection

