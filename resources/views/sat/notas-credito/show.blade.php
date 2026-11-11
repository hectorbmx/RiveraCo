@extends('layouts.admin')

@section('title', 'Detalle de nota de crédito')

@section('content')
<div x-data="{ envioAbierto: false, cancelarAbierto: false, motivo: '02' }" @keydown.escape.window="envioAbierto = false; cancelarAbierto = false" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Nota de crédito {{ $factura->serie }}{{ $factura->serie && $factura->folio ? '-' : '' }}{{ $factura->folio }}</h1>
            <p class="mt-1 text-sm text-slate-500">UUID: {{ $factura->uuid ?: 'Sin UUID' }}</p>
        </div>
        <a href="{{ route('sat.notas-credito.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver</a>
    </div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-semibold text-slate-900">Datos generales</h2><span class="rounded-full px-3 py-1 text-xs font-medium {{ $factura->estado === 'timbrada' ? 'bg-emerald-100 text-emerald-800' : ($factura->estado === 'cancelada' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">{{ $factura->estado === 'cancelacion_solicitada' ? 'Cancelación solicitada' : ucfirst($factura->estado) }}</span></div>
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                    @foreach([
                        'Emisor' => $factura->empresa?->nombre, 'Receptor' => $factura->receptor_nombre,
                        'RFC receptor' => $factura->receptor_rfc, 'Uso CFDI' => $factura->uso_cfdi,
                        'Método de pago' => $factura->metodo_pago, 'Forma de pago' => $factura->forma_pago,
                        'Obra' => $factura->obra?->nombre, 'Fecha de timbrado' => $factura->fecha_timbrado?->format('d/m/Y H:i'),
                        'Timbrado por' => $factura->timbradoPor?->name,
                    ] as $etiqueta => $valor)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><span class="block text-xs uppercase text-slate-500">{{ $etiqueta }}</span><span class="mt-1 block font-medium text-slate-800">{{ $valor ?: '—' }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <h2 class="border-b border-slate-200 px-6 py-4 text-lg font-semibold text-slate-900">Conceptos</h2>
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-[#0B265A] text-xs uppercase text-white"><tr><th class="px-5 py-3 text-left">Descripción</th><th class="px-5 py-3 text-right">Cantidad</th><th class="px-5 py-3 text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">@forelse($factura->conceptos as $concepto)
                        <tr><td class="px-5 py-3">{{ $concepto->descripcion }}<div class="text-xs text-slate-500">SAT: {{ $concepto->clave_producto_servicio }}</div></td><td class="px-5 py-3 text-right">{{ number_format($concepto->cantidad, 2) }}</td><td class="px-5 py-3 text-right">${{ number_format($concepto->total, 2) }}</td></tr>
                    @empty <tr><td colspan="3" class="px-5 py-5 text-center text-slate-500">Sin conceptos registrados.</td></tr> @endforelse</tbody>
                </table></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold text-slate-900">CFDI relacionados</h2>
                @forelse($relaciones as $relacion)
                    <p class="break-all text-sm text-slate-700">Relación {{ $relacion->tipo_relacion }} · {{ $relacion->uuid_relacionado }}</p>
                @empty <p class="text-sm text-slate-500">Sin relación registrada.</p> @endforelse
            </div>
        </div>
        <div class="space-y-6">
            <div class="rounded-2xl bg-slate-950 p-6 text-white shadow-sm">
                <div class="mb-5 flex justify-between"><h2 class="text-lg font-semibold">Resumen</h2><span class="text-sm text-slate-400">{{ $factura->moneda }}</span></div>
                <div class="space-y-4 text-sm"><div class="flex justify-between"><span>Subtotal</span><strong>${{ number_format((float) $factura->subtotal, 2) }}</strong></div><div class="flex justify-between"><span>IVA</span><strong>${{ number_format((float) $factura->iva, 2) }}</strong></div><div class="flex justify-between border-t border-white/10 pt-4"><span>Total</span><strong class="text-xl text-amber-300">${{ number_format((float) $factura->total, 2) }}</strong></div></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Archivos y acciones</h2>
                <div class="space-y-3">
                    <a href="{{ route('sat.notas-credito.archivo', [$factura, 'xml']) }}" class="flex items-center justify-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-blue-700 hover:bg-blue-50"><span aria-hidden="true">&lt;/&gt;</span> Descargar XML</a>
                    <a href="{{ route('sat.notas-credito.archivo', [$factura, 'pdf']) }}" class="flex items-center justify-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-red-600 hover:bg-red-50"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h8l4 4v14H6z M14 3v5h4 M9 13h6 M9 17h6"/></svg> Descargar PDF</a>
                    @if($factura->estado === 'timbrada')
                        <button type="button" @click="envioAbierto = true" class="flex w-full items-center justify-center gap-3 rounded-xl border border-indigo-200 px-4 py-3 text-sm font-bold text-indigo-700 hover:bg-indigo-50"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11 21 3l-7 18-3-7-8-3z M11 14l10-11"/></svg>{{ $factura->email_enviado_at ? 'Reenviar nota de crédito' : 'Enviar nota de crédito' }}</button>
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-3 rounded-xl border border-emerald-200 px-4 py-3 text-sm font-bold text-emerald-700 hover:bg-emerald-50"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.7 14.9L2 22l5.2-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20zm4.4-5.9c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.6.2s-.6.8-.8.9-.3.1-.5 0c-1.3-.6-2.2-1.3-2.9-2.6-.2-.3.2-.3.6-1.1.1-.2 0-.4 0-.5l-.7-1.7c-.2-.4-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.2-.9.9-.9 2.1s.9 2.3 1 2.5c.1.2 1.7 2.6 4.1 3.6 1.5.6 2.1.7 2.9.6.5-.1 1.4-.6 1.6-1.2.2-.6.2-1.1.2-1.2-.1-.2-.3-.3-.5-.4z"/></svg>Enviar por WhatsApp</a>
                    @endif
                    @can('sat.cfdi_autoriza.access')
                        @if(in_array($factura->estado, ['timbrada', 'cancelacion_solicitada']))
                            <form method="POST" action="{{ route('sat.notas-credito.cancelacion.consultar', $factura) }}">@csrf<button type="submit" class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700 hover:bg-amber-100">↻ Actualizar estatus SAT</button></form>
                        @endif
                        @if($factura->estado === 'timbrada')
                            <button type="button" @click="cancelarAbierto = true" class="w-full rounded-xl border border-red-200 px-4 py-3 text-sm font-bold text-red-700 hover:bg-red-50">✕ Cancelar nota de crédito</button>
                        @endif
                    @endcan
                    @if($factura->estado === 'cancelada')
                        <a href="{{ route('sat.facturacion.acuse', [$factura, 'pdf']) }}" class="block rounded-xl border border-red-200 px-4 py-3 text-center text-sm font-semibold text-red-700">Descargar acuse PDF</a>
                        <a href="{{ route('sat.facturacion.acuse', [$factura, 'xml']) }}" class="block rounded-xl border border-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-700">Descargar acuse XML</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div x-cloak x-show="cancelarAbierto" @click.self="cancelarAbierto = false" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="nota-cancelar-titulo">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <h2 id="nota-cancelar-titulo" class="text-lg font-semibold text-slate-900">Cancelar nota de crédito</h2>
            <p class="mt-1 text-sm text-slate-500">Selecciona el motivo de cancelación SAT.</p>
            <form method="POST" action="{{ route('sat.notas-credito.cancelar', $factura) }}" class="mt-5 space-y-4" onsubmit="return confirm('¿Enviar la solicitud de cancelación de esta nota de crédito?');">
                @csrf
                <div><label for="nota-motivo-cancelacion" class="mb-1 block text-sm font-medium text-slate-700">Motivo</label><select id="nota-motivo-cancelacion" name="motivo_cancelacion" x-model="motivo" class="w-full rounded-lg border-slate-300 text-sm"><option value="01">01 · Comprobante con errores con relación</option><option value="02">02 · Comprobante con errores sin relación</option><option value="03">03 · Operación no realizada</option><option value="04">04 · Operación nominativa relacionada en una factura global</option></select></div>
                <div x-show="motivo === '01'"><label for="nota-sustitucion" class="mb-1 block text-sm font-medium text-slate-700">UUID de la nota sustituta</label><input id="nota-sustitucion" name="sustitucion_uuid" type="text" :required="motivo === '01'" maxlength="36" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div class="flex justify-end gap-2"><button type="button" @click="cancelarAbierto = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cerrar</button><button type="submit" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Solicitar cancelación</button></div>
            </form>
        </div>
    </div>
    <div x-cloak x-show="envioAbierto" @click.self="envioAbierto = false" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="nota-envio-titulo">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <h2 id="nota-envio-titulo" class="text-lg font-semibold text-slate-900">Enviar nota de crédito</h2>
            <p class="mt-1 text-sm text-slate-500">Se enviarán el XML y el PDF adjuntos.</p>
            @if($errors->any())<div class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('sat.notas-credito.enviar', $factura) }}" class="mt-5 space-y-4">
                @csrf
                <div><label for="nota-email" class="mb-1 block text-sm font-medium text-slate-700">Correo destino</label><input id="nota-email" name="email_destino" type="email" required value="{{ old('email_destino', $factura->email_destino ?: $factura->cliente?->email) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label for="nota-email-adicional" class="mb-1 block text-sm font-medium text-slate-700">Correo adicional (opcional)</label><input id="nota-email-adicional" name="email_adicional" type="email" value="{{ old('email_adicional') }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div class="flex justify-end gap-2"><button type="button" @click="envioAbierto = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancelar</button><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Enviar</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
