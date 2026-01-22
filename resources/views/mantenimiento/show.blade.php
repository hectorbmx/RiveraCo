@extends('layouts.admin')

@section('content')
    <div class="max-w-4xl mx-auto py-8">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">
                    Mantenimiento #{{ $mantenimiento->id }}
                </h1>
                <p class="text-sm text-slate-500">
                    Vehículo: {{ $mantenimiento->vehiculo->marca ?? '' }} {{ $mantenimiento->vehiculo->modelo ?? '' }}
                    — Placas: {{ $mantenimiento->vehiculo->placas ?? '—' }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('mantenimiento.mantenimientos.edit', $mantenimiento) }}"
                   class="px-3 py-1.5 text-xs rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Editar
                </a>
                <a href="{{ route('mantenimiento.mantenimientos.index') }}"
                   class="text-sm text-slate-500 hover:text-slate-700">
                    ← Volver al listado
                </a>
            </div>
        </div>

        {{-- Datos principales --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-xs font-semibold text-slate-500">Tipo</div>
                    <div class="text-slate-800">{{ ucfirst($mantenimiento->tipo) }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Categoría</div>
                    <div class="text-slate-800">{{ $mantenimiento->categoria_mantenimiento ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Estatus</div>
                    <div class="text-slate-800">
                        {{ ucfirst(str_replace('_', ' ', $mantenimiento->estatus)) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Mecánico</div>
                    <div class="text-slate-800">
                        
                            {{ $mantenimiento->mecanico ? trim(($mantenimiento->mecanico->Nombre ?? '') . ' ' . ($mantenimiento->mecanico->Apellidos ?? '')) : '—' }}

                    </div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Fecha programada</div>
                    <div class="text-slate-800">
                        {{ $mantenimiento->fecha_programada ? $mantenimiento->fecha_programada->format('d/m/Y H:i') : '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Inicio</div>
                    <div class="text-slate-800">
                        {{ $mantenimiento->fecha_inicio ? $mantenimiento->fecha_inicio->format('d/m/Y H:i') : '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Fin</div>
                    <div class="text-slate-800">
                        {{ $mantenimiento->fecha_fin ? $mantenimiento->fecha_fin->format('d/m/Y H:i') : '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Duración (minutos)</div>
                    <div class="text-slate-800">
                        {{ $mantenimiento->duracion_en_minutos ?? '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Km actuales</div>
                    <div class="text-slate-800">{{ $mantenimiento->km_actuales ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Km próximo servicio</div>
                    <div class="text-slate-800">{{ $mantenimiento->km_proximo_servicio ?? '—' }}</div>
                </div>
            </div>

            {{-- Descripción --}}
            <div class="pt-4 border-t border-slate-100">
                <div class="text-xs font-semibold text-slate-500 mb-1">Descripción</div>
                <div class="text-sm text-slate-800 whitespace-pre-line">
                    {{ $mantenimiento->descripcion ?? 'Sin descripción.' }}
                </div>
            </div>

            {{-- Aquí más adelante podemos agregar: detalles y fotos --}}
            {{-- Detalles de refacciones / mano de obra --}}
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm font-semibold text-slate-700">Detalle del mantenimiento</div>
                    <div class="text-xs text-slate-500">
                        Costo total: ${{ number_format($mantenimiento->costo_total, 2) }}
                    </div>
                </div>

                @if($mantenimiento->detalles->isEmpty())
                    <p class="text-sm text-slate-500">
                        No hay conceptos capturados para este mantenimiento.
                    </p>
                @else
                    <table class="min-w-full text-xs border border-slate-100 rounded-lg overflow-hidden">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Concepto</th>
                                <th class="px-3 py-2 text-right font-semibold text-slate-500">Cantidad</th>
                                <th class="px-3 py-2 text-right font-semibold text-slate-500">Costo unitario</th>
                                <th class="px-3 py-2 text-right font-semibold text-slate-500">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mantenimiento->detalles as $detalle)
                                <tr class="border-t border-slate-100">
                                    <td class="px-3 py-2 text-slate-700">
                                        {{ $detalle->concepto }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-slate-700">
                                        {{ $detalle->cantidad }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-slate-700">
                                        ${{ number_format($detalle->costo_unitario, 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-slate-800 font-medium">
                                        ${{ number_format($detalle->costo_total, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Fotos (si más adelante las cargamos) --}}
            <div class="pt-4 border-t border-slate-100">
                <div class="text-sm font-semibold text-slate-700 mb-2">Fotos / evidencias</div>

                @if($mantenimiento->fotos->isEmpty())
                    <p class="text-sm text-slate-500">
                        No hay fotos registradas para este mantenimiento.
                    </p>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach($mantenimiento->fotos as $foto)
                            <div class="border border-slate-200 rounded-lg overflow-hidden">
                                <img src="{{ asset($foto->ruta) }}" alt="Foto mantenimiento"
                                     class="w-full h-32 object-cover">
                                @if($foto->descripcion)
                                    <div class="px-2 py-1 text-xs text-slate-600">
                                        {{ $foto->descripcion }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
