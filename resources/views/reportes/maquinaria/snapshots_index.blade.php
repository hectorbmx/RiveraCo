@extends('layouts.admin')

@section('title', 'Snapshots maquinaria')

@section('content')
<div class="w-full px-4 py-6 space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Snapshots de maquinaria</h1>
            <p class="text-sm text-gray-600">Fechas guardadas (histórico).</p>
        </div>

        <form method="GET" class="flex items-end gap-2">
            <div>
                <label class="block text-xs text-gray-600">Desde</label>
                <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-gray-300">
            </div>
            <div>
                <label class="block text-xs text-gray-600">Hasta</label>
                <input type="date" name="to" value="{{ $to }}" class="rounded-lg border-gray-300">
            </div>
            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                Filtrar
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Máquinas</th>
                        <th class="text-left px-4 py-3">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($snapshots as $s)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::parse($s->fecha)->toDateString() }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $s->estado === 'cerrado' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $s->estado ?? 'abierto' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ (int) ($s->total_maquinas ?? 0) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('reportes.maquinaria.historial', ['fecha' => \Illuminate\Support\Carbon::parse($s->fecha)->toDateString()]) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                    Ver histórico
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                No hay snapshots en el rango seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
