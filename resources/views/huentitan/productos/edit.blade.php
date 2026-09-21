@extends('layouts.admin')

@section('title', 'Editar producto - HUENTITAN')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
        <a href="{{ route('huentitan.index') }}" class="hover:underline">HUENTITAN</a>
        <span>/</span>
        <a href="{{ route('huentitan.productos.index') }}" class="hover:underline">Productos</a>
        <span>/</span>
        <a href="{{ route('huentitan.productos.show', $producto) }}" class="hover:underline">{{ $producto->sku }}</a>
        <span>/</span>
        <span class="text-gray-900">Editar</span>
    </div>

    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Editar producto HUENTITAN</h1>
        <p class="mt-1 text-sm text-gray-600">Actualiza datos del catalogo exclusivo de {{ $almacen->nombre }}.</p>
    </div>

    @if($errors->any())
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            Hay errores en la informacion capturada. Revisa los campos marcados.
        </div>
    @endif

    @include('huentitan.productos._form')
</div>
@endsection

