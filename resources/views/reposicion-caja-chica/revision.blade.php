@extends('layouts.admin')

@section('title', 'Revision caja chica')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
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

    <div class="overflow-hidden rounded-lg bg-white shadow-sm border border-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Gasto</th>
                    <th class="px-4 py-3 text-left">Categoria</th>
                    <th class="px-4 py-3 text-left">Destino</th>
                    <th class="px-4 py-3 text-left">Solicitante</th>
                    <th class="px-4 py-3 text-right">Registrado</th>
                    <th class="px-4 py-3 text-right">Autorizado</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
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
                        $puedeAutorizar = auth()->user()?->can('caja_chica.authorize');
                        $puedeRechazar = auth()->user()?->canAny(['caja_chica.reject', 'caja_chica.authorize']);
                    @endphp
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-900">RCC-G-{{ str_pad($gasto->id, 5, '0', STR_PAD_LEFT) }}</div>
                            <div class="text-xs text-slate-500">{{ $gasto->proveedor_nombre }}</div>
                            <div class="mt-1 max-w-md text-xs text-slate-600">{{ $gasto->concepto }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">{{ $gasto->categoria->nombre ?? '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $gasto->subcategoria->nombre ?? 'Sin categoria' }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-700">
                            @if($gasto->destino === 'obra')
                                {{ $gasto->obra->nombre ?? 'Obra no definida' }}
                            @else
                                {{ $gasto->almacen->nombre ?? 'Almacen no definido' }}
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $gasto->solicitadoPor->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right font-semibold">${{ number_format((float) $gasto->importe_registrado, 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-green-700">
                            @if($gasto->importe_autorizado !== null)
                                ${{ number_format((float) $gasto->importe_autorizado, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $estadoClasses }}">{{ str_replace('_', ' ', $gasto->estado_autorizacion) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-col items-end gap-2">
                                <a href="{{ route('reposicion-caja-chica.show', $gasto) }}" class="font-semibold text-blue-700 hover:underline">Ver</a>

                                @if($gasto->estado_autorizacion === 'pendiente' && ($puedeAutorizar || $puedeRechazar))
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if($puedeAutorizar)
                                            <form method="POST" action="{{ route('reposicion-caja-chica.autorizar', $gasto) }}">
                                                @csrf
                                                <button class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-green-700">
                                                    Autorizar
                                                </button>
                                            </form>
                                        @endif

                                        @if($puedeRechazar)
                                            <form method="POST" action="{{ route('reposicion-caja-chica.rechazar', $gasto) }}" class="flex gap-1">
                                                @csrf
                                                <input type="text" name="motivo_rechazo" placeholder="Motivo" required class="w-32 rounded-lg border-slate-300 text-xs">
                                                <button class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-700">
                                                    Rechazar
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    @if($puedeAutorizar)
                                        <form method="POST" action="{{ route('reposicion-caja-chica.autorizar-parcial', $gasto) }}" class="flex flex-wrap justify-end gap-1">
                                            @csrf
                                            <input type="number" step="0.01" min="0.01" max="{{ max((float) $gasto->importe_registrado - 0.01, 0.01) }}" name="importe_autorizado" placeholder="Importe parcial" required class="w-32 rounded-lg border-slate-300 text-xs text-right">
                                            <input type="text" name="observaciones_autorizacion" placeholder="Observacion" class="w-36 rounded-lg border-slate-300 text-xs">
                                            <button class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-blue-700">
                                                Parcial
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
