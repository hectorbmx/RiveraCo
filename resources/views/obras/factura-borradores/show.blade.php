@extends('layouts.admin')

@section('title', 'Borrador de factura BF-' . str_pad((string) $borrador->id, 5, '0', STR_PAD_LEFT))

@section('content')
@php
    $estatusLabels = \App\Models\ObraFacturaBorrador::estatusLabels();
    $estatusLabel = $estatusLabels[$borrador->estatus] ?? ucfirst((string) $borrador->estatus);
    $badgeClass = match ($borrador->estatus) {
        \App\Models\ObraFacturaBorrador::ESTATUS_AUTORIZADO => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        \App\Models\ObraFacturaBorrador::ESTATUS_RECHAZADO => 'bg-red-50 text-red-700 border-red-200',
        \App\Models\ObraFacturaBorrador::ESTATUS_FACTURADO => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        \App\Models\ObraFacturaBorrador::ESTATUS_CANCELADO => 'bg-slate-100 text-slate-600 border-slate-200',
        default => 'bg-amber-50 text-amber-700 border-amber-200',
    };
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">
                    Borrador de factura BF-{{ str_pad((string) $borrador->id, 5, '0', STR_PAD_LEFT) }}
                </h1>
                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                    {{ $estatusLabel }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                {{ $obra->nombre ?: 'Obra sin nombre' }} @if($obra->clave_obra) · {{ $obra->clave_obra }} @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'facturacion']) }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Ver obra
            </a>

            @can('obra_factura_borradores.print.access')
                <a href="{{ route('obras.factura-borradores.print', [$obra, $borrador]) }}"
                   target="_blank"
                   class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    Imprimir
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-5">Datos principales</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Cliente</p>
                        <p class="mt-1 font-semibold text-slate-900">
                            {{ $borrador->cliente?->razon_social ?: $borrador->cliente?->nombre_comercial ?: '-' }}
                        </p>
                        <p class="text-sm text-slate-500">{{ $borrador->cliente?->rfc ?: 'RFC no capturado' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Fecha</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ optional($borrador->fecha)->format('d/m/Y') ?: '-' }}</p>
                        <p class="text-sm text-slate-500">Creado {{ optional($borrador->created_at)->format('d/m/Y H:i') }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Solicitado por</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $borrador->creador?->name ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Autorizacion</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $borrador->autorizador?->name ?: 'Pendiente' }}</p>
                        <p class="text-sm text-slate-500">{{ optional($borrador->autorizado_at)->format('d/m/Y H:i') ?: '' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-5">Datos fiscales</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Regimen fiscal</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $regimenesFiscales[$borrador->regimen_fiscal] ?? ($borrador->regimen_fiscal ?: '-') }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Uso CFDI</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $usosCfdi[$borrador->uso_cfdi] ?? $borrador->uso_cfdi }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Metodo de pago</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $metodosPagoCfdi[$borrador->metodo_pago] ?? $borrador->metodo_pago }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Forma de pago</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $formasPagoCfdi[$borrador->forma_pago] ?? ($borrador->forma_pago ?: '-') }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Concepto</h2>
                    <span class="text-sm text-slate-500">1 concepto</span>
                </div>

                @can('obra_factura_borradores.edit.access')
                    @unless(in_array($borrador->estatus, [\App\Models\ObraFacturaBorrador::ESTATUS_FACTURADO, \App\Models\ObraFacturaBorrador::ESTATUS_CANCELADO], true))
                        <form method="POST" action="{{ route('obras.factura-borradores.update', [$obra, $borrador]) }}" class="px-6 py-5 border-b border-slate-100 bg-slate-50/70 space-y-3">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Concepto modificado</label>
                                <textarea name="concepto_descripcion"
                                          rows="4"
                                          required
                                          maxlength="1000"
                                          class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('concepto_descripcion', $borrador->concepto_descripcion) }}</textarea>
                                <p class="mt-1 text-xs text-slate-500">El concepto SAT se conserva como base; esta descripcion editable se usara para imprimir y facturar.</p>
                                @error('concepto_descripcion')
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                    Guardar concepto
                                </button>
                            </div>
                        </form>
                    @endunless
                @endcan
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-6 py-3 text-left">Concepto SAT</th>
                                <th class="px-6 py-3 text-left">Descripcion</th>
                                <th class="px-6 py-3 text-right">Cantidad</th>
                                <th class="px-6 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-6 py-4 align-top">
                                    <div class="font-semibold text-slate-900">{{ $borrador->conceptoSat?->codigo ?: $borrador->conceptoSat?->clave_producto_servicio ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $borrador->conceptoSat?->descripcion ?: '' }}</div>
                                </td>
                                <td class="px-6 py-4 align-top text-slate-700 whitespace-pre-line">{{ $borrador->concepto_descripcion }}</td>
                                <td class="px-6 py-4 align-top text-right tabular-nums">{{ number_format((float) $borrador->cantidad, 6) }}</td>
                                <td class="px-6 py-4 align-top text-right font-semibold tabular-nums">${{ number_format((float) $borrador->subtotal, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-5">Resumen</h2>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="font-semibold tabular-nums">${{ number_format((float) $borrador->subtotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500">IVA{{ $borrador->iva_tasa !== null ? ' (' . rtrim(rtrim(number_format((float) $borrador->iva_tasa * 100, 4), '0'), '.') . '%)' : '' }}</span>
                        <span class="font-semibold tabular-nums">${{ number_format((float) $borrador->iva, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500">Retenciones{{ $borrador->retencion_tipo && $borrador->retencion_tipo !== 'sin_retencion' ? ' - ' . (\App\Models\ObraFacturaBorrador::retencionTipoLabels()[$borrador->retencion_tipo] ?? $borrador->retencion_tipo) : '' }}</span>
                        <span class="font-semibold text-amber-700 tabular-nums">- ${{ number_format((float) $borrador->retenciones, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500">Descuentos</span>
                        <span class="font-semibold text-amber-700 tabular-nums">- ${{ number_format((float) $borrador->descuentos, 2) }}</span>
                    </div>
                    <div class="border-t border-slate-200 pt-4 flex items-center justify-between gap-4">
                        <span class="text-base font-semibold text-slate-900">Total</span>
                        <span class="text-xl font-bold text-slate-950 tabular-nums">${{ number_format((float) $borrador->total, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-5">Flujo del borrador</h2>

                <div class="space-y-5">
                    <div class="flex gap-3">
                        <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-700">
                            1
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Solicita</p>
                            <p class="font-semibold text-slate-900">{{ $borrador->creador?->name ?: '-' }}</p>
                            <p class="text-sm text-slate-500">{{ optional($borrador->created_at)->format('d/m/Y H:i') ?: '-' }}</p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $borrador->autorizador ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }} text-sm font-bold">
                            2
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Autoriza</p>
                            <p class="font-semibold text-slate-900">{{ $borrador->autorizador?->name ?: 'Pendiente' }}</p>
                            <p class="text-sm text-slate-500">{{ optional($borrador->autorizado_at)->format('d/m/Y H:i') ?: '-' }}</p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $borrador->sat_factura_id ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }} text-sm font-bold">
                            3
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Factura</p>
                            <p class="font-semibold text-slate-900">
                                @if($borrador->satFactura)
                                    Factura {{ $borrador->satFactura->uuid ?: '#' . $borrador->satFactura->id }}
                                @else
                                    Pendiente
                                @endif
                            </p>
                            <p class="text-sm text-slate-500">
                                @if($borrador->satFactura)
                                    {{ optional($borrador->satFactura->fecha_timbrado)->format('d/m/Y H:i') ?: optional($borrador->satFactura->created_at)->format('d/m/Y H:i') }}
                                @else
                                    Se completara al timbrar desde el borrador.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            @if($borrador->estatus === \App\Models\ObraFacturaBorrador::ESTATUS_RECHAZADO && $borrador->observaciones_revision)
                <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
                    <p class="font-semibold">Observaciones de rechazo</p>
                    <p class="mt-2 whitespace-pre-line">{{ $borrador->observaciones_revision }}</p>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Siguiente accion</h2>

                @if($borrador->estatus === \App\Models\ObraFacturaBorrador::ESTATUS_PENDIENTE_REVISION)
                    <div class="space-y-3">
                        @can('obra_factura_borradores.authorize.access')
                            <form method="POST" action="{{ route('obras.factura-borradores.autorizar', [$obra, $borrador]) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                                    Autorizar borrador
                                </button>
                            </form>
                        @endcan

                        @can('obra_factura_borradores.reject.access')
                            <form method="POST" action="{{ route('obras.factura-borradores.rechazar', [$obra, $borrador]) }}" class="space-y-3">
                                @csrf
                                <label class="block text-sm font-medium text-slate-700">Observaciones de rechazo</label>
                                <textarea name="observaciones_revision"
                                          rows="3"
                                          class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                          placeholder="Motivo u observaciones para el solicitante"></textarea>
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 hover:bg-red-100">
                                    Rechazar borrador
                                </button>
                            </form>
                        @endcan

                        @cannot('obra_factura_borradores.authorize.access')
                            @cannot('obra_factura_borradores.reject.access')
                                <p class="text-sm text-slate-500">Este borrador esta pendiente de revision administrativa.</p>
                            @endcannot
                        @endcannot
                    </div>
                @elseif($borrador->estatus === \App\Models\ObraFacturaBorrador::ESTATUS_AUTORIZADO)
                    @can('obra_factura_borradores.invoice.access')
                        <p class="text-sm text-slate-500 mb-4">
                            El borrador esta autorizado y listo para facturarse.
                        </p>
                        <a href="{{ route('sat.facturacion.create', ['borrador_id' => $borrador->id]) }}"
                           class="w-full inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                            Facturar
                        </a>
                    @else
                        <p class="text-sm text-slate-500">El borrador ya fue autorizado y esta listo para facturacion.</p>
                    @endcan
                @elseif($borrador->estatus === \App\Models\ObraFacturaBorrador::ESTATUS_RECHAZADO)
                    <p class="text-sm text-slate-500">El borrador fue rechazado. El solicitante recibio una notificacion para revisarlo.</p>
                @else
                    <p class="text-sm text-slate-500">No hay acciones pendientes para este borrador.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
