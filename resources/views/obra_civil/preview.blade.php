@extends('layouts.admin')

@section('title', 'Preview catalogo civil')

@section('content')
@php
    $metadata = $import->metadata ?? [];
    $warnings = $metadata['warnings'] ?? [];
@endphp

<div class="max-w-8xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->nombre }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Previsualizacion de catalogo</h1>
            <p class="text-sm text-slate-500">Revisa edificios, partidas y conceptos antes de guardar el catalogo definitivo.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('obra_civil.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver
            </a>
            <a href="{{ route('obra_civil.details', $obra) }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Ver detalles
            </a>
        </div>
    </div>

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Archivo</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $import->filename }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Estado</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $import->status }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Edificios</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format($import->total_buildings) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Partidas</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format($import->total_partidas) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Conceptos</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format($import->total_concepts) }}</div>
            </div>
        </div>
    </section>

    @if($warnings)
        <section class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            <div class="font-bold">Advertencias</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Contenido detectado</h2>
            <form method="POST" action="{{ route('obra_civil.catalog.confirm', [$obra, $import]) }}">
                @csrf
                <button type="submit"
                        @disabled((int) $import->total_concepts === 0)
                        class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f] disabled:cursor-not-allowed disabled:opacity-50">
                    Guardar catalogo
                </button>
            </form>
        </div>

        @if($import->buildings->isEmpty())
            <div class="px-5 py-12 text-center">
                <div class="text-base font-semibold text-slate-700">Aun no hay partidas renderizadas.</div>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">
                    La carga del archivo ya quedo registrada como borrador. El siguiente paso tecnico es conectar el parser de Excel para llenar esta previsualizacion con edificios, partidas y conceptos antes de permitir guardar.
                </p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($import->buildings as $building)
                    <div class="p-5">
                        <h3 class="font-bold text-[#0B265A]">{{ $building->name }}</h3>
                        <div class="mt-4 space-y-4">
                            @foreach($building->partidas as $partida)
                                <div class="rounded-lg border border-slate-200">
                                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 font-semibold text-slate-800">
                                        {{ $partida->code }} {{ $partida->name }}
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
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($partida->concepts as $concept)
                                                    <tr>
                                                        <td class="px-4 py-2 font-mono text-xs">{{ $concept->excel_code }}</td>
                                                        <td class="px-4 py-2">{{ $concept->description }}</td>
                                                        <td class="px-4 py-2">{{ $concept->unit }}</td>
                                                        <td class="px-4 py-2 text-right">{{ number_format((float) $concept->budget_quantity, 4) }}</td>
                                                        <td class="px-4 py-2 text-right">${{ number_format((float) $concept->unit_price, 4) }}</td>
                                                        <td class="px-4 py-2 text-right">${{ number_format((float) $concept->budget_amount, 2) }}</td>
                                                    </tr>
                                                @endforeach
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
</div>
@endsection
