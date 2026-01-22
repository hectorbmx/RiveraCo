@extends('layouts.admin')

@section('content')
    <div class="max-w-4xl mx-auto py-8">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">
                    Vehículo: {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                </h1>
                <p class="text-sm text-slate-500">
                    Placas: {{ $vehiculo->placas }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('mantenimiento.vehiculos.edit', $vehiculo) }}"
                   class="px-3 py-1.5 text-xs rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Editar
                </a>
                <a href="{{ route('mantenimiento.vehiculos.index') }}"
                   class="text-sm text-slate-500 hover:text-slate-700">
                    ← Volver al listado
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-xs font-semibold text-slate-500">Marca</div>
                    <div class="text-slate-800">{{ $vehiculo->marca ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Modelo</div>
                    <div class="text-slate-800">{{ $vehiculo->modelo ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Año</div>
                    <div class="text-slate-800">{{ $vehiculo->anio ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Color</div>
                    <div class="text-slate-800">{{ $vehiculo->color ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Placas</div>
                    <div class="text-slate-800">{{ $vehiculo->placas }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Serie (VIN)</div>
                    <div class="text-slate-800">{{ $vehiculo->serie ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Tipo</div>
                    <div class="text-slate-800">{{ $vehiculo->tipo ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Estatus</div>
                    <div class="text-slate-800">
                        {{ ucfirst(str_replace('_', ' ', $vehiculo->estatus)) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Fecha de registro</div>
                    <div class="text-slate-800">
                        {{ $vehiculo->fecha_registro ?? $vehiculo->created_at?->format('d/m/Y') ?? '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
