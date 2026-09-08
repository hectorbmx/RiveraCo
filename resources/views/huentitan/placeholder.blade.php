@extends('layouts.admin')

@section('title', 'HUENTITAN - ' . $titulo)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">HUENTITAN</div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">{{ $titulo }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $descripcion }}</p>
        </div>
        <a href="{{ route('huentitan.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium hover:bg-gray-50">
            Volver al panel
        </a>
    </div>

    <div class="bg-white border rounded-lg p-6">
        <div class="text-sm font-medium text-gray-900">Estado</div>
        <p class="mt-2 text-sm text-gray-600">{{ $estado }}</p>
    </div>
</div>
@endsection
