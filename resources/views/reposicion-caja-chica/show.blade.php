@extends('layouts.admin')

@section('title', 'Detalle de gasto')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">RCC-G-{{ str_pad($gasto->id, 5, '0', STR_PAD_LEFT) }}</h1>
            <p class="text-sm text-slate-500">Detalle del gasto y autorizacion individual.</p>
        </div>
        <a href="{{ route('reposicion-caja-chica.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Volver</a>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200 md:col-span-2">
            <h2 class="mb-4 text-lg font-bold text-slate-900">Informacion registrada</h2>
            <dl class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Tipo de comprobacion</dt><dd class="font-semibold text-slate-900">{{ $gasto->categoria->nombre ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Categoria</dt><dd>{{ $gasto->subcategoria->nombre ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Fecha</dt><dd>{{ optional($gasto->fecha_gasto)->format('d/m/Y') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Forma de pago</dt><dd>{{ $gasto->forma_pago ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Proveedor / beneficiario</dt><dd>{{ $gasto->proveedor_nombre }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">RFC</dt><dd>{{ $gasto->proveedor_rfc ?? '-' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-xs font-semibold uppercase text-slate-500">Concepto</dt><dd>{{ $gasto->concepto }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Destino</dt><dd>{{ $gasto->destino === 'obra' ? 'Obra' : 'Almacen' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Obra / almacen</dt><dd>{{ $gasto->obra->nombre ?? $gasto->almacen->nombre ?? '-' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-xs font-semibold uppercase text-slate-500">Motivo sin factura</dt><dd>{{ $gasto->motivo_sin_factura ?? '-' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-xs font-semibold uppercase text-slate-500">Observaciones</dt><dd>{{ $gasto->observaciones ?? '-' }}</dd></div>
            </dl>
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <h2 class="mb-4 text-lg font-bold text-slate-900">Autorizacion</h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Estado</dt><dd class="font-semibold text-slate-900">{{ str_replace('_', ' ', $gasto->estado_autorizacion) }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Importe registrado</dt><dd class="text-lg font-bold text-[#0B265A]">${{ number_format((float) $gasto->importe_registrado, 2) }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Importe autorizado</dt><dd class="text-lg font-bold text-green-700">${{ number_format((float) ($gasto->importe_autorizado ?? 0), 2) }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Resuelto por</dt><dd>{{ $gasto->resueltoPor->name ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Fecha resolucion</dt><dd>{{ optional($gasto->resuelto_at)->format('d/m/Y H:i') ?? '-' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Motivo rechazo</dt><dd>{{ $gasto->motivo_rechazo ?? '-' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
        <h2 class="mb-4 text-lg font-bold text-slate-900">Evidencias</h2>
        @forelse($gasto->archivos as $archivo)
            <a href="{{ $archivo->url }}" target="_blank" class="mb-2 block rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-slate-50">
                {{ $archivo->nombre_original ?? basename($archivo->path) }}
            </a>
        @empty
            <p class="text-sm text-slate-500">No hay evidencias cargadas.</p>
        @endforelse
    </div>
</div>
@endsection

