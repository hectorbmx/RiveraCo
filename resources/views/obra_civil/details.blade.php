@extends('layouts.admin')

@section('title', 'Detalle obra civil')

@section('content')
<div class="max-w-8xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? '-' }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">{{ $obra->nombre }}</h1>
            <p class="text-sm text-slate-500">Catalogo civil, partidas, conceptos y saldos de la obra.</p>
        </div>
        <a href="{{ route('obra_civil.index') }}"
           class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Volver
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            {{ session('success') }}
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
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Catalogo activo</h2>
        </div>

        @if(!$activeImport)
            <div class="px-5 py-12 text-center text-sm text-slate-500">
                Esta obra civil todavia no tiene un catalogo guardado.
            </div>
        @else
            @php
                $subtotal = (float) $activeImport->total_amount;
                $iva = $subtotal * 0.16;
                $total = $subtotal + $iva;
            @endphp
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
                    <div class="mt-1 font-semibold text-slate-900">${{ number_format($subtotal, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">IVA</div>
                    <div class="mt-1 font-semibold text-slate-900">${{ number_format($iva, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">Total</div>
                    <div class="mt-1 font-semibold text-slate-900">${{ number_format($total, 2) }}</div>
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
                                                    <th class="px-4 py-2 text-right">Usado</th>
                                                    <th class="px-4 py-2 text-right">Disponible</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($partida->concepts as $concept)
                                                    @php
                                                        $balance = $balances->get($concept->id, []);
                                                        $usedQuantity = (float) ($balance['used_quantity'] ?? 0);
                                                        $usedAmount = (float) ($balance['used_amount'] ?? 0);
                                                        $availableQuantity = (float) ($balance['available_quantity'] ?? $concept->budget_quantity);
                                                        $availableAmount = (float) ($balance['available_amount'] ?? $concept->budget_amount);
                                                        $ordersCount = (int) ($movementCounts->get($concept->id, 0));
                                                        $isExceeded = $availableQuantity < 0 || $availableAmount < 0;
                                                    @endphp
                                                    <tr class="align-top hover:bg-slate-50 {{ $isExceeded ? 'bg-amber-50/60' : '' }}">
                                                        <td class="px-4 py-2 font-mono text-xs text-slate-600">{{ $concept->excel_code }}</td>
                                                        <td class="px-4 py-2 text-slate-800">{{ $concept->description }}</td>
                                                        <td class="px-4 py-2 text-slate-600">{{ $concept->unit }}</td>
                                                        <td class="px-4 py-2 text-right tabular-nums">{{ number_format((float) $concept->budget_quantity, 4) }}</td>
                                                        <td class="px-4 py-2 text-right tabular-nums">${{ number_format((float) $concept->unit_price, 4) }}</td>
                                                        <td class="px-4 py-2 text-right tabular-nums font-semibold">${{ number_format((float) $concept->budget_amount, 2) }}</td>
                                                        <td class="px-4 py-2 text-right tabular-nums text-slate-600">
                                                            <div>{{ number_format($usedQuantity, 4) }}</div>
                                                            <div class="text-xs">${{ number_format($usedAmount, 2) }}</div>
                                                            @if($ordersCount > 0)
                                                                <a href="{{ route('obra_civil.concept.orders', [$obra, $concept]) }}" class="mt-1 inline-block text-xs font-semibold text-[#0B265A] hover:underline">
                                                                    Ver OCs ({{ $ordersCount }})
                                                                </a>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-right tabular-nums font-semibold {{ $isExceeded ? 'text-red-700' : 'text-slate-800' }}">
                                                            <div>{{ number_format($availableQuantity, 4) }}</div>
                                                            <div class="text-xs">${{ number_format($availableAmount, 2) }}</div>
                                                        </td>
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
</div>
@endsection
