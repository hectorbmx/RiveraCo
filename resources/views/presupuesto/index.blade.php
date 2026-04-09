@extends('layouts.admin')

@section('content')
<div class="p-6">
    <div class="flex justify-between mb-4">
        <h1 class="text-xl font-semibold">Presupuestos Sincronizados (Excel)</h1>
        {{-- Mantenemos la coherencia visual con tus otros botones --}}
        <div class="flex gap-2">
            <span class="text-xs text-gray-500 self-center">Sincronizado desde APUPI.xlsm</span>
        </div>
    </div>

@php
    $estadoBadge = function ($estado) {
        $estado = strtolower(trim((string) $estado));
        return match ($estado) {
            'sincronizado' => 'bg-green-100 text-green-800 border-green-200',
            'pendiente'    => 'bg-amber-100 text-amber-800 border-amber-200',
            'error'        => 'bg-red-100 text-red-800 border-red-200',
            default        => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    };
@endphp

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border font-semibold text-center">Código Proyecto</th>
                    <th class="p-2 border font-semibold text-left">Cliente</th>
                    <th class="p-2 border font-semibold text-center">Costo Directo</th>
                    <th class="p-2 border font-semibold text-center">Total Presupuesto</th>
                    <th class="p-2 border font-semibold text-center">Estado</th>
                    <th class="p-2 border font-semibold text-center">Fecha Sinc.</th>
                    <th class="p-2 border font-semibold text-center"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($presupuestos as $presupuesto)
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="p-2 border text-center font-bold text-blue-900">
                        {{ $presupuesto->codigo_proyecto }}
                    </td>
                    <td class="p-2 border text-left">
                        {{ $presupuesto->nombre_cliente }}
                    </td>
                    <td class="p-2 border text-center">
                        ${{ number_format($presupuesto->total_costo_directo, 2) }}
                    </td>
                    <td class="p-2 border text-center font-semibold">
                        ${{ number_format($presupuesto->total_presupuesto, 2) }}
                    </td>
                    <td class="p-2 border text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $estadoBadge($presupuesto->estatus) }}">
                            {{ ucfirst($presupuesto->estatus) }}
                        </span>
                    </td>
                    <td class="p-2 border text-center text-gray-600">
                        {{ $presupuesto->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="p-2 border text-center">
                        <div class="flex items-center justify-center gap-3">
                            {{-- Abrir detalle --}}
                            <a href="{{ route('presupuesto.show', $presupuesto->id) }}" 
                               class="text-blue-600 hover:underline flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Abrir
                            </a>

                            {{-- Eliminar (Opcional, misma estética que tus otras vistas) --}}
                            <form action="#" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta sincronización?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-700 hover:text-red-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- Paginación con estilo Tailwind --}}
    <div class="mt-4">
        @if(method_exists($presupuestos, 'links'))
            {{ $presupuestos->links() }}
        @endif
    </div>
</div>
@endsection