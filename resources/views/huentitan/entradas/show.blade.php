@extends('layouts.admin')

@section('title', 'Entrada ' . $entrada->folio . ' - HUENTITAN')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    {{-- Migas de pan --}}
    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
        <a href="{{ route('huentitan.index') }}" class="hover:underline">HUENTITAN</a>
        <span>/</span>
        <a href="{{ route('huentitan.entradas.index') }}" class="hover:underline">Entradas</a>
        <span>/</span>
        <span class="text-gray-900">{{ $entrada->folio }}</span>
    </div>

    {{-- Encabezado con estado y acciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900">{{ $entrada->folio }}</h1>
                @if($entrada->estado === 'aplicada')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                        Aplicada al Inventario
                    </span>
                @elseif($entrada->estado === 'cancelada')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                        Cancelada
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        Borrador / Pendiente de Aplicar
                    </span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-600">
                {{ $almacen->nombre }} &bull; Fecha: {{ $entrada->fecha ? $entrada->fecha->format('d/m/Y H:i') : '-' }} &bull; Creada por: {{ $entrada->usuario?->name ?? 'Sistema' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('huentitan.entradas.index') }}" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                &larr; Volver al listado
            </a>
            @if($entrada->isBorrador())
                @can('huentitan.entradas.apply')
                <form method="POST" action="{{ route('huentitan.entradas.aplicar', $entrada) }}" onsubmit="return confirm('¿Confirmas que deseas aplicar esta entrada al inventario? Se incrementará el stock y se registrará en el kardex.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-green-600 text-white text-sm font-semibold hover:bg-green-700 shadow-sm transition">
                        Aplicar al Inventario
                    </button>
                </form>
                @endcan
            @elseif($entrada->isAplicada())
                @can('huentitan.entradas.apply')
                <button type="button" onclick="document.getElementById('modal-cancelar').classList.remove('hidden')" class="inline-flex items-center justify-center px-4 py-2 rounded-md border border-red-300 bg-red-50 text-red-700 text-sm font-medium hover:bg-red-100 transition">
                    Cancelar Entrada
                </button>
                @endcan
            @endif
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">{{ session('error') }}</div>
    @endif

    {{-- Ficha Informativa --}}
    <div class="bg-white border rounded-lg p-6 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
            <div>
                <span class="text-xs uppercase font-semibold text-gray-500 tracking-wider">Orden de Compra</span>
                <div class="mt-1 font-medium text-gray-900">
                    @if($entrada->ordenCompra)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-50 text-blue-700">
                            {{ $entrada->ordenCompra->folio }}
                        </span>
                    @else
                        <span class="text-gray-400">Sin orden de compra</span>
                    @endif
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                    Fecha OC: {{ $entrada->ordenCompra?->fecha ? $entrada->ordenCompra->fecha->format('d/m/Y') : 'N/A' }}
                </div>
            </div>

            <div>
                <span class="text-xs uppercase font-semibold text-gray-500 tracking-wider">Proveedor</span>
                <div class="mt-1 font-medium text-gray-900">
                    {{ $entrada->ordenCompra?->proveedor?->nombre ?? 'N/A' }}
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                    {{ $entrada->ordenCompra?->proveedor?->rfc ? 'RFC: ' . $entrada->ordenCompra->proveedor->rfc : '' }}
                </div>
            </div>

            <div>
                <span class="text-xs uppercase font-semibold text-gray-500 tracking-wider">Almacén de Entrada</span>
                <div class="mt-1 font-medium text-gray-900">{{ $entrada->almacen?->nombre }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Código: {{ $entrada->almacen?->codigo }}</div>
            </div>

            <div>
                <span class="text-xs uppercase font-semibold text-gray-500 tracking-wider">Auditoría</span>
                <div class="mt-1 text-xs text-gray-700">
                    <div>Registrado: <span class="font-medium text-gray-900">{{ $entrada->usuario?->name ?? 'Sistema' }}</span></div>
                    @if($entrada->isAplicada())
                        <div class="mt-1 text-green-700">
                            Aplicado por: <span class="font-medium">{{ $entrada->aplicadaPor?->name ?? 'Sistema' }}</span>
                            ({{ $entrada->fecha_aplicacion ? $entrada->fecha_aplicacion->format('d/m/Y H:i') : '' }})
                        </div>
                    @elseif($entrada->isCancelada())
                        <div class="mt-1 text-red-600">
                            Cancelado por: <span class="font-medium">{{ $entrada->canceladaPor?->name ?? 'Sistema' }}</span>
                            ({{ $entrada->fecha_cancelacion ? $entrada->fecha_cancelacion->format('d/m/Y H:i') : '' }})
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($entrada->observaciones)
            <div class="mt-4 pt-4 border-t text-sm">
                <span class="text-xs font-semibold uppercase text-gray-500">Observaciones Generales:</span>
                <p class="mt-1 text-gray-700 whitespace-pre-line">{{ $entrada->observaciones }}</p>
            </div>
        @endif

        @if($entrada->isCancelada() && $entrada->motivo_cancelacion)
            <div class="mt-4 pt-4 border-t text-sm rounded-md bg-red-50 p-3">
                <span class="text-xs font-semibold uppercase text-red-800">Motivo de Cancelación:</span>
                <p class="mt-1 text-red-700 whitespace-pre-line">{{ $entrada->motivo_cancelacion }}</p>
            </div>
        @endif
    </div>

    {{-- Tabla de Partidas Recibidas --}}
    <div class="bg-white border rounded-lg overflow-hidden shadow-sm">
        <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Productos Recibidos en esta Entrada</h2>
            <span class="text-xs font-medium text-gray-500">{{ $entrada->detalles->count() }} partida(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-center w-12 font-semibold">#</th>
                        <th class="px-4 py-3 text-left font-semibold">Producto / Descripción</th>
                        <th class="px-4 py-3 text-center font-semibold">Unidad</th>
                        <th class="px-4 py-3 text-right font-semibold">Cant. Ordenada</th>
                        <th class="px-4 py-3 text-right font-semibold text-blue-900 bg-blue-50/50">Cant. Recibida</th>
                        <th class="px-4 py-3 text-right font-semibold">Costo Unitario</th>
                        <th class="px-4 py-3 text-right font-semibold">Importe</th>
                        <th class="px-4 py-3 text-left font-semibold">Origen</th>
                        <th class="px-4 py-3 text-left font-semibold">Observaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-800">
                    @forelse($entrada->detalles as $idx => $d)
                        @php
                            $ocDetalle = $d->ordenCompraDetalle;
                            $salidaDetalle = $d->huentitanSalidaDetalle ?: $ocDetalle?->huentitanSalidaDetalle;
                            $salida = $salidaDetalle?->salida;
                            $fabricacionMaterial = $ocDetalle?->huentitanOrdenFabricacionMaterial;
                            $ordenFabricacion = $fabricacionMaterial?->orden;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-center text-xs text-gray-400 font-mono">
                                {{ $idx + 1 }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    {{ $d->producto?->nombre ?? $d->descripcion }}
                                </div>
                                @if($d->producto?->sku)
                                    <div class="text-xs text-gray-500 font-mono">SKU: {{ $d->producto->sku }}</div>
                                @endif
                                @if($d->descripcion && $d->descripcion !== $d->producto?->nombre)
                                    <div class="text-xs text-gray-400 italic">{{ $d->descripcion }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs font-semibold text-gray-600">
                                {{ $d->unidad }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-gray-600">
                                {{ number_format($d->cantidad_ordenada, 3) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-bold text-blue-900 bg-blue-50/50">
                                {{ number_format($d->cantidad_recibida, 3) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-gray-600 text-xs">
                                ${{ number_format($d->costo_unitario, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-medium text-gray-900">
                                ${{ number_format($d->importe, 2) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                @if($salida)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-800">Salida a obra</span>
                                    <div class="mt-1 font-medium text-gray-800">{{ $salida->folio }}</div>
                                    <div class="text-gray-500">{{ $salida->obra ? trim(($salida->obra->clave_obra ? $salida->obra->clave_obra . ' - ' : '') . $salida->obra->nombre) : 'Sin obra' }}</div>
                                @elseif($ordenFabricacion)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-indigo-100 text-indigo-800">Orden fabricacion</span>
                                    <div class="mt-1 font-medium text-gray-800">{{ $ordenFabricacion->folio }}</div>
                                    <div class="text-gray-500">{{ $ordenFabricacion->producto?->nombre ?? 'Producto terminado' }}</div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold bg-gray-100 text-gray-700">Stock / almacen</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $d->observaciones ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                                No hay partidas registradas en esta entrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t font-semibold text-gray-900">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right text-xs uppercase tracking-wider text-gray-600">
                            Total recibido:
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-blue-900">
                            {{ number_format($entrada->total_recibido, 3) }}
                        </td>
                        <td class="px-4 py-3 text-right text-xs uppercase tracking-wider text-gray-600">
                            Importe total:
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">
                            ${{ number_format($entrada->total_importe, 2) }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Modal Cancelar (para Fase 6) --}}
@if($entrada->isAplicada())
<div id="modal-cancelar" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900">Confirmar Cancelación de Entrada</h3>
        <p class="mt-2 text-sm text-gray-600">
            Al cancelar esta entrada se revertirá el movimiento de inventario, se descontará el stock recibido y se reintegrará el saldo pendiente a la orden de compra.
        </p>
        <form method="POST" action="{{ route('huentitan.entradas.cancelar', $entrada) }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="motivo_cancelacion" class="block text-xs font-medium text-gray-700 mb-1">
                    Motivo de la cancelación <span class="text-red-500">*</span>
                </label>
                <textarea 
                    name="motivo_cancelacion" 
                    id="motivo_cancelacion" 
                    rows="3" 
                    required 
                    placeholder="Describe la razón por la cual se cancela esta entrada..."
                    class="w-full rounded-md border border-gray-300 p-2 text-sm focus:border-blue-500 focus:outline-none"
                ></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-cancelar').classList.add('hidden')" class="px-4 py-2 rounded-md border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">
                    Regresar
                </button>
                <button type="submit" class="px-4 py-2 rounded-md bg-red-600 text-white text-sm font-semibold hover:bg-red-700">
                    Sí, cancelar entrada
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection


