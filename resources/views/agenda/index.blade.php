@extends('layouts.admin')

@section('title', 'Agenda')

@section('content')
@php
    $tipoLabels = [
        'todos' => 'Todos',
        'clientes' => 'Clientes',
        'proveedores' => 'Proveedores',
        'empleados' => 'Empleados',
    ];

    $entityLabels = [
        \App\Models\Cliente::class => 'Cliente',
        \App\Models\Proveedor::class => 'Proveedor',
        \App\Models\Empleado::class => 'Empleado',
    ];
@endphp

<div class="max-w-7xl mx-auto space-y-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Agenda</h1>
            <p class="text-sm text-slate-500">Directorio telefonico para llamadas desde SIRICO.</p>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-semibold text-slate-500">Total</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ $stats['total'] }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-semibold text-slate-500">Clientes</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ $stats['clientes'] }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-semibold text-slate-500">Proveedores</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ $stats['proveedores'] }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-semibold text-slate-500">Empleados</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ $stats['empleados'] }}</div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-100 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-100 p-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @php
        $perPageFiltroOpciones = [];

        foreach ($perPageOpciones as $opcion) {
            $perPageFiltroOpciones[$opcion] = $opcion . ' filas';
        }
    @endphp

    <x-filters.card action="{{ route('agenda.index') }}">
        <x-filters.input
            name="q"
            label="Buscar"
            :value="$search"
            placeholder="Nombre, puesto, area o telefono"
            span="md:col-span-5"
            type="search"
            glow />

        <x-filters.select
            name="tipo"
            label="Tipo"
            :value="$tipo"
            :options="$tipoLabels"
            span="md:col-span-2 md:max-w-44" />

        <x-filters.select
            name="per_page"
            label="Filas"
            :value="$perPage"
            :options="$perPageFiltroOpciones"
            span="md:col-span-2 md:max-w-44"
            onchange="this.form.submit()" />

        <x-filters.actions
            submit-label="Filtrar"
            clear-url="{{ route('agenda.index') }}"
            span="md:col-span-3" />
    </x-filters.card>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Puesto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Area</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Telefono</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Accion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($agenda as $telefono)
                        @php
                            $persona = $telefono->phoneable;
                            $tipoEntidad = $entityLabels[$telefono->phoneable_type] ?? class_basename($telefono->phoneable_type);

                            if ($persona instanceof \App\Models\Empleado) {
                                $nombre = $persona->nombre_completo ?: $telefono->display_name;
                                $puesto = $persona->Puesto ?: 'Empleado';
                                $area = $persona->areaRef?->nombre ?: ($persona->Area ? 'Area '.$persona->Area : 'Sin area');
                            } elseif ($persona instanceof \App\Models\Proveedor) {
                                $nombre = $telefono->display_name ?: ($persona->nombre ?: $persona->razon_social);
                                $puesto = $telefono->source_column === 'telefono_contacto' ? 'Contacto proveedor' : 'Proveedor';
                                $area = 'Proveedores';
                            } else {
                                $nombre = $telefono->display_name ?: optional($persona)->nombre_comercial ?: optional($persona)->razon_social;
                                $puesto = isset($telefono->metadata['contacto_cargo']) && $telefono->metadata['contacto_cargo'] 
                                    ? $telefono->metadata['contacto_cargo'] . ' (' . (optional($persona)->nombre_comercial ?: optional($persona)->razon_social) . ')'
                                    : ($telefono->source_column !== 'telefono' ? 'Contacto cliente' : 'Cliente');
                                $area = optional($persona)->nombre_comercial ?: optional($persona)->razon_social ?: 'Clientes';
                            }
                        @endphp

                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $nombre ?: 'Sin nombre' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $tipoEntidad }} / {{ $telefono->label }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $puesto ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $area ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-mono text-slate-900">{{ $telefono->raw_number }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $telefono->normalized_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST"
                                      action="{{ route('telephony.phone-numbers.call', $telefono) }}"
                                      onsubmit="return confirm('Se llamara desde tu extension al numero {{ $telefono->raw_number }}. Deseas continuar?')">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                        Llamar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                No hay contactos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($agenda->hasPages())
            <div class="border-t border-slate-200 p-4">
                {{ $agenda->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

