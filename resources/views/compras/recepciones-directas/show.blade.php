@extends('layouts.admin')

@section('title', 'Detalle de recepción directa')

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    @if(session('success'))<div class="rounded-lg bg-green-50 p-4 text-green-800">{{ session('success') }}</div>@endif
    @if(isset($errors) && $errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-bold text-[#0B265A]">Recepción {{ $recepcion->folio }}</h1><p class="text-sm text-slate-500">Entrega directa a obra · {{ ucfirst($recepcion->estado) }}</p></div>
        <div class="flex gap-3 text-sm">
            <a target="_blank" rel="noopener" class="rounded-lg border border-slate-300 px-4 py-2 text-blue-800" href="{{ route('ordenes_compra.recepciones_directas.imprimir', [$recepcion->orden_compra_id, $recepcion]) }}">Imprimir comprobante</a>
            <a class="rounded-lg border border-slate-300 px-4 py-2 text-blue-800" href="{{ route('ordenes_compra.edit', $recepcion->orden_compra_id) }}">Ver OC {{ $recepcion->ordenCompra?->folio }}</a>
            <a class="rounded-lg border border-slate-300 px-4 py-2 text-blue-800" href="{{ route('obras.edit', ['obra' => $recepcion->obra_id, 'tab' => 'ordenes-compra']) }}">Ver obra</a>
        </div>
    </div>
    @if($recepcion->estado !== 'aplicada')
        <p class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900">Esta recepción no está aplicada. Los importes siguientes son el registro de la entrega y no se suman al monto neto entregado de la obra.</p>
    @endif
    <div class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 text-sm md:grid-cols-3">
        <div><div class="text-xs text-slate-500">Obra destino</div><div class="font-semibold">{{ $recepcion->obra?->nombre ?? 'Obra #'.$recepcion->obra_id }}</div></div>
        <div><div class="text-xs text-slate-500">Almacén responsable</div><div>{{ $recepcion->almacen?->nombre ?? 'No disponible' }}</div></div>
        <div><div class="text-xs text-slate-500">Fecha de recepción</div><div>{{ $recepcion->fecha_recepcion?->format('d/m/Y') }}</div></div>
        <div><div class="text-xs text-slate-500">Receptor físico</div><div>{{ $recepcion->receptor_nombre }}</div></div>
        <div><div class="text-xs text-slate-500">Confirmó Compras</div><div>{{ $recepcion->confirmadoPor?->name ?? 'Sin registro' }}</div><div class="text-xs text-slate-500">{{ $recepcion->confirmado_at?->format('d/m/Y H:i') }}</div></div>
        <div><div class="text-xs text-slate-500">Remisión</div><div>{{ $recepcion->remision ?: 'Sin número de remisión' }}</div></div>
        <div><div class="text-xs text-slate-500">Entrada documental</div><div>{{ $recepcion->entrada?->folio ?? 'No disponible' }} · {{ $recepcion->entrada?->estado ?? 'Sin documento' }}</div></div>
        <div><div class="text-xs text-slate-500">Salida a obra</div><div>{{ $recepcion->salida?->folio ?? 'No disponible' }} · {{ $recepcion->salida?->estado ?? 'Sin documento' }}</div></div>
        <div><div class="text-xs text-slate-500">Importe registrado de esta entrega</div><div class="font-semibold">${{ number_format((float) $recepcion->total_mxn, 2) }} MXN</div><div class="text-xs text-slate-500">Compra: {{ number_format((float) $recepcion->total_oc, 2) }} {{ $recepcion->moneda }} · TC {{ number_format((float) $recepcion->tipo_cambio, 8) }}</div></div>
        <div class="md:col-span-3"><div class="text-xs text-slate-500">Observaciones</div><p class="whitespace-pre-line">{{ $recepcion->observaciones ?: 'Sin observaciones' }}</p></div>
    </div>
    @include('compras.recepciones-directas.partials.detalle')
    @if($recepcion->estado === 'revertida')
        <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm"><strong>Revertida:</strong> {{ $recepcion->revertido_at?->format('d/m/Y H:i') }} · {{ $recepcion->revertidoPor?->name ?? 'Usuario no disponible' }}<p>{{ $recepcion->motivo_reversion }}</p></section>
    @endif
    @if(auth()->user()?->can(\App\Models\CompraRecepcionDirecta::PERMISO_REVERTIR))
        @if($recepcion->estado === 'aplicada')
        <form method="POST" action="{{ route('ordenes_compra.recepciones_directas.revertir', [$recepcion->orden_compra_id, $recepcion]) }}" class="rounded-xl border border-red-200 bg-white p-4" onsubmit="return confirm('¿Revertir toda la recepción? Esta acción registra movimientos compensatorios.');">
            @csrf <label for="motivo" class="block font-semibold text-red-800">Motivo de reversión</label>
            <textarea id="motivo" name="motivo" required maxlength="2000" class="mt-2 w-full rounded border-slate-300">{{ old('motivo') }}</textarea>
            <button class="mt-2 rounded bg-red-700 px-4 py-2 text-white">Revertir recepción completa</button>
        </form>
        @endif
    @endif
    <section class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-semibold">Evidencias de entrega</h2>
        <ul class="mt-2 space-y-2 text-sm">
        @forelse($recepcion->archivos as $archivo)
            <li><a class="text-blue-800 underline" href="{{ route('ordenes_compra.recepciones_directas.evidencia', [$recepcion->orden_compra_id, $recepcion, $archivo]) }}">{{ $archivo->nombre_original }}</a> · {{ number_format($archivo->size_bytes / 1024, 1) }} KB</li>
        @empty <li class="text-slate-500">Sin evidencias disponibles.</li> @endforelse
        </ul>
    </section>
</div>
@endsection
