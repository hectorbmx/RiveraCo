@extends('layouts.admin')

@section('title', 'Nueva herramienta HUENTITAN')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">HUENTITAN / Herramientas</div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Nueva herramienta</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $almacen->nombre }}</p>
        </div>
        <a href="{{ route('huentitan.herramientas.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">Volver</a>
    </div>

    @include('huentitan.herramientas._form')
</div>
@endsection

