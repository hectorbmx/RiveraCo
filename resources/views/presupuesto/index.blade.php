@extends('layouts.admin')

@section('content')
<style>
    .presupuestos-table thead { background: #0B265A; color: #fff; }
    .presupuestos-table tbody tr:hover,
    .presupuestos-table tbody tr:focus-within { background: #dce9fa; }
</style>
@php
    $filtrosListado = array_filter(['buscar' => $buscar, 'tipo' => $tipoFiltro], fn ($valor) => $valor !== null && $valor !== '');
    $claseAccion = 'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2';
@endphp
<div class="p-6" x-data>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Presupuestos</h1>
        <a href="{{ route('presupuesto.create') }}" aria-haspopup="dialog"
           @unless($versionDuplicacion) @click.prevent="$refs.nuevoPresupuesto.showModal()" @endunless
           class="rounded-xl bg-[#0B265A] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-900">Nuevo presupuesto</a>
    </div>
    <dialog x-ref="nuevoPresupuesto" aria-labelledby="titulo-nuevo-presupuesto"
            @if($versionDuplicacion)
                x-init="$el.showModal()" @cancel.prevent="window.location.assign(@js(route('presupuesto.index', $filtrosListado)))"
            @elseif($errors->datosPresupuesto->any())
                x-init="$el.showModal()"
            @endif
            class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-xl backdrop:bg-slate-900/50 sm:p-6">
        <h2 id="titulo-nuevo-presupuesto" class="mb-4 text-xl font-semibold text-[#0B265A]">{{ $versionDuplicacion ? 'Duplicar presupuesto' : 'Nuevo presupuesto' }}</h2>
        @if($versionDuplicacion)
            <p class="mb-4 text-sm text-slate-600">
                {{ $origenDuplicacion->codigo_proyecto }} · {{ $origenDuplicacion->estaAprobado() ? 'Versión aprobada' : 'Última versión guardada' }} {{ $versionDuplicacion->folio() }}
            </p>
        @endif
        @include('presupuesto.partials.datos', [
            'presupuesto' => $nuevoPresupuesto,
            'enModal' => true,
            'versionParaCopiar' => $versionDuplicacion,
            'cancelarDuplicacion' => route('presupuesto.index', $filtrosListado),
        ])
    </dialog>

@php
    $estados = config('sat_catalogs.estados');
    $estadoBadge = function ($estado) {
        $estado = strtolower(trim((string) $estado));
        return match ($estado) {
            'sincronizado' => 'bg-green-100 text-green-800 border-green-200',
            'pendiente'    => 'bg-amber-100 text-amber-800 border-amber-200',
            'error'        => 'bg-red-100 text-red-800 border-red-200',
            default        => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    };
@endphp

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <form method="GET" action="{{ route('presupuesto.index') }}" class="flex flex-wrap items-end gap-3 px-5 py-4 border-b border-slate-200 bg-slate-50" role="search">
            <div class="flex-1 min-w-0 max-w-xl">
                <label for="buscar-presupuesto" class="block text-sm font-medium text-slate-700 mb-1">Buscar por cliente o código</label>
                <input id="buscar-presupuesto" type="search" name="buscar" value="{{ $buscar }}" maxlength="255"
                       class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="Escribe el cliente o código del proyecto…">
                @error('buscar')
                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="filtrar-tipo-presupuesto" class="mb-1 block text-sm font-medium text-slate-700">Tipo de presupuesto</label>
                <select id="filtrar-tipo-presupuesto" name="tipo" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos los tipos</option>
                    @foreach(array_keys(\App\Models\Presupuesto::TIPOS) as $tipo)
                        <option value="{{ $tipo }}" @selected($tipoFiltro === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
                @error('tipo')
                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="rounded-xl bg-[#0B265A] px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-900">Filtrar</button>
            @if($buscar !== '' || $tipoFiltro !== null)
                <a href="{{ route('presupuesto.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-white">Limpiar</a>
            @endif
        </form>
        <div class="overflow-x-auto" role="region" aria-label="Presupuestos" tabindex="0">
        <table class="presupuestos-table w-full text-sm border-collapse">
            <thead>
                <tr>
                    <th class="p-2 border font-semibold text-center">Código Proyecto</th>
                    <th class="p-2 border font-semibold text-left">Tipo</th>
                    <th class="p-2 border font-semibold text-left">Cliente</th>
                    <th class="p-2 border font-semibold text-left">Estado</th>
                    <th class="p-2 border font-semibold text-left">Ciudad</th>
                    <th class="p-2 border font-semibold text-center">Costo Directo</th>
                    <th class="p-2 border font-semibold text-center">Total Presupuesto</th>
                    <th class="p-2 border font-semibold text-center">Estatus</th>
                    <th class="p-2 border font-semibold text-left">Seguimiento</th>
                    <th class="p-2 border font-semibold text-center">Origen</th>
                    <th class="p-2 border font-semibold text-center">Fecha de alta</th>
                    <th class="p-2 border font-semibold text-center"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($presupuestos as $presupuesto)
                <tr class="border-b transition-colors">
                    <td class="p-2 border text-center font-bold text-blue-900">
                        {{ $presupuesto->codigo_proyecto }}
                    </td>
                    <td class="p-2 border text-left">
                        <span class="inline-flex items-center gap-2">
                            <span class="h-6 w-6 shrink-0">
                                @include('presupuesto.partials.imagen-tipo', ['tipoImagen' => $presupuesto->tipo, 'clasesImagen' => 'h-full w-full object-contain'])
                            </span>
                            {{ $presupuesto->tipo ?? 'Sin clasificar' }}
                        </span>
                    </td>
                    <td class="p-2 border text-left">
                        {{ $presupuesto->nombreClienteVisible() }}
                    </td>
                    <td class="p-2 border">
                        {{ \Illuminate\Support\Str::after($estados[$presupuesto->estado] ?? '—', ' - ') }}
                    </td>
                    <td class="p-2 border">{{ $presupuesto->ciudad ?? '—' }}</td>
                    <td class="p-2 border text-center">
                        {{ $importes[$presupuesto->id]['directo'] === null ? '—' : '$' . number_format($importes[$presupuesto->id]['directo'], 2) }}
                    </td>
                    <td class="p-2 border text-center font-semibold">
                        ${{ number_format($importes[$presupuesto->id]['total'], 2) }}
                    </td>
                    <td class="p-2 border text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $estadoBadge($presupuesto->estatus) }}">
                            {{ ucfirst($presupuesto->estatus) }}
                        </span>
                    </td>
                    <td class="p-2 border">
                        @if($presupuesto->ultimoMovimiento)
                            @php($movimiento = $presupuesto->ultimoMovimiento)
                            <a href="{{ route('presupuesto.versiones.show', [$presupuesto, $movimiento->version]) }}#seguimiento" class="font-semibold text-[#0B265A] hover:underline">
                                {{ $movimiento->etiqueta() }} · {{ $movimiento->version->folio() }}
                            </a>
                            <p class="text-xs text-slate-500">{{ $movimiento->created_at->format('d/m/Y H:i') }}</p>
                            @if($presupuesto->aceptacionVigente)
                                @php($aceptada = $presupuesto->aceptacionVigente->version)
                                <a href="{{ route('presupuesto.versiones.show', [$presupuesto, $aceptada]) }}#seguimiento" class="mt-1 block text-sm font-medium text-emerald-700 hover:underline">
                                    Versión aceptada {{ $aceptada->folio() }}
                                </a>
                            @endif
                        @else
                            <span class="text-slate-500">—</span>
                        @endif
                    </td>
                    <td class="p-2 border text-center text-gray-600">
                        {{ $presupuesto->esImportado() ? 'Excel' : 'SIRICO' }}
                    </td>
                    <td class="p-2 border text-center text-gray-600">
                        {{ $presupuesto->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="p-2 border text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('presupuesto.show', $presupuesto->id) }}" title="Abrir"
                               aria-label="Abrir presupuesto {{ $presupuesto->codigo_proyecto }}"
                               class="{{ $claseAccion }} text-blue-700 hover:bg-blue-50">
                                <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>

                            @if($presupuesto->versiones_count > 0)
                                <a href="{{ route('presupuesto.index', $filtrosListado + ['duplicar' => $presupuesto->id]) }}"
                                   aria-haspopup="dialog" title="Duplicar" aria-label="Duplicar presupuesto {{ $presupuesto->codigo_proyecto }}"
                                   class="{{ $claseAccion }} text-blue-700 hover:bg-blue-50">
                                    <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <rect x="8" y="8" width="12" height="12" rx="2" />
                                        <path stroke-linecap="round" d="M16 4H6a2 2 0 0 0-2 2v10" />
                                    </svg>
                                </a>
                            @else
                                <button type="button" aria-disabled="true" title="Duplicar. Guarda una versión para poder duplicar."
                                        aria-label="Duplicar. Guarda una versión para poder duplicar."
                                        class="{{ $claseAccion }} cursor-not-allowed text-slate-400">
                                    <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <rect x="8" y="8" width="12" height="12" rx="2" />
                                        <path stroke-linecap="round" d="M16 4H6a2 2 0 0 0-2 2v10" />
                                    </svg>
                                </button>
                            @endif

                            @can('presupuestos.delete.access')
                                @if($presupuesto->esBorrador() && !$presupuesto->tiene_seguimiento && !$presupuesto->obras_exists)
                                    <form action="{{ route('presupuesto.destroy', $presupuesto->id) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar este borrador, sus datos y todas sus versiones guardadas?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Eliminar" aria-label="Eliminar borrador {{ $presupuesto->codigo_proyecto }}"
                                                class="{{ $claseAccion }} text-red-700 hover:bg-red-50">
                                            <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 10v7m4-7v7" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="px-5 py-10 text-center text-slate-500">
                        {{ $buscar !== '' || $tipoFiltro !== null ? 'No se encontraron presupuestos con los filtros seleccionados.' : 'Aún no hay presupuestos.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Paginación con estilo Tailwind --}}
    <div class="mt-4">
        @if(method_exists($presupuestos, 'links'))
            {{ $presupuestos->links() }}
        @endif
    </div>
</div>
@endsection
