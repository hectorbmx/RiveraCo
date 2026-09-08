@extends('layouts.admin')

@section('title', 'Reposicion de caja chica')

@section('content')
@php
    $ambitoFirmaSeleccionado = request('ambito', $ambitoFirma ?? \App\Models\DocumentoFirmante::AMBITO_REPOSICION_GASTOS_ALMACEN);
    $printQuery = array_merge(request()->query(), ['ambito' => $ambitoFirmaSeleccionado]);
@endphp
<div class="max-w-8xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Reposicion de caja chica</h1>
            <p class="text-sm text-slate-500">Gastos capturados por ingenieria con autorizacion individual.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a id="reposicion-print-link" href="{{ route('reposicion-caja-chica.imprimir', $printQuery) }}" data-print-base="{{ route('reposicion-caja-chica.imprimir') }}" target="_blank" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Imprimir</a>
            <a href="{{ route('reposicion-caja-chica.exportar-excel', request()->query()) }}" class="rounded-lg border border-green-300 bg-green-50 px-4 py-2 text-sm font-semibold text-green-800 hover:bg-green-100">Exportar Excel</a>
            <a href="{{ route('reposicion-caja-chica.revision') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Revision oficina</a>
            <a href="{{ route('reposicion-caja-chica.relaciones.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Relaciones</a>
            <a href="{{ route('reposicion-caja-chica.create') }}" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900">Nuevo gasto</a>
        </div>
    </div>

    {{-- KPIs pausados temporalmente
    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-slate-500">Registrado</p>
            <p class="mt-1 text-xl font-bold text-[#0B265A]">${{ number_format((float) $stats['registrado'], 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-slate-500">Autorizado</p>
            <p class="mt-1 text-xl font-bold text-green-700">${{ number_format((float) $stats['autorizado'], 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-slate-500">Borradores</p>
            <p class="mt-1 text-xl font-bold text-slate-800">{{ number_format($stats['borrador']) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-slate-500">Pendientes</p>
            <p class="mt-1 text-xl font-bold text-amber-700">{{ number_format($stats['pendiente']) }}</p>
        </div>
    </div>
    --}}

    <x-filters.card id="reposicion-filtros" action="{{ route('reposicion-caja-chica.index') }}" class="mb-4 p-3">
        <x-filters.date
            name="fecha_inicio"
            label="Fecha inicio"
            :value="$fechaInicio->format('Y-m-d')"
            span="md:col-span-2" />

        <x-filters.date
            name="fecha_fin"
            label="Fecha fin"
            :value="$fechaFin->format('Y-m-d')"
            span="md:col-span-2" />

        <x-filters.select
            name="estado"
            label="Estado"
            :value="request('estado')"
            :options="['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'autorizado' => 'Autorizado', 'autorizado_parcial' => 'Autorizado parcial', 'rechazado' => 'Rechazado']"
            placeholder="Todos"
            span="md:col-span-2" />

        <x-filters.select
            name="categoria_id"
            label="Tipo de comprobacion"
            :value="request('categoria_id')"
            :options="$categorias->pluck('nombre', 'id')->all()"
            placeholder="Todas"
            span="md:col-span-2" />

        <x-filters.select
            name="destino"
            label="Destino"
            :value="request('destino')"
            :options="['obra' => 'Obra', 'almacen' => 'Almacen']"
            placeholder="Todos"
            span="md:col-span-1" />

        <x-filters.select
            name="ambito"
            label="Firma impresa"
            :value="$ambitoFirmaSeleccionado"
            :options="[
                \App\Models\DocumentoFirmante::AMBITO_REPOSICION_GASTOS_ALMACEN => 'Reposicion gastos almacen',
                \App\Models\DocumentoFirmante::AMBITO_GIRALDA => 'Giralda',
            ]"
            span="md:col-span-2" />

        <x-filters.actions
            submit-label="Filtrar"
            clear-url="{{ route('reposicion-caja-chica.index', ['ambito' => $ambitoFirmaSeleccionado]) }}"
            span="md:col-span-1" />

        <div class="md:col-span-12 flex flex-wrap justify-end gap-2 border-t border-white/10 pt-3">
            <a href="{{ route('reposicion-caja-chica.index', [
                'fecha_inicio' => $semanaAnteriorInicio,
                'fecha_fin' => $semanaAnteriorFin,
                'estado' => request('estado'),
                'categoria_id' => request('categoria_id'),
                'destino' => request('destino'),
                'ambito' => $ambitoFirmaSeleccionado,
                'q' => request('q'),
            ]) }}" class="rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-white/20">
                Semana anterior
            </a>
            <a href="{{ route('reposicion-caja-chica.index', [
                'estado' => request('estado'),
                'categoria_id' => request('categoria_id'),
                'destino' => request('destino'),
                'ambito' => $ambitoFirmaSeleccionado,
                'q' => request('q'),
            ]) }}" class="rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-white/20">
                Semana actual
            </a>
            <a href="{{ route('reposicion-caja-chica.index', [
                'fecha_inicio' => $semanaSiguienteInicio,
                'fecha_fin' => $semanaSiguienteFin,
                'estado' => request('estado'),
                'categoria_id' => request('categoria_id'),
                'destino' => request('destino'),
                'ambito' => $ambitoFirmaSeleccionado,
                'q' => request('q'),
            ]) }}" class="rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-white/20">
                Semana siguiente
            </a>
        </div>
    </x-filters.card>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm border border-slate-200">
        <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase text-slate-500">Semana mostrada</p>
                <p class="text-sm font-bold text-slate-800">
                    {{ $fechaInicio->format('d/m/Y') }} al {{ $fechaFin->format('d/m/Y') }}
                </p>
            </div>
            <div class="w-full lg:max-w-md">
                <label for="reposicion-q" class="block text-xs font-semibold text-slate-600 mb-1">Buscar</label>
                <input id="reposicion-q"
                       form="reposicion-filtros"
                       type="search"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Proveedor, RFC, concepto, UUID, obra o almacen"
                       class="w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-200"
                       style="box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.18), 0 0 20px rgba(255, 193, 7, 0.32);">
            </div>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Folio</th>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3 text-left">Tipo de comprobacion</th>
                    <th class="px-4 py-3 text-left">Proveedor / concepto</th>
                    <th class="px-4 py-3 text-left">Destino</th>
                    <th class="px-4 py-3 text-right">Registrado</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($gastos as $gasto)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-semibold text-slate-800">RCC-G-{{ str_pad($gasto->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">{{ optional($gasto->fecha_gasto)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">{{ $gasto->categoria->nombre ?? '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $gasto->subcategoria->nombre ?? 'Sin categoria' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">{{ $gasto->proveedor_nombre }}</div>
                            <div class="text-xs text-slate-500">{{ $gasto->concepto }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($gasto->destino === 'obra')
                                {{ $gasto->obra->nombre ?? 'Obra no definida' }}
                            @else
                                {{ $gasto->almacen->nombre ?? 'Almacen no definido' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">${{ number_format((float) $gasto->importe_registrado, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str_replace('_', ' ', $gasto->estado_autorizacion) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('reposicion-caja-chica.show', $gasto) }}" class="font-semibold text-blue-700 hover:underline">Ver</a>

                                @if($gasto->estado_autorizacion === 'pendiente' && auth()->user()?->can('caja_chica.authorize'))
                                    <form method="POST" action="{{ route('reposicion-caja-chica.autorizar', $gasto) }}" onsubmit="return confirm('¿Autorizar este gasto completo?')">
                                        @csrf
                                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white hover:bg-green-700" title="Autorizar gasto">
                                            ✓
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-slate-500">Aun no hay gastos de reposicion de caja chica.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $gastos->links() }}</div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reposicion-filtros');
    const printLink = document.getElementById('reposicion-print-link');

    if (!form || !printLink) {
        return;
    }

    const updatePrintLink = () => {
        const params = new URLSearchParams(new FormData(form));

        for (const [key, value] of Array.from(params.entries())) {
            if (value === '') {
                params.delete(key);
            }
        }

        const queryString = params.toString();
        printLink.href = queryString
            ? `${printLink.dataset.printBase}?${queryString}`
            : printLink.dataset.printBase;
    };

    form.addEventListener('change', updatePrintLink);
    form.addEventListener('input', updatePrintLink);
    updatePrintLink();
});
</script>
@endpush






