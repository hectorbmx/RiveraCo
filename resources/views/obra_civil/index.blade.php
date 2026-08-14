@extends('layouts.admin')

@section('title', 'Obra Civil')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="obraCivilUploadModal()">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Obra Civil</h1>
            <p class="text-sm text-slate-500">
                Catalogos de conceptos, partidas y saldos para ordenes de compra.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('obras.create') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Nueva obra
            </a>
        </div>
    </div>

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if(isset($catalogTablesReady) && !$catalogTablesReady)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
            El modulo de obra civil esta instalado, pero faltan migraciones de catalogo civil en esta base de datos. Ejecuta las migraciones antes de cargar catalogos.
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Obras civiles</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['obras']) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Catalogos</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['catalogos']) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Conceptos</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['conceptos']) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Presupuesto civil</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($stats['importe'], 2) }}</div>
        </div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Obras civiles</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Obra</th>
                        <th class="px-5 py-3 text-left">Cliente</th>
                        <th class="px-5 py-3 text-left">Clave</th>
                        <th class="px-5 py-3 text-right">Catalogos</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($obras as $obra)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $obra->nombre }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $obra->cliente->nombre_comercial ?? '-' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ $obra->clave_obra ?: '-' }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($obra->civil_catalog_imports_count) }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-3">
                                    @if($obra->civil_catalog_imports_count > 0)
                                        <a href="{{ route('obra_civil.details', $obra) }}"
                                           class="font-semibold text-slate-600 hover:underline">
                                            Ver detalle
                                        </a>
                                    @endif
                                    @if(isset($catalogTablesReady) && $catalogTablesReady)
                                        <button type="button"
                                                class="font-semibold text-[#0B265A] hover:underline"
                                                @click="open(@js($obra->nombre), @js(route('obra_civil.catalog.upload', $obra)))">
                                            Cargar
                                        </button>
                                    @else
                                        <span class="text-xs font-semibold text-slate-400">Migraciones pendientes</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                Aun no hay obras con tipo OBRA_CIVIL o CIVIL.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Ultimos catalogos importados</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Archivo</th>
                        <th class="px-5 py-3 text-left">Obra</th>
                        <th class="px-5 py-3 text-left">Estado</th>
                        <th class="px-5 py-3 text-right">Conceptos</th>
                        <th class="px-5 py-3 text-right">Importe</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($catalogImports as $import)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $import->filename }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $import->obra->nombre ?? '-' }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                    {{ $import->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">{{ number_format($import->total_concepts) }}</td>
                            <td class="px-5 py-3 text-right">${{ number_format((float) $import->total_amount, 2) }}</td>
                            <td class="px-5 py-3 text-right">
                                @if($import->obra)
                                    <form method="POST"
                                          action="{{ route('obra_civil.catalog.destroy', [$import->obra, $import]) }}"
                                          class="inline"
                                          onsubmit="return confirm('Eliminar este catalogo civil? Esta accion tambien borra sus edificios, partidas y conceptos si no estan usados en ordenes de compra.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-semibold text-red-600 hover:underline">
                                            Eliminar
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                Aun no hay catalogos importados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div x-show="show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4">
        <div class="w-full max-w-lg rounded-lg bg-white shadow-xl" @click.away="close()">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Cargar catalogo civil</h3>
                <p class="mt-1 text-sm text-slate-500" x-text="obraNombre"></p>
            </div>

            <form :action="actionUrl" method="POST" enctype="multipart/form-data" class="space-y-4 px-5 py-5">
                @csrf
                <div>
                    <label for="catalogo" class="block text-sm font-semibold text-slate-700">Archivo Excel</label>
                    <input id="catalogo"
                           name="catalogo"
                           type="file"
                           accept=".xlsx,.xlsm,.xls,.csv"
                           required
                           class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#0B265A] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                    <p class="mt-2 text-xs text-slate-500">Formatos permitidos: xlsx, xlsm, xls, csv. Maximo 20 MB.</p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            @click="close()">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]">
                        Subir y previsualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function obraCivilUploadModal() {
    return {
        show: false,
        obraNombre: '',
        actionUrl: '',
        open(nombre, url) {
            this.obraNombre = nombre;
            this.actionUrl = url;
            this.show = true;
        },
        close() {
            this.show = false;
            this.obraNombre = '';
            this.actionUrl = '';
        },
    };
}
</script>
@endpush
