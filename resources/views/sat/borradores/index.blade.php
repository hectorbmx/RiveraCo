@extends('layouts.admin')

@section('title', 'Borradores SAT')

@section('content')
<div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Borradores SAT</h1>
            <p class="text-sm text-slate-500">Capturas CFDI pendientes de completar o timbrar.</p>
        </div>

        <a href="{{ route('sat.facturacion.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
            <span>+</span>
            Nuevo borrador
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <x-filters.card action="{{ route('sat.borradores.index') }}" class="mb-4 p-3">
        <x-filters.input
            name="q"
            label="Buscar"
            :value="$busqueda"
            placeholder="Cliente, RFC, obra, empresa o titulo"
            span="md:col-span-9"
            type="search"
            glow />

        <x-filters.actions
            submit-label="Filtrar"
            clear-url="{{ route('sat.borradores.index') }}"
            span="md:col-span-3" />
    </x-filters.card>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-4">Fecha</th>
                        <th class="px-5 py-4">Titulo</th>
                        <th class="px-5 py-4">Cliente</th>
                        <th class="px-5 py-4">Obra</th>
                        <th class="px-5 py-4">Empresa</th>
                        <th class="px-5 py-4 text-right">Total estimado</th>
                        <th class="px-5 py-4">Creado por</th>
                        <th class="px-5 py-4 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($borradores as $borrador)
                        @php
                            $payload = $borrador->payload ?: [];
                            $conceptos = collect($payload['conceptos'] ?? []);
                            $subtotal = $conceptos->sum(fn ($concepto) => (float) ($concepto['cantidad'] ?? 0) * (float) ($concepto['precio_unitario'] ?? 0));
                            $tipoIva = $payload['tipo_iva'] ?? '0.16';
                            $ivaTasa = in_array($tipoIva, ['0.16', '0.08'], true) ? (float) $tipoIva : 0;
                            $base = max(0, $subtotal - (float) ($payload['amortizacion'] ?? 0) - (float) ($payload['descuento'] ?? 0));
                            $totalEstimado = max(0, $base + ($base * $ivaTasa) - (float) ($payload['retenciones'] ?? 0));
                            $clienteNombre = $borrador->cliente?->razon_social ?: $borrador->cliente?->nombre_comercial;
                            $obraNombre = $borrador->obra?->nombre ?: $borrador->obra?->Nombre;
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 text-slate-600">
                                <div class="font-medium text-slate-800">{{ $borrador->updated_at?->format('d/m/Y') }}</div>
                                <div class="text-xs text-slate-400">{{ $borrador->updated_at?->format('H:i') }}</div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">{{ $borrador->titulo ?: 'Borrador CFDI #' . $borrador->id }}</div>
                                <div class="mt-1 text-xs text-slate-400">ID: {{ $borrador->id }}</div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-800">{{ $clienteNombre ?: '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $borrador->cliente?->rfc ?: '-' }}</div>
                            </td>

                            <td class="px-5 py-4 text-slate-700">
                                {{ $obraNombre ?: '-' }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-800">{{ $borrador->empresa?->nombre ?: '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $borrador->empresa?->rfc ?: '-' }}</div>
                            </td>

                            <td class="px-5 py-4 text-right font-semibold text-slate-900">
                                ${{ number_format($totalEstimado, 2) }}
                            </td>

                            <td class="px-5 py-4 text-slate-700">
                                {{ $borrador->user?->name ?: 'General' }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('sat.facturacion.create', ['cfdi_borrador_id' => $borrador->id]) }}"
                                       class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Editar
                                    </a>

                                    <form method="POST"
                                          action="{{ route('sat.facturacion.borradores.destroy', $borrador) }}"
                                          onsubmit="return confirm('Eliminar este borrador CFDI?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="inline-flex items-center rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">
                                No hay borradores CFDI pendientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $borradores->links() }}
    </div>
</div>
@endsection