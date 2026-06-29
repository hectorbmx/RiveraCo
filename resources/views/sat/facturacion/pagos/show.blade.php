@extends('layouts.admin')

@section('title', 'Detalle complemento de pago')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Complemento de pago</h1>
            <p class="text-sm text-slate-500 mt-1">
                UUID: <span class="font-mono">{{ $pago->uuid ?: 'Sin UUID' }}</span>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sat.complementos-pago.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Complementos
            </a>
            @if($pago->factura)
                <a href="{{ route('sat.facturacion.show', $pago->factura) }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Ver factura
                </a>
            @endif
            @if($pago->estado === 'timbrado')
                <form method="POST"
                      action="{{ route('sat.facturacion.pagos.cancelar', $pago) }}"
                      onsubmit="return confirm('¿Cancelar este complemento de pago?');">
                    @csrf
                    <input type="hidden" name="motivo_cancelacion" value="02">
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700">
                        Cancelar complemento
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Monto pagado</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">${{ number_format($pago->monto, 2) }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Saldo anterior</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($pago->saldo_anterior, 2) }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Saldo insoluto</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">${{ number_format($pago->saldo_insoluto, 2) }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Estado</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ ucfirst($pago->estado) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Factura relacionada</h2>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Folio</div>
                        <div class="font-semibold text-slate-900">
                            @if($pago->factura)
                                {{ trim(($pago->factura->serie ? $pago->factura->serie . '-' : '') . $pago->factura->folio) ?: 'Factura #' . $pago->factura->id }}
                            @else
                                Factura no encontrada
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">UUID factura</div>
                        <div class="font-mono text-xs text-slate-700 break-all">{{ $pago->factura?->uuid ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Cliente</div>
                        <div class="font-semibold text-slate-900">
                            {{ $pago->factura?->receptor_nombre ?: $pago->factura?->cliente?->razon_social ?: 'Sin cliente' }}
                        </div>
                        <div class="text-xs text-slate-500">
                            {{ $pago->factura?->receptor_rfc ?: $pago->factura?->cliente?->rfc ?: '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Obra</div>
                        @if($pago->factura?->obra)
                            <a href="{{ route('obras.edit', $pago->factura->obra) }}"
                               class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                                {{ $pago->factura->obra->nombre ?? 'Obra #' . $pago->factura->obra->id }}
                            </a>
                        @else
                            <div class="text-slate-500">Sin obra</div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Pagos internos ligados</h2>
                    <span class="text-xs text-slate-500">{{ $pago->pagosInternosObra->count() }} registro(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-slate-500 uppercase text-xs tracking-wide">
                                <th class="px-5 py-4 text-left">Fecha</th>
                                <th class="px-5 py-4 text-left">Obra</th>
                                <th class="px-5 py-4 text-left">Referencia</th>
                                <th class="px-5 py-4 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($pago->pagosInternosObra as $pagoInterno)
                                <tr>
                                    <td class="px-5 py-4">{{ $pagoInterno->fecha_pago?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-5 py-4">
                                        @if($pagoInterno->obra)
                                            <a href="{{ route('obras.edit', $pagoInterno->obra) }}"
                                               class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline">
                                                {{ $pagoInterno->obra->nombre ?? 'Obra #' . $pagoInterno->obra->id }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">Sin obra</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <div>{{ $pagoInterno->referencia ?: '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $pagoInterno->metodoPago?->nombre ?: '' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold">${{ number_format($pagoInterno->monto, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-slate-500">
                                        Este complemento no tiene pagos internos ligados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Datos del complemento</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Fecha pago</div>
                        <div class="font-semibold text-slate-900">{{ $pago->fecha_pago?->format('d/m/Y H:i') ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Forma de pago</div>
                        <div>{{ config('sat_catalogs.formas_pago.' . $pago->forma_pago) ?? $pago->forma_pago }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Parcialidad</div>
                        <div>{{ $pago->numero_parcialidad ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Facturapi ID</div>
                        <div class="font-mono text-xs break-all">{{ $pago->facturapi_invoice_id ?: '-' }}</div>
                    </div>
                    @if($pago->estado === 'cancelado')
                        <div>
                            <div class="text-xs font-semibold text-slate-500 uppercase">Cancelado</div>
                            <div>{{ $pago->fecha_cancelacion?->format('d/m/Y H:i') ?: '-' }}</div>
                            <div class="text-xs text-slate-500">Motivo {{ $pago->motivo_cancelacion ?: '-' }}</div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Archivos</h2>
                <div class="space-y-2">
                    @if($pago->xml_path)
                        <a href="{{ route('sat.facturacion.pagos.xml', $pago) }}"
                           class="flex items-center justify-center rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Descargar XML
                        </a>
                    @endif
                    @if($pago->pdf_path)
                        <a href="{{ route('sat.facturacion.pagos.pdf', $pago) }}"
                           class="flex items-center justify-center rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Descargar PDF
                        </a>
                    @endif
                    @if(!$pago->xml_path && !$pago->pdf_path)
                        <p class="text-sm text-slate-500">No hay archivos guardados.</p>
                    @endif
                </div>
            </section>
        </aside>
    </div>

    @if(!empty($pago->facturapi_response))
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mt-6">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Respuesta Facturapi</h2>
            </div>
            <pre class="p-5 text-xs overflow-x-auto bg-slate-950 text-slate-100">{{ json_encode($pago->facturapi_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </section>
    @endif
</div>
@endsection
