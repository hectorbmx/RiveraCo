@extends('layouts.admin')

@section('title', 'CFDIs por emisor')

@section('content')
<div class="max-w-8xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <a href="{{ route('sat.cfdis.index', ['sat_empresa_id' => $empresaSeleccionada?->id]) }}"
               class="text-sm font-semibold text-indigo-700 hover:underline">
                Volver al visor SAT
            </a>
            <h1 class="mt-2 text-2xl font-semibold text-gray-900">
                {{ $emisorNombre ?: 'Emisor SAT' }}
            </h1>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-600">
                <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                    {{ $rfc }}
                </span>
                @if($empresaSeleccionada)
                    <span>{{ $empresaSeleccionada->nombre }} - {{ $empresaSeleccionada->rfc }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-gray-500">Cantidad de facturas</div>
            <div class="mt-3 text-2xl font-semibold text-gray-900">{{ number_format($kpis['cantidad']) }}</div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-gray-500">Total facturas</div>
            <div class="mt-3 text-2xl font-semibold text-gray-900">${{ number_format((float) $kpis['total'], 2) }}</div>
        </div>

        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
            <div class="text-sm font-medium text-indigo-700">Facturas este mes</div>
            <div class="mt-3 text-2xl font-semibold text-gray-900">{{ number_format($kpis['cantidad_mes']) }}</div>
            <div class="mt-1 text-sm font-semibold text-indigo-800">${{ number_format((float) $kpis['total_mes'], 2) }}</div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <div class="text-sm font-medium text-emerald-700">Facturas este año</div>
            <div class="mt-3 text-2xl font-semibold text-gray-900">{{ number_format($kpis['cantidad_anio']) }}</div>
            <div class="mt-1 text-sm font-semibold text-emerald-800">${{ number_format((float) $kpis['total_anio'], 2) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Facturas del emisor</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-indigo-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">UUID</th>
                        <th class="px-4 py-3 text-left font-medium">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium">Tipo</th>
                        <th class="px-4 py-3 text-left font-medium">RFC receptor</th>
                        <th class="px-4 py-3 text-left font-medium">Receptor</th>
                        <th class="px-4 py-3 text-left font-medium">Moneda</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                        <th class="px-4 py-3 text-left font-medium">Relacion</th>
                        <th class="px-4 py-3 text-left font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($cfdis as $cfdi)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="inline-flex max-w-[260px] truncate rounded-lg border border-indigo-300 bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700"
                                      title="{{ $cfdi->uuid }}">
                                    {{ $cfdi->uuid }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ optional($cfdi->fecha_emision)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $cfdi->tipo_comprobante ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $cfdi->rfc_receptor ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $cfdi->receptor_nombre ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $cfdi->moneda ?: '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                ${{ number_format((float) $cfdi->total, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                @if($cfdi->obra)
                                    <span class="inline-flex max-w-[220px] truncate rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 border border-emerald-200"
                                          title="{{ $cfdi->obra->nombre ?? $cfdi->obra->Nombre ?? 'Obra #' . $cfdi->obra->id }}">
                                        Obra: {{ $cfdi->obra->nombre ?? $cfdi->obra->Nombre ?? 'Obra #' . $cfdi->obra->id }}
                                    </span>
                                @elseif($cfdi->ordenCompra)
                                    <span class="inline-flex max-w-[220px] truncate rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 border border-blue-200"
                                          title="{{ $cfdi->ordenCompra->folio ?? 'OC #' . $cfdi->ordenCompra->id }}">
                                        OC: {{ $cfdi->ordenCompra->folio ?? 'OC #' . $cfdi->ordenCompra->id }}
                                    </span>
                                @else
                                    <span class="inline-flex rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 border border-amber-200">
                                        Sin relacion
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('sat.cfdis.show', $cfdi) }}"
                                       class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 border border-blue-200 hover:bg-blue-100">
                                        Ver
                                    </a>
                                    <a href="{{ route('sat.cfdis.pdf', $cfdi) }}"
                                       target="_blank"
                                       class="inline-flex items-center rounded-lg bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 border border-slate-200 hover:bg-slate-100">
                                        PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-gray-500">
                                No hay CFDIs registrados para este emisor.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 border-t border-gray-200">
            {{ $cfdis->links() }}
        </div>
    </div>
</div>
@endsection
