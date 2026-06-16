@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Detalle de Solicitud #{{ $solicitud->id }}</h1>
            <p class="text-slate-500">Obra: {{ $obra->nombre }} · Semana {{ $solicitud->semana }}</p>
        </div>
        <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'solicitudes-gastos']) }}" class="text-slate-600 hover:underline">
            Volver
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border">
            <p class="text-xs font-bold uppercase text-slate-400">Estatus</p>
            <p class="mt-1">
                <span class="px-3 py-1 rounded-full text-xs font-bold 
                    {{ $solicitud->estatus === 'solicitado' ? 'bg-yellow-100 text-yellow-700' : '' }}
                    {{ $solicitud->estatus === 'autorizado' ? 'bg-blue-100 text-blue-700' : '' }}
                    {{ $solicitud->estatus === 'pagado' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $solicitud->estatus === 'rechazado' ? 'bg-red-100 text-red-700' : '' }}
                ">
                    {{ ucfirst($solicitud->estatus) }}
                </span>
            </p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border">
            <p class="text-xs font-bold uppercase text-slate-400">Total Solicitado</p>
            <p class="text-xl font-bold text-[#0B265A] mt-1">${{ number_format($solicitud->total, 2) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border">
            <p class="text-xs font-bold uppercase text-slate-400">Fecha Solicitud</p>
            <p class="text-lg font-semibold text-slate-700 mt-1">{{ $solicitud->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-6">
        <div class="p-4 bg-slate-50 border-b">
            <h3 class="font-bold text-slate-800">Conceptos de la Solicitud</h3>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b text-slate-500">
                    <th class="py-3 px-4 text-left">Concepto / Partida</th>
                    <th class="py-3 px-4 text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($solicitud->detalles as $detalle)
                    <tr class="border-b">
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-700">{{ $detalle->planeacionGasto->concepto }}</div>
                            <div class="text-xs text-slate-400">{{ $detalle->planeacionGasto->partida }}</div>
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-slate-600">
                            ${{ number_format($detalle->monto_solicitado, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-slate-50 font-bold">
                    <td class="py-3 px-4 text-right text-slate-500 uppercase">Total</td>
                    <td class="py-3 px-4 text-right text-[#0B265A] text-lg">
                        ${{ number_format($solicitud->total, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
        <h3 class="font-bold text-slate-800 mb-2">Información de Auditoría</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-slate-500">Solicitado por:</p>
                <p class="font-medium">{{ $solicitud->solicitadoPor->name ?? 'Sistema' }} el {{ $solicitud->solicitado_at?->format('d/m/Y H:i') }}</p>
            </div>
            @if($solicitud->autorizado_por)
                <div>
                    <p class="text-slate-500">Autorizado por:</p>
                    <p class="font-medium">{{ $solicitud->autorizadoPor->name ?? 'N/A' }} el {{ $solicitud->autorizado_at?->format('d/m/Y H:i') }}</p>
                </div>
            @endif
            @if($solicitud->pagado_por)
                <div>
                    <p class="text-slate-500">Pagado por:</p>
                    <p class="font-medium">{{ $solicitud->pagadoPor->name ?? 'N/A' }} el {{ $solicitud->pagado_at?->format('d/m/Y H:i') }}</p>
                </div>
            @endif
        </div>

        @if($solicitud->observaciones)
            <div class="mt-4 pt-4 border-t">
                <p class="text-slate-500 text-sm">Observaciones:</p>
                <p class="text-slate-700 italic mt-1">"{{ $solicitud->observaciones }}"</p>
            </div>
        @endif
    </div>

    @if($solicitud->estatus === 'solicitado')
        <div class="flex justify-end gap-3">
            @can('autorizar.solicitud.planeacion.access')
                <form action="{{ route('obras.solicitudes-gastos.rechazar', ['obra' => $obra->id, 'solicitud' => $solicitud->id]) }}" method="POST" onsubmit="return confirm('¿Rechazar esta solicitud?')">
                    @csrf
                    <button type="submit" class="px-6 py-2 rounded-xl bg-red-50 text-red-600 font-bold hover:bg-red-100 transition">
                        Rechazar
                    </button>
                </form>

                <form action="{{ route('obras.solicitudes-gastos.autorizar', ['obra' => $obra->id, 'solicitud' => $solicitud->id]) }}" method="POST" onsubmit="return confirm('¿Autorizar esta solicitud?')">
                    @csrf
                    <button type="submit" class="px-8 py-2 rounded-xl bg-green-600 text-white font-bold hover:bg-green-700 transition shadow-lg">
                        Autorizar Solicitud
                    </button>
                </form>
            @else
                <p class="text-slate-400 text-sm italic">No tienes permisos para autorizar o rechazar esta solicitud.</p>
            @endcan
        </div>
    @endif
</div>
@endsection
