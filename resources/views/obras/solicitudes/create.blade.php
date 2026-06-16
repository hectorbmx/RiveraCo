@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Nueva Solicitud de Gasto</h1>
            <p class="text-slate-500">Obra: {{ $obra->nombre }}</p>
        </div>
        <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'solicitudes-gastos']) }}" class="text-slate-600 hover:underline">
            Volver
        </a>
    </div>

    @if(!$semana)
        <div class="bg-white rounded-xl shadow-sm border p-8 text-center">
            <h3 class="text-lg font-bold mb-4">Selecciona la semana a solicitar</h3>
            <form action="{{ route('obras.solicitudes-gastos.create', $obra) }}" method="GET" class="flex flex-col items-center gap-4">
                <select name="semana" class="rounded-xl border-slate-300 w-64">
                    @for($i = 1; $i <= $obra->semanas_totales; $i++)
                        <option value="{{ $i }}">Semana {{ $i }}</option>
                    @endfor
                </select>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-700 transition">
                    Continuar
                </button>
            </form>
        </div>
    @else
        <form action="{{ route('obras.solicitudes-gastos.store', $obra) }}" method="POST">
            @csrf
            <input type="hidden" name="semana" value="{{ $semana }}">

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-6">
                <div class="p-4 bg-slate-50 border-b">
                    <h3 class="font-bold text-slate-800">Conceptos planeados para la Semana {{ $semana }}</h3>
                </div>
                
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b text-slate-500">
                            <th class="py-3 px-4 text-left">Concepto</th>
                            <th class="py-3 px-4 text-right">Planeado</th>
                            <th class="py-3 px-4 text-right">Monto a Solicitar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conceptosPlaneados as $cp)
                            <tr class="border-b">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-700">{{ $cp->gastoBase->concepto }}</div>
                                    <div class="text-xs text-slate-400">{{ $cp->gastoBase->partida }}</div>
                                </td>
                                <td class="py-3 px-4 text-right font-medium text-slate-600">
                                    ${{ number_format($cp->monto_programado, 2) }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <input type="number" 
                                           name="conceptos[{{ $cp->planeacion_gasto_id }}][monto]" 
                                           value="{{ $cp->monto_programado }}"
                                           step="0.01"
                                           max="{{ $cp->monto_programado }}"
                                           class="w-32 rounded-xl border-slate-300 text-right focus:ring-blue-500">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-400">
                                    No hay conceptos planeados con monto mayor a $0 para esta semana.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones / Motivo de la solicitud</label>
                <textarea name="observaciones" rows="3" class="w-full rounded-xl border-slate-300 focus:ring-blue-500" placeholder="Ej. Gastos para viáticos y materiales menores..."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'solicitudes-gastos']) }}" class="px-6 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold hover:bg-slate-200 transition">
                    Cancelar
                </a>
                <button type="submit" class="px-8 py-2 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition shadow-lg">
                    Enviar Solicitud
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
