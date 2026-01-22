@extends('layouts.admin')

@section('content')
<div class="p-6">
    <div class="flex justify-between mb-4">
        <h1 class="text-xl font-semibold">Órdenes de compra</h1>
        <a href="{{ route('ordenes_compra.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded">
            Nueva orden
        </a>
    </div>
@php
    $estadoBadge = function ($estado) {
        $estado = strtolower(trim((string) $estado));

        return match ($estado) {
            'autorizada', 'autorizado' => 'bg-green-100 text-green-800 border-green-200',
            'cancelada', 'cancelado'   => 'bg-red-100 text-red-800 border-red-200',
            'borrador'                 => 'bg-gray-100 text-gray-800 border-gray-200',
            'pendiente'                => 'bg-amber-100 text-amber-800 border-amber-200',
            default                    => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    };
@endphp

    <table class="w-full text-sm border">
        <thead class="bg-gray-100">
        <tr>
            <th class="p-2 border">Folio</th>
            <th class="p-2 border">Proveedor</th>
            <th class="p-2 border">Área</th>
            <th class="p-2 border">Fecha</th>
            <th class="p-2 border">Estado</th>
            <th class="p-2 border">Total</th>
            <th class="p-2 border"></th>
        </tr>
        </thead>
        <tbody>
        @foreach($ordenes as $oc)
            <tr class="border-b">
                <td class="p-2 text-center">{{ $oc->folio }}</td>
                <td class="p-2 text-center">{{ $oc->proveedor->nombre ?? '-' }}</td>
                
                <td class="p-2 text-center">{{ $oc->areaCatalogo->nombre ?? $oc->area }}</td>
                <td class="p-2 text-center">{{ $oc->fecha }}</td>
                <td class="p-2 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $estadoBadge($oc->estado_normalizado) }}">
                        {{ ucfirst($oc->estado) }}
                    </span>
                </td>

                <td class="p-2 text-center">${{ number_format($oc->total,2) }}</td>
                <td class="p-2 text-center">
                    <div class="flex items-center justify-center gap-3">
                    {{-- Imprimir --}}
                            @can('ordenes_compra.imprimir')
                                <a href="{{ route('ordenes_compra.print', $oc->id) }}"
                                target="_blank"
                                class="text-slate-600 hover:text-slate-900"
                                title="Imprimir OC">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6v-8z" />
                                    </svg>
                                </a>
                            @endcan

                        {{-- Abrir --}}
                        <a href="{{ route('ordenes_compra.edit',$oc->id) }}"
                        class="text-blue-600 hover:underline">
                            Abrir
                        </a>

                        @php
                            $estadoNorm = strtolower(trim((string) ($oc->estado ?? 'borrador')));
                        @endphp
                        {{-- Autorizar (solo si NO está autorizada y NO está cancelada) --}}
                    @if(!in_array($estadoNorm, ['autorizada','autorizado','cancelada','cancelado']))
                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('compras'))
                            <form method="POST" action="{{ route('ordenes_compra.autorizar', $oc->id) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="text-green-700 hover:underline"
                                        onclick="return confirm('¿Autorizar la orden {{ $oc->folio }}?');">
                                    Autorizar
                                </button>
                            </form>
                        @endif
                    @endif



                        {{-- Cancelar (solo si NO está cancelada) --}}
                        @if(!in_array($oc->estado_normalizado, ['cancelada','cancelado']))
                            <form method="POST" action="{{ route('ordenes_compra.cancelar', $oc->id) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="text-red-700 hover:underline"
                                        onclick="return confirm('¿Cancelar la orden {{ $oc->folio }}?');">
                                    Cancelar
                                </button>
                            </form>
                        @endif

                    </div>
                </td>

            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $ordenes->links() }}
    </div>
</div>
@endsection
