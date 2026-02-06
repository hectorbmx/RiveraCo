@extends('layouts.admin')

@section('title', 'Producto')

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- Header expediente --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">
                Producto: {{ $producto->nombre }}
            </h1>
            <p class="text-sm text-slate-500">
                ID: {{ $producto->id }} ·
                SKU: {{ $producto->sku ?? '-' }} ·
                Estatus:
                @if($producto->activo)
                    <span class="text-green-600 font-semibold">Activo</span>
                @else
                    <span class="text-red-600 font-semibold">Inactivo</span>
                @endif
            </p>
        </div>

        <a href="{{ route('productos.index') }}"
           class="text-sm text-slate-500 hover:text-slate-800">
            ← Volver al listado
        </a>
    </div>

    {{-- Tabs --}}
    <div class="border-b mb-4 flex gap-6 text-sm">
        @php
            $tabs = [
                'general'     => 'Información general',
                'proveedores' => 'Proveedores',
                'costos'      => 'Costos / historial',
                'kardex'      => 'Kardex',
            ];
            $tab = $tab ?? 'general';
        @endphp

        @foreach($tabs as $key => $label)
            <a href="{{ route('productos.edit', ['producto' => $producto->id, 'tab' => $key]) }}"
               class="pb-2 border-b-2 transition-all
               {{ $tab === $key
                    ? 'border-[#FFC107] text-[#0B265A] font-semibold'
                    : 'border-transparent text-slate-500 hover:text-slate-800'
                }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            Hay errores en el formulario, revisa la información.
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow p-6">

        @if($tab === 'general')
            @include('productos.partials._general', ['producto' => $producto])
        @endif

        @if($tab === 'proveedores')
            @include('productos.partials._proveedores', [
                'producto' => $producto,
                'proveedores' => $proveedores ?? collect(),
            ])
        @endif

        @if($tab === 'costos')
            @include('productos.partials._costos', ['producto' => $producto])
        @endif
        @if($tab === 'kardex')
        @include('productos.partials._kardex', [
            'producto' => $producto,
            'movimientos' => $movimientos ?? collect(),
            'almacenes' => $almacenes ?? collect(),
            'almacenId' => $almacenId ?? null,
            'desde' => $desde ?? null,
            'hasta' => $hasta ?? null,
        ])
    @endif


    </div>
</div>
@endsection
