@extends('layouts.admin')

@section('title', 'Revision caja chica')

@section('content')
<div class="max-w-8xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Revision de caja chica</h1>
            <p class="text-sm text-slate-500">Autorizacion individual por gasto capturado.</p>
        </div>
        <a href="{{ route('reposicion-caja-chica.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Gastos</a>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">No se pudo completar la autorizacion:</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm border border-slate-200">
        <table class="min-w-full table-fixed text-sm">
            <colgroup>
                <col class="w-[24%]">
                <col class="w-[14%]">
                <col class="w-[16%]">
                <col class="w-[13%]">
                <col class="w-[9%]">
                <col class="w-[9%]">
                <col class="w-[8%]">
                <col class="w-[17%]">
            </colgroup>
            <thead class="bg-[#0B265A] text-xs uppercase text-white">
                <tr>
                    <th class="px-2 py-2 text-left">Gasto</th>
                    <th class="px-2 py-2 text-left">Categoria</th>
                    <th class="px-2 py-2 text-left">Destino</th>
                    <th class="px-2 py-2 text-left">Solicitante</th>
                    <th class="px-2 py-2 text-right">Registrado</th>
                    <th class="px-2 py-2 text-right">Autorizado</th>
                    <th class="px-2 py-2 text-center">Estado</th>
                    <th class="px-2 py-2 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($gastos as $gasto)
                    @php
                        $estadoClasses = match($gasto->estado_autorizacion) {
                            'pendiente' => 'bg-amber-100 text-amber-800',
                            'autorizado' => 'bg-green-100 text-green-800',
                            'autorizado_parcial' => 'bg-blue-100 text-blue-800',
                            'rechazado' => 'bg-red-100 text-red-800',
                            default => 'bg-slate-100 text-slate-700',
                        };
                        $puedeAutorizar = auth()->user()?->can('caja_chica.authorize.access');
                        $puedeRechazar = auth()->user()?->canAny(['caja_chica.reject.access', 'caja_chica.authorize.access']);
                    @endphp
                    <tr class="align-top">
                        <td class="px-2 py-2">
                            <div class="font-semibold text-slate-900">RCC-G-{{ str_pad($gasto->id, 5, '0', STR_PAD_LEFT) }}</div>
                            <div class="text-xs text-slate-500">{{ $gasto->proveedor_nombre }}</div>
                            <div class="mt-1 max-w-md text-xs text-slate-600">{{ $gasto->concepto }}</div>
                        </td>
                        <td class="px-2 py-2">
                            <div class="font-semibold text-slate-800">{{ $gasto->categoria->nombre ?? '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $gasto->subcategoria->nombre ?? 'Sin categoria' }}</div>
                        </td>
                        <td class="px-2 py-2 text-xs text-slate-700">
                            @if($gasto->destino === 'obra')
                                {{ $gasto->obra->nombre ?? 'Obra no definida' }}
                            @else
                                {{ $gasto->almacen->nombre ?? 'Almacen no definido' }}
                            @endif
                            @if($gasto->activo_operativo_label !== '-')
                                <div class="mt-1 font-semibold text-slate-500">{{ $gasto->activo_operativo_label }}</div>
                            @endif
                        </td>
                        <td class="px-2 py-2">{{ $gasto->solicitadoPor->name ?? '-' }}</td>
                        <td class="px-2 py-2 text-right font-semibold">${{ number_format((float) $gasto->importe_registrado, 2) }}</td>
                        <td class="px-2 py-2 text-right font-semibold text-green-700">
                            @if($gasto->importe_autorizado !== null)
                                ${{ number_format((float) $gasto->importe_autorizado, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-2 py-2 text-center">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $estadoClasses }}">{{ str_replace('_', ' ', $gasto->estado_autorizacion) }}</span>
                        </td>
                        <td class="px-2 py-2 text-right">
                            <div class="flex flex-col items-end gap-2">
                                <a href="{{ route('reposicion-caja-chica.show', $gasto) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-blue-700 hover:bg-blue-50" title="Ver detalle" aria-label="Ver detalle">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>

                                @if($gasto->estado_autorizacion === 'pendiente' && ($puedeAutorizar || $puedeRechazar))
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        @if($puedeAutorizar)
                                            <form method="POST" action="{{ route('reposicion-caja-chica.autorizar', $gasto) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-600 text-white hover:bg-green-700" title="Autorizar gasto completo" aria-label="Autorizar gasto completo">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                                                </button>
                                            </form>
                                        @endif

                                        @if($puedeRechazar)
                                            <form method="POST" action="{{ route('reposicion-caja-chica.rechazar', $gasto) }}" class="flex items-center gap-1">
                                                @csrf
                                                <input type="text" name="motivo_rechazo" placeholder="Motivo" required class="w-24 rounded-lg border-slate-300 px-2 py-1 text-xs">
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-600 text-white hover:bg-red-700" title="Rechazar gasto" aria-label="Rechazar gasto">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    @if($puedeAutorizar)
                                        <form method="POST" action="{{ route('reposicion-caja-chica.autorizar-parcial', $gasto) }}" class="flex flex-wrap items-center justify-end gap-1">
                                            @csrf
                                            <input type="number" step="0.01" min="0.01" max="{{ max((float) $gasto->importe_registrado - 0.01, 0.01) }}" name="importe_autorizado" placeholder="Importe parcial" required class="w-24 rounded-lg border-slate-300 px-2 py-1 text-xs text-right">
                                            <input type="text" name="observaciones_autorizacion" placeholder="Observacion" class="w-24 rounded-lg border-slate-300 px-2 py-1 text-xs">
                                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-white hover:bg-blue-700" title="Autorizar importe parcial" aria-label="Autorizar importe parcial">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v18M3 12h18"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">No hay gastos enviados a revision.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $gastos->links() }}</div>
</div>
@endsection

