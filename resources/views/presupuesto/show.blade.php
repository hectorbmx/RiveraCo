@extends('layouts.admin')

@section('content')
<div class="p-6 space-y-6">
    <div class="flex gap-2">
    {{-- Botón de PDF --}}
    <a href="{{ route('presupuesto.pdf', $presupuesto->id) }}" 
       target="_blank"
       class="flex items-center bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        Exportar PDF
    </a>
</div>
    
    <div class="flex justify-between items-start bg-white p-6 rounded-lg shadow border border-gray-200">
        <div>
            <span class="text-blue-600 text-xs font-bold uppercase tracking-wider">Presupuesto Sincronizado</span>
            <h1 class="text-3xl font-extrabold text-slate-800">{{ $presupuesto->codigo_proyecto }}</h1>
            <p class="text-lg text-slate-600">{{ $presupuesto->nombre_cliente }}</p>
        </div>
        <div class="flex gap-8">
            <div class="text-center">
                <p class="text-xs text-gray-500 uppercase font-bold">Costo Directo</p>
                <p class="text-xl font-semibold text-slate-700">${{ number_format($presupuesto->total_costo_directo, 2) }}</p>
            </div>
            <div class="text-center border-l pl-8">
                <p class="text-xs text-gray-500 uppercase font-bold">Total Venta</p>
                <p class="text-2xl font-black text-green-600">${{ number_format($presupuesto->total_presupuesto, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
        <div class="bg-slate-800 text-white px-4 py-2 flex justify-between items-center">
            <h2 class="font-bold uppercase text-sm tracking-wide">I. Resumen de Venta por Partidas</h2>
            <span class="text-xs opacity-75">Filas 196 - 219</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border text-left text-gray-600">Partida</th>
                        <th class="p-2 border text-left text-gray-600">Concepto</th>
                        <th class="p-2 border text-center text-gray-600">Unidad</th>
                        <th class="p-2 border text-center text-gray-600">Cantidad</th>
                        <th class="p-2 border text-right text-gray-600">P.U. Venta</th>
                        <th class="p-2 border text-right text-gray-600">Importe</th>
                    </tr>
                </thead>
        <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
   
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border text-left text-gray-600 w-40">Partida</th>
                    <th class="p-2 border text-left text-gray-600">Concepto</th>
                    <th class="p-2 border text-center text-gray-600 w-24">Unidad</th>
                    <th class="p-2 border text-center text-gray-600 w-24">Cant.</th>
                    <th class="p-2 border text-right text-gray-600 w-32">P.U.</th>
                    <th class="p-2 border text-right text-gray-600 w-32">Importe</th>
                </tr>
            </thead>
            <tbody>
                {{-- 1. Resúmenes estándar --}}
                @foreach($presupuesto->resumenes as $res)
                    @if($res->cantidad > 0 && $res->precio_unitario > 0)
                        <tr class="hover:bg-slate-50 border-b">
                            <td class="p-2 border text-[10px] font-bold text-slate-500 uppercase">{{ $res->partida }}</td>
                            <td class="p-2 border">{{ $res->concepto }}</td>
                            <td class="p-2 border text-center text-gray-400">{{ $res->unidad }}</td>
                            <td class="p-2 border text-center">{{ number_format($res->cantidad, 2) }}</td>
                            <td class="p-2 border text-right">${{ number_format($res->precio_unitario, 2) }}</td>
                            <td class="p-2 border text-right font-semibold text-slate-700">
                                ${{ number_format($res->importe, 2) }}
                            </td>
                        </tr>
                    @endif
                @endforeach

                {{-- 2. Pilas (Perforación) --}}
                @foreach($presupuesto->pilas as $pila)
                    @if($pila->cantidad > 0 && $pila->costo > 0)
                        <tr class="hover:bg-blue-50 border-b bg-blue-50/20">
                            <td class="p-2 border text-[10px] font-bold text-blue-600 uppercase">PERFORACIÓN</td>
                            <td class="p-2 border italic">{{ $pila->concepto }}</td>
                            <td class="p-2 border text-center text-gray-400">{{ $pila->unidad }}</td>
                            <td class="p-2 border text-center">{{ number_format($pila->cantidad, 2) }}</td>
                            <td class="p-2 border text-right">${{ number_format($pila->costo, 2) }}</td>
                            <td class="p-2 border text-right font-semibold text-slate-700">
                                ${{ number_format($pila->total, 2) }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
            
            {{-- EL NUEVO ROW DEL TOTAL --}}
            <tfoot class="bg-slate-100 font-bold">
                <tr>
                    <td colspan="5" class="p-3 border text-right text-xs uppercase text-slate-600">
                        Total General del Presupuesto (Venta):
                    </td>
                    <td class="p-3 border text-right text-lg text-blue-900 bg-slate-200">
                        ${{ number_format($presupuesto->resumenes->where('cantidad', '>', 0)->sum('importe') + $presupuesto->pilas->where('cantidad', '>', 0)->sum('total'), 2) }}
                    </td>
                </tr>
            </tfoot>
        
    </div>

            </table>
        </div>
    </div>
<div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
        <div class="bg-gray-800 text-white px-4 py-2 flex justify-between items-center">
            <h2 class="font-bold uppercase text-sm tracking-wide">III. Desglose Operativo y Técnico Detallado</h2>
            <span class="text-xs opacity-75">Incluye Perforación y Conceptos Generales</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border text-left text-gray-600">Partida / Concepto</th>
                        <th class="p-2 border text-center text-gray-600 w-16">Und.</th>
                        <th class="p-2 border text-center text-gray-600 w-16">Cant.</th>
                        <th class="p-2 border text-right text-gray-600 w-24">P.U. / Costo</th>
                        <th class="p-2 border text-right text-gray-600 w-28">Importe Total</th>
                        <th class="p-2 border text-right text-green-700 italic w-28">Optimista</th>
                        <th class="p-2 border text-right text-red-700 italic w-28">Pesimista</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- 1. Insertamos primero las PILAS forzando la partida PERFORACIÓN --}}
                    @foreach($presupuesto->pilas as $pila)
                    <tr class="hover:bg-blue-50 border-b bg-blue-50/30">
                        <td class="p-2 border">
                            <span class="block font-bold text-blue-800 text-[10px] uppercase">PERFORACIÓN (PILAS)</span>
                            <span class="text-gray-700">{{ $pila->concepto }}</span>
                        </td>
                        <td class="p-2 border text-center text-gray-400">{{ $pila->unidad }}</td>
                        <td class="p-2 border text-center">{{ $pila->cantidad }}</td>
                        <td class="p-2 border text-right">${{ number_format($pila->costo, 2) }}</td>
                        <td class="p-2 border text-right font-bold text-slate-700">${{ number_format($pila->total, 2) }}</td>
                        <td class="p-2 border text-right text-green-600 italic">${{ number_format($pila->optimista, 2) }}</td>
                        <td class="p-2 border text-right text-red-600 italic">${{ number_format($pila->pesimista, 2) }}</td>
                    </tr>
                    @endforeach

                    {{-- 2. Insertamos el DESGLOSE GENERAL --}}
                    @foreach($presupuesto->detalles as $det)
                    <tr class="hover:bg-gray-50 border-b">
                        <td class="p-2 border">
                            <span class="block font-bold text-blue-900 text-[10px] uppercase">{{ $det->partida }}</span>
                            <span class="text-gray-700">{{ $det->concepto }}</span>
                        </td>
                        <td class="p-2 border text-center text-gray-400">{{ $det->unidad }}</td>
                        <td class="p-2 border text-center">{{ $det->cantidad }}</td>
                        <td class="p-2 border text-right">${{ number_format($det->precio_unitario, 2) }}</td>
                        <td class="p-2 border text-right font-bold text-slate-700">${{ number_format($det->importe, 2) }}</td>
                        <td class="p-2 border text-right text-green-600 italic">${{ number_format($det->importe_optimista, 2) }}</td>
                        <td class="p-2 border text-right text-red-600 italic">${{ number_format($det->importe_pesimista, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100 font-bold">
                    <tr>
                        <td colspan="4" class="p-2 border text-right uppercase">Totales Operativos:</td>
                        <td class="p-2 border text-right text-slate-900">
                            ${{ number_format($presupuesto->pilas->sum('total') + $presupuesto->detalles->sum('importe'), 2) }}
                        </td>
                        <td class="p-2 border text-right text-green-700">
                            ${{ number_format($presupuesto->pilas->sum('optimista') + $presupuesto->detalles->sum('importe_optimista'), 2) }}
                        </td>
                        <td class="p-2 border text-right text-red-700">
                            ${{ number_format($presupuesto->pilas->sum('pesimista') + $presupuesto->detalles->sum('importe_pesimista'), 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
@endsection
