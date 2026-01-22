@extends('layouts.admin')

@section('title', 'Reportes')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Reportes</h1>
            <p class="text-sm text-gray-600">Concentrado de reportes operativos y administrativos.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        
         <a href="{{ route('reportes.maquinaria.snapshots.index') }}"
           class="block bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-600">Maquinaria</div>
                    <div class="text-lg font-semibold text-gray-900">Reporte diario</div>
                </div>
                <div class="text-2xl">🛠️</div>
            </div>
            <p class="mt-3 text-sm text-gray-600">
                Captura diaria por máquina (observaciones) con precarga del último registro.
            </p>
        </a>
    </div>
</div>
@endsection
