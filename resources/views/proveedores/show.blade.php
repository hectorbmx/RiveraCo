@extends('layouts.admin')

@section('title', 'Proveedor')

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">
                Proveedor: {{ $proveedor->nombre }}
            </h1>
            <p class="text-sm text-slate-500">
                ID: {{ $proveedor->id }}
                @if($proveedor->rfc) · RFC: {{ $proveedor->rfc }} @endif
                · Estatus:
                @if($proveedor->activo)
                    <span class="text-green-600 font-semibold">Activo</span>
                @else
                    <span class="text-red-600 font-semibold">Inactivo</span>
                @endif
            </p>
        </div>

        <a href="{{ route('proveedores.index') }}"
           class="text-sm text-slate-500 hover:text-slate-800">
            ← Volver al listado
        </a>
    </div>

    {{-- Tabs --}}
    <div class="border-b mb-4 flex gap-6 text-sm">
        @php
            $tabs = [
                'general'  => 'General',
                'productos'=> 'Productos',
                'ordenes'  => 'Órdenes',
                'pagado'   => 'Pagado',
            ];
        @endphp

        @foreach($tabs as $key => $label)
            <a href="{{ route('proveedores.show', ['proveedor' => $proveedor->id, 'tab' => $key]) }}"
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

    {{-- Contenido --}}
    <div class="bg-white rounded-2xl shadow p-6">
        @if($tab === 'general')
            @include('proveedores.partials._general', ['proveedor' => $proveedor])
        @endif

        @if($tab === 'productos')
            @include('proveedores.partials._productos', ['proveedor' => $proveedor])
        @endif

        @if($tab === 'ordenes')
            @include('proveedores.partials._ordenes', ['proveedor' => $proveedor, 'ordenes' => $ordenes])
        @endif

        @if($tab === 'pagado')
            @include('proveedores.partials._pagado', ['proveedor' => $proveedor])
        @endif
    </div>

</div>
@endsection
