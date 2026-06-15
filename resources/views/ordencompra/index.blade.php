@extends('layouts.admin')

@section('content')
<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-[#0B265A]">Órdenes de compra</h1>
        <a href="{{ route('ordenes_compra.create') }}"
           class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl shadow hover:bg-[#e0ac05] transition">
            + Nueva orden
        </a>
    </div>

    {{-- BUSCADOR Y FILTROS --}}
    <div class="bg-white rounded-2xl shadow p-6 mb-6">
        <form action="{{ route('ordenes_compra.index') }}" method="GET" class="space-y-4">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="relative flex-1">
                    <input type="text" 
                           name="search" 
                           value="{{ $search ?? '' }}"
                           placeholder="Buscar por proveedor, razón social o RFC..." 
                           class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0B265A] focus:border-transparent transition text-sm">
                    <div class="absolute left-3 top-2.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="bg-[#0B265A] text-white px-6 py-2 rounded-xl text-sm font-semibold hover:bg-[#163a7a] transition">
                        Buscar
                    </button>
                    @if(request('search') || request('estado'))
                        <a href="{{ route('ordenes_compra.index') }}" class="bg-slate-200 text-slate-600 px-6 py-2 rounded-xl text-sm font-semibold hover:bg-slate-300 transition text-center">
                            Limpiar
                        </a>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="text-sm font-medium text-slate-500 self-center mr-2">Estados:</span>
                
                <a href="{{ route('ordenes_compra.index', array_merge(request()->query(), ['estado' => 'autorizada'])) }}" 
                   class="px-4 py-1.5 rounded-full text-xs font-semibold border transition {{ (request('estado') == 'autorizada') ? 'bg-green-600 text-white border-green-600' : 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100' }}">
                    Autorizada
                </a>

                <a href="{{ route('ordenes_compra.index', array_merge(request()->query(), ['estado' => 'programada'])) }}" 
                   class="px-4 py-1.5 rounded-full text-xs font-semibold border transition {{ (request('estado') == 'programada') ? 'bg-blue-600 text-white border-blue-600' : 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100' }}">
                    Programada
                </a>

                <a href="{{ route('ordenes_compra.index', array_merge(request()->query(), ['estado' => 'por autorizar'])) }}" 
                   class="px-4 py-1.5 rounded-full text-xs font-semibold border transition {{ (request('estado') == 'por autorizar') ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' }}">
                    Por autorizar
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow overflow-hidden">
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

    <div class="bg-white rounded-2xl shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b text-slate-500 font-medium">
                    <th class="py-3 px-4 text-center">Folio</th>
                    <th class="py-3 px-4 text-left">Proveedor</th>
                    <th class="py-3 px-4 text-left">Área</th>
                    <th class="py-3 px-4 text-left">Destino</th>
                    <th class="py-3 px-4 text-center">Fecha</th>
                    <th class="py-3 px-4 text-center">Estado</th>
                    <th class="py-3 px-4 text-right">Total</th>
                    <th class="py-3 px-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($ordenes as $oc)
                <tr class="border-b hover:bg-slate-50 transition">
                    <td class="py-3 px-4 text-center font-medium">{{ $oc->folio }}</td>
                    <td class="py-3 px-4">
                        @if($oc->proveedor)
                            <div class="flex flex-col">
                                <a href="{{ route('proveedores.show', ['proveedor' => $oc->proveedor->id, 'tab' => 'general']) }}"
                                   class="font-semibold text-blue-700 hover:text-blue-900 hover:underline">
                                    {{ $oc->proveedor->nombre }}
                                </a>
                                @if($oc->proveedor->rfc)
                                    <span class="text-xs text-slate-400">{{ $oc->proveedor->rfc }}</span>
                                @endif
                            </div>
                        @else
                            -
                        @endif
                    </td>
                    
                    <td class="py-3 px-4">{{ $oc->areaCatalogo->nombre ?? $oc->area }}</td>
                    <td class="py-3 px-4">
                        @if($oc->obra)
                            <span class="text-slate-700">{{ $oc->obra->nombre }}</span>
                        @elseif($oc->centroCosto)
                            <span class="text-slate-700">{{ $oc->centroCosto->codigo ? $oc->centroCosto->codigo . ' - ' : '' }}{{ $oc->centroCosto->nombre }}</span>
                        @else
                            <span class="text-slate-400">Compra general</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center text-slate-600">{{ $oc->fecha->format('d/m/Y') }}</td>
                    <td class="py-3 px-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $estadoBadge($oc->estado_normalizado) }}">
                            {{ ucfirst($oc->estado) }}
                        </span>
                    </td>

                    <td class="py-3 px-4 text-right font-bold text-[#0B265A]">${{ number_format($oc->total,2) }}</td>
                    <td class="py-3 px-4">
                        <div class="flex items-center justify-end gap-3">
                        {{-- Imprimir --}}
                                @can('ordenes_compra.imprimir')
                                    <a href="{{ route('ordenes_compra.print', $oc->id) }}"
                                    target="_blank"
                                    class="text-slate-400 hover:text-slate-600 transition"
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
                            class="text-blue-600 hover:text-blue-800 font-medium text-sm transition">
                                Editar
                            </a>

                            @if($oc->estado_normalizado === 'autorizada' && !$oc->pagoProveedorActivo)
                                <a href="{{ route('pagos-proveedores.create', ['orden_compra_id' => $oc->id]) }}"
                                   class="text-amber-600 hover:text-amber-800 font-medium text-sm transition">
                                    Pagar
                                </a>
                            @endif

                            @php
                                $estadoNorm = strtolower(trim((string) ($oc->estado ?? 'borrador')));
                            @endphp

                            @if(!in_array($estadoNorm, ['autorizada','autorizado','cancelada','cancelado']))
                                @can('ordenes_compra.autorizar')
                                    <form method="POST" action="{{ route('ordenes_compra.autorizar', $oc->id) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="text-green-600 hover:text-green-800 font-medium text-sm transition"
                                                onclick="return confirm('¿Autorizar la orden {{ $oc->folio }}?');">
                                            Autorizar
                                        </button>
                                    </form>
                                @endcan
                            @endif

                            {{-- Cancelar (solo si NO está cancelada) --}}
                            @if(!in_array($oc->estado_normalizado, ['cancelada','cancelado']))
                                <form method="POST" action="{{ route('ordenes_compra.cancelar', $oc->id) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="text-red-500 hover:text-red-700 font-medium text-sm transition"
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
    </div>

    <div class="mt-4">
        {{ $ordenes->links() }}
    </div>
</div>
@endsection
