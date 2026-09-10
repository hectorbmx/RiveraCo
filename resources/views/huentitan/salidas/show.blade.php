@extends('layouts.admin')

@section('title', 'Salida - HUENTITAN')

@section('content')
@php
    $faltantesActuales = $faltantesActuales ?? collect();
    $estadoPartidas = $estadoPartidas ?? collect();
    $estadoOperativo = $estadoOperativo ?? ($salida->isBorrador() ? ($faltantesActuales->isEmpty() ? 'listo_para_aplicar' : 'con_faltantes') : $salida->estado);
@endphp
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <a href="{{ route('huentitan.index') }}" class="hover:underline">HUENTITAN</a>
                <span>/</span>
                <a href="{{ route('huentitan.salidas.index') }}" class="hover:underline">Salidas</a>
            </div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Salida {{ $salida->folio }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $almacen->nombre }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('huentitan.salidas.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Regresar</a>
            <a href="{{ route('huentitan.salidas.print', $salida) }}" target="_blank" class="inline-flex items-center justify-center px-4 py-2 rounded-md border border-gray-300 text-sm font-medium hover:bg-gray-50">Imprimir comprobante</a>
            @if($salida->isBorrador() && $estadoOperativo === 'listo_para_aplicar')
                @can('huentitan.salidas.apply')
                <form method="POST" action="{{ route('huentitan.salidas.aplicar', $salida) }}" onsubmit="return confirm('¿Confirmas que deseas aplicar esta salida? Se descontará el inventario y se registrará el movimiento en kardex.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-green-600 text-white text-sm font-medium hover:bg-green-700">Aplicar al inventario</button>
                </form>
                @endcan
            @endif
            @can('huentitan.salidas.create')
            <a href="{{ route('huentitan.salidas.create') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Nueva salida</a>
            @endcan
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">{{ session('error') }}</div>
    @endif

    @if($salida->isBorrador() && $estadoOperativo === 'listo_para_aplicar')
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900 shadow-sm">
            <div class="font-semibold">Salida lista para aplicar.</div>
            <p class="mt-1">El stock actual ya cubre todas las partidas. Al aplicar se descontara inventario y se enviara el movimiento al kardex.</p>
        </div>
    @endif

    @if($faltantesActuales->isNotEmpty())
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-sm">
            <div class="font-semibold">Esta salida tiene material faltante.</div>
            <p class="mt-1">Genera una orden de compra HUENTITAN para estos productos, aplica la entrada y despues vuelve a abrir esta salida para revalidar el stock actual.</p>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach($faltantesActuales as $faltante)
                    <div class="rounded-md border border-amber-200 bg-white px-3 py-2">
                        <div class="font-semibold text-gray-900">{{ $faltante['detalle']->producto?->sku }} - {{ $faltante['detalle']->producto?->nombre ?? $faltante['detalle']->descripcion }}</div>
                        <div class="text-xs text-gray-600">Disponible actual: {{ number_format($faltante['disponible'], 3) }} {{ $faltante['detalle']->unidad }} · Faltante actual: {{ number_format($faltante['faltante'], 3) }} {{ $faltante['detalle']->unidad }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white border rounded-lg overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b bg-gray-50 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900">Datos de la salida</h2>
            <div class="flex flex-wrap items-center gap-2">
                @if($salida->isBorrador())
                    @if($estadoOperativo === 'listo_para_aplicar')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Lista para aplicar</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Con faltantes</span>
                    @endif
                @endif

                @if($salida->estado === 'aplicada')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aplicada</span>
                @elseif($salida->estado === 'cancelada')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Cancelada</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">Borrador</span>
                @endif
            </div>
        </div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 text-sm">
            <div>
                <div class="text-xs text-gray-500">Destino</div>
                <div class="mt-1 font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $salida->tipo_destino)) }}</div>
            </div>
            <div class="lg:col-span-2">
                <div class="text-xs text-gray-500">Obra</div>
                <div class="mt-1 font-semibold text-gray-900">{{ $salida->obra ? trim(($salida->obra->clave_obra ? $salida->obra->clave_obra . ' - ' : '') . $salida->obra->nombre) : '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Fecha</div>
                <div class="mt-1 font-semibold text-gray-900">{{ $salida->fecha ? $salida->fecha->format('d/m/Y H:i') : '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Usuario creador</div>
                <div class="mt-1 font-semibold text-gray-900">{{ $salida->usuario?->name ?? 'Sistema' }}</div>
            </div>
        </div>
        @if($salida->observaciones)
            <div class="px-4 pb-4 text-sm text-gray-700">
                <div class="text-xs text-gray-500">Observaciones</div>
                <div class="mt-1">{{ $salida->observaciones }}</div>
            </div>
        @endif
    </div>

    <div class="bg-white border rounded-lg overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Partidas</h2>
                <p class="text-xs text-gray-500">{{ $salida->detalles->count() }} productos capturados</p>
            </div>
            <div class="text-sm font-semibold text-gray-900">${{ number_format($salida->total_importe, 2) }}</div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Producto</th>
                        <th class="px-4 py-3 text-left font-semibold">Unidad</th>
                        <th class="px-4 py-3 text-right font-semibold">Cantidad</th>
                        <th class="px-4 py-3 text-right font-semibold">Disp. al crear</th>
                        <th class="px-4 py-3 text-right font-semibold">Disp. actual</th>
                        <th class="px-4 py-3 text-right font-semibold">Faltante actual</th>
                        <th class="px-4 py-3 text-center font-semibold">Estado</th>
                        <th class="px-4 py-3 text-left font-semibold">Compra / recepcion</th>
                        <th class="px-4 py-3 text-right font-semibold">Costo prom.</th>
                        <th class="px-4 py-3 text-right font-semibold">Importe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-800">
                    @forelse($salida->detalles as $detalle)
                        @php
                            $estadoPartida = $estadoPartidas->get($detalle->id);
                            $disponibleActual = $estadoPartida['disponible'] ?? max(0, (float) $detalle->cantidad_salida);
                            $faltanteActual = $estadoPartida['faltante'] ?? 0;
                            $compra = $estadoPartida['compra'] ?? ['estado' => 'sin_compra', 'ordenes' => collect(), 'entradas_borrador' => collect(), 'entradas_aplicadas' => collect(), 'cantidad_necesaria_compra' => 0, 'cantidad_comprada' => 0, 'cantidad_recibida_aplicada' => 0, 'cantidad_pendiente_compra' => 0, 'cantidad_pendiente_recibir' => 0];
                            $ordenesCompra = collect($compra['ordenes'] ?? []);
                            $entradasBorrador = collect($compra['entradas_borrador'] ?? []);
                            $entradasAplicadas = collect($compra['entradas_aplicadas'] ?? []);
                        @endphp
                        <tr class="{{ $faltanteActual > 0 ? 'bg-amber-50' : '' }}">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">{{ $detalle->producto?->nombre ?? $detalle->descripcion }}</div>
                                <div class="text-xs text-gray-500">{{ $detalle->producto?->sku ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $detalle->unidad ?: '-' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold">{{ number_format((float) $detalle->cantidad_salida, 3) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-gray-500">{{ number_format((float) $detalle->stock_disponible_snapshot, 3) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-gray-700">{{ number_format((float) $disponibleActual, 3) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap {{ $faltanteActual > 0 ? 'font-semibold text-amber-700' : 'text-gray-500' }}">{{ number_format((float) $faltanteActual, 3) }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($faltanteActual > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Comprar</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Completo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                @if(($compra['estado'] ?? 'sin_compra') === 'entrada_completa')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-green-100 text-green-800">Entrada completa</span>
                                    <div class="mt-1">Recibido: {{ number_format((float) ($compra['cantidad_recibida_aplicada'] ?? 0), 3) }} / {{ number_format((float) ($compra['cantidad_necesaria_compra'] ?? 0), 3) }} {{ $detalle->unidad }}</div>
                                    @if($entradasAplicadas->isNotEmpty())
                                        <div class="mt-1 text-gray-500">{{ $entradasAplicadas->pluck('folio')->filter()->join(', ') }}</div>
                                    @endif
                                @elseif(($compra['estado'] ?? 'sin_compra') === 'entrada_parcial')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-blue-100 text-blue-800">Entrada parcial</span>
                                    <div class="mt-1">Recibido: {{ number_format((float) ($compra['cantidad_recibida_aplicada'] ?? 0), 3) }} {{ $detalle->unidad }}</div>
                                    <div class="text-gray-500">Pendiente recibir: {{ number_format((float) ($compra['cantidad_pendiente_recibir'] ?? 0), 3) }} {{ $detalle->unidad }}</div>
                                    @if($entradasAplicadas->isNotEmpty())
                                        <div class="mt-1 text-gray-500">{{ $entradasAplicadas->pluck('folio')->filter()->join(', ') }}</div>
                                    @endif
                                @elseif(($compra['estado'] ?? 'sin_compra') === 'entrada_borrador')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-blue-100 text-blue-800">Entrada borrador</span>
                                    @if($entradasBorrador->isNotEmpty())
                                        <div class="mt-1 text-gray-500">{{ $entradasBorrador->pluck('folio')->filter()->join(', ') }}</div>
                                    @endif
                                @elseif(in_array(($compra['estado'] ?? 'sin_compra'), ['oc_completa', 'oc_generada'], true))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-indigo-100 text-indigo-800">OC completa</span>
                                    <div class="mt-1">Comprado: {{ number_format((float) ($compra['cantidad_comprada'] ?? 0), 3) }} {{ $detalle->unidad }}</div>
                                    <div class="text-gray-500">Pendiente recibir: {{ number_format((float) ($compra['cantidad_pendiente_recibir'] ?? 0), 3) }} {{ $detalle->unidad }}</div>
                                    @if($ordenesCompra->isNotEmpty())
                                        <div class="mt-1 text-gray-500">{{ $ordenesCompra->pluck('folio')->filter()->join(', ') }}</div>
                                    @endif
                                @elseif(($compra['estado'] ?? 'sin_compra') === 'oc_parcial')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-800">OC parcial</span>
                                    <div class="mt-1">Comprado: {{ number_format((float) ($compra['cantidad_comprada'] ?? 0), 3) }} {{ $detalle->unidad }}</div>
                                    <div class="text-gray-500">Pendiente comprar: {{ number_format((float) ($compra['cantidad_pendiente_compra'] ?? 0), 3) }} {{ $detalle->unidad }}</div>
                                    @if($ordenesCompra->isNotEmpty())
                                        <div class="mt-1 text-gray-500">{{ $ordenesCompra->pluck('folio')->filter()->join(', ') }}</div>
                                    @endif
                                @elseif($detalle->requiere_compra)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-800">Sin OC</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">${{ number_format((float) $detalle->costo_unitario, 4) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold">${{ number_format((float) $detalle->importe, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center text-sm text-gray-500">Sin partidas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($salida->isBorrador())
            <div class="px-4 py-3 border-t {{ $estadoOperativo === 'listo_para_aplicar' ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-800' }} text-sm">
                @if($estadoOperativo === 'listo_para_aplicar')
                    Esta salida esta en borrador y ya tiene stock suficiente. Ya puedes aplicar al inventario.
                @else
                    Esta salida esta en borrador, pero aun tiene faltantes. Primero hay que surtirlos con OC y entrada.
                @endif
            </div>
        @endif
    </div>
</div>
@endsection




